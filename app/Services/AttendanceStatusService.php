<?php

namespace App\Services;

use App\Models\Attendance;

class AttendanceStatusService
{
    public const FULL_DAY_HOURS = 8.5;

    public const HALF_DAY_MIN_HOURS = 4.0;

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
        $hours = (float) ($record->total_hours ?? 0);
        $checkOutHm = substr($record->getRawPunchTime('check_out') ?? '', 0, 5);
        $hasPunches = $record->getRawPunchTime('check_in') && $record->getRawPunchTime('check_out');

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

        if ($isActivityDay && ($hasPunches || $hours > 0) && $hours >= self::FULL_DAY_HOURS) {
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
            if ($hours >= self::FULL_DAY_HOURS) {
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

        $hours = (float) ($record->total_hours ?? 0);
        $hasBothPunches = $record->getRawPunchTime('check_in') && $record->getRawPunchTime('check_out');

        if ($hasBothPunches || $hours > 0) {
            if ($hours >= self::FULL_DAY_HOURS) {
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
