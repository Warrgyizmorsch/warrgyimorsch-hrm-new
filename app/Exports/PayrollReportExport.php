<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Payroll register laid out like HR's monthly "final salary" sheet, so an export can be
 * checked line-by-line against (or re-imported via) PayrollImport.
 */
class PayrollReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Collection $payrolls)
    {
    }

    public function headings(): array
    {
        return [
            'Name',
            'Emp Code',
            'Department',
            'Month',
            'Month Days',
            'Pay',
            'Paid Leave',
            'Unpaid Days',
            'Leave Balance',
            'Salary',
            'per day',
            'Variable Earning',
            'Paid',
            'ESIC 0.75%',
            'PF',
            'Other Deductions',
            'Total Deductions',
            'Final',
            'Salary Loss',
            'Status',
            'Remarks',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->payrolls as $p) {
            $employee = $p->employee;
            $monthDays = Carbon::createFromFormat('Y-m', $p->month)->daysInMonth;

            // Monthly salary = all fixed components (imported rows only carry basic).
            $salary = (float) $p->basic_salary + (float) $p->dearness_allowance + (float) $p->hra
                + (float) $p->conveyance_allowance + (float) $p->medical_allowance + (float) $p->other_allowance;

            $otherDeductions = (float) $p->other_deduction + (float) $p->epf_deduction
                + (float) $p->professional_tax_deduction + (float) $p->loan_recovery_deduction;

            $rows[] = [
                $employee?->name ?? '-',
                $employee?->employee_code ?? '-',
                $employee?->departmentRef?->name ?? '-',
                Carbon::createFromFormat('Y-m', $p->month)->format('M Y'),
                $monthDays,
                (float) $p->payable_days,
                (float) $p->paid_leave_days,
                (float) $p->unpaid_days,
                (float) $p->leave_balance_before_payroll,
                round($salary, 2),
                round($salary / $monthDays, 2),
                (float) $p->variable_earning,
                (float) $p->gross_salary,
                (float) $p->esi_deduction,
                (float) $p->pf_deduction,
                round($otherDeductions, 2),
                (float) $p->deductions,
                (float) $p->net_salary,
                (float) $p->salary_loss,
                ucfirst((string) $p->status),
                $p->remarks,
            ];
        }

        if ($rows) {
            $sum = fn (int $col) => round(array_sum(array_column($rows, $col)), 2);
            $rows[] = ['TOTAL', '', '', '', '', '', '', '', '', $sum(9), '', $sum(11), $sum(12), $sum(13), $sum(14), $sum(15), $sum(16), $sum(17), $sum(18), '', ''];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->payrolls->count() + 2;

        $sheet->getStyle('J2:S' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

        return [
            1 => ['font' => ['bold' => true]],
            $lastRow => ['font' => ['bold' => true]],
        ];
    }
}
