<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** The Attendance History list as shown, with its filters applied. */
class AttendanceLogExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    public function __construct(private Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['DATE', 'DAY', 'EMPLOYEE', 'CODE', 'DEPARTMENT', 'DESIGNATION', 'CHECK IN', 'CHECK OUT', 'WORKED HOURS', 'SATURDAY ACTIVITY', 'STATUS', 'PAYABLE DAY', 'EDITED BY HAND'];
    }

    public function map($row): array
    {
        return [
            $row['date_label'],
            $row['day_label'],
            $row['employee_name'],
            $row['employee_code'],
            $row['department'],
            $row['designation'],
            $row['punch_in'] === '--:--' ? '' : $row['punch_in'],
            $row['punch_out'] === '--:--' ? '' : $row['punch_out'],
            $row['total_hours'] === '--' ? '' : $row['total_hours'],
            $row['activity_credit'] ? '+1h' : '',
            $row['status'],
            $row['payable'],
            $row['is_manual'] ? 'Yes' : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3858F9']],
        ]);

        return [];
    }
}
