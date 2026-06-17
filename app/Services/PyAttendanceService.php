<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use Carbon\Carbon;

class PyAttendanceService
{
    public function processPunches($records)
    {
        $data = [];
        $allDates = [];

        foreach ($records as $row) {

            $employeeCode = trim($row['employee_code'] ?? '');
            $dateTimeRaw  = $row['timestamp'] ?? null;

            if (!$employeeCode || !$dateTimeRaw) {
                continue;
            }

            $employee = Employee::where('employee_code', $employeeCode)->first();

            if (!$employee) {
                \Log::warning('Employee not found', [
                    'employee_code' => $employeeCode
                ]);
                continue;
            }

            try {
                $dateTime = Carbon::parse($dateTimeRaw);
            } catch (\Exception $e) {
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
            $data[$key]['employee'] = $employee;
            $data[$key]['attendance_date'] = $attendanceDate;
            $data[$key]['punches'][] = $dateTime;
        }

        foreach ($data as $entry) {

            $employeeId = $entry['employee_id'];
            $employee = $entry['employee'];
            $punches = $entry['punches'];

            usort($punches, function ($a, $b) {
                return $a->timestamp <=> $b->timestamp;
            });

            $first = $punches[0];
            $last  = count($punches) > 1 ? $punches[count($punches) - 1] : null;
            $shiftMinutes = $this->getShiftMinutes($employee);

            if (!$last) {
                $workedMinutes = 0;
                $status = 'missing_punch';
            } else {
                $workedMinutes = $first->diffInMinutes($last);

                if ($workedMinutes >= $shiftMinutes) {
                    $status = 'present';
                } elseif ($workedMinutes >= 4 * 60) {
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
                    'total_hours' => round($workedMinutes / 60, 2),
                    'status'      => $status,
                ]
            );
        }

        $allDates = array_unique($allDates);
        $employees = Employee::all();

        foreach ($employees as $employee) {
            foreach ($allDates as $date) {

                $carbonDate = Carbon::parse($date);

                if ($carbonDate->isSunday()) {
                    continue;
                }

                $alreadyExists = Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $date)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

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
                    $leaveType     = strtolower(trim($leaveApplication->leave_type ?? ''));
                    $leaveCategory = strtolower(trim($leaveApplication->leave_category ?? ''));

                    if ($leaveStatus === 'unpaid') {
                        $status = 'unpaid_leave';
                    } elseif ($leaveStatus === 'unauthorised') {
                        $status = 'unauthorised';
                    } elseif ($leaveType === 'wfh' || $leaveCategory === 'wfh') {
                        $status = 'wfh';
                        $totalHours = 8;
                    } elseif (
                        $leaveCategory === 'gatepass' ||
                        $leaveCategory === 'early leave' ||
                        $leaveType === 'gatepass leave' ||
                        $leaveType === 'early leave'
                    ) {
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

    private function getShiftMinutes(Employee $employee): int
    {
        $timeIn = $employee->time_in ?? '09:30:00';
        $timeOut = $employee->time_out ?? '18:00:00';

        try {
            $shiftStart = Carbon::parse($timeIn);
            $shiftEnd = Carbon::parse($timeOut);

            if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
                $shiftEnd->addDay();
            }

            return max($shiftStart->diffInMinutes($shiftEnd), 1);
        } catch (\Exception $e) {
            return 8 * 60 + 30;
        }
    }
}
