<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveAllotment;
use App\Models\LeaveApplication;
use Carbon\Carbon;

class LeaveBalanceService
{
    public function getEmployeeBalanceSummary(int $employeeId): array
    {
        $totalAllotted = (float) LeaveAllotment::where('employee_id', $employeeId)->sum('leave_count');
        $totalTaken = $this->getDeductibleLeaveDaysForEmployee($employeeId);

        return [
            'total_allotted' => $totalAllotted,
            'total_taken' => $totalTaken,
            'balance' => $totalAllotted - $totalTaken,
        ];
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
        $monthStart = $monthDate->copy()->startOfMonth();
        $monthEnd = $monthDate->copy()->endOfMonth();
        $availableBalance = $this->getAvailableLeaveBalanceForMonth($employee, $monthDate);
        $currentMonthLeaveDays = $this->getDeductibleLeaveDaysBetween($employee, $monthStart, $monthEnd);
        $paidLeaveDays = min($currentMonthLeaveDays, $availableBalance);

        return [
            'available_balance' => $availableBalance,
            'current_month_leave_days' => $currentMonthLeaveDays,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => max(0, $currentMonthLeaveDays - $paidLeaveDays),
        ];
    }

    public function getAvailableLeaveBalanceForMonth(Employee $employee, Carbon $monthDate): float
    {
        $targetMonth = $monthDate->copy()->startOfMonth();
        $cursor = $this->getFirstLeaveBalanceMonth($employee, $targetMonth);
        $balance = 0.0;

        while ($cursor->lt($targetMonth)) {
            $balance += $this->getLeaveAllottedForMonth($employee, $cursor);

            $leaveDays = $this->getDeductibleLeaveDaysBetween(
                $employee,
                $cursor->copy()->startOfMonth(),
                $cursor->copy()->endOfMonth()
            );

            $balance -= min($leaveDays, $balance);
            $cursor->addMonth();
        }

        return $balance + $this->getLeaveAllottedForMonth($employee, $targetMonth);
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
        return (float) LeaveAllotment::where('employee_id', $employee->id)
            ->where('year', $monthDate->format('Y'))
            ->whereIn('month', [$monthDate->format('m'), (string) (int) $monthDate->format('m')])
            ->sum('leave_count');
    }

    private function getHolidayDatesBetween(Carbon $startDate, Carbon $endDate): array
    {
        return Holiday::whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }
}
