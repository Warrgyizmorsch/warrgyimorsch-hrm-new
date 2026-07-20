<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceHistoryService
{
    /**
     * Build one row per calendar day in the range, using payroll display rules.
     */
    public function buildMonthlyHistory(int $employeeId, Carbon $startDate, Carbon $endDate): array
    {
        $records = Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', '>=', $startDate->toDateString())
            ->whereDate('attendance_date', '<=', $endDate->toDateString())
            ->get()
            ->keyBy(fn (Attendance $record) => $record->attendance_date->format('Y-m-d'));

        $dateStrings = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStrings[] = $date->format('Y-m-d');
        }

        $holidayMap = Holiday::query()
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->get()
            ->keyBy(fn ($holiday) => Carbon::parse($holiday->date)->format('Y-m-d'));

        $activityDays = Attendance::computeActivityDays($dateStrings);
        $todayStr = Carbon::today()->format('Y-m-d');
        $lastWorkingDay = $this->employeeLastWorkingDay($employeeId);
        $lastWorkingDayStr = $lastWorkingDay?->format('Y-m-d');
        $history = [];

        for ($date = $endDate->copy(); $date->gte($startDate); $date->subDay()) {
            $dayStr = $date->format('Y-m-d');
            $isHoliday = $holidayMap->has($dayStr);
            $isSunday = $date->isSunday();
            $isActivityDay = (bool) ($activityDays[$dayStr] ?? false);
            $record = $records->get($dayStr);
            // Compare date strings, not Carbon instances — $date/$endDate may carry a
            // non-midnight time (e.g. endOfMonth() = 23:59:59), which would otherwise make
            // "today" itself compare as being in the future.
            $excludedFromPayable = $dayStr > $todayStr || ($lastWorkingDayStr && $dayStr > $lastWorkingDayStr);

            if ($record) {
                $resolved = $record->resolvePayrollDisplayStatus($isHoliday, $isActivityDay, $isSunday);

                $history[] = [
                    'date' => $date->format('d M, Y (D)'),
                    'date_key' => $dayStr,
                    'status' => $resolved['label'],
                    'status_key' => $resolved['key'],
                    'statusClass' => $resolved['class'],
                    'punch_in' => $record->formattedCheckIn() ?? '--:--',
                    'punch_out' => $record->formattedCheckOut() ?? '--:--',
                    'raw_check_in' => $record->getRawPunchTime('check_in'),
                    'raw_check_out' => $record->getRawPunchTime('check_out'),
                    'total_hours' => Attendance::formatTotalHours($record->total_hours),
                    'total_hours_decimal' => (float) ($record->total_hours ?? 0),
                    'attendance_id' => $record->id,
                    'is_activity' => $resolved['is_activity'] ?? false,
                    'excluded_from_payable' => $excludedFromPayable,
                ];

                continue;
            }

            if ($isSunday) {
                $history[] = $this->syntheticRow($date, $dayStr, 'Sunday', 'sunday', $excludedFromPayable);
                continue;
            }

            if ($isHoliday) {
                $holiday = $holidayMap->get($dayStr);
                $label = 'Holiday';
                if ($holiday?->title) {
                    $label .= ' (' . $holiday->title . ')';
                }
                $history[] = $this->syntheticRow($date, $dayStr, $label, 'holiday', $excludedFromPayable);
                continue;
            }

            $history[] = $this->syntheticRow($date, $dayStr, 'Absent', 'absent', $excludedFromPayable);
        }

        return $history;
    }

    private function employeeLastWorkingDay(int $employeeId): ?Carbon
    {
        $lastWorkingDay = Employee::find($employeeId)?->user?->last_working_day;

        return $lastWorkingDay ? Carbon::parse($lastWorkingDay)->startOfDay() : null;
    }

    private function syntheticRow(Carbon $date, string $dayStr, string $label, string $key, bool $excludedFromPayable = false): array
    {
        return [
            'date' => $date->format('d M, Y (D)'),
            'date_key' => $dayStr,
            'status' => $label,
            'status_key' => $key,
            'statusClass' => Attendance::classForStatusLabel($label),
            'punch_in' => '--:--',
            'punch_out' => '--:--',
            'raw_check_in' => null,
            'raw_check_out' => null,
            'total_hours' => '--',
            'total_hours_decimal' => 0,
            'attendance_id' => null,
            'is_activity' => false,
            'excluded_from_payable' => $excludedFromPayable,
        ];
    }

    /**
     * Resolve the date range used for employee-wise attendance history and counts.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveDateRange(Request $request, ?int $employeeId = null, bool $useEmployeeSpan = false): array
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            return [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->startOfDay(),
            ];
        }

        if ($request->filled('start_date')) {
            return [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::today()->startOfDay(),
            ];
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->startOfDay();

            return [$endDate->copy()->startOfMonth(), $endDate];
        }

        $range = $request->input('range');
        if ($range) {
            return $this->datesForQuickRange((string) $range, $employeeId);
        }

        if ($useEmployeeSpan && $employeeId) {
            $minDate = Attendance::query()
                ->where('employee_id', $employeeId)
                ->min('attendance_date');

            if ($minDate) {
                return [Carbon::parse($minDate)->startOfDay(), Carbon::today()->startOfDay()];
            }
        }

        return $this->datesForQuickRange('month');
    }

    public function countCalendarDays(Carbon $startDate, Carbon $endDate): int
    {
        return (int) $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1;
    }

    /** Paid leave is only merged into payable days for a single payroll month. */
    public function shouldIncludePaidLeaveForRange(Carbon $startDate, Carbon $endDate): bool
    {
        return $startDate->format('Y-m') === $endDate->format('Y-m');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function datesForQuickRange(string $range, ?int $employeeId = null): array
    {
        $today = Carbon::today();

        if ($range === 'all') {
            $query = Attendance::query();
            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }

            $minDate = $query->min('attendance_date');
            $maxDate = $query->max('attendance_date');

            if ($minDate && $maxDate) {
                return [
                    Carbon::parse($minDate)->startOfDay(),
                    Carbon::parse($maxDate)->startOfDay(),
                ];
            }

            return [$today->copy()->startOfMonth(), $today->copy()];
        }

        return match ($range) {
            'today' => [$today->copy(), $today->copy()],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            'week' => [$today->copy()->startOfWeek(Carbon::SUNDAY), $today->copy()],
            'month' => [$today->copy()->startOfMonth(), $today->copy()],
            'lastMonth' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            '3months' => [$today->copy()->subMonthsNoOverflow(2)->startOfMonth(), $today->copy()],
            '6months' => [$today->copy()->subMonthsNoOverflow(5)->startOfMonth(), $today->copy()],
            '1year' => [$today->copy()->subYear()->addMonth()->startOfMonth(), $today->copy()],
            default => [$today->copy()->startOfMonth(), $today->copy()],
        };
    }

    /**
     * Shape history rows for the employee-wise detail drawer JSON response.
     */
    public function buildDetailPayload(int $employeeId, Carbon $startDate, Carbon $endDate): array
    {
        return collect($this->buildMonthlyHistory($employeeId, $startDate, $endDate))
            ->map(fn (array $row) => [
                'id' => $row['attendance_id'],
                'employee_id' => $employeeId,
                'attendance_date' => $row['date_key'],
                'date' => $row['date'],
                'check_in' => $row['raw_check_in'] ?? null,
                'check_out' => $row['raw_check_out'] ?? null,
                'total_hours' => ($row['total_hours_decimal'] ?? 0) > 0 ? $row['total_hours_decimal'] : null,
                'total_hours_label' => $row['total_hours'],
                'display_status' => $row['status'],
                'display_status_key' => $row['status_key'],
                'display_status_class' => $row['statusClass'],
                'is_holiday' => ($row['status_key'] ?? '') === 'holiday',
                'is_sunday' => ($row['status_key'] ?? '') === 'sunday',
                'is_activity' => $row['is_activity'] ?? false,
            ])
            ->values()
            ->all();
    }

    /**
     * Payable day fraction for a resolved history row (matches payroll rules).
     */
    public function resolvePayableFractionForHistoryRow(array $row): float
    {
        if (!empty($row['excluded_from_payable'])) {
            return 0.0;
        }

        $key = strtolower($row['status_key'] ?? '');

        return match (true) {
            in_array($key, ['present', 'present_activity', 'late', 'wfh', 'missing_punch', 'early_out', 'early_leave', 'sunday'], true) => 1.0,
            in_array($key, ['half_day', 'half_day_leave'], true) => 0.5,
            default => 0.0,
        };
    }

    /**
     * Sum payable attendance days from full calendar history (excludes paid leave allotment).
     */
    public function calculateAttendancePayableDays(int $employeeId, Carbon $startDate, Carbon $endDate): float
    {
        $history = $this->buildMonthlyHistory($employeeId, $startDate, $endDate);
        $payable = 0.0;

        foreach ($history as $row) {
            $payable += $this->resolvePayableFractionForHistoryRow($row);
        }

        return round($payable, 2);
    }

    /**
     * Count resolved statuses for the employee attendance history summary bar.
     */
    public function buildMonthlySummary(array $history): array
    {
        $summary = [
            'present' => 0,
            'overtime' => 0,
            'half_day' => 0,
            'leave' => 0,
            'absent' => 0,
            'wfh' => 0,
            'early_out' => 0,
            'missing_punch' => 0,
            'unpaid_leave' => 0,
            'holiday' => 0,
        ];

        foreach ($history as $row) {
            $key = strtolower($row['status_key'] ?? '');

            if (($row['total_hours_decimal'] ?? 0) > 9.5) {
                $summary['overtime']++;
            }

            match (true) {
                in_array($key, ['present', 'present_activity', 'late'], true) => $summary['present']++,
                in_array($key, ['half_day', 'half_day_leave'], true) => $summary['half_day']++,
                $key === 'leave' => $summary['leave']++,
                $key === 'wfh' => $summary['wfh']++,
                in_array($key, ['early_out', 'early_leave'], true) => $summary['early_out']++,
                $key === 'absent' => $summary['absent']++,
                $key === 'missing_punch' => $summary['missing_punch']++,
                in_array($key, ['unpaid_leave', 'unauthorised'], true) => $summary['unpaid_leave']++,
                in_array($key, ['holiday', 'sunday'], true) => $summary['holiday']++,
                default => null,
            };
        }

        return $summary;
    }

    /**
     * Format a history row for spreadsheet day cells.
     */
    public function formatExportDayCell(array $row): string
    {
        $text = $row['status'];
        $times = [];

        if (($row['punch_in'] ?? '--:--') !== '--:--') {
            $times[] = $row['punch_in'];
        }

        if (($row['punch_out'] ?? '--:--') !== '--:--') {
            $times[] = $row['punch_out'];
        }

        if ($times) {
            $text .= ' (' . implode(' - ', $times) . ')';
        }

        if (!empty($row['is_activity'])) {
            $text .= ' Activity';
        }

        return $text;
    }

    /**
     * Map resolved summary counts to export footer columns.
     */
    public function summaryToExportTotals(array $summary): array
    {
        return [
            'present' => $summary['present']
                + $summary['wfh']
                + $summary['holiday']
                + $summary['early_out']
                + $summary['missing_punch'],
            'absent' => $summary['absent'],
            'leave_hd' => $summary['half_day'] + $summary['leave'] + $summary['unpaid_leave'],
        ];
    }

    /**
     * Resolve export date range (supports explicit filters or full attendance span).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveExportDateRange(Request $request, ?int $employeeId = null): array
    {
        if ($request->filled('start_date') || $request->filled('end_date') || $request->filled('range')) {
            return $this->resolveDateRange($request, $employeeId);
        }

        $query = Attendance::query();
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $minDate = $query->min('attendance_date');
        $maxDate = $query->max('attendance_date');

        if ($minDate && $maxDate) {
            return [
                Carbon::parse($minDate)->startOfDay(),
                Carbon::parse($maxDate)->startOfDay(),
            ];
        }

        return [Carbon::today()->copy()->startOfMonth(), Carbon::today()->startOfDay()];
    }

    /**
     * Map summary counts to employee-wise list badge fields.
     */
    public function summaryToListCounts(
        array $summary,
        float $attendancePayableDays = 0.0,
        float $paidLeaveDays = 0.0,
        int $totalDays = 0,
        bool $includePaidLeave = true
    ): array {
        $leaveForPayable = $includePaidLeave ? $paidLeaveDays : 0.0;
        $cap = max($totalDays, 1);
        $payableDays = min($attendancePayableDays + $leaveForPayable, $cap);

        return [
            'present_count' => $summary['present'],
            'overtime_count' => $summary['overtime'],
            'half_day_count' => $summary['half_day'],
            'leave_count' => $summary['leave'] + $summary['unpaid_leave'],
            'absent_count' => $summary['absent'],
            'wfh_count' => $summary['wfh'],
            'early_count' => $summary['early_out'],
            'missing_punch_count' => $summary['missing_punch'],
            'weekly_off_count' => $summary['holiday'],
            'attendance_payable_days' => round($attendancePayableDays, 2),
            'paid_leave_days' => round($leaveForPayable, 2),
            'payable_days' => round($payableDays, 2),
            'unpaid_days' => round(max(0, $cap - $payableDays), 2),
        ];
    }
}
