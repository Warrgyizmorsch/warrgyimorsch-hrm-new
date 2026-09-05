<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PayrollImport implements ToCollection
{
    /** @var array<int, string> Names of rows that had no matching employee. */
    public array $skipped = [];

    /** @var array<int, string> Names of rows successfully saved. */
    public array $imported = [];

    private string $month;

    private int $daysInMonth;

    public function __construct(string $month)
    {
        $this->month = $month;
        $this->daysInMonth = Carbon::createFromFormat('Y-m', $month)->daysInMonth;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            $employeeCode = trim((string) ($row[1] ?? ''));
            $payableDays = $row[3] ?? null;   // "Pay" (days present)
            $basicSalary = $row[4] ?? null;   // "Salary" (monthly base)
            $grossSalary = $row[6] ?? null;   // "Paid" (prorated gross for the month)
            $addNote = trim((string) ($row[9] ?? ''));

            // Skip header rows and blank trailing rows.
            if ($name === '' || $name === 'Name' || !is_numeric($payableDays) || !is_numeric($grossSalary)) {
                continue;
            }

            $employee = $this->findEmployee($employeeCode);

            if (!$employee) {
                $this->skipped[] = $name . ($employeeCode !== '' ? " (code {$employeeCode})" : '');
                continue;
            }

            $payableDays = round((float) $payableDays, 2);
            $basicSalary = round((float) ($basicSalary ?? 0), 2);
            $grossSalary = round((float) $grossSalary, 2);

            $esiDeduction = ($employee->esi && $grossSalary <= 21000)
                ? round($grossSalary * 0.0075, 2)
                : 0.0;

            $netSalary = round($grossSalary - $esiDeduction, 2);
            $unpaidDays = max(0, round($this->daysInMonth - $payableDays, 2));
            $salaryLoss = round($basicSalary - $grossSalary, 2);

            Payroll::updateOrCreate(
                ['employee_id' => $employee->id, 'month' => $this->month],
                [
                    'payable_days' => $payableDays,
                    'unpaid_days' => $unpaidDays,
                    'basic_salary' => $basicSalary,
                    'gross_salary' => $grossSalary,
                    'hra' => 0,
                    'conveyance_allowance' => 0,
                    'medical_allowance' => 0,
                    'other_allowance' => 0,
                    'pf_deduction' => 0,
                    'esi_deduction' => $esiDeduction,
                    'other_deduction' => 0,
                    'deductions' => $esiDeduction,
                    'salary_loss' => $salaryLoss,
                    'net_salary' => $netSalary,
                    'status' => 'pending',
                    'remarks' => $addNote !== '' ? "Imported from Excel. Add.: {$addNote}" : 'Imported from Excel.',
                ]
            );

            $this->imported[] = $employee->name;
        }
    }

    private function findEmployee(string $employeeCode): ?Employee
    {
        if ($employeeCode === '' || !is_numeric($employeeCode)) {
            return null;
        }

        return Employee::where('employee_code', $employeeCode)->first();
    }
}
