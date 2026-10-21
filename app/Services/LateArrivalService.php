<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for late-arrival counting — shared by the dashboard widget,
 * the dashboard attendance card and the Late Arrivals report used for payroll.
 *
 * Rule: a check-in up to LATE_ARRIVAL_ALLOWANCE_MINUTES after shift start is not late.
 * Past that, the day counts as late by the full minutes since shift start (9:45 on a
 * 9:30 shift = 15 min). More than VERY_LATE_ARRIVAL_MINUTES is flagged "very late".
 * Holidays and Sundays for employees without a Sunday shift are never late. An approved
 * Gatepass / Early Leave moves the expected arrival back by GATEPASS_OFFSET_MINUTES, and an
 * approved first-half leave by FIRST_HALF_OFFSET_MINUTES; lateness is counted from there
 * (9:30 shift + gatepass, in at 10:40 → 10 min late).
 */
class LateArrivalService
{
    public const GATEPASS_OFFSET_MINUTES = 60;
    public const FIRST_HALF_OFFSET_MINUTES = 240;

    public const CATEGORY_LATE = 'late';
    public const CATEGORY_VERY_LATE = 'very_late';
    public const CATEGORY_ALLOWANCE = 'allowance';
    public const CATEGORY_HOLIDAY = 'holiday';
    public const CATEGORY_SUNDAY = 'sunday';
    public const CATEGORY_EXCUSED = 'excused';

    /**
     * @return array<string, bool> holiday dates (Y-m-d) in the range
     */
    public static function holidayDatesBetween(Carbon $from, Carbon $to): array
    {
        return Holiday::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')
            ->mapWithKeys(fn ($date) => [Carbon::parse($date)->toDateString() => true])
            ->all();
    }

    /**
     * Days on which the expected arrival is moved back by an approved leave application:
     * Gatepass / Early Leave (+1 hr) or a first-half leave (+4 hrs). Both on one day → the larger.
     *
     * @return array<string, array{reason: string, offset: int, from: string|null}> "employeeId|Y-m-d" => adjustment
     */
    public static function excusedDaysBetween(Carbon $from, Carbon $to): array
    {
        $excused = [];

        LeaveApplication::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate(DB::raw('COALESCE(end_date, start_date)'), '>=', $from->toDateString())
            ->where(function ($q) {
                $q->where('leave_category', 'like', '%gatepass%')
                    ->orWhere('leave_type', 'like', '%early%')
                    ->orWhere('leave_type', 'like', '%first half%');
            })
            ->get()
            ->each(function (LeaveApplication $leave) use (&$excused, $from, $to) {
                $isGatepass = str_contains(strtolower($leave->leave_category . ' ' . $leave->leave_type), 'gatepass')
                    || str_contains(strtolower($leave->leave_type), 'early');
                $window = $leave->start_time && $leave->end_time
                    ? ' (' . Carbon::parse($leave->start_time)->format('H:i') . '–' . Carbon::parse($leave->end_time)->format('H:i') . ')'
                    : '';
                // 'from' lets classify() ignore a gatepass taken later in the day (an evening early out
                // doesn't excuse a late morning).
                $entry = $isGatepass
                    ? ['reason' => 'Gatepass approved' . $window, 'offset' => self::GATEPASS_OFFSET_MINUTES,
                        'from' => $leave->start_time ? Carbon::parse($leave->start_time)->format('H:i') : null]
                    : ['reason' => 'First-half leave approved', 'offset' => self::FIRST_HALF_OFFSET_MINUTES, 'from' => null];

                // copy(): max()/min() can return $from/$to themselves, which the loop would mutate.
                $day = Carbon::parse($leave->start_date)->max($from->copy()->startOfDay())->copy();
                $last = Carbon::parse($leave->end_date ?: $leave->start_date)->min($to->copy()->startOfDay())->copy();

                for (; $day->lte($last); $day->addDay()) {
                    $key = $leave->employee_id . '|' . $day->toDateString();
                    if (($excused[$key]['offset'] ?? 0) < $entry['offset']) {
                        $excused[$key] = $entry;
                    }
                }
            });

        return $excused;
    }

