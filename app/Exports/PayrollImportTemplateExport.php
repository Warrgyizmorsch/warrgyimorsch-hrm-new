<?php

namespace App\Exports;

use App\Services\PayrollImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Payroll import template, pre-filled with each employee's code and name.
 * The import reads only the first sheet, so the instructions sheet is ignored on upload.
 */
class PayrollImportTemplateExport implements WithMultipleSheets
{
    /** @param Collection<int, \App\Models\Employee> $employees */
    public function __construct(private Collection $employees)
    {
    }

    public function sheets(): array
    {
        $rows = $this->employees
            ->map(fn ($employee) => [$employee->employee_code, $employee->name])
            ->values()
            ->all();

        return [
            new class($rows) implements FromArray, WithHeadings, WithTitle, ShouldAutoSize {
                public function __construct(private array $rows)
                {
                }

                public function array(): array
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return PayrollImportService::HEADERS;
                }

                public function title(): string
                {
                    return 'Payroll';
                }
            },
            new class implements FromArray, WithTitle, ShouldAutoSize {
                public function array(): array
                {
                    return [
                        ['Column', 'Required', 'What to enter'],
                        ['employee_code', 'Yes', 'Employee code. This decides whose payroll the row updates.'],
                        ['employee_name', 'Yes', 'Name, used only to catch a wrong code. Short forms are fine.'],
                        ['working_days', 'Yes', 'Days actually worked.'],
                        ['payable_days', 'Yes', 'Days being paid, including paid leave.'],
                        ['leave_balance', 'No', 'Leave balance before this payroll.'],
                        ['monthly_salary', 'Yes', 'Full monthly salary.'],
                        ['gross_paid', 'Yes', 'Salary earned this month, before deductions.'],
                        ['additional', 'No', 'Extra earnings this month. Added to net pay.'],
                        ['pf', 'No', 'PF deduction.'],
                        ['esic', 'No', 'ESIC deduction.'],
                        ['late_deduction', 'No', 'Late arrival deduction.'],
                        ['net_salary', 'Yes', 'Net pay, saved exactly as entered. The preview warns if it differs from gross_paid + additional - pf - esic - late_deduction.'],
                        ['remarks', 'No', 'Added to the payroll remarks.'],
                        [],
                        ['Rules'],
                        ['Keep the column names in row 1 and enter plain numbers, without commas or currency symbols.'],
                        ['Rows with no amounts are skipped, so you can leave employees blank or delete their rows.'],
                        ['You choose the month when uploading. Nothing is saved until you confirm the preview.'],
                    ];
                }

                public function title(): string
                {
                    return 'Instructions';
                }
            },
        ];
    }
}
