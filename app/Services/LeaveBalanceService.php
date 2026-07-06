<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveAllotment;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaveBalanceService
{
    /**
     * Batch balance calculation for listing pages (avoids N+1 queries).
     *
     * @return array<int, array<string, float>>
     */
    public function getBulkEmployeeBalanceSummaries(iterable $employees, Carbon $monthDate): array
    {
        $employees = collect($employees)->filter()->values();
        if ($employees->isEmpty()) {
            return [];
        }

        $targetMonth = $monthDate->copy()->startOfMonth();
        $employeeIds = $employees->pluck('id')->all();

        $allotmentsByEmployee = LeaveAllotment::whereIn('employee_id', $employeeIds)
            ->get()
            ->groupBy('employee_id');

        $leavesByEmployee = LeaveApplication::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $targetMonth->copy()->endOfMonth()->toDateString())
            ->orderBy('start_date')
            ->get()
            ->groupBy('employee_id');

        $rangeStart = $targetMonth->copy()->startOfYear();
        foreach ($employees as $employee) {
            $employeeAllotments = $allotmentsByEmployee->get($employee->id, collect());
            $employeeLeaves = $leavesByEmployee->get($employee->id, collect());
            $firstMonth = $this->resolveFirstLeaveBalanceMonth(
                $targetMonth,
                $employeeAllotments,
                $employeeLeaves
            );
            if ($firstMonth->lt($rangeStart)) {
                $rangeStart = $firstMonth->copy();
            }
        }

        $holidayLookup = $this->buildHolidayLookup(
            $rangeStart,
            $targetMonth->copy()->endOfMonth()
        );

        $summaries = [];
        foreach ($employees as $employee) {
            $employeeAllotments = $allotmentsByEmployee->get($employee->id, collect());
            $employeeLeaves = $leavesByEmployee->get($employee->id, collect());
            $allotmentMap = $this->buildAllotmentMap($employeeAllotments);
            $usedByMonth = $this->buildMonthlyUsedMap(
                $employeeLeaves,
                $targetMonth->copy()->endOfMonth(),
                $holidayLookup
            );

            $opening = $this->computeOpeningBalance(
                $targetMonth,
                $employeeAllotments,
                $allotmentMap,
                $employeeLeaves,
                $usedByMonth
            );

            $monthKey = $targetMonth->format('Y') . '-' . ((int) $targetMonth->format('m'));
            $used = (float) ($usedByMonth[$monthKey] ?? 0);
            $closing = max(0, $opening - $used);

            $summaries[$employee->id] = [
                'total_allotted' => $opening,
                'total_taken' => $used,
                'balance' => $closing,
                'unpaid_leave_days' => max(0, $used - $opening),
                'monthly_allotment' => $this->allottedFromMap($allotmentMap, $targetMonth),
            ];
        }

        return $summaries;
    }

    public function getEmployeeBalanceSummary(int $employeeId, ?Carbon $monthDate = null): array
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return [
                'total_allotted' => 0,
                'total_taken' => 0,
                'balance' => 0,
                'unpaid_leave_days' => 0,
                'monthly_allotment' => 0,
            ];
        }

        $monthDate = ($monthDate ?? now())->copy()->startOfMonth();
        $summaries = $this->getBulkEmployeeBalanceSummaries(collect([$employee]), $monthDate);

        return $summaries[$employee->id] ?? [
            'total_allotted' => 0,
            'total_taken' => 0,
            'balance' => 0,
            'unpaid_leave_days' => 0,
            'monthly_allotment' => 0,
        ];
    }

    public function buildMonthlyBalanceTimeline(int $employeeId): array
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return [];
        }

        $monthlyAllotments = LeaveAllotment::where('employee_id', $employeeId)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $rows = [];
        $carryForward = 0.0;

        foreach ($monthlyAllotments as $allotment) {
            $monthDate = Carbon::createFromDate((int) $allotment->year, (int) $allotment->month, 1)->startOfMonth();
            $used = $this->getDeductibleLeaveDaysBetween(
                $employee,
                $monthDate->copy()->startOfMonth(),
                $monthDate->copy()->endOfMonth()
            );

            $monthResult = $this->closeMonthBalance($carryForward, (float) $allotment->leave_count, $used);

            $rows[] = [
                'type' => strtoupper($monthDate->format('F')) . " ({$allotment->year})",
                'allotted' => (float) $allotment->leave_count,
                'used' => $used,
                'available' => $monthResult['closing'],
                'unpaid_leave' => $monthResult['unpaid_leave'],
                'carry_forward' => $monthResult['carry_forward'],
                'reference' => 'Monthly Quota',
            ];

            $carryForward = $monthResult['carry_forward'];
        }

        return array_reverse($rows);
    }

    public function getDeductibleLeaveDaysForEmployee(int $employeeId, ?int $year = null, ?int $month = null): float
    {
        if ($year !== null || $month !== null) {
            $periodYear = $year ?? (int) now()->format('Y');
            $startDate = $month !== null
                ? Carbon::createFromDate($periodYear, (int) $month, 1)->startOfMonth()
                : Carbon::createFromDate($periodYear, 1, 1)->startOfYear();

            $endDate = $month !== null
                ? $startDate->copy()->endOfMonth()
                : $startDate->copy()->endOfYear();

            $employee = Employee::find($employeeId);

            return $employee
                ? $this->getDeductibleLeaveDaysBetween($employee, $startDate, $endDate)
                : 0;
        }

        $query = LeaveApplication::where('employee_id', $employeeId)
            ->where('status', 'approved');

        return $query->get()->sum(fn (LeaveApplication $leave) => $this->getDeductibleLeaveDays($leave));
    }

    public function getPayrollLeaveSummary(Employee $employee, Carbon $monthDate): array
    {
        $monthDate = $monthDate->copy()->startOfMonth();
        $summary = $this->getBulkEmployeeBalanceSummaries(collect([$employee]), $monthDate);
        $row = $summary[$employee->id] ?? [
            'total_allotted' => 0,
            'total_taken' => 0,
            'balance' => 0,
            'unpaid_leave_days' => 0,
        ];

        return [
            'available_balance' => $row['total_allotted'],
            'current_month_leave_days' => $row['total_taken'],
            'paid_leave_days' => min($row['total_taken'], $row['total_allotted']),
            'unpaid_leave_days' => $row['unpaid_leave_days'],
        ];
    }

    public function getAvailableLeaveBalanceForMonth(Employee $employee, Carbon $monthDate): float
    {
        $summary = $this->getBulkEmployeeBalanceSummaries(collect([$employee]), $monthDate->copy()->startOfMonth());

        return (float) ($summary[$employee->id]['total_allotted'] ?? 0);
    }

    /**
     * Close a leave month: excess usage becomes salary deduction (unpaid leave), never negative balance.
     * Any positive closing balance carries forward to the next month.
     */
    private function closeMonthBalance(float $carryIn, float $allotment, float $used): array
    {
        $opening = $carryIn + $allotment;
        $paidLeave = min($used, $opening);
        $unpaidLeave = max(0, $used - $opening);
        $closing = max(0, $opening - $used);

        return [
            'opening' => $opening,
            'paid_leave' => $paidLeave,
            'unpaid_leave' => $unpaidLeave,
            'closing' => $closing,
            'carry_forward' => $this->resolveCarryForward($closing),
        ];
    }

    private function resolveCarryForward(float $closingBalance): float
    {
        return $closingBalance > 0 ? $closingBalance : 0.0;
    }

    public function getDeductibleLeaveDaysBetween(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        return array_sum($this->getDeductibleLeaveDatesBetween($employee, $startDate, $endDate));
    }

    public function getDeductibleLeaveDatesBetween(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        $leaveDates = [];

        $leaves = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->where(function ($query) use ($startDate) {
                $query->whereDate('end_date', '>=', $startDate->toDateString())
                    ->orWhereNull('end_date');
            })
            ->orderBy('start_date')
            ->get();

        foreach ($leaves as $leave) {
            if (!$this->isDeductibleLeave($leave)) {
                continue;
            }

            $leaveStart = Carbon::parse($leave->start_date)->startOfDay()->max($startDate->copy()->startOfDay());
            $leaveEnd = Carbon::parse($leave->end_date ?: $leave->start_date)->startOfDay()->min($endDate->copy()->startOfDay());

            if ($leaveEnd->lt($leaveStart)) {
                continue;
            }

            if ($this->isHalfDayLeave($leave)) {
                $leaveDates[$leaveStart->toDateString()] = ($leaveDates[$leaveStart->toDateString()] ?? 0) + 0.5;
                continue;
            }

            $holidayDates = $this->getHolidayDatesBetween($leaveStart, $leaveEnd);

            for ($date = $leaveStart->copy(); $date->lte($leaveEnd); $date->addDay()) {
                if ($date->isSunday() || in_array($date->toDateString(), $holidayDates, true)) {
                    continue;
                }

                $leaveDates[$date->toDateString()] = ($leaveDates[$date->toDateString()] ?? 0) + 1;
            }
        }

        ksort($leaveDates);

        return $leaveDates;
    }

    public function isDeductibleLeave(LeaveApplication $leave): bool
    {
        $category = strtolower($leave->leave_category ?? '');
        $type = strtolower($leave->leave_type ?? '');

        return !str_contains($category, 'wfh')
            && !str_contains($type, 'wfh')
            && !str_contains($category, 'gatepass')
            && !str_contains($type, 'gatepass')
            && !str_contains($category, 'early')
            && !str_contains($type, 'early');
    }

    public function isHalfDayLeave(LeaveApplication $leave): bool
    {
        $category = strtolower($leave->leave_category ?? '');
        $type = strtolower($leave->leave_type ?? '');

        return str_contains($category, 'half')
            || str_contains($type, 'half')
            || (float) $leave->total_days === 0.5;
    }

    private function getDeductibleLeaveDays(LeaveApplication $leave): float
    {
        if (!$this->isDeductibleLeave($leave)) {
            return 0;
        }

        if ($this->isHalfDayLeave($leave)) {
            return 0.5;
        }

        $startDate = Carbon::parse($leave->start_date)->startOfDay();
        $endDate = Carbon::parse($leave->end_date ?: $leave->start_date)->startOfDay();

        if ($endDate->lt($startDate)) {
            return 0;
        }

        $holidayDates = $this->getHolidayDatesBetween($startDate, $endDate);
        $total = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isSunday() || in_array($date->toDateString(), $holidayDates, true)) {
                continue;
            }

            $total++;
        }

        return $total;
    }

    private function getFirstLeaveBalanceMonth(Employee $employee, Carbon $fallbackMonth): Carbon
    {
        $firstAllotment = LeaveAllotment::where('employee_id', $employee->id)
            ->orderBy('year')
            ->orderBy('month')
            ->first();

        $firstLeaveDate = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->orderBy('start_date')
            ->value('start_date');

        $dates = [$fallbackMonth->copy()->startOfMonth()];

        if ($firstAllotment) {
            $dates[] = Carbon::createFromDate((int) $firstAllotment->year, (int) $firstAllotment->month, 1)->startOfMonth();
        }

        if ($firstLeaveDate) {
            $dates[] = Carbon::parse($firstLeaveDate)->startOfMonth();
        }

        return collect($dates)->sortBy(fn (Carbon $date) => $date->timestamp)->first();
    }

    private function getLeaveAllottedForMonth(Employee $employee, Carbon $monthDate): float
    {
        $monthNum = (int) $monthDate->format('m');

        return (float) LeaveAllotment::where('employee_id', $employee->id)
            ->where('year', $monthDate->format('Y'))
            ->whereIn('month', [
                $monthDate->format('m'),
                sprintf('%02d', $monthNum),
                (string) $monthNum,
            ])
            ->sum('leave_count');
    }

    private function getHolidayDatesBetween(Carbon $startDate, Carbon $endDate, ?array $holidayLookup = null): array
    {
        if ($holidayLookup !== null) {
            $dates = [];
            for ($date = $startDate->copy()->startOfDay(); $date->lte($endDate); $date->addDay()) {
                $key = $date->toDateString();
                if (isset($holidayLookup[$key])) {
                    $dates[] = $key;
                }
            }

            return $dates;
        }

        return Holiday::whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }

    private function buildHolidayLookup(Carbon $startDate, Carbon $endDate): array
    {
        return Holiday::whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->pluck('date')
            ->mapWithKeys(fn ($date) => [Carbon::parse($date)->toDateString() => true])
            ->all();
    }

    /**
     * @return array<string, float> keys like "2026-7"
     */
    private function buildAllotmentMap(Collection $allotments): array
    {
        $map = [];
        foreach ($allotments as $allotment) {
            $key = ((int) $allotment->year) . '-' . ((int) $allotment->month);
            $map[$key] = ($map[$key] ?? 0) + (float) $allotment->leave_count;
        }

        return $map;
    }

    private function allottedFromMap(array $allotmentMap, Carbon $monthDate): float
    {
        $key = $monthDate->format('Y') . '-' . ((int) $monthDate->format('m'));

        return (float) ($allotmentMap[$key] ?? 0);
    }

    private function resolveFirstLeaveBalanceMonth(
        Carbon $fallbackMonth,
        Collection $allotments,
        Collection $leaves
    ): Carbon {
        $dates = [$fallbackMonth->copy()->startOfMonth()];

        $firstAllotment = $allotments->sortBy(fn ($row) => sprintf('%04d-%02d', (int) $row->year, (int) $row->month))->first();
        if ($firstAllotment) {
            $dates[] = Carbon::createFromDate((int) $firstAllotment->year, (int) $firstAllotment->month, 1)->startOfMonth();
        }

        $firstLeaveDate = $leaves->sortBy('start_date')->value('start_date');
        if ($firstLeaveDate) {
            $dates[] = Carbon::parse($firstLeaveDate)->startOfMonth();
        }

        return collect($dates)->sortBy(fn (Carbon $date) => $date->timestamp)->first();
    }

    private function computeOpeningBalance(
        Carbon $targetMonth,
        Collection $allotments,
        array $allotmentMap,
        Collection $leaves,
        array $usedByMonth
    ): float {
        $cursor = $this->resolveFirstLeaveBalanceMonth($targetMonth, $allotments, $leaves);
        $balance = 0.0;

        while ($cursor->lt($targetMonth)) {
            $monthKey = $cursor->format('Y') . '-' . ((int) $cursor->format('m'));
            $monthResult = $this->closeMonthBalance(
                $balance,
                $this->allottedFromMap($allotmentMap, $cursor),
                (float) ($usedByMonth[$monthKey] ?? 0)
            );

            $balance = $monthResult['carry_forward'];
            $cursor->addMonth();
        }

        return $balance + $this->allottedFromMap($allotmentMap, $targetMonth);
    }

    /**
     * @return array<string, float> keys like "2026-7"
     */
    private function buildMonthlyUsedMap(
        Collection $leaves,
        Carbon $rangeEnd,
        array $holidayLookup
    ): array {
        if ($leaves->isEmpty()) {
            return [];
        }

        $firstLeave = $leaves->sortBy('start_date')->first();
        $rangeStart = Carbon::parse($firstLeave->start_date)->startOfMonth();

        $leaveDates = $this->buildDeductibleLeaveDates($leaves, $rangeStart, $rangeEnd, $holidayLookup);
        $usedByMonth = [];

        foreach ($leaveDates as $dateStr => $days) {
            $monthKey = Carbon::parse($dateStr)->format('Y') . '-' . ((int) Carbon::parse($dateStr)->format('m'));
            $usedByMonth[$monthKey] = ($usedByMonth[$monthKey] ?? 0) + $days;
        }

        return $usedByMonth;
    }

    /**
     * @return array<string, float>
     */
    private function buildDeductibleLeaveDates(
        Collection $leaves,
        Carbon $startDate,
        Carbon $endDate,
        ?array $holidayLookup = null
    ): array {
        $leaveDates = [];

        foreach ($leaves as $leave) {
            if (!$this->isDeductibleLeave($leave)) {
                continue;
            }

            $leaveStart = Carbon::parse($leave->start_date)->startOfDay()->max($startDate->copy()->startOfDay());
            $leaveEnd = Carbon::parse($leave->end_date ?: $leave->start_date)->startOfDay()->min($endDate->copy()->startOfDay());

            if ($leaveEnd->lt($leaveStart)) {
                continue;
            }

            if ($this->isHalfDayLeave($leave)) {
                $leaveDates[$leaveStart->toDateString()] = ($leaveDates[$leaveStart->toDateString()] ?? 0) + 0.5;
                continue;
            }

            $holidayDates = $this->getHolidayDatesBetween($leaveStart, $leaveEnd, $holidayLookup);

            for ($date = $leaveStart->copy(); $date->lte($leaveEnd); $date->addDay()) {
                if ($date->isSunday() || in_array($date->toDateString(), $holidayDates, true)) {
                    continue;
                }

                $leaveDates[$date->toDateString()] = ($leaveDates[$date->toDateString()] ?? 0) + 1;
            }
        }

        ksort($leaveDates);

        return $leaveDates;
    }
}
