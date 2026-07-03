<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceService
{
    public function processPunches($records)
    {
        $data = [];

        foreach ($records as $row) {

            $employeeCode = $row['employee_code'];
            $dateTime     = Carbon::parse($row['timestamp']);

            $employee = Employee::where('employee_code', $employeeCode)->first();

            if (!$employee) continue;

            $key = $employee->id . '_' . $dateTime->format('Y-m-d');

            $data[$key]['employee_id'] = $employee->id;
            $data[$key]['punches'][]   = $dateTime;
        }

        foreach ($data as $entry) {

            $employeeId = $entry['employee_id'];
            $punches    = $entry['punches'];

            sort($punches);

            $count = count($punches);

            if ($count == 1) {
                $first = $punches[0];
                $last  = null;
                $hours = 0;
                $status = 'missing_punch';

            } elseif ($count > 1) {

                $first = $punches[0];
                $last  = end($punches);

                $hours = $first->diffInMinutes($last) / 60;

                if ($hours >= 8.5) {
                    $status = 'present';
                } elseif ($hours >= 4) {
                    $status = 'half_day';
                } else {
                    $status = 'absent';
                }

            } else {
                continue;
            }

            Attendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'attendance_date' => $first->format('Y-m-d'),
                ],
                [
                    'check_in'    => $first->format('H:i:s'),
                    'check_out'   => $last ? $last->format('H:i:s') : null,
                    'total_hours' => round($hours, 2),
                    'status'      => $status,
                ]
            );
        }
    }
}
