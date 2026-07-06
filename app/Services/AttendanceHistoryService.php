<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use Carbon\Carbon;

class AttendanceHistoryService
{
    /**
     * Build attendance rows using the same source + rules as payroll employee-wise details.
     */
    public function buildMonthlyHistory(int $employeeId, Carbon $startDate, Carbon $endDate): array
    {
        $records = Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', '>=', $startDate->toDateString())
            ->whereDate('attendance_date', '<=', $endDate->toDateString())
            ->orderBy('attendance_date', 'desc')
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        $dateStrings = $records
            ->map(fn (Attendance $record) => $record->attendance_date->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        $holidayMap = Holiday::query()
            ->whereIn('date', $dateStrings)
            ->get()
            ->keyBy(fn ($holiday) => Carbon::parse($holiday->date)->format('Y-m-d'));

        $activityDays = Attendance::computeActivityDays($dateStrings);

        return $records->map(function (Attendance $record) use ($holidayMap, $activityDays) {
            $dayStr = $record->attendance_date->format('Y-m-d');
            $isHoliday = $holidayMap->has($dayStr);
            $isActivityDay = (bool) ($activityDays[$dayStr] ?? false);

            $resolved = $record->resolvePayrollDisplayStatus($isHoliday, $isActivityDay);

            return [
                'date' => $record->attendance_date->format('d M, Y (D)'),
                'date_key' => $dayStr,
                'status' => $resolved['label'],
                'statusClass' => $resolved['class'],
                'punch_in' => $record->formattedCheckIn() ?? '--:--',
                'punch_out' => $record->formattedCheckOut() ?? '--:--',
                'total_hours' => Attendance::formatTotalHours($record->total_hours),
                'is_activity' => $resolved['is_activity'] ?? false,
            ];
        })->values()->all();
    }
}
