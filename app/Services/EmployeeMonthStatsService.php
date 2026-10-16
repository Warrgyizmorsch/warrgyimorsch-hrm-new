<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The "Days present / Report submitted / Missed / working days / leaves / absent" summary on
 * Daily Tasks for one employee and date range.
 *
 * Rules (each one fixes a mismatch employees reported):
 *  - Only days up to TODAY count. Future days of the month are not "absent" and not "working days so far".
 *  - WFH is a working day, not leave: it counts as present and is shown separately, never in "leaves".
 *  - Leave days are counted inside the range only, on working days (no Sundays / holidays).
 *  - A day is "reported" if a work log was written FOR that day (task dated that day) OR ON that day
 *    (follow-up created that day) — multi-day tasks no longer leave later days looking unreported.
 *  - Today is never "missed": the report can still be submitted until the day ends.
 */
class EmployeeMonthStatsService
{
    private const PRESENT_KEYS = ['present', 'present_activity', 'late', 'half_day', 'half_day_leave', 'wfh', 'early_out', 'early_leave'];
    private const ABSENT_KEYS = ['absent', 'unauthorised', 'missing_punch'];

    public function build(int $employeeId, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $start = $rangeStart->copy()->startOfDay();
        $fullEnd = $rangeEnd->copy()->startOfDay();
        $end = $fullEnd->gt($today) ? $today->copy() : $fullEnd->copy();   // "so far"
        $isPartial = $end->lt($fullEnd);

        $holidays = Holiday::whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $fullEnd->toDateString())
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $isWorkingDay = fn (Carbon $d) => !$d->isSunday() && !$holidays->has($d->toDateString());
        $workingDaysBetween = function (Carbon $from, Carbon $to) use ($isWorkingDay): Collection {
            $days = collect();
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                if ($isWorkingDay($d)) {
                    $days->push($d->toDateString());
                }
            }

