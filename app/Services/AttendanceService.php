<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    /** Minimum gap (minutes) between punch clusters treated as in/out sessions. */
    private const MIN_SESSION_GAP_MINUTES = 90;

    /** Night shift earliest expected start (3:30 PM). */
    private const NIGHT_SHIFT_START = '15:00:00';

    /** Night shift latest expected end (4:30 AM next day). */
    private const NIGHT_SHIFT_END = '04:30:00';

    /** Max plausible drift (minutes) between a punch and the employee's scheduled time. */
    private const MAX_SCHEDULE_DEVIATION_MINUTES = 150;

    /** Punches arriving within this many seconds of each other are treated as a device/sync echo, not a real session. */
    private const NOISE_CLUSTER_SECONDS = 120;

    /**
     * Resolve employee by biometric / import code.
     * Prefers active accounts when duplicate codes exist in the database.
     */
    public static function findByEmployeeCode(string $employeeCode): ?Employee
    {
        $employeeCode = trim($employeeCode);

        if ($employeeCode === '') {
            return null;
        }

        $matches = Employee::with('user')
            ->where('employee_code', $employeeCode)
            ->orderByDesc('id')
            ->get();

        if ($matches->isEmpty()) {
            return null;
        }

        if ($matches->count() > 1) {
            Log::warning('Duplicate employee_code detected during attendance sync', [
                'employee_code' => $employeeCode,
                'employee_ids' => $matches->pluck('id')->all(),
            ]);
        }

        $activeMatches = $matches->filter(
            fn (Employee $employee) => $employee->user && $employee->user->account_status === 'active'
        );

        if ($activeMatches->isNotEmpty()) {
            return $activeMatches->sortByDesc('id')->first();
        }

        return $matches->first();
    }

    public static function isNightShiftEmployee(Employee $employee): bool
    {
        $timeIn = substr($employee->time_in ?? '09:30:00', 0, 8);
        $timeOut = substr($employee->time_out ?? '18:00:00', 0, 8);

        // A shift is "night shift" only if it actually crosses midnight (check-out time
        // is numerically earlier than check-in). A late-starting shift that still ends
        // the same day (e.g. 3 PM-11:30 PM) is not a night shift — it must not be routed
        // through the midnight punch-pairing logic, which would never find its same-day
        // check-out in the post-midnight window and mark it as a missing punch.
        return $timeOut < $timeIn;
    }

    /**
     * Map a raw punch timestamp to the attendance calendar date (supports night shifts).
     */
    public static function resolveAttendanceDateForPunch(Carbon $punch, Employee $employee): string
    {
        if (self::isNightShiftEmployee($employee)) {
            return self::resolveNightShiftAttendanceDate($punch);
        }

        $shiftStart = substr($employee->time_in ?? '09:30:00', 0, 8);
        $punchTime = $punch->format('H:i:s');

        if ($punchTime < $shiftStart && $punchTime <= '05:00:00') {
            return $punch->copy()->subDay()->format('Y-m-d');
        }

        return $punch->format('Y-m-d');
    }

    public static function resolveNightShiftAttendanceDate(Carbon $punch): string
    {
        $punchMinutes = self::timeToMinutes($punch->format('H:i:s'));

        if ($punchMinutes <= self::timeToMinutes(self::NIGHT_SHIFT_END)) {
            return $punch->copy()->subDay()->format('Y-m-d');
        }

        return $punch->format('Y-m-d');
    }

    /**
     * Ignore stray gate/device punches between end of night shift and next shift start.
     */
    public static function isValidNightShiftPunch(Carbon $punch): bool
    {
        $minutes = self::timeToMinutes($punch->format('H:i:s'));

        if ($minutes >= self::timeToMinutes(self::NIGHT_SHIFT_START)) {
            return true;
        }

        if ($minutes <= self::timeToMinutes(self::NIGHT_SHIFT_END)) {
            return true;
        }

        return false;
    }

    /**
     * Import/sync punch rows into attendance records.
     *
     * @param  array<int, array{employee_code?: string, timestamp?: mixed}>  $records
     */
    public function processPunchRecords(array $records, bool $fillAbsences = true): int
    {
        $groupedByEmployee = [];

        foreach ($records as $row) {
            $employeeCode = trim((string) ($row['employee_code'] ?? ''));
            $dateTimeRaw = $row['timestamp'] ?? null;

            if ($employeeCode === '' || blank($dateTimeRaw)) {
                continue;
            }

            try {
                $dateTime = Carbon::parse($dateTimeRaw);
            } catch (\Exception $e) {
                Log::error('Attendance punch datetime parse failed', ['value' => $dateTimeRaw]);
                continue;
            }

            $groupedByEmployee[$employeeCode][] = $dateTime;
        }

        $processedGroups = 0;
        $allDates = [];

        foreach ($groupedByEmployee as $employeeCode => $punches) {
            $employee = self::findByEmployeeCode($employeeCode);

            if (!$employee) {
                Log::warning('Employee not found for attendance punch', [
                    'employee_code' => $employeeCode,
                ]);
                continue;
            }

            $dates = $this->processEmployeePunchSet($employee, $punches);
            $processedGroups += count($dates);
            $allDates = array_merge($allDates, $dates);
        }

        if ($fillAbsences) {
            $this->fillMissingAttendanceForDates(array_unique($allDates));
        }

        return $processedGroups;
    }

    /**
     * @param  Carbon[]  $punches
     * @return string[] attendance dates processed
     */
    private function processEmployeePunchSet(Employee $employee, array $punches): array
    {
        $dates = [];
        $data = [];

        $punches = self::filterNoisePunches($punches);

        foreach ($punches as $dateTime) {
            if ($this->isAfterLastWorkingDay($employee, $dateTime)) {
                continue;
            }

            if (self::isNightShiftEmployee($employee) && !self::isValidNightShiftPunch($dateTime)) {
                continue;
            }

            $attendanceDate = self::resolveAttendanceDateForPunch($dateTime, $employee);
            $key = $employee->id . '_' . $attendanceDate;

            $data[$key]['employee_id'] = $employee->id;
            $data[$key]['employee'] = $employee;
            $data[$key]['attendance_date'] = $attendanceDate;
            $data[$key]['punches'][] = $dateTime;
        }

        foreach ($data as $entry) {
            $resolved = self::resolveForEmployee($entry['punches'], $entry['employee'], $entry['attendance_date']);

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

            $dates[] = $entry['attendance_date'];
        }

        return $dates;
    }

    /**
     * Rebuild attendance from stored biometric device logs.
     */
    public function rebuildFromDeviceLogs(?string $employeeCode = null): int
    {
        $query = DB::table('attendance_logs')->orderBy('timestamp');

        if ($employeeCode !== null && $employeeCode !== '') {
            $query->where('user_id', $employeeCode);
        }

        $grouped = $query->get()->groupBy('user_id');
        $processedGroups = 0;
        $allDates = [];

        foreach ($grouped as $userId => $logs) {
            $employee = self::findByEmployeeCode((string) $userId);

            if (!$employee) {
                continue;
            }

            $punches = $logs
                ->map(fn ($log) => Carbon::parse($log->timestamp))
                ->all();

            $this->clearAttendanceForEmployeeRebuild($employee, $punches);

            $dates = $this->processEmployeePunchSet($employee, $punches);
            $processedGroups += count($dates);
            $allDates = array_merge($allDates, $dates);
        }

        $this->fillMissingAttendanceForDates(array_unique($allDates));

        return $processedGroups;
    }

    /**
     * @deprecated Use processPunchRecords()
     */
    public function processPunches($records)
    {
        $this->processPunchRecords($records);
    }

    /**
     * Remove existing attendance rows before a log rebuild so stale midday punches do not remain.
     *
     * @param  Carbon[]  $punches
     */
    private function clearAttendanceForEmployeeRebuild(Employee $employee, array $punches): void
    {
        if ($punches === []) {
            return;
        }

        $calendarDates = array_map(
            fn (Carbon $punch) => $punch->format('Y-m-d'),
            $punches
        );

        $minDate = min($calendarDates);
        $maxDate = max($calendarDates);

        Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$minDate, $maxDate])
            ->delete();
    }

    /**
     * Drop punches that arrive within NOISE_CLUSTER_SECONDS of another punch.
     *
     * The biometric sync has been observed writing near-simultaneous duplicate punches
     * (a few seconds apart) for unrelated employees at the same wall-clock moment — a
     * device/sync artifact, not a real clock-in/out. Left unfiltered these get treated
     * as a valid punch pair with ~0 worked hours, fabricating bogus "Absent" rows (or,
     * for night shift, misidentified as the real check-in/out).
     *
     * @param  Carbon[]  $punches
     * @return Carbon[]
     */
    private static function filterNoisePunches(array $punches): array
    {
        $punches = array_values($punches);
        usort($punches, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        $count = count($punches);
        $keep = array_fill(0, $count, true);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $gapSeconds = $punches[$i]->diffInSeconds($punches[$j]);

                if ($gapSeconds > self::NOISE_CLUSTER_SECONDS) {
                    break;
                }

                $keep[$i] = false;
                $keep[$j] = false;
            }
        }

        $filtered = [];
        foreach ($punches as $index => $punch) {
            if ($keep[$index]) {
                $filtered[] = $punch;
            }
        }

        return $filtered;
    }

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

    private function fillMissingAttendanceForDates(array $allDates): void
    {
        if ($allDates === []) {
            return;
        }

        $employees = Employee::active()->get();

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
                    $leaveStatus = strtolower(trim($leaveApplication->status ?? ''));
                    $leaveType = strtolower(trim($leaveApplication->leave_type ?? ''));
                    $leaveCategory = strtolower(trim($leaveApplication->leave_category ?? ''));

                    if ($leaveStatus === 'unpaid') {
                        $status = 'unpaid_leave';
                    } elseif ($leaveStatus === 'unauthorised') {
                        $status = 'unauthorised';
                    } elseif ($leaveType === 'wfh' || $leaveCategory === 'wfh') {
                        $status = 'wfh';
                        $totalHours = 8;
                    } elseif (
                        $leaveCategory === 'gatepass'
                        || $leaveCategory === 'early leave'
                        || $leaveType === 'gatepass leave'
                        || $leaveType === 'early leave'
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
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'check_in' => null,
                    'check_out' => null,
                    'total_hours' => $totalHours,
                    'status' => $status,
                ]);
            }
        }
    }

    public static function resolveForEmployee(array $punches, Employee $employee, ?string $attendanceDate = null): array
    {
        if (self::isNightShiftEmployee($employee)) {
            return self::resolveNightShiftCheckInOut($punches, $employee, $attendanceDate);
        }

        return self::resolveCheckInOutFromPunches(
            $punches,
            $employee->time_in,
            $employee->time_out
        );
    }

    /**
     * Resolve the employee's scheduled shift start/end for a given attendance date,
     * honoring a Sunday-specific override (some night-shift staff work a different,
     * usually earlier/shorter shift on their weekly-off day).
     *
     * @return array{0: string, 1: string} [time_in, time_out]
     */
    private static function scheduledShiftForDate(Employee $employee, ?string $attendanceDate): array
    {
        $isSunday = $attendanceDate !== null && Carbon::parse($attendanceDate)->isSunday();

        if ($isSunday && $employee->sunday_time_in && $employee->sunday_time_out) {
            return [$employee->sunday_time_in, $employee->sunday_time_out];
        }

        return [
            $employee->time_in ?? self::NIGHT_SHIFT_START,
            $employee->time_out ?? self::NIGHT_SHIFT_END,
        ];
    }

    /**
     * Night shift: check-in from evening punches (3 PM+), check-out from early morning (up to 4:30 AM).
     *
     * @param  Carbon[]  $punches
     */
    public static function resolveNightShiftCheckInOut(array $punches, Employee $employee, ?string $attendanceDate = null): array
    {
        $punches = array_values(array_filter($punches));
        usort($punches, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        if ($punches === []) {
            return self::punchResult(null, null, 0, 'absent');
        }

        $shiftStartMinutes = self::timeToMinutes(self::NIGHT_SHIFT_START);
        $shiftEndMinutes = self::timeToMinutes(self::NIGHT_SHIFT_END);

        $eveningPunches = array_values(array_filter(
            $punches,
            fn (Carbon $punch) => self::timeToMinutes($punch->format('H:i:s')) >= $shiftStartMinutes
        ));

        $morningPunches = array_values(array_filter(
            $punches,
            fn (Carbon $punch) => self::timeToMinutes($punch->format('H:i:s')) <= $shiftEndMinutes
        ));

        [$scheduledTimeIn, $scheduledTimeOut] = self::scheduledShiftForDate($employee, $attendanceDate);
        $scheduledStartMinutes = self::timeToMinutes(substr($scheduledTimeIn, 0, 8));
        $scheduledEndMinutes = self::timeToMinutes(substr($scheduledTimeOut, 0, 8));

        // Pick the punch closest to the scheduled time rather than the earliest/latest one.
        // A stray scan hours before shift start (device glitch, accidental read) must not be
        // mistaken for the real check-in just because it happens to be first chronologically.
        // If the nearest candidate is still implausibly far from the scheduled time (and no
        // closer one exists), discard it rather than fabricating an oversized shift — the day
        // falls back to "missing punch" for manual review instead.
        $checkInPunch = self::nearestPunchWithinTolerance($eveningPunches, $scheduledStartMinutes);
        $checkOutPunch = self::nearestPunchWithinTolerance($morningPunches, $scheduledEndMinutes);

        if (!$checkInPunch && !$checkOutPunch) {
            return self::punchResult(null, null, 0, 'absent');
        }

        if (!$checkInPunch || !$checkOutPunch) {
            $single = $checkInPunch ?? $checkOutPunch;
            $isMorningPunch = self::timeToMinutes($single->format('H:i:s')) <= $shiftEndMinutes;

            return $isMorningPunch
                ? self::punchResult(null, $single->format('H:i:s'), 0, 'missing_punch')
                : self::punchResult($single->format('H:i:s'), null, 0, 'missing_punch');
        }

        if ($checkInPunch->equalTo($checkOutPunch)) {
            return self::punchResult($checkInPunch->format('H:i:s'), null, 0, 'missing_punch');
        }

        $hours = self::calculateWorkedHours($checkInPunch, $checkOutPunch);

        return self::punchResult(
            $checkInPunch->format('H:i:s'),
            $checkOutPunch->format('H:i:s'),
            $hours,
            self::resolveStatusFromHours($hours, true)
        );
    }

    /**
     * Nearest punch to the target time, discarded if even the closest one drifts
     * further than plausible (a lone punch hours off schedule is more likely a
     * stray/duplicate scan than a genuine irregular check-in or check-out).
     *
     * @param  Carbon[]  $punches
     */
    private static function nearestPunchWithinTolerance(array $punches, int $targetMinutes): ?Carbon
    {
        $nearest = self::nearestPunchToMinutes($punches, $targetMinutes);

        if (!$nearest) {
            return null;
        }

        $distance = abs(self::timeToMinutes($nearest->format('H:i:s')) - $targetMinutes);

        return $distance <= self::MAX_SCHEDULE_DEVIATION_MINUTES ? $nearest : null;
    }

    /**
     * @param  Carbon[]  $punches
     */
    private static function nearestPunchToMinutes(array $punches, int $targetMinutes): ?Carbon
    {
        if ($punches === []) {
            return null;
        }

        $nearest = $punches[0];
        $nearestDistance = abs(self::timeToMinutes($nearest->format('H:i:s')) - $targetMinutes);

        foreach ($punches as $punch) {
            $distance = abs(self::timeToMinutes($punch->format('H:i:s')) - $targetMinutes);

            if ($distance < $nearestDistance) {
                $nearest = $punch;
                $nearestDistance = $distance;
            }
        }

        return $nearest;
    }

    /**
     * @param  Carbon[]  $punches
     */
    public static function resolveCheckInOutFromPunches(
        array $punches,
        ?string $timeIn = null,
        ?string $timeOut = null
    ): array {
        unset($timeIn, $timeOut);

        $punches = array_values(array_filter($punches));
        usort($punches, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        if ($punches === []) {
            return self::punchResult(null, null, 0, 'absent');
        }

        if (count($punches) === 1) {
            return self::punchResult($punches[0]->format('H:i:s'), null, 0, 'missing_punch');
        }

        if (count($punches) === 2) {
            $checkInPunch = $punches[0];
            $checkOutPunch = $punches[1];
        } else {
            [$beforeGap, $afterGap] = self::splitPunchesByLargestGap($punches);

            if ($beforeGap === [] || $afterGap === []) {
                $checkInPunch = $punches[0];
                $checkOutPunch = $punches[array_key_last($punches)];
            } else {
                $checkInPunch = $beforeGap[array_key_last($beforeGap)];
                $checkOutPunch = $afterGap[array_key_last($afterGap)];
            }
        }

        if (!$checkInPunch || !$checkOutPunch || $checkInPunch->equalTo($checkOutPunch)) {
            return self::punchResult(
                $checkInPunch?->format('H:i:s'),
                null,
                0,
                'missing_punch'
            );
        }

        $hours = self::calculateWorkedHours($checkInPunch, $checkOutPunch);

        return self::punchResult(
            $checkInPunch->format('H:i:s'),
            $checkOutPunch->format('H:i:s'),
            $hours,
            self::resolveStatusFromHours($hours, false)
        );
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

    private static function calculateWorkedHours(Carbon $checkInPunch, Carbon $checkOutPunch): float
    {
        if ($checkInPunch->equalTo($checkOutPunch)) {
            return 0;
        }

        $checkOut = $checkOutPunch->copy();

        if ($checkOut->lte($checkInPunch)) {
            $checkOut->addDay();
        }

        $minutes = $checkInPunch->diffInMinutes($checkOut);

        if ($minutes <= 0 || $minutes > 16 * 60) {
            return 0;
        }

        return round($minutes / 60, 2);
    }

    private static function resolveStatusFromHours(float $hours, bool $isNightShift): string
    {
        $fullDayHours = $isNightShift ? 8.0 : AttendanceStatusService::FULL_DAY_HOURS;

        if ($hours >= $fullDayHours) {
            return 'present';
        }

        if ($hours >= AttendanceStatusService::HALF_DAY_MIN_HOURS) {
            return 'half_day';
        }

        return $hours > 0 ? 'absent' : 'absent';
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
