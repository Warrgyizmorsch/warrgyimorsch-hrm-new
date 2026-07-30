<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceStatusService
{
    /** Grace window (minutes) within which a late arrival can be forgiven — see lateArrivalMinutes(). */
    public const LATE_ARRIVAL_GRACE_MINUTES = 15;

    /** Forgiven late arrivals allowed per calendar month before the grace credit stops applying. */
    public const LATE_ARRIVAL_GRACE_LIMIT_PER_MONTH = 2;

    public const FULL_DAY_HOURS = 8.5;

    public const NIGHT_SHIFT_FULL_DAY_HOURS = 8.0;

    public const HALF_DAY_MIN_HOURS = 4.0;

    /** Recurring Saturday office activity — credited toward Present/Half-day thresholds and overtime. */
    public const SATURDAY_CREDIT_HOURS = 1.0;

    public static function saturdayCreditHours(Attendance $record): float
    {
        return $record->attendance_date && $record->attendance_date->isSaturday()
            ? self::SATURDAY_CREDIT_HOURS
            : 0.0;
    }

    public static function isNightShiftRecord(Attendance $record): bool
    {
        $checkIn = substr($record->getRawPunchTime('check_in') ?? '', 0, 5);

        return $checkIn !== '' && $checkIn >= '15:00';
    }

    public static function fullDayHoursForRecord(Attendance $record): float
    {
        return self::isNightShiftRecord($record)
            ? self::NIGHT_SHIFT_FULL_DAY_HOURS
            : self::FULL_DAY_HOURS;
    }

    /**
     * The employee's scheduled shift start for the day this record covers (Sunday override aware).
     */
    public static function resolveShiftStart(Attendance $record, Employee $employee): Carbon
    {
        $date = $record->attendance_date instanceof Carbon
            ? $record->attendance_date->toDateString()
            : Carbon::parse($record->attendance_date)->toDateString();

        $isSunday = Carbon::parse($date)->isSunday();
        $timeIn = ($isSunday && $employee->sunday_time_in)
            ? $employee->sunday_time_in
            : ($employee->time_in ?? '09:30:00');

        try {
            return Carbon::parse($date . ' ' . Carbon::parse($timeIn)->format('H:i:s'));
        } catch (\Exception $e) {
            return Carbon::parse($date . ' 09:30:00');
        }
    }

    /**
     * Minutes the check-in punch falls after the employee's scheduled shift start (0 if on time or early).
     */
    public static function lateArrivalMinutes(Attendance $record, Employee $employee): int
    {
        $checkIn = $record->getRawPunchTime('check_in');
        if (!$checkIn) {
            return 0;
        }

        $date = $record->attendance_date instanceof Carbon
            ? $record->attendance_date->toDateString()
            : Carbon::parse($record->attendance_date)->toDateString();

        $shiftStart = self::resolveShiftStart($record, $employee);
        $checkInTime = Carbon::parse($date . ' ' . Carbon::parse($checkIn)->format('H:i:s'));

        return max(intdiv($checkInTime->timestamp - $shiftStart->timestamp, 60), 0);
    }

    public static function isLeaveDerivedStatus(string $status): bool
    {
        return in_array(strtolower($status), [
            'leave',
            'wfh',
            'unpaid_leave',
            'half_day_leave',
            'unauthorised',
            'early_leave',
        ], true);
    }

    public static function resolveStatusFromHours(?float $hours): string
    {
        $hours = (float) ($hours ?? 0);

        if ($hours >= self::FULL_DAY_HOURS) {
            return 'present';
        }

        if ($hours >= self::HALF_DAY_MIN_HOURS) {
            return 'half_day';
        }

        return $hours > 0 ? 'absent' : 'absent';
    }

    /**
     * Derive stored DB status from punches/hours while preserving leave-derived records.
     */
    public static function resolveStoredStatus(string $currentStatus, ?float $hours, bool $hasBothPunches): string
    {
        $currentStatus = strtolower(trim($currentStatus));

        if (self::isLeaveDerivedStatus($currentStatus)) {
            return $currentStatus;
        }

        if (!$hasBothPunches) {
            return $currentStatus === 'missing_punch' ? 'missing_punch' : ($currentStatus ?: 'absent');
        }

        return self::resolveStatusFromHours($hours);
    }

    public static function resolveDisplayStatus(
        Attendance $record,
        bool $isHoliday = false,
        bool $isActivityDay = false,
        bool $isSunday = false
    ): array {
        $status = strtolower($record->status ?? '');
        $hours = (float) ($record->total_hours ?? 0) + self::saturdayCreditHours($record);
        $checkOutHm = substr($record->getRawPunchTime('check_out') ?? '', 0, 5);
        $hasPunches = $record->getRawPunchTime('check_in') && $record->getRawPunchTime('check_out');
        $fullDayHours = self::fullDayHoursForRecord($record);

        if ($isHoliday && $status === 'absent') {
            return self::result('Holiday', 'holiday', false);
        }

        if ($isSunday && ($status === 'absent' || (!$hasPunches && $hours <= 0))) {
            return self::result('Sunday', 'sunday', false);
        }

        if ($status === 'activity') {
            return self::result('Present Activity', 'present_activity', true);
        }

        if (self::isLeaveDerivedStatus($status)) {
            return self::result(self::labelForStatus($status), $status, false);
        }

        // An admin's manual correction (both punches hand-set — see PayrollController::updateByName)
        // is the source of truth for its status. Without this, the hours-first rules below would
        // silently recompute a different label right after a manual save, making the correction
        // look like it never took effect even though it was written to the database correctly.
        if ($record->is_manual) {
            return self::result(self::labelForStatus($status), $status, false);
        }

        if ($isActivityDay && ($hasPunches || $hours > 0) && $hours >= $fullDayHours) {
            $isHalfDayPunch = $checkOutHm && $checkOutHm < '15:00';
            $isEarly = $checkOutHm && $checkOutHm >= '15:00' && $checkOutHm < '17:30';

            if (
                ($isEarly || in_array($status, ['early_out', 'early_leave'], true))
                && !($status === 'half_day' && $isHalfDayPunch)
            ) {
                return self::result('Present Activity', 'present_activity', true);
            }
        }

        if ($status === 'missing_punch' && !$hasPunches) {
            return self::result('Missing Punch', 'missing_punch', false);
        }

        if ($hasPunches || ($hours > 0 && !self::isLeaveDerivedStatus($status))) {
            if ($hours >= $fullDayHours) {
                $isEarly = $checkOutHm && $checkOutHm >= '15:00' && $checkOutHm < '17:30';

                if ($isActivityDay && ($isEarly || in_array($status, ['early_out', 'early_leave'], true))) {
                    return self::result('Present Activity', 'present_activity', true);
                }

                if ($isEarly || in_array($status, ['early_out', 'early_leave'], true)) {
                    return self::result('Early Out', 'early_out', false);
                }

                return self::result('Present', 'present', false);
            }

            if ($hours >= self::HALF_DAY_MIN_HOURS) {
                return self::result('Half Day', 'half_day', false);
            }

            if ($hours > 0) {
                return self::result('Absent', 'absent', false);
            }
        }

        if ($status === 'missing_punch') {
            return self::result('Missing Punch', 'missing_punch', false);
        }

        if ($status === 'half_day' && $hours < self::HALF_DAY_MIN_HOURS) {
            return self::result('Absent', 'absent', false);
        }

        $key = $status ?: 'absent';

        return self::result(self::labelForStatus($key), $key, false);
    }

    /**
     * Payable day fraction for payroll (8.5h = 1, 4–8.5h = 0.5).
     */
    public static function resolvePayableDayFraction(Attendance $record): float
    {
        $status = strtolower($record->status ?? '');

        if (self::isLeaveDerivedStatus($status)) {
            return match ($status) {
                'wfh', 'early_leave' => 1.0,
                'half_day_leave' => 0.5,
                default => 0.0,
            };
        }

        if ($status === 'missing_punch') {
            return 1.0;
        }

        if (in_array($status, ['present', 'late', 'early_out', 'overtime'], true)) {
            return 1.0;
        }

        $hours = (float) ($record->total_hours ?? 0) + self::saturdayCreditHours($record);
        $hasBothPunches = $record->getRawPunchTime('check_in') && $record->getRawPunchTime('check_out');
        $fullDayHours = self::fullDayHoursForRecord($record);

        if ($hasBothPunches || $hours > 0) {
            if ($hours >= $fullDayHours) {
                return 1.0;
            }

            if ($hours >= self::HALF_DAY_MIN_HOURS) {
                return 0.5;
            }
        }

        if ($status === 'half_day' && $hours >= self::HALF_DAY_MIN_HOURS) {
            return 0.5;
        }

        return 0.0;
    }

    /** Leave-derived statuses excluded from punch-hour rules. */
    public static function leaveDerivedStatusesSql(): string
    {
        return "'leave','wfh','unpaid_leave','half_day_leave','unauthorised','early_leave'";
    }

    public static function punchDerivedFullDaySql(): string
    {
        $full = self::FULL_DAY_HOURS;

        return "attendances.check_in IS NOT NULL
            AND attendances.check_out IS NOT NULL
            AND attendances.status NOT IN (" . self::leaveDerivedStatusesSql() . ")
            AND COALESCE(attendances.total_hours, 0) >= {$full}";
    }

    public static function punchDerivedHalfDaySql(): string
    {
        $full = self::FULL_DAY_HOURS;
        $half = self::HALF_DAY_MIN_HOURS;

        return "attendances.check_in IS NOT NULL
            AND attendances.check_out IS NOT NULL
            AND attendances.status NOT IN (" . self::leaveDerivedStatusesSql() . ")
            AND COALESCE(attendances.total_hours, 0) >= {$half}
            AND COALESCE(attendances.total_hours, 0) < {$full}";
    }

    public static function labelForStatus(string $status): string
    {
        return match (strtolower($status)) {
            'present' => 'Present',
            'half_day' => 'Half Day',
            'half_day_leave' => 'Half Day Leave',
            'absent' => 'Absent',
            'missing_punch' => 'Missing Punch',
            'late' => 'Late',
            'leave' => 'Leave',
            'wfh' => 'WFH',
            'early_out', 'early_leave' => 'Early Out',
            'unpaid_leave' => 'Unpaid Leave',
            'unauthorised' => 'Unauthorised',
            'present_activity' => 'Present Activity',
            'holiday' => 'Holiday',
            'sunday' => 'Sunday',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private static function result(string $label, string $key, bool $isActivity): array
    {
        return [
            'label' => $label,
            'key' => $key,
            'class' => Attendance::classForStatusLabel($label),
            'is_activity' => $isActivity,
        ];
    }
}
