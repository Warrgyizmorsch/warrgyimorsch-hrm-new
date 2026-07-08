<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in',
        'check_out',
        'status',
        'total_hours',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'total_hours' => 'float',
    ];

    // Optional: default status
    protected $attributes = [
        'status' => 'present',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Hide attendance dated after an inactive employee's last working day.
     * Employees marked inactive without a recorded last working day are hidden entirely
     * (last_working_day is always set by EmployeeController::updateAccountStatus).
     */
    public function scopeVisible($query)
    {
        $cutoffs = User::where('account_status', 'inactive')
            ->whereNotNull('employee_id')
            ->pluck('last_working_day', 'employee_id');

        if ($cutoffs->isEmpty()) {
            return $query;
        }

        return $query->where(function ($q) use ($cutoffs) {
            $q->whereNotIn('employee_id', $cutoffs->keys())
                ->orWhere(function ($orQ) use ($cutoffs) {
                    foreach ($cutoffs as $employeeId => $lastWorkingDay) {
                        $orQ->orWhere(function ($inner) use ($employeeId, $lastWorkingDay) {
                            $inner->where('employee_id', $employeeId);
                            if ($lastWorkingDay) {
                                $inner->whereDate('attendance_date', '<=', $lastWorkingDay);
                            } else {
                                $inner->whereRaw('1 = 0');
                            }
                        });
                    }
                });
        });
    }

    /**
     * Read punch time directly from DB (TIME column) — avoids timezone shifts from Carbon casts.
     */
    public function getRawPunchTime(string $field): ?string
    {
        if (!in_array($field, ['check_in', 'check_out'], true)) {
            return null;
        }

        $raw = $this->attributes[$field] ?? $this->getRawOriginal($field);
        if (blank($raw)) {
            return null;
        }

        $raw = (string) $raw;
        if (str_contains($raw, ' ')) {
            $raw = explode(' ', $raw)[1];
        }

        return substr($raw, 0, 8);
    }

    public function formattedCheckIn(): ?string
    {
        return self::formatPunchTime($this->getRawPunchTime('check_in'));
    }

    public function formattedCheckOut(): ?string
    {
        return self::formatPunchTime($this->getRawPunchTime('check_out'));
    }

    public static function extractTimeValue($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $raw = $value instanceof Carbon
            ? $value->copy()->utc()->format('H:i:s')
            : (string) $value;

        if (str_contains($raw, ' ')) {
            $raw = explode(' ', $raw)[1];
        }

        return substr($raw, 0, 8);
    }

    /**
     * Activity-day detection — same rule as payroll employee-wise details modal.
     */
    public static function computeActivityDays(array $dateStrings): array
    {
        if (empty($dateStrings)) {
            return [];
        }

        $activityDays = [];
        $allDailyAtts = self::with('employee')
            ->whereIn('attendance_date', $dateStrings)
            ->get()
            ->groupBy(fn ($att) => Carbon::parse($att->attendance_date)->format('Y-m-d'));

        foreach ($allDailyAtts as $date => $dailyAtts) {
            $earlyOuts = 0;
            $totalPresent = 0;

            foreach ($dailyAtts as $att) {
                $status = strtolower($att->status ?? '');
                if (\App\Services\AttendanceStatusService::isLeaveDerivedStatus($status)) {
                    continue;
                }

                $hours = (float) ($att->total_hours ?? 0);
                if ($hours < \App\Services\AttendanceStatusService::FULL_DAY_HOURS) {
                    continue;
                }

                if (!$att->check_out) {
                    continue;
                }

                $totalPresent++;
                $punchTime = substr($att->getRawPunchTime('check_out') ?? '', 0, 5);

                if ($punchTime >= '15:00' && $punchTime < '17:30') {
                    $earlyOuts++;
                }
            }

            if ($totalPresent > 2 && ($earlyOuts / $totalPresent) >= 0.7) {
                $activityDays[$date] = true;
            }
        }

        return $activityDays;
    }

    /**
     * Resolve display status using hours-first rules (8.5h full day, 4h min half day).
     */
    public function resolvePayrollDisplayStatus(
        bool $isHoliday = false,
        bool $isActivityDay = false,
        bool $isSunday = false
    ): array {
        return \App\Services\AttendanceStatusService::resolveDisplayStatus(
            $this,
            $isHoliday,
            $isActivityDay,
            $isSunday
        );
    }

    /** @deprecated Use resolvePayrollDisplayStatus() */
    public function resolveHistoryStatus(Employee $employee, bool $isHoliday = false, bool $isActivityDay = false): array
    {
        return $this->resolvePayrollDisplayStatus($isHoliday, $isActivityDay);
    }

    public static function formatPunchTime(?string $rawTime): ?string
    {
        if (blank($rawTime)) {
            return null;
        }

        $raw = (string) $rawTime;
        if (str_contains($raw, ' ')) {
            $raw = explode(' ', $raw)[1];
        }

        $raw = substr($raw, 0, 8);
        if (!preg_match('/^(\d{1,2}):(\d{2})/', $raw, $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = $matches[2];
        $ampm = $hours >= 12 ? 'PM' : 'AM';
        $hours12 = $hours % 12 ?: 12;

        return sprintf('%d:%s %s', $hours12, $minutes, $ampm);
    }

    public static function formatTotalHours(?float $decimalHours): string
    {
        if ($decimalHours === null || $decimalHours <= 0) {
            return '--';
        }

        $hours = (int) floor($decimalHours);
        $minutes = (int) round(($decimalHours - $hours) * 60);

        if ($minutes === 60) {
            $hours++;
            $minutes = 0;
        }

        return sprintf('%dh %02dm', $hours, $minutes);
    }

    public function displayStatusLabel(?bool $isHoliday = false): string
    {
        $status = strtolower($this->status ?? '');

        if ($isHoliday && $status === 'absent') {
            return 'Holiday';
        }

        return match ($status) {
            'present' => 'Present',
            'half_day' => 'Half Day',
            'absent' => 'Absent',
            'missing_punch' => 'Missing Punch',
            'late' => 'Late',
            'leave' => 'Leave',
            'wfh' => 'WFH',
            'early_out', 'early_leave' => 'Early Out',
            'unpaid_leave' => 'Unpaid Leave',
            'unauthorised' => 'Unauthorised',
            default => ucfirst(str_replace('_', ' ', $status ?: 'Absent')),
        };
    }

    public static function classForStatusLabel(string $label): string
    {
        $normalized = strtolower($label);

        return match (true) {
            str_contains($normalized, 'activity') => 'info',
            str_contains($normalized, 'half') => 'warning',
            str_contains($normalized, 'missing') => 'warning',
            str_contains($normalized, 'early') => 'info',
            str_contains($normalized, 'leave'), str_contains($normalized, 'wfh') => 'info',
            str_contains($normalized, 'holiday'), str_contains($normalized, 'sunday') => 'secondary',
            str_contains($normalized, 'absent'), str_contains($normalized, 'unauthorised') => 'danger',
            str_contains($normalized, 'present') => 'success',
            default => 'secondary',
        };
    }

    public function displayStatusClass(?bool $isHoliday = false): string
    {
        return self::classForStatusLabel($this->displayStatusLabel($isHoliday));
    }
}
