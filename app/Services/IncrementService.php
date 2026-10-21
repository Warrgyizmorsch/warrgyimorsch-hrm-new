<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeIncrement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Salary increments.
 *
 *   record()      HR enters effective date + monthly amount. A date on/before today is applied at once;
 *                 a future one waits and is applied by `increments:apply-due` (daily schedule).
 *   apply()       New gross = current gross + amount, split into the salary components the same way
 *                 the employee's salary is split today (Business Development: configured ratios;
 *                 everyone else: current proportions). The before/after split is kept on the row.
 *   salaryAsOf()  The salary components valid for a given payroll month, so October payroll
 *                 calculated after a 1 November increment still uses the October salary.
 *   nextDueDate() Last increment (or joining date) + config('increment.cycle_months').
 *
 * An increment counts for a payroll month when its effective date falls on or before that month's
 * last day — use the 1st of a month as the effective date to avoid paying a partial month at the new rate.
 */
class IncrementService
{
    public const SALARY_FIELDS = [
        'basic_salary',
        'dearness_allowance',
        'hra',
        'conveyance_allowance',
        'medical_allowance',
        'other_allowance',
    ];

    /** @return array<string,float> */
    public function components(Employee $employee): array
    {
        $out = [];
        foreach (self::SALARY_FIELDS as $field) {
            $out[$field] = round((float) ($employee->{$field} ?? 0), 2);
        }

        return $out;
    }

    public function gross(array $components): float
    {
        return round(array_sum($components), 2);
    }

    /** Split $newGross like the employee's current salary. */
    public function splitFor(Employee $employee, float $newGross): array
    {
        $breakdown = SalaryStructureService::breakdown($newGross, $employee->department_id);
        if ($breakdown) {
            return array_merge(array_fill_keys(self::SALARY_FIELDS, 0.0), $breakdown);
        }

        $current = $this->components($employee);
        $oldGross = $this->gross($current);
        if ($oldGross <= 0) {
            return array_merge(array_fill_keys(self::SALARY_FIELDS, 0.0), ['basic_salary' => round($newGross, 2)]);
        }

        $factor = $newGross / $oldGross;
        $new = array_map(fn ($v) => round($v * $factor, 2), $current);
        $new['basic_salary'] = round($new['basic_salary'] + ($newGross - array_sum($new)), 2); // rounding drift

        return $new;
    }

    public function record(Employee $employee, Carbon $effectiveDate, float $amount, ?string $remarks, ?int $userId): EmployeeIncrement
    {
        return DB::transaction(function () use ($employee, $effectiveDate, $amount, $remarks, $userId) {
            // Figures are a preview against today's salary; apply() recalculates them on the day.
            $previous = $this->components($employee);
            $previousGross = $this->gross($previous);
            $newGross = round($previousGross + $amount, 2);

            $increment = EmployeeIncrement::create([
                'employee_id' => $employee->id,
                'effective_date' => $effectiveDate->toDateString(),
                'previous_gross' => $previousGross,
                'increment_amount' => round($amount, 2),
                'new_gross' => $newGross,
                'increment_percent' => $this->percent($amount, $previousGross),
                'previous_components' => $previous,
                'new_components' => $this->splitFor($employee, $newGross),
                'remarks' => $remarks,
                'created_by' => $userId,
            ]);

            $this->applyDue($employee->id);

            return $increment->refresh();
        });
    }

    /** Apply every increment whose effective date has arrived (one employee, or all). Returns how many. */
    public function applyDue(?int $employeeId = null): int
    {
        $due = EmployeeIncrement::whereNull('applied_at')
            ->whereDate('effective_date', '<=', today())
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        foreach ($due as $increment) {
            $this->apply($increment);
        }

        return $due->count();
    }

    private function apply(EmployeeIncrement $increment): void
    {
        DB::transaction(function () use ($increment) {
            $employee = Employee::lockForUpdate()->find($increment->employee_id);
            $fresh = EmployeeIncrement::lockForUpdate()->find($increment->id);
            if (!$employee || !$fresh || $fresh->applied_at) {
                return;
            }

            $previous = $this->components($employee);
            $previousGross = $this->gross($previous);
            $newGross = round($previousGross + $fresh->increment_amount, 2);
            $new = $this->splitFor($employee, $newGross);

            $employee->forceFill($new)->save();
            $fresh->update([
                'previous_gross' => $previousGross,
                'new_gross' => $newGross,
                'increment_percent' => $this->percent($fresh->increment_amount, $previousGross),
                'previous_components' => $previous,
                'new_components' => $new,
                'applied_at' => now(),
            ]);
        });
    }

    /**
     * Salary components valid for the payroll month ending $monthEnd (not saved — in-memory only).
     *
     * @return array<string,float>
     */
    public function salaryAsOf(Employee $employee, Carbon $monthEnd): array
    {
        $increments = EmployeeIncrement::where('employee_id', $employee->id)->orderBy('effective_date')->orderBy('id')->get();

        // Already applied but effective after this month → the month still uses the salary before it.
        $laterApplied = $increments->first(fn ($i) => $i->applied_at && $i->effective_date->gt($monthEnd));
        if ($laterApplied && $laterApplied->previous_components) {
            return $this->normalise($laterApplied->previous_components);
        }

        // Effective in/before this month but not applied yet (scheduler hasn't run) → use its new salary.
        $pending = $increments->filter(fn ($i) => !$i->applied_at && $i->effective_date->lte($monthEnd));
        if ($pending->isNotEmpty()) {
            $gross = $this->gross($this->components($employee)) + $pending->sum('increment_amount');

            return $this->splitFor($employee, round($gross, 2));
        }

        return $this->components($employee);
    }

    public function nextDueDate(Employee $employee, ?EmployeeIncrement $last = null): ?Carbon
    {
        $from = $last?->effective_date ?? ($employee->date_of_joining ? Carbon::parse($employee->date_of_joining) : null);

        return $from?->copy()->addMonthsNoOverflow((int) config('increment.cycle_months', 12));
    }

    private function percent(float $amount, float $previousGross): float
    {
        return $previousGross > 0 ? round($amount / $previousGross * 100, 2) : 0.0;
    }

    private function normalise(array $components): array
    {
        $out = [];
        foreach (self::SALARY_FIELDS as $field) {
            $out[$field] = round((float) ($components[$field] ?? 0), 2);
        }

        return $out;
    }
}
