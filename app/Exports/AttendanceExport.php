<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class AttendanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $dates = [];
    protected $attendanceMap;
    protected $holidayMap = [];
    protected $activityDays = [];
    protected $employeeId;
    protected $department;


    public function __construct($startDate, $endDate, $employeeId)
    {
        $this->startDate = !empty($startDate)
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::parse(Attendance::min('attendance_date'))->startOfDay();

        $this->endDate = !empty($endDate)
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::parse(Attendance::max('attendance_date'))->endOfDay();
        $this->employeeId = $employeeId;

        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isTeamLeader = $role === 'team_leader';

        $loggedEmployeeId = $user->employee_id;
        $loggedEmployee = Employee::where('id', $loggedEmployeeId)->first();
        $this->department = $isTeamLeader ? ($loggedEmployee->department ?? null) : null;

        $period = Carbon::parse($this->startDate);

        while ($period->lte($this->endDate)) {
            $this->dates[] = $period->copy();
            $period->addDay();
        }

        $attQuery = Attendance::query();

        if (!empty($this->department)) {
            $attQuery->whereHas('employee', function ($q) {
                $q->where('department', $this->department);
            });
        }

        if (!empty($this->employeeId)) {
            $attQuery->where('employee_id', $this->employeeId);
        }

        $attQuery->whereBetween('attendance_date', [
            $this->startDate->toDateString(),
            $this->endDate->toDateString()
        ]);

        $attendances = $attQuery->with('employee')->get();

        $this->attendanceMap = [];
        $attendancesByDate = [];
        foreach ($attendances as $att) {
            $dateKey = Carbon::parse($att->attendance_date)->format('Y-m-d');
            $this->attendanceMap[$att->employee_id][$dateKey] = $att;
            $attendancesByDate[$dateKey][] = $att;
        }

        foreach ($attendancesByDate as $date => $atts) {
            $earlyOuts = 0;
            $totalPresent = 0;
            foreach ($atts as $att) {
                if (strtolower($att->status) === 'present' || strtolower($att->status) === 'early_out') {
                    if ($att->check_out && $att->employee && $att->employee->time_out) {
                        $totalPresent++;
                        $checkOut = Carbon::parse($att->check_out);
                        $punchTime = $checkOut->format('H:i');
                        // Early out if between 3:00 PM and 5:30 PM
                        if ($punchTime >= '15:00' && $punchTime < '17:30') {
                            $earlyOuts++;
                        }
                    }
                }
            }
            if ($totalPresent > 2 && ($earlyOuts / $totalPresent) >= 0.7) {
                $this->activityDays[$date] = true;
            }
        }

        $holidays = Holiday::whereBetween('date', [
            $this->startDate->toDateString(),
            $this->endDate->toDateString()
        ])->get();

        foreach ($holidays as $h) {
            $dateKey = Carbon::parse($h->date)->format('Y-m-d');
            $this->holidayMap[$dateKey] = $h->title;
        }
    }

    public function collection()
    {
        $query = Employee::orderBy('name', 'asc');

        if (!empty($this->department)) {
            $query->where('department', $this->department);
        }

        if (!empty($this->employeeId)) {
            $query->where('id', $this->employeeId);
        }

        $query->whereHas('attendances', function ($q) {
            $q->whereBetween('attendance_date', [
                $this->startDate,
                $this->endDate
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

        $row = [
            $counter,
            $emp->name,
            $emp->designation ?? 'N/A',
        ];

        $present = 0;
        $absent = 0;
        $others = 0;

        foreach ($this->dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $att = $this->attendanceMap[$emp->id][$dateKey] ?? null;

            if ($att) {
                $statusRaw = strtolower($att->status);
                $displayText = ucfirst(str_replace('_', ' ', $statusRaw));
                $isActivityDay = isset($this->activityDays[$dateKey]);
                $isHoliday = isset($this->holidayMap[$dateKey]);

                if ($isHoliday && $statusRaw === 'absent') {
                    $displayText = 'Holiday';
                    $present++;
                } elseif (in_array($statusRaw, ['present', 'late', 'early_out', 'half_day', 'wfh', 'early_leave'])) {
                    $times = [];
                    if ($att->check_in) {
                        $times[] = Carbon::parse($att->check_in)->format('h:i A');
                    }
                    if ($att->check_out) {
                        $times[] = Carbon::parse($att->check_out)->format('h:i A');
                    }

                    $appendActivity = false;
                    $isEarly = false;
                    $isHalfDayPunch = false;

                    if ($att->check_out && $att->employee && $att->employee->time_out) {
                        $checkOut = Carbon::parse($att->check_out);
                        $punchTime = $checkOut->format('H:i');
                        
                        if ($punchTime < '15:00') {
                            $isHalfDayPunch = true;
                        } elseif ($punchTime < '17:30') {
                            $isEarly = true;
                        }
                    }

                    if ($isActivityDay && ($isEarly || $statusRaw === 'early_out' || $statusRaw === 'early_leave' || ($statusRaw === 'half_day' && !$isHalfDayPunch))) {
                        $displayText = "Present";
                        $appendActivity = true;
                    } else {
                        if ($isEarly) {
                            $displayText = "Early Out";
                        } elseif ($isHalfDayPunch || $statusRaw === 'half_day') {
                            $displayText = "Half Day";
                        } else {
                            $displayText = ucfirst(str_replace('_', ' ', $statusRaw));
                            if ($displayText === 'Early out' || $displayText === 'Early leave') {
                                $displayText = 'Early Out';
                            }
                        }
                    }

                    if (!empty($times)) {
                        $displayText .= ' (' . implode(' - ', $times) . ')';
                    }

                    if ($appendActivity) {
                        $displayText .= " Activity";
                    }

                    $present++;
                }
                elseif ($statusRaw === 'absent' || $statusRaw === 'leave') {
                    $absent++;
                } else {
                    $others++;
                }
                $row[] = $displayText;
            } else {
                if ($date->isSunday()) {
                    $row[] = 'Sunday';
                } elseif (isset($this->holidayMap[$dateKey])) {
                    $row[] = 'Holiday';
                    $present++;
                } else {
                    $row[] = '-';
                }
            }
        }

        $row[] = $present;
        $row[] = $absent;
        $row[] = $others;

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
