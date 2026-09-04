<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceHistoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    protected Carbon $startDate;

    protected Carbon $endDate;

    /** @var Carbon[] */
    protected array $dates = [];

    protected ?int $employeeId;

    protected ?int $department;

    protected AttendanceHistoryService $historyService;

    public function __construct(Request $request)
    {
        $this->historyService = app(AttendanceHistoryService::class);
        $this->employeeId = $request->filled('employee_id') ? (int) $request->employee_id : null;

        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isTeamLeader = $role === 'team_leader';

        $loggedEmployee = Employee::find($user->employee_id);
        $this->department = $isTeamLeader ? ($loggedEmployee->department_id ?? null) : null;

        [$this->startDate, $this->endDate] = $this->historyService->resolveExportDateRange(
            $request,
            $this->employeeId
        );

        for ($period = $this->startDate->copy(); $period->lte($this->endDate); $period->addDay()) {
            $this->dates[] = $period->copy();
        }
    }

    public function collection()
    {
        $query = Employee::active()->orderBy('name', 'asc');

        if (!empty($this->department)) {
            $query->where('department_id', $this->department);
        }

        if (!empty($this->employeeId)) {
            $query->where('id', $this->employeeId);
        }

        $query->whereHas('attendances', function ($q) {
            $q->whereBetween('attendance_date', [
                $this->startDate->toDateString(),
                $this->endDate->toDateString(),
            ]);
        });

        return $query->get();
    }

    public function headings(): array
    {
        $headers = ['SR. NO', 'EMPLOYEE NAME', 'DESIGNATION'];

        foreach ($this->dates as $date) {
            $headers[] = $date->format('d M');
        }

        $headers[] = 'PRESENT';
        $headers[] = 'ABSENT';
        $headers[] = 'LEAVE/HD';

        return $headers;
    }

    public function map($emp): array
    {
        static $counter = 0;
        $counter++;

        $history = $this->historyService->buildMonthlyHistory(
            (int) $emp->id,
            $this->startDate,
            $this->endDate
        );
        $historyByDate = collect($history)->keyBy('date_key');
        $totals = $this->historyService->summaryToExportTotals(
            $this->historyService->buildMonthlySummary($history)
        );

        $row = [
            $counter,
            $emp->name,
            $emp->designation ?? 'N/A',
        ];

        foreach ($this->dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $day = $historyByDate->get($dateKey);
            $row[] = $day
                ? $this->historyService->formatExportDayCell($day)
                : '-';
        }

        $row[] = $totals['present'];
        $row[] = $totals['absent'];
        $row[] = $totals['leave_hd'];

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3858F9'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D2:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ]);

        return [];
    }
}
