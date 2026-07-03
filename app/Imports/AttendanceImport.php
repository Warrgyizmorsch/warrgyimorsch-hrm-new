<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AttendanceImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $data = [];
        $allDates = [];

        foreach ($rows as $index => $row) {

            if ($index == 0) continue;

            $employeeCode = trim($row[0] ?? '');
            $dateTimeRaw  = $row[1] ?? null;

            if (!$employeeCode || !$dateTimeRaw) continue;

            $employee = Employee::where('employee_code', $employeeCode)->first();

            if (!$employee) {
                \Log::warning('Employee not found', [
                    'employee_code' => $employeeCode
                ]);
                continue;
            }

            try {
                if (is_numeric($dateTimeRaw)) {
                    $dateTime = Carbon::instance(
                        \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateTimeRaw)
                    );
                } else {
                    $dateTime = Carbon::parse($dateTimeRaw);
                }
            } catch (\Exception $e) {
                \Log::error('Date parse failed', ['value' => $dateTimeRaw]);
                continue;
            }

            $shiftStart = $employee->time_in ?? '09:30:00';

            if (
                $dateTime->format('H:i:s') < $shiftStart &&
                $dateTime->format('H:i:s') <= '05:00:00'
            ) {
                $attendanceDate = $dateTime->copy()->subDay()->format('Y-m-d');
            } else {
                $attendanceDate = $dateTime->format('Y-m-d');
            }

            $allDates[] = $attendanceDate;

            $key = $employee->id . '_' . $attendanceDate;

            $data[$key]['employee_id'] = $employee->id;
            $data[$key]['attendance_date'] = $attendanceDate;
            $data[$key]['punches'][] = $dateTime;
        }

        foreach ($data as $entry) {

            $employeeId = $entry['employee_id'];
            $punches = $entry['punches'];

            usort($punches, function ($a, $b) {
                return $a->timestamp <=> $b->timestamp;
            });

            $first = $punches[0];
            $last  = count($punches) > 1 ? $punches[count($punches) - 1] : null;

            if (!$last) {
                $hours = 0;
                $status = 'missing_punch';
            } else {
                $hours = $first->diffInMinutes($last) / 60;

                if ($hours >= 8.5) {
                    $status = 'present';
                } elseif ($hours >= 4) {
                    $status = 'half_day';
                } else {
                    $status = 'absent';
                }
            }

            Attendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'attendance_date' => $entry['attendance_date'],
                ],
                [
                    'check_in'    => $first->format('H:i:s'),
                    'check_out'   => $last ? $last->format('H:i:s') : null,
                    'total_hours' => round($hours, 2),
                    'status'      => $status,
                ]
            );
        }

        // Mark absent / approved leave / WFH for employees who have no punch on imported dates
        $allDates = array_unique($allDates);

        $employees = Employee::all();

        foreach ($employees as $employee) {
            foreach ($allDates as $date) {

                $carbonDate = Carbon::parse($date);

                // Skip Sunday
                if ($carbonDate->isSunday()) {
                    continue;
                }

                $alreadyExists = Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $date)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                // Check approved leave or WFH
                $leaveApplication = LeaveApplication::where('employee_id', $employee->id)
                    ->whereIn('status', ['approved', 'unpaid', 'unauthorised'])
                    ->whereDate('start_date', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->whereDate('end_date', '>=', $date)
                            ->orWhere(function ($q) use ($date) {
                                $q->whereNull('end_date')
                                    ->whereDate('start_date', $date);
                            });
                    })
                    ->first();

                $status = 'absent';
                $totalHours = 0;

                if ($leaveApplication) {
                    $leaveStatus   = strtolower(trim($leaveApplication->status ?? ''));
                    $leaveType = strtolower(trim($leaveApplication->leave_type ?? ''));
                    $leaveCategory = strtolower(trim($leaveApplication->leave_category ?? ''));

                  if ($leaveStatus === 'unpaid') {

                    $status = 'unpaid_leave';

                    } elseif ($leaveStatus === 'unauthorised') {

                        $status = 'unauthorised';

                    } elseif ($leaveType === 'wfh' || $leaveCategory === 'wfh') {

                        // WFH is treated as working day
                        $status = 'wfh';
                        $totalHours = 8;

                    } elseif (
                        $leaveCategory === 'gatepass' ||
                        $leaveCategory === 'early leave' ||
                        $leaveType === 'gatepass leave' ||
                        $leaveType === 'early leave'
                    ) {

                        // Early leave is only for information, not leave deduction
                        $status = 'early_leave';
                        $totalHours = 1;

                    } elseif ($leaveCategory === 'half day' || (float) $leaveApplication->total_days == 0.5) {

                        $status = 'half_day_leave';
                        $totalHours = 4;

                    } else {

                        $status = 'leave';
                        $totalHours = 0;
                    }
                }

                Attendance::create([
                    'employee_id'     => $employee->id,
                    'attendance_date' => $date,
                    'check_in'        => null,
                    'check_out'       => null,
                    'total_hours'     => $totalHours,
                    'status'          => $status,
                ]);
            }
        }
    }

    private function getAttendanceDateByShift(Carbon $punch, $shiftStart, $shiftEnd)
    {
        $date = $punch->copy()->format('Y-m-d');

        $start = Carbon::parse($date . ' ' . $shiftStart);
        $end   = Carbon::parse($date . ' ' . $shiftEnd);

        // Night shift like 19:00 to 04:00
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();

            // If punch is after midnight but before shift end,
            // attendance date should be previous day
            if ($punch->format('H:i:s') <= $shiftEnd) {
                return $punch->copy()->subDay()->format('Y-m-d');
            }
        }

        return $punch->format('Y-m-d');
    }

    private function getShiftHours($attendanceDate, $shiftStart, $shiftEnd)
    {
        $start = Carbon::parse($attendanceDate . ' ' . $shiftStart);
        $end   = Carbon::parse($attendanceDate . ' ' . $shiftEnd);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end) / 60;
    }
}
