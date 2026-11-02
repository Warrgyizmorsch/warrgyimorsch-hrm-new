<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * One row per employee per day for the Attendance History screen and its export.
 * Statuses come from AttendanceHistoryService, the same pipeline payroll uses.
 */
class AttendanceLogService
{
    /** Filter value => label and the display status keys it covers. */
    public const STATUS_GROUPS = [
        'present' => ['label' => 'Present', 'keys' => ['present', 'present_activity', 'late', 'overtime', 'activity']],
        'half_day' => ['label' => 'Half Day', 'keys' => ['half_day', 'half_day_leave']],
        'leave' => ['label' => 'Leave', 'keys' => ['leave', 'unpaid_leave', 'unauthorised']],
        'absent' => ['label' => 'Absent', 'keys' => ['absent']],
        'wfh' => ['label' => 'WFH', 'keys' => ['wfh']],
        'early_out' => ['label' => 'Early Out', 'keys' => ['early_out', 'early_leave']],
        'missing_punch' => ['label' => 'Missing Punch', 'keys' => ['missing_punch']],
        'off' => ['label' => 'Sunday / Holiday', 'keys' => ['sunday', 'holiday']],
    ];

    private const OVERTIME_HOURS = 9.5;

    public function __construct(private AttendanceHistoryService $history)
    {
    }

    /**
     * Employees this user may see: team leaders only their own department (or just
     * themselves with no department linked). Past employees appear while they have
     * visible attendance in the range.
     */
    public function employeesFor(User $user, bool $isTeamLeader, Carbon $start, Carbon $end): Collection
    {
        $withAttendance = Attendance::query()->visible()
            ->whereDate('attendance_date', '>=', $start->toDateString())
            ->whereDate('attendance_date', '<=', $end->toDateString())
            ->distinct()
            ->pluck('employee_id');

        $ids = Employee::active()->pluck('id')->merge($withAttendance)->unique();

        return Employee::with(['departmentRef', 'user'])
            ->whereIn('id', $ids)
            ->when($isTeamLeader, function ($query) use ($user) {
                $department = $user->employee->department_id ?? null;

                $department
                    ? $query->where('department_id', $department)
                    : $query->where('id', $user->employee_id ?? 0);
            })
            ->orderBy('name')
            ->get();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function dateRange(Request $request): array
    {
        [$start, $end] = $this->history->resolveDateRange($request, $request->integer('employee_id') ?: null);

        return [$start->copy()->startOfDay(), $end->copy()->startOfDay()->min(Carbon::today())];
    }

    /**
     * @return array{rows: Collection, counts: array<string, int>, overtime: int, payable: float}
     */
    public function build(Request $request, Collection $employees, Carbon $start, Carbon $end): array
    {
        $employees = $employees
            ->when($request->filled('employee_id'), fn ($c) => $c->where('id', (int) $request->employee_id))
            ->when($request->filled('department_id'), fn ($c) => $c->where('department_id', (int) $request->department_id));

        $rows = collect();

        if ($start->lte($end)) {
            foreach ($employees as $employee) {
                $rows = $rows->concat($this->employeeRows($employee, $start, $end));
            }
        }

        $term = strtolower(trim((string) $request->search));
        if ($term !== '') {
            $rows = $rows->filter(fn (array $row) => str_contains(
                strtolower($row['employee_name'] . ' ' . $row['employee_code'] . ' ' . $row['status'] . ' ' . $row['date_label'] . ' ' . $row['date_key']),
                $term
            ));
        }

        $counts = array_fill_keys(array_keys(self::STATUS_GROUPS), 0);
        foreach ($rows as $row) {
            $counts[$row['group']]++;
        }
        $overtime = $rows->where('is_overtime', true)->count();
        $payable = round($rows->sum('payable'), 2);

        // Sundays and holidays only add noise across many people, so they stay out of
        // the list unless asked for or one person's full calendar is being read.
        $status = (string) $request->status;
        $rows = match (true) {
            $status === 'overtime' => $rows->where('is_overtime', true),
            isset(self::STATUS_GROUPS[$status]) => $rows->where('group', $status),
            $request->filled('employee_id') => $rows,
            default => $rows->where('group', '!=', 'off'),
        };

        return [
            'rows' => $rows->sortBy([['date_key', 'desc'], ['employee_name', 'asc']])->values(),
            'counts' => $counts,
            'overtime' => $overtime,
            'payable' => $payable,
        ];
    }

    private function employeeRows(Employee $employee, Carbon $start, Carbon $end): array
    {
        $lastWorkingDay = $employee->user?->account_status === 'inactive'
            ? $employee->user->last_working_day?->toDateString()
            : null;
        $firstDay = $employee->date_of_joining
            ? Carbon::parse($employee->date_of_joining)->toDateString()
            : (string) Attendance::where('employee_id', $employee->id)->min('attendance_date');

        $rows = [];

        foreach ($this->history->buildMonthlyHistory($employee->id, $start, $end) as $day) {
            $date = $day['date_key'];

            if ($lastWorkingDay && $date > $lastWorkingDay) {
                continue;
            }

            // Not employed yet: a calendar gap, not an absence.
            if (!$day['attendance_id'] && $firstDay && $date < $firstDay) {
                continue;
            }

            $carbon = Carbon::parse($date);
            $worked = (float) $day['total_hours_decimal'];

            $rows[] = $day + [
                'date_label' => $carbon->format('d M Y'),
                'day_label' => $carbon->format('D'),
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_code' => (string) $employee->employee_code,
                'department' => $employee->departmentRef->name ?? '',
                'designation' => (string) $employee->designation,
                'group' => $this->groupForKey((string) $day['status_key']),
                'is_overtime' => $worked > self::OVERTIME_HOURS,
                'activity_credit' => $carbon->isSaturday() && $worked > 0,
                'payable' => $this->history->resolvePayableFractionForHistoryRow($day),
            ];
        }

        return $rows;
    }

    private function groupForKey(string $key): string
    {
        foreach (self::STATUS_GROUPS as $group => $definition) {
            if (in_array($key, $definition['keys'], true)) {
                return $group;
            }
        }

        return 'absent';
    }
}