    /**
     * Every leave application touching each day (all categories — leave, half day, WFH,
     * gatepass), for the report's Day status column. Pending/rejected ones are left out;
     * unpaid/unauthorised ones are shown with that outcome.
     *
     * @param  int[]|null  $employeeIds
     * @return array<string, string[]> "employeeId|Y-m-d" => descriptions
     */
    public static function leavesByDay(Carbon $from, Carbon $to, ?array $employeeIds = null): array
    {
        $byDay = [];

        LeaveApplication::query()
            ->whereIn('status', ['approved', 'unpaid', 'unauthorised'])
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate(DB::raw('COALESCE(end_date, start_date)'), '>=', $from->toDateString())
            ->when($employeeIds !== null, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->get()
            ->each(function (LeaveApplication $leave) use (&$byDay, $from, $to) {
                $category = strtolower($leave->leave_category) === 'wfh' ? 'WFH' : $leave->leave_category;
                $text = trim($category . ' · ' . $leave->leave_type, ' ·');

                $isGatepass = stripos($leave->leave_category . ' ' . $leave->leave_type, 'gatepass') !== false
                    || stripos((string) $leave->leave_type, 'early') !== false;

                if ($isGatepass && $leave->start_time && $leave->end_time) {
                    $text .= ' ' . Carbon::parse($leave->start_time)->format('H:i') . '–' . Carbon::parse($leave->end_time)->format('H:i');
                }

                if ($leave->status !== 'approved') {
                    $text .= ' (' . ucfirst($leave->status) . ')';
                }

                $day = Carbon::parse($leave->start_date)->max($from->copy()->startOfDay())->copy();
                $last = Carbon::parse($leave->end_date ?: $leave->start_date)->min($to->copy()->startOfDay())->copy();

                for (; $day->lte($last); $day->addDay()) {
                    $byDay[$leave->employee_id . '|' . $day->toDateString()][] = $text;
                }
            });

        return $byDay;
    }

    /**
     * Human label for a stored attendance status, holiday/Sunday aware for punch-less days.
     */
    public static function dayStatusLabel(?string $status, Carbon $date, bool $hasPunch, array $holidayDates): string
    {
        if (!$hasPunch && isset($holidayDates[$date->toDateString()])) {
            return 'Holiday';
        }

        if (!$hasPunch && $date->isSunday()) {
            return 'Sunday';
        }

        return match (strtolower((string) $status)) {
            'present' => 'Present',
            'half_day' => 'Half Day',
            'leave' => 'Leave',
            'half_day_leave' => 'Half Day Leave',
            'wfh' => 'WFH',
            'early_leave' => 'Early Leave',
            'missing_punch' => 'Missing Punch',
            'unpaid_leave' => 'Unpaid Leave',
            'unauthorised' => 'Unauthorised',
            '', 'absent' => 'Absent',
            default => ucwords(str_replace('_', ' ', (string) $status)),
        };
    }

    /**
     * Classify one attendance day. `minutes` is the lateness after the expected arrival (shift
     * start, moved back by an approved gatepass / first-half leave — see excusedDaysBetween());
     * `counted_minutes` is what counts toward late arrivals (0 when not counted). `adjustment`
     * explains a moved start ("Gatepass approved: expected 10:30"), null otherwise.
     *
     * @param  array<string, bool>  $holidayDates
     * @param  array<string, array{reason: string, offset: int}>  $excusedDays  from excusedDaysBetween()
     * @return array{minutes: int, counted_minutes: int, category: string|null, excuse: string|null, adjustment: string|null}
     */
    public static function classify(Attendance $attendance, array $holidayDates, array $excusedDays = []): array
    {
        $none = ['minutes' => 0, 'counted_minutes' => 0, 'category' => null, 'excuse' => null, 'adjustment' => null];
        $employee = $attendance->employee;

        if (!$employee || !$attendance->check_in) {
            return $none;
        }

        $rawMinutes = AttendanceStatusService::lateArrivalMinutes($attendance, $employee);

        if ($rawMinutes <= 0) {
            return $none;
        }

        $date = Carbon::parse($attendance->attendance_date);
        $excuse = $excusedDays[$employee->id . '|' . $date->toDateString()] ?? null;
        $shiftStart = AttendanceStatusService::resolveShiftStart($attendance, $employee);
        // A gatepass that starts an hour or more into the shift is an early exit, not a late start.
        if ($excuse && $excuse['from'] && $excuse['from'] >= $shiftStart->copy()->addMinutes(self::GATEPASS_OFFSET_MINUTES)->format('H:i')) {
            $excuse = null;
        }
        $adjustment = null;
        $minutes = $rawMinutes;
        if ($excuse) {
            $minutes = max($rawMinutes - $excuse['offset'], 0);
            $adjustment = $excuse['reason'] . ': expected ' . $shiftStart->copy()->addMinutes($excuse['offset'])->format('H:i');
        }

        $category = match (true) {
            isset($holidayDates[$date->toDateString()]) => self::CATEGORY_HOLIDAY,
            self::isOffDay($attendance, $holidayDates) => self::CATEGORY_SUNDAY,
            // Arrived within the allowance of the moved start → excused by the leave.
            $excuse !== null && $minutes <= AttendanceStatusService::LATE_ARRIVAL_ALLOWANCE_MINUTES => self::CATEGORY_EXCUSED,
            $minutes <= AttendanceStatusService::LATE_ARRIVAL_ALLOWANCE_MINUTES => self::CATEGORY_ALLOWANCE,
            $minutes > AttendanceStatusService::VERY_LATE_ARRIVAL_MINUTES => self::CATEGORY_VERY_LATE,
            default => self::CATEGORY_LATE,
        };

        $counted = in_array($category, [self::CATEGORY_LATE, self::CATEGORY_VERY_LATE], true);

        return [
            'minutes' => $category === self::CATEGORY_EXCUSED ? $rawMinutes : $minutes,
            'counted_minutes' => $counted ? $minutes : 0,
            'category' => $category,
            'excuse' => $category === self::CATEGORY_EXCUSED ? $excuse['reason'] : null,
            'adjustment' => $adjustment,
        ];
    }

    /**
     * A holiday, or a Sunday for an employee with no Sunday shift — no scheduled hours.
     *
     * @param  array<string, bool>  $holidayDates
     */
    public static function isOffDay(Attendance $attendance, array $holidayDates): bool
    {
        $date = Carbon::parse($attendance->attendance_date);

        return isset($holidayDates[$date->toDateString()])
            || ($date->isSunday() && !$attendance->employee?->sunday_time_in);
    }

    /**
     * Scheduled shift end for the day (Sunday override aware; rolls to the next day for
     * shifts that cross midnight).
     */
    public static function shiftEnd(Attendance $attendance): Carbon
    {
        $employee = $attendance->employee;
        $date = Carbon::parse($attendance->attendance_date)->toDateString();
        $useSunday = Carbon::parse($date)->isSunday() && $employee->sunday_time_in && $employee->sunday_time_out;

        $start = AttendanceStatusService::resolveShiftStart($attendance, $employee);
        $end = Carbon::parse($date . ' ' . substr(($useSunday ? $employee->sunday_time_out : $employee->time_out) ?? '18:00:00', 0, 8));

        return $end->lte($start) ? $end->addDay() : $end;
    }

    /**
     * Minutes the employee stayed after their scheduled shift end (0 if they left on time,
     * early, have no check-out, or it was an off day). Informational — used to balance
     * late arrivals manually; it never reduces counted late minutes by itself.
     *
     * @param  array<string, bool>  $holidayDates
     */
    public static function stayOverMinutes(Attendance $attendance, array $holidayDates): int
    {
        return max(self::checkOutOffsetMinutes($attendance, $holidayDates) ?? 0, 0);
    }

    /**
     * Minutes the employee left before their scheduled shift end (0 if they stayed until
     * shift end or later, have no check-out, or it was an off day).
     *
     * @param  array<string, bool>  $holidayDates
     */
    public static function earlyOutMinutes(Attendance $attendance, array $holidayDates): int
    {
        return max(-(self::checkOutOffsetMinutes($attendance, $holidayDates) ?? 0), 0);
    }

    /**
     * Signed minutes between check-out and shift end (+ stayed back, − left early), or null
     * when there's nothing to compare (no punches, or an off day).
     *
     * @param  array<string, bool>  $holidayDates
     */
    private static function checkOutOffsetMinutes(Attendance $attendance, array $holidayDates): ?int
    {
        if (!$attendance->employee || !$attendance->check_in || !$attendance->check_out
            || self::isOffDay($attendance, $holidayDates)) {
            return null;
        }

        $date = Carbon::parse($attendance->attendance_date)->toDateString();
        $checkIn = Carbon::parse($date . ' ' . Carbon::parse($attendance->getRawPunchTime('check_in'))->format('H:i:s'));
        $checkOut = Carbon::parse($date . ' ' . Carbon::parse($attendance->getRawPunchTime('check_out'))->format('H:i:s'));

        // Check-out earlier in the clock than check-in means it happened after midnight.
        if ($checkOut->lte($checkIn)) {
            $checkOut->addDay();
        }

        return intdiv($checkOut->timestamp - self::shiftEnd($attendance)->timestamp, 60);
    }

    public static function categoryLabel(?string $category): string
    {
        return match ($category) {
            self::CATEGORY_LATE => 'Late',
            self::CATEGORY_VERY_LATE => 'Very late (' . AttendanceStatusService::VERY_LATE_ARRIVAL_MINUTES . '+ min)',
            self::CATEGORY_ALLOWANCE => 'Within ' . AttendanceStatusService::LATE_ARRIVAL_ALLOWANCE_MINUTES . '-min allowance',
            self::CATEGORY_HOLIDAY => 'Holiday — not counted',
            self::CATEGORY_SUNDAY => 'Sunday — not counted',
            default => 'On time',
        };
    }

    public static function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $hours > 0 ? "{$hours} hr {$rest} min" : "{$rest} min";
    }

