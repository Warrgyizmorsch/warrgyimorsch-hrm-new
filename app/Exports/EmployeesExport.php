<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport extends DefaultValueBinder implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping, WithColumnWidths, WithColumnFormatting, WithCustomValueBinder
{
    protected $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function collection()
    {
        return $this->employees;
    }

    public function headings(): array
    {
        return [
            'SR. NO',
            'EMPLOYEE ID',
            'NAME',
            'EMAIL',
            'MOBILE NUMBER',
            'GENDER',
            'DATE OF BIRTH',
            'DATE OF JOINING',
            'ROLE',
            'DEPARTMENT',
            'DESIGNATION',
            'AADHAAR NUMBER',
            'PAN NUMBER',
            'RESIDENTIAL ADDRESS',
            'TIME IN',
            'TIME OUT',
            'PF',
            'PF NUMBER',
            'ESI',
            'ESI NUMBER',
            'INSURANCE',
            'INSURANCE PROVIDER',
            'INSURANCE POLICY NO.',
            'BANK NAME',
            'ACCOUNT NUMBER',
            'IFSC CODE',
            'ANNUAL BASIC SALARY',
            'HRA (ANNUAL)',
            'CONVEYANCE ALLOWANCE',
            'MEDICAL ALLOWANCE',
            'OTHER ALLOWANCE',
            'TOTAL ANNUAL CTC',
        ];
    }

    public function map($emp): array
    {
        static $counter = 0;
        $counter++;

        $totalSalary = ($emp->basic_salary ?? 0) + ($emp->hra ?? 0) + ($emp->conveyance_allowance ?? 0) + ($emp->medical_allowance ?? 0) + ($emp->other_allowance ?? 0);

        return [
            $counter,
            $emp->employee_code ?? ('EC' . str_pad($emp->id, 4, '0', STR_PAD_LEFT)),
            $emp->name,
            $emp->email ?? '-',
            $emp->mobile_number ?? '-',
            $emp->gender ? ucfirst($emp->gender) : '-',
            $emp->date_of_birth ?? '-',
            $emp->date_of_joining ?? '-',
            $emp->role ? ucfirst(str_replace('_', ' ', $emp->role)) : '-',
            $emp->departmentRef->name ?? '-',
            $emp->designation ?? '-',
            $emp->aadhaar_number ?? '-',
            $emp->pan_number ?? '-',
            $emp->address ?? '-',
            $emp->time_in ?? '-',
            $emp->time_out ?? '-',
            $emp->pf ? 'Yes' : 'No',
            $emp->pf_number ?? '-',
            $emp->esi ? 'Yes' : 'No',
            $emp->esi_number ?? '-',
            $emp->insurance ? 'Yes' : 'No',
            $emp->insurance_provider ?? '-',
            $emp->insurance_policy_number ?? '-',
            $emp->bank_name ?? '-',
            $emp->account_number ? (string) $emp->account_number : '-',
            $emp->ifsc_code ?? '-',
            $emp->basic_salary ?? 0,
            $emp->hra ?? 0,
            $emp->conveyance_allowance ?? 0,
            $emp->medical_allowance ?? 0,
            $emp->other_allowance ?? 0,
            $totalSalary,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        $column = $cell->getColumn();
        
        // Force strings for columns with long numbers (Mobile, Aadhaar, Account Number)
        if (in_array($column, ['E', 'L', 'Y'])) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        // Return default binding for other columns
        return parent::bindValue($cell, $value);
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3858F9']
                ]
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT,  // Mobile as Text
            'L' => NumberFormat::FORMAT_TEXT,  // Aadhaar as Text
            'Y' => NumberFormat::FORMAT_TEXT,  // Account Number as Text
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,   // SR. NO
            'B' => 18,   // EMPLOYEE CODE
            'C' => 30,   // NAME
            'D' => 35,   // EMAIL
            'E' => 18,   // MOBILE
            'F' => 12,   // GENDER
            'G' => 16,   // DOB
            'H' => 16,   // DOJ
            'I' => 20,   // ROLE
            'J' => 25,   // DEPARTMENT
            'K' => 25,   // DESIGNATION
            'L' => 22,   // AADHAAR
            'M' => 18,   // PAN
            'N' => 45,   // ADDRESS
            'O' => 12,   // TIME IN
            'P' => 12,   // TIME OUT
            'Q' => 8,    // PF
            'R' => 20,   // PF NUMBER
            'S' => 8,    // ESI
            'T' => 20,   // ESI NUMBER
            'U' => 12,   // INSURANCE
            'V' => 25,   // INSURANCE PROVIDER
            'W' => 25,   // INSURANCE POLICY
            'X' => 25,   // BANK NAME
            'Y' => 25,   // ACCOUNT NUMBER
            'Z' => 18,   // IFSC
            'AA' => 20,  // BASIC
            'AB' => 20,  // HRA
            'AC' => 25,  // CONVEYANCE
            'AD' => 25,  // MEDICAL
            'AE' => 22,  // OTHER
            'AF' => 22,  // TOTAL
        ];
    }
}
