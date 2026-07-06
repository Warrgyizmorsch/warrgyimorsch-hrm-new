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
                if (!in_array($status, ['present', 'early_out', 'early_leave'], true)) {
                    continue;
                }

                if (!$att->check_out || !$att->employee?->time_out) {
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
     * Resolve display status — exact mirror of payroll employeeWise renderTable().
     */
    public function resolvePayrollDisplayStatus(bool $isHoliday = false, bool $isActivityDay = false): array
    {
        $status = strtolower($this->status ?? '');
        $checkOutHm = substr($this->getRawPunchTime('check_out') ?? '', 0, 5);

        $isEarly = false;
        $isHalfDayPunch = false;

        if ($checkOutHm) {
            if ($checkOutHm < '15:00') {
                $isHalfDayPunch = true;
            } elseif ($checkOutHm < '17:30') {
                $isEarly = true;
            }
        }

        if ($isHoliday && $status === 'absent') {
            return ['label' => 'Holiday', 'class' => self::classForStatusLabel('Holiday'), 'is_activity' => false];
        }

        if ($isActivityDay && ($isEarly || in_array($status, ['early_out', 'early_leave'], true) || ($status === 'half_day' && !$isHalfDayPunch))) {
            return ['label' => 'Present Activity', 'class' => self::classForStatusLabel('Present Activity'), 'is_activity' => true];
        }

        if ($isEarly) {
            return ['label' => 'Early Out', 'class' => self::classForStatusLabel('Early Out'), 'is_activity' => false];
        }

        if ($isHalfDayPunch || $status === 'half_day') {
            return ['label' => 'Half Day', 'class' => self::classForStatusLabel('Half Day'), 'is_activity' => false];
        }

        $label = match ($status) {
            'wfh' => 'Wfh',
            'early_out', 'early_leave' => 'Early Out',
            'missing_punch' => 'Missing Punch',
            'unpaid_leave' => 'Unpaid Leave',
            'half_day' => 'Half Day',
            default => ucfirst(str_replace('_', ' ', $status ?: 'Absent')),
        };

        if ($label === 'Early out' || $label === 'Early leave') {
            $label = 'Early Out';
        }

        return [
            'label' => $label,
            'class' => self::classForStatusLabel($label),
            'is_activity' => false,
        ];
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