    /**
     * Per-employee late-arrival report for a date range. Every day the employee checked in
     * is listed — late, within allowance, excused, or on time — so payroll can see exactly
     * how each total was reached and balance late arrivals against time stayed back.
     *
     * @param  int[]|null  $employeeIds  restrict to these employees (null = everyone)
     * @param  bool  $includeNonWorkedDays  also list days without a check-in (leave, WFH,
     *                                     absent, holiday) — used for the single-employee view
     * @return Collection<int, array>
     */
    public function report(Carbon $from, Carbon $to, ?array $employeeIds = null, bool $includeNonWorkedDays = false): Collection
    {
        $holidayDates = self::holidayDatesBetween($from, $to);
        $excusedDays = self::excusedDaysBetween($from, $to);
        // Always loaded (one query): the per-employee early-out / gatepass / half-day-leave counts need it.
        $leavesByDay = self::leavesByDay($from, $to, $employeeIds);

        $records = Attendance::with('employee')
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->when(!$includeNonWorkedDays, fn ($q) => $q->whereNotNull('check_in'))
            ->when($employeeIds !== null, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->orderBy('attendance_date')
            ->get();

        return $records
            ->map(function (Attendance $attendance) use ($holidayDates, $excusedDays, $leavesByDay) {
                if (!$attendance->employee) {
                    return null;
                }

                $employee = $attendance->employee;
                $date = Carbon::parse($attendance->attendance_date);
                $base = [
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'shift_start' => AttendanceStatusService::resolveShiftStart($attendance, $employee)->format('H:i'),
                    'shift_end' => self::shiftEnd($attendance)->format('H:i'),
                    'day_status' => self::dayStatusLabel($attendance->status, $date, (bool) $attendance->check_in, $holidayDates),
                    'leaves' => $leavesByDay[$employee->id . '|' . $date->toDateString()] ?? [],
                ];

                if (!$attendance->check_in) {
                    return $base + [
                        'minutes' => 0, 'counted_minutes' => 0, 'category' => null, 'excuse' => null, 'adjustment' => null,
                        'check_in' => null, 'check_out' => null, 'stay_minutes' => 0, 'early_minutes' => 0,
                        'covered' => false, 'label' => 'No check-in', 'worked' => false,
                    ];
                }

                $result = self::classify($attendance, $holidayDates, $excusedDays);
                $stayOver = self::stayOverMinutes($attendance, $holidayDates);
                $checkOut = $attendance->getRawPunchTime('check_out');

                return $result + $base + [
                    'check_in' => Carbon::parse($attendance->getRawPunchTime('check_in'))->format('H:i'),
                    'check_out' => $checkOut ? Carbon::parse($checkOut)->format('H:i') : null,
                    'early_minutes' => self::earlyOutMinutes($attendance, $holidayDates),
                    'stay_minutes' => $stayOver,
                    'covered' => $result['counted_minutes'] > 0 && $stayOver >= $result['counted_minutes'],
                    'label' => $result['excuse'] ?? self::categoryLabel($result['category']),
                    'worked' => true,
                ];
            })
            ->filter()
            ->groupBy('employee_id')
            ->map(function (Collection $days) use ($records, $includeNonWorkedDays, $from, $to, $holidayDates, $leavesByDay) {
                $employee = $records->firstWhere('employee_id', $days->first()['employee_id'])->employee;
                $late = $days->where('category', self::CATEGORY_LATE);
                $veryLate = $days->where('category', self::CATEGORY_VERY_LATE);
                $totalMinutes = (int) $days->sum('counted_minutes');
                $stayMinutes = (int) $days->sum('stay_minutes');

                return [
                    'employee' => $employee,
                    'days' => $includeNonWorkedDays
                        ? self::withMissingCalendarDays($days, $from, $to, $holidayDates, $leavesByDay, $employee->id)
                        : $days->values(),
                    'late_days' => $late->count(),
                    'late_minutes' => (int) $late->sum('counted_minutes'),
                    'very_late_days' => $veryLate->count(),
                    'very_late_minutes' => (int) $veryLate->sum('counted_minutes'),
                    'total_days' => $late->count() + $veryLate->count(),
                    'total_minutes' => $totalMinutes,
                    'total_duration' => self::formatMinutes($totalMinutes),
                    'allowance_days' => $days->where('category', self::CATEGORY_ALLOWANCE)->count(),
                    'excused_days' => $days->where('category', self::CATEGORY_EXCUSED)->count(),
                    'stay_days' => $days->where('stay_minutes', '>', 0)->count(),
                    'stay_minutes' => $stayMinutes,
                    'covered_days' => $days->where('covered', true)->count(),
                    // Based on actual check-out vs shift end, so a gatepass / half-day-leave day that
                    // payroll ends up calling "Half Day" still shows as an early out here.
                    'early_out_days' => $days->where('early_minutes', '>', 0)->count(),
                    'early_out_minutes' => (int) $days->sum('early_minutes'),
                    'gatepass_days' => $days->filter(fn (array $day) => self::hasLeaveLike($day, 'gatepass'))->count(),
                    'half_day_leave_days' => $days->filter(fn (array $day) => self::hasLeaveLike($day, 'half day'))->count(),
                    // Positive = still owes time; negative = stayed back more than they were late.
                    'net_minutes' => $totalMinutes - $stayMinutes,
                ];
            })
            ->sortByDesc('total_minutes')
            ->values();
    }

    /**
     * Whether any leave application on this day matches the text (case-insensitive),
     * ignoring Sundays/holidays a longer leave range happens to span.
     */
    private static function hasLeaveLike(array $day, string $needle): bool
    {
        if ($day['date']->isSunday() || ($day['day_status'] ?? '') === 'Holiday') {
            return false;
        }

        foreach ($day['leaves'] ?? [] as $text) {
            if (stripos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pad a day list so every calendar date in the range appears — Sundays, holidays and
     * dates with no attendance record get a placeholder row instead of silently vanishing.
     *
     * @param  array<string, bool>  $holidayDates
     */
    private static function withMissingCalendarDays(
        Collection $days,
        Carbon $from,
        Carbon $to,
        array $holidayDates,
        array $leavesByDay,
        int $employeeId
    ): Collection {
        $byDate = $days->keyBy(fn (array $day) => $day['date']->toDateString());
        $padded = collect();

        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $key = $date->toDateString();
            $leaves = $leavesByDay[$employeeId . '|' . $key] ?? [];

            $padded->push($byDate[$key] ?? [
                'date' => $date->copy(),
                'shift_start' => '—', 'shift_end' => '—',
                'minutes' => 0, 'counted_minutes' => 0, 'category' => null, 'excuse' => null, 'adjustment' => null,
                'check_in' => null, 'check_out' => null, 'stay_minutes' => 0, 'early_minutes' => 0, 'covered' => false,
                'day_status' => match (true) {
                    isset($holidayDates[$key]) => 'Holiday',
                    $date->isSunday() => 'Sunday',
                    $leaves !== [] => 'Leave',
                    default => 'No record',
                },
                'leaves' => $leaves,
                'label' => 'No check-in',
                'worked' => false,
            ]);
        }

        return $padded;
    }
}