            return $days;
        };

        $elapsedWorkingDays = $end->lt($start) ? collect() : $workingDaysBetween($start, $end);
        $monthWorkingDays = $workingDaysBetween($start, $fullEnd);

        // Attendance rows, only up to today.
        $history = $end->lt($start) ? collect() : collect(
            app(AttendanceHistoryService::class)->buildMonthlyHistory($employeeId, $start, $end->copy()->endOfDay())
        )->filter(fn ($row) => $row['date_key'] <= $todayStr);

        // Approved leave / WFH overlapping the range.
        $requests = LeaveApplication::where('employee_id', $employeeId)
            ->whereIn('status', ['approved', 'unpaid'])
            ->whereDate('start_date', '<=', $fullEnd->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get(['leave_type', 'leave_category', 'start_date', 'end_date', 'total_days']);

        $isWfh = fn ($r) => str_contains(strtolower((string) $r->leave_category), 'wfh')
            || str_contains(strtolower((string) $r->leave_category), 'work from home');
        $isEarlyLeave = fn ($r) => $r->leave_type === 'Early Leave' || str_contains(strtolower((string) $r->leave_category), 'gatepass');

        $leaveFractionByDate = [];   // date => fraction of the day on leave (0.5 = half day)
        $wfhDates = collect();
        foreach ($requests as $r) {
            if ($isEarlyLeave($r)) {
                continue; // shown in hours elsewhere, not as a day of leave
            }
            $reqDays = $workingDaysBetween($r->start_date->copy()->startOfDay(), $r->end_date->copy()->startOfDay());
            if ($reqDays->isEmpty()) {
                continue;
            }
            $inRangeSoFar = $reqDays->filter(fn ($d) => $d >= $start->toDateString() && $d <= $end->toDateString());

            if ($isWfh($r)) {
                $wfhDates = $wfhDates->merge($inRangeSoFar);
                continue;
            }
            $perDay = min(1, (float) $r->total_days / $reqDays->count());
            foreach ($inRangeSoFar as $d) {
                $leaveFractionByDate[$d] = min(1, ($leaveFractionByDate[$d] ?? 0) + $perDay);
            }
        }
        $wfhDates = $wfhDates->unique()->values();
        $leaveDates = collect(array_keys($leaveFractionByDate));

        // Present = attended (punch / activity) or approved WFH, minus any leave share of that day.
        $presentDates = $history->filter(fn ($row) => in_array($row['status_key'], self::PRESENT_KEYS, true))
            ->pluck('date_key')
            ->merge($wfhDates)
            ->unique()
            ->values();
        $presentDays = $presentDates->sum(fn ($d) => max(0, 1 - ($leaveFractionByDate[$d] ?? 0)));

        // Absent = no attendance, not on leave / WFH, and not in the future.
        $absentDates = $history->filter(fn ($row) => in_array($row['status_key'], self::ABSENT_KEYS, true))
            ->pluck('date_key')
            ->reject(fn ($d) => ($leaveFractionByDate[$d] ?? 0) >= 1 || $wfhDates->contains($d))
            ->values();

        // Reported days: work logged FOR the day (task date) or ON the day (follow-up date).
        $base = DB::table('daily_tasks')
            ->join('task_follow_ups', 'task_follow_ups.daily_task_id', '=', 'daily_tasks.id')
            ->where('daily_tasks.employee_id', $employeeId);

        $hoursByTaskDate = (clone $base)
            ->whereDate('daily_tasks.start_date', '>=', $start->toDateString())
            ->whereDate('daily_tasks.start_date', '<=', $end->toDateString())
            ->selectRaw('DATE(daily_tasks.start_date) as d, SUM(CAST(task_follow_ups.time_taken AS DECIMAL(10,2))) as hrs')
            ->groupBy('d')
            ->pluck('hrs', 'd');

        $loggedOnDates = (clone $base)
            ->whereDate('task_follow_ups.created_at', '>=', $start->toDateString())
            ->whereDate('task_follow_ups.created_at', '<=', $end->toDateString())
            ->selectRaw('DATE(task_follow_ups.created_at) as d')
            ->distinct()
            ->pluck('d');

        $reportedDates = $hoursByTaskDate->keys()->merge($loggedOnDates)->map(fn ($d) => (string) $d)->unique()->values();

        // Missed = a past day she was present (not fully on leave) with no work log at all.
        $missedDates = $presentDates
            ->filter(fn ($d) => $d < $todayStr && ($leaveFractionByDate[$d] ?? 0) < 1)
            ->diff($reportedDates)
            ->sort()
            ->values();

        $underTarget = $hoursByTaskDate
            ->filter(fn ($hrs, $d) => (string) $d < $todayStr && (float) $hrs < 8)
            ->count();

        $totalDaysSoFar = $end->lt($start) ? 0 : (int) $start->diffInDays($end) + 1;
        $sundaysSoFar = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $sundaysSoFar += $d->isSunday() ? 1 : 0;
        }
        $holidaysSoFar = $holidays->keys()->filter(fn ($d) => $d <= $end->toDateString())->count();

        $isWholeMonth = $start->isSameDay($start->copy()->startOfMonth()) && $fullEnd->isSameDay($fullEnd->copy()->endOfMonth()->startOfDay());

        return [
            'present_days' => round($presentDays, 1),
            'submitted_days' => $reportedDates->count(),
            'missed_days' => $missedDates->count(),
            'missed_dates' => $missedDates->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(),
            'under_target_days' => $underTarget,
            'total_days' => $totalDaysSoFar,
            'sunday_count' => $sundaysSoFar,
            'holiday_count' => $holidaysSoFar,
            'working_days' => $elapsedWorkingDays->count(),
            'month_working_days' => $monthWorkingDays->count(),
            'leave_count' => round(array_sum($leaveFractionByDate), 1),
            'wfh_count' => $wfhDates->count(),
            'absent_count' => $absentDates->count(),
            'absent_dates' => $absentDates->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(),
            'is_partial' => $isPartial,
            'range_label' => ($isWholeMonth ? $start->format('F Y') : $start->format('d M Y') . ' – ' . $fullEnd->format('d M Y'))
                . ($isPartial ? ' (up to ' . $end->format('d M') . ')' : ''),
        ];
    }
}
