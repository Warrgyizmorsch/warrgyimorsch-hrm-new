<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Single source of truth for late-arrival counting — shared by the dashboard widget,
 * the dashboard attendance card and the Late Arrivals report used for payroll.
 *
 * Rule: a check-in up to LATE_ARRIVAL_ALLOWANCE_MINUTES after shift start is not late.
 * Past that, the day counts as late by the full minutes since shift start (9:45 on a
 * 9:30 shift = 15 min). More than VERY_LATE_ARRIVAL_MINUTES is flagged "very late".
 * Holidays, and Sundays for employees without a Sunday shift, are never late.
 */
class LateArrivalService
{
    public const CATEGORY_LATE = 'late';
    public const CATEGORY_VERY_LATE = 'very_late';
    public const CATEGORY_ALLOWANCE = 'allowance';
    public const CATEGORY_HOLIDAY = 'holiday';
    public const CATEGORY_SUNDAY = 'sunday';

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
     * Classify one attendance day. `minutes` is the raw lateness after shift start;
     * `counted_minutes` is what counts toward late arrivals (0 when not counted).
     *
     * @param  array<string, bool>  $holidayDates
     * @return array{minutes: int, counted_minutes: int, category: string|null}
     */
    public static function classify(Attendance $attendance, array $holidayDates): array
    {
        $employee = $attendance->employee;

        if (!$employee || !$attendance->check_in) {
            return ['minutes' => 0, 'counted_minutes' => 0, 'category' => null];
        }

        $minutes = AttendanceStatusService::lateArrivalMinutes($attendance, $employee);

        if ($minutes <= 0) {
            return ['minutes' => 0, 'counted_minutes' => 0, 'category' => null];
        }

        $date = Carbon::parse($attendance->attendance_date);

        $category = match (true) {
            isset($holidayDates[$date->toDateString()]) => self::CATEGORY_HOLIDAY,
            $date->isSunday() && !$employee->sunday_time_in => self::CATEGORY_SUNDAY,
            $minutes <= AttendanceStatusService::LATE_ARRIVAL_ALLOWANCE_MINUTES => self::CATEGORY_ALLOWANCE,
            $minutes > AttendanceStatusService::VERY_LATE_ARRIVAL_MINUTES => self::CATEGORY_VERY_LATE,
            default => self::CATEGORY_LATE,
        };

        $counted = in_array($category, [self::CATEGORY_LATE, self::CATEGORY_VERY_LATE], true);

        return [
            'minutes' => $minutes,
            'counted_minutes' => $counted ? $minutes : 0,
            'category' => $category,
        ];
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
     * Per-employee late-arrival report for a date range. Every day with a check-in after
     * shift start is listed (including ones not counted, with the reason) so payroll can
     * see exactly how each total was reached.
     *
     * @param  int[]|null  $employeeIds  restrict to these employees (null = everyone)
     * @return Collection<int, array>
     */
    public function report(Carbon $from, Carbon $to, ?array $employeeIds = null): Collection
    {
        $holidayDates = self::holidayDatesBetween($from, $to);

        $records = Attendance::with('employee')
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('check_in')
            ->when($employeeIds !== null, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->orderBy('attendance_date')
            ->get();

        return $records
            ->map(function (Attendance $attendance) use ($holidayDates) {
                $result = self::classify($attendance, $holidayDates);

                if ($result['minutes'] <= 0) {
                    return null;
                }

                $employee = $attendance->employee;

                return $result + [
                    'employee_id' => $employee->id,
                    'date' => Carbon::parse($attendance->attendance_date),
                    'shift_start' => AttendanceStatusService::resolveShiftStart($attendance, $employee)->format('H:i'),
                    'check_in' => Carbon::parse($attendance->getRawPunchTime('check_in'))->format('H:i'),
                    'label' => self::categoryLabel($result['category']),
                ];
            })
            ->filter()
            ->groupBy('employee_id')
            ->map(function (Collection $days) use ($records) {
                $employee = $records->firstWhere('employee_id', $days->first()['employee_id'])->employee;
                $late = $days->where('category', self::CATEGORY_LATE);
                $veryLate = $days->where('category', self::CATEGORY_VERY_LATE);
                $totalMinutes = (int) $days->sum('counted_minutes');

                return [
                    'employee' => $employee,
                    'days' => $days->values(),
                    'late_days' => $late->count(),
                    'late_minutes' => (int) $late->sum('counted_minutes'),
                    'very_late_days' => $veryLate->count(),
                    'very_late_minutes' => (int) $veryLate->sum('counted_minutes'),
                    'total_days' => $late->count() + $veryLate->count(),
                    'total_minutes' => $totalMinutes,
                    'total_duration' => self::formatMinutes($totalMinutes),
                    'allowance_days' => $days->where('category', self::CATEGORY_ALLOWANCE)->count(),
                ];
            })
            ->sortByDesc('total_minutes')
            ->values();
    }
}
