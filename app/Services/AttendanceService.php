<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceService
{
    /** Minimum gap (minutes) between punch clusters treated as in/out sessions. */
    private const MIN_SESSION_GAP_MINUTES = 90;

    public function processPunches($records)
    {
        $data = [];

        foreach ($records as $row) {
            $employeeCode = $row['employee_code'];
            $dateTime = Carbon::parse($row['timestamp']);

            $employee = Employee::with('user')->where('employee_code', $employeeCode)->first();

            if (!$employee) {
                continue;
            }

            if ($this->isAfterLastWorkingDay($employee, $dateTime)) {
                continue;
            }

            $key = $employee->id . '_' . $dateTime->format('Y-m-d');

            $data[$key]['employee_id'] = $employee->id;
            $data[$key]['employee'] = $employee;
            $data[$key]['attendance_date'] = $dateTime->format('Y-m-d');
            $data[$key]['punches'][] = $dateTime;
        }

        foreach ($data as $entry) {
            $resolved = self::resolveForEmployee($entry['punches'], $entry['employee']);

            Attendance::updateOrCreate(
                [
                    'employee_id' => $entry['employee_id'],
                    'attendance_date' => $entry['attendance_date'],
                ],
                [
                    'check_in' => $resolved['check_in'],
                    'check_out' => $resolved['check_out'],
                    'total_hours' => $resolved['total_hours'],
                    'status' => $resolved['status'],
                ]
            );
        }
    }

    /**
     * Guard against stale punches for employees who have already left.
     */
    private function isAfterLastWorkingDay(Employee $employee, Carbon $punchDate): bool
    {
        $user = $employee->user;

        if (!$user || $user->account_status !== 'inactive') {
            return false;
        }

        if (!$user->last_working_day) {
            return true;
        }

        return $punchDate->toDateString() > $user->last_working_day->toDateString();
    }

    /**
     * Derive check-in/out from punch list — last punch in morning, last punch in evening.
     */
    public static function resolveForEmployee(array $punches, Employee $employee): array
    {
        return self::resolveCheckInOutFromPunches(
            $punches,
            $employee->time_in,
            $employee->time_out
        );
    }

    /**
     * @param  Carbon[]  $punches
     */
    public static function resolveCheckInOutFromPunches(
        array $punches,
        ?string $timeIn = null,
        ?string $timeOut = null
    ): array {
        $punches = array_values(array_filter($punches));
        usort($punches, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        if ($punches === []) {
            return self::punchResult(null, null, 0, 'absent');
        }

        if (count($punches) === 1) {
            return self::punchResult($punches[0]->format('H:i:s'), null, 0, 'missing_punch');
        }

        $splitMinutes = self::shiftSplitMinutes(
            substr($timeIn ?? '09:30:00', 0, 8),
            substr($timeOut ?? '18:00:00', 0, 8)
        );

        [$morning, $evening] = self::splitPunchesByShift($punches, $splitMinutes);

        if ($morning === [] || $evening === []) {
            [$morning, $evening] = self::splitPunchesByLargestGap($punches);
        }

        $checkInPunch = $morning !== [] ? $morning[array_key_last($morning)] : null;
        $checkOutPunch = $evening !== [] ? $evening[array_key_last($evening)] : null;

        if ($checkInPunch && !$checkOutPunch) {
            return self::punchResult($checkInPunch->format('H:i:s'), null, 0, 'missing_punch');
        }

        if (!$checkInPunch && $checkOutPunch) {
            return self::punchResult(null, $checkOutPunch->format('H:i:s'), 0, 'missing_punch');
        }

        if (!$checkInPunch || !$checkOutPunch) {
            $midpoint = (int) floor(count($punches) / 2);
            $morning = array_slice($punches, 0, max(1, $midpoint));
            $evening = array_slice($punches, max(1, $midpoint));
            $checkInPunch = $morning[array_key_last($morning)];
            $checkOutPunch = $evening[array_key_last($evening)];
        }

        $hours = round($checkInPunch->diffInMinutes($checkOutPunch) / 60, 2);

        return self::punchResult(
            $checkInPunch->format('H:i:s'),
            $checkOutPunch->format('H:i:s'),
            $hours,
            AttendanceStatusService::resolveStatusFromHours($hours)
        );
    }

    /**
     * @param  Carbon[]  $punches
     * @return array{0: Carbon[], 1: Carbon[]}
     */
    private static function splitPunchesByShift(array $punches, int $splitMinutes): array
    {
        $morning = [];
        $evening = [];

        foreach ($punches as $punch) {
            if (self::timeToMinutes($punch->format('H:i:s')) < $splitMinutes) {
                $morning[] = $punch;
            } else {
                $evening[] = $punch;
            }
        }

        return [$morning, $evening];
    }

    /**
     * @param  Carbon[]  $punches
     * @return array{0: Carbon[], 1: Carbon[]}
     */
    private static function splitPunchesByLargestGap(array $punches): array
    {
        $maxGap = 0;
        $splitAt = null;

        for ($i = 0; $i < count($punches) - 1; $i++) {
            $gap = $punches[$i]->diffInMinutes($punches[$i + 1]);
            if ($gap > $maxGap) {
                $maxGap = $gap;
                $splitAt = $i;
            }
        }

        if ($splitAt === null || $maxGap < self::MIN_SESSION_GAP_MINUTES) {
            return [[], []];
        }

        return [
            array_slice($punches, 0, $splitAt + 1),
            array_slice($punches, $splitAt + 1),
        ];
    }

    private static function shiftSplitMinutes(string $timeIn, string $timeOut): int
    {
        $inMinutes = self::timeToMinutes($timeIn);
        $outMinutes = self::timeToMinutes($timeOut);

        if ($outMinutes <= $inMinutes) {
            $outMinutes += 24 * 60;
        }

        return (int) floor(($inMinutes + $outMinutes) / 2);
    }

    private static function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }

    private static function punchResult(?string $checkIn, ?string $checkOut, float $hours, string $status): array
    {
        return [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_hours' => $hours,
            'status' => $status,
        ];
    }
}
