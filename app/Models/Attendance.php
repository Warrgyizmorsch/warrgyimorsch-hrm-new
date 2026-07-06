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
        'check_in' => 'datetime:H:i:s',
        'check_out' => 'datetime:H:i:s',
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

    public static function extractTimeValue($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $raw = $value instanceof Carbon
            ? $value->format('H:i:s')
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
                $punchTime = substr(self::extractTimeValue($att->check_out) ?? '', 0, 5);

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
     * Resolve display status — mirrors payroll/employeeWise renderTable() logic.
     */
    public function resolveHistoryStatus(Employee $employee, bool $isHoliday = false, bool $isActivityDay = false): array
    {
        $status = strtolower($this->status ?? '');
        $checkOutHm = substr(self::extractTimeValue($this->check_out) ?? '', 0, 5);

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
            return ['label' => 'Holiday', 'class' => 'secondary', 'is_activity' => false];
        }

        if ($isActivityDay && ($isEarly || in_array($status, ['early_out', 'early_leave'], true) || ($status === 'half_day' && !$isHalfDayPunch))) {
            return ['label' => 'Present Activity', 'class' => 'info', 'is_activity' => true];
        }

        if ($isEarly) {
            return ['label' => 'Early Out', 'class' => 'info', 'is_activity' => false];
        }

        if ($isHalfDayPunch || $status === 'half_day') {
            return ['label' => 'Half Day', 'class' => 'warning', 'is_activity' => false];
        }

        if ($status === 'wfh') {
            return ['label' => 'Wfh', 'class' => 'info', 'is_activity' => false];
        }

        return [
            'label' => $this->displayStatusLabel($isHoliday),
            'class' => $this->displayStatusClass($isHoliday),
            'is_activity' => false,
        ];
    }

    /**
     * Format a TIME/datetime punch value for display (matches payroll employee-wise view).
     */
    public static function formatPunchTime($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $raw = self::extractTimeValue($value);
        if (!$raw || !preg_match('/^(\d{1,2}):(\d{2})/', $raw, $matches)) {
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

    public function displayStatusClass(?bool $isHoliday = false): string
    {
        $label = strtolower($this->displayStatusLabel($isHoliday));

        return match (true) {
            str_contains($label, 'present') => 'success',
            str_contains($label, 'half') => 'warning',
            str_contains($label, 'missing') => 'warning',
            str_contains($label, 'early') => 'info',
            str_contains($label, 'late') => 'info',
            str_contains($label, 'leave'), str_contains($label, 'wfh') => 'info',
            str_contains($label, 'holiday'), str_contains($label, 'sunday') => 'secondary',
            str_contains($label, 'absent'), str_contains($label, 'unauthorised') => 'danger',
            default => 'secondary',
        };
    }
}
