<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Two-level leave approval.
 *
 *   Level 1 — the applicant's Team Leader (a user with role team_leader who leads the
 *             applicant's department, directly or via additional led departments).
 *   Level 2 — Manager / HR (the existing admin roles that already approve leave).
 *
 * Only plain "employee" role applicants go through Level 1 (the same people a TL can see
 * today). Anyone else — or an employee whose department has no TL — goes straight to Level 2.
 * A TL rejection closes the application; a manager can still override it later.
 */
class LeaveApprovalService
{
    public const MANAGER_ROLES = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head'];

    public static function role(?string $role): string
    {
        return str_replace(' ', '_', strtolower(trim($role ?? '')));
    }

    public function isManager(?User $user): bool
    {
        return $user && in_array(self::role($user->role), self::MANAGER_ROLES, true);
    }

    public function isTeamLeader(?User $user): bool
    {
        return $user && self::role($user->role) === 'team_leader' && $user->employee_id;
    }

    /** Active team-leader users responsible for this employee (never the employee themself). */
    public function teamLeadersFor(Employee $employee): Collection
    {
        $departmentId = $employee->department_id;
        if (!$departmentId) {
            return collect();
        }

        return User::query()
            ->whereRaw("LOWER(REPLACE(role, ' ', '_')) = 'team_leader'")
            ->where('account_status', 'active')
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', $employee->id)
            ->whereHas('employee', function ($q) use ($departmentId) {
                $q->where(function ($w) use ($departmentId) {
                    $w->where('department_id', $departmentId)
                        ->orWhereHas('ledDepartmentRefs', fn ($d) => $d->where('departments.id', $departmentId));
                });
            })
            ->get();
    }

    public function requiresTeamLeader(Employee $employee): bool
    {
        return self::role($employee->role) === 'employee'
            && $this->teamLeadersFor($employee)->isNotEmpty();
    }

    /** Approval columns for a brand-new application. */
    public function initialState(Employee $employee): array
    {
        return $this->requiresTeamLeader($employee)
            ? ['approval_stage' => 'tl', 'tl_status' => 'pending']
            : ['approval_stage' => 'manager', 'tl_status' => 'skipped'];
    }

    /** Department IDs whose plain employees this TL handles. */
    public function ledDepartmentIds(User $user): array
    {
        $employee = $user->employee;

        return $employee ? $employee->ledDepartmentIds() : [];
    }

    /** Restrict an Employee query to the people this TL is Level 1 for. */
    public function scopeEmployeesForTeamLeader(Builder $query, User $user): Builder
    {
        $departmentIds = $this->ledDepartmentIds($user);

        return $query->whereIn('department_id', $departmentIds ?: [0])
            ->where('id', '!=', $user->employee_id)
            ->whereRaw("LOWER(REPLACE(role, ' ', '_')) = 'employee'");
    }

    public function isTeamLeaderOf(User $user, ?Employee $employee): bool
    {
        if (!$employee || !$this->isTeamLeader($user) || (int) $employee->id === (int) $user->employee_id) {
            return false;
        }

        return self::role($employee->role) === 'employee'
            && in_array((int) $employee->department_id, array_map('intval', $this->ledDepartmentIds($user)), true);
    }

    public function canTeamLeaderAct(User $user, LeaveApplication $leave): bool
    {
        return strtolower($leave->status) === 'pending'
            && $leave->approval_stage === 'tl'
            && $leave->tl_status === 'pending'
            && $this->isTeamLeaderOf($user, $leave->employee);
    }

    /** Applications waiting for this user's decision right now. */
    public function awaitingQuery(User $user): ?Builder
    {
        if ($this->isManager($user)) {
            return LeaveApplication::query()
                ->where('status', 'pending')
                ->where('approval_stage', 'manager');
        }

        if ($this->isTeamLeader($user)) {
            return LeaveApplication::query()
                ->where('status', 'pending')
                ->where('approval_stage', 'tl')
                ->where('tl_status', 'pending')
                ->whereHas('employee', fn ($q) => $this->scopeEmployeesForTeamLeader($q, $user));
        }

        return null;
    }

    /**
     * Level 1 decision. Returns the refreshed application.
     *
     * @param  'approved'|'rejected'  $decision
     */
    public function teamLeaderDecide(LeaveApplication $leave, User $user, string $decision, string $remarks): LeaveApplication
    {
        return DB::transaction(function () use ($leave, $user, $decision, $remarks) {
            $locked = LeaveApplication::with('employee')->lockForUpdate()->findOrFail($leave->id);

            if (!$this->canTeamLeaderAct($user, $locked)) {
                abort(409, 'This application is no longer waiting for Team Leader approval.');
            }

            $update = [
                'tl_status' => $decision,
                'tl_action_by' => $user->id,
                'tl_action_at' => now(),
                'tl_remarks' => $remarks,
            ];

            if ($decision === 'approved') {
                $update['approval_stage'] = 'manager';
            } else {
                $update['approval_stage'] = 'done';
                $update['status'] = 'rejected';
            }

            $locked->update($update);

            LeaveApplicationLog::record(
                $locked,
                $decision === 'approved' ? 'tl_approved' : 'tl_rejected',
                'tl',
                'pending',
                $decision === 'approved' ? 'pending' : 'rejected',
                $remarks,
                $user
            );

            return $locked;
        });
    }

    /** Approval columns to store alongside a Level 2 (manager) status change. */
    public function managerUpdate(LeaveApplication $leave, User $user, string $newStatus, string $remarks): array
    {
        $update = [
            'manager_action_by' => $user->id,
            'manager_action_at' => now(),
            'manager_remarks' => $remarks,
        ];

        $tlStillPending = $leave->tl_status === 'pending';

        if ($newStatus === 'pending') {
            $update['approval_stage'] = $tlStillPending ? 'tl' : 'manager';
        } elseif ($newStatus === 'on_hold') {
            $update['approval_stage'] = 'manager';
            if ($tlStillPending) {
                $update['tl_status'] = 'bypassed';
            }
        } else {
            $update['approval_stage'] = 'done';
            if ($tlStillPending) {
                $update['tl_status'] = 'bypassed';
            }
        }

        return $update;
    }

    /** Approval trail for the details drawer. */
    public function trail(LeaveApplication $leave): array
    {
        $leave->loadMissing(['tlActionUser:id,name', 'managerActionUser:id,name', 'employee']);

        $tlNames = $leave->employee && $leave->tl_status === 'pending'
            ? $this->teamLeadersFor($leave->employee)->pluck('name')->implode(', ')
            : null;

        return [
            'timeline' => $this->timeline($leave),
            'approval_stage' => $leave->approval_stage,
            'stage_label' => $leave->stage_label,
            'tl' => [
                'status' => $leave->tl_status,
                'by' => $leave->tlActionUser?->name,
                'at' => $leave->tl_action_at?->format('d M Y, h:i A'),
                'remarks' => $leave->tl_remarks,
                'waiting_on' => $tlNames,
            ],
            'manager' => [
                'status' => $leave->manager_action_by ? $leave->status : null,
                'by' => $leave->managerActionUser?->name,
                'at' => $leave->manager_action_at?->format('d M Y, h:i A'),
                'remarks' => $leave->manager_remarks,
            ],
        ];
    }

    private const STATUS_TEXT = [
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'on_hold' => 'Put on hold',
        'unpaid' => 'Marked unpaid',
        'unauthorised' => 'Marked unauthorised',
        'pending' => 'Moved back to pending',
    ];

    /**
     * Every step with exact timestamp and the time taken since the previous step, plus a
     * final "waiting" entry while the application is still open. Older applications (before
     * the log existed) get their steps rebuilt from the stored columns.
     *
     * @return array<int, array{title:string, by:?string, at:string, at_iso:string, gap:?string, remarks:?string, state:string, level:string, pending?:bool}>
     */
    public function timeline(LeaveApplication $leave): array
    {
        $leave->loadMissing(['logs', 'employee', 'tlActionUser:id,name', 'managerActionUser:id,name']);
        $employeeName = $leave->employee?->name ?? 'Employee';
        $logs = $leave->logs;

        $events = [];
        $hasApplied = $logs->contains('action', 'applied');

        if (!$hasApplied && $leave->created_at) {
            $events[] = ['action' => 'applied', 'level' => 'employee', 'at' => $leave->created_at, 'actor' => $employeeName, 'to' => 'pending', 'from' => null, 'remarks' => null];
        }

        foreach ($logs as $log) {
            $events[] = [
                'action' => $log->action,
                'level' => $log->level,
                'at' => $log->created_at,
                'actor' => $log->actor_name,
                'from' => $log->from_status,
                'to' => $log->to_status,
                'remarks' => $log->remarks,
            ];
        }

        // Older applications: rebuild TL / manager steps from the columns when not logged.
        if (!$logs->contains(fn ($l) => str_starts_with($l->action, 'tl_')) && $leave->tl_action_at && in_array($leave->tl_status, ['approved', 'rejected'], true)) {
            $events[] = ['action' => 'tl_' . $leave->tl_status, 'level' => 'tl', 'at' => $leave->tl_action_at, 'actor' => $leave->tlActionUser?->name, 'from' => 'pending', 'to' => $leave->tl_status === 'approved' ? 'pending' : 'rejected', 'remarks' => $leave->tl_remarks];
        }
        if (!$logs->contains('action', 'manager_update') && $leave->manager_action_at) {
            $events[] = ['action' => 'manager_update', 'level' => 'manager', 'at' => $leave->manager_action_at, 'actor' => $leave->managerActionUser?->name, 'from' => 'pending', 'to' => strtolower($leave->status), 'remarks' => $leave->manager_remarks];
        }

        usort($events, fn ($a, $b) => $a['at'] <=> $b['at']);

        $out = [];
        $previous = null;
        foreach ($events as $e) {
            [$title, $state] = $this->describe($e, $employeeName);
            $at = Carbon::parse($e['at']);
            $out[] = [
                'title' => $title,
                'by' => $e['actor'],
                'level' => $e['level'] ?? '',
                'at' => $at->format('d M Y, h:i A'),
                'at_iso' => $at->toIso8601String(),
                'gap' => $previous ? self::duration($previous, $at) . ' later' : null,
                'remarks' => $e['remarks'],
                'state' => $state,
            ];
            $previous = $at;
        }

        if (strtolower((string) $leave->status) === 'pending' && $previous) {
            $waitingFor = $leave->approval_stage === 'tl' ? 'Team Leader' : 'Manager';
            $out[] = [
                'title' => "Waiting for {$waitingFor}",
                'by' => $leave->approval_stage === 'tl' && $leave->employee
                    ? ($this->teamLeadersFor($leave->employee)->pluck('name')->implode(', ') ?: null)
                    : null,
                'level' => $leave->approval_stage === 'tl' ? 'tl' : 'manager',
                'at' => 'Pending for ' . self::duration($previous, now()),
                'at_iso' => now()->toIso8601String(),
                'gap' => null,
                'remarks' => null,
                'state' => 'pending',
                'pending' => true,
            ];
        }

        return $out;
    }

    private function describe(array $e, string $employeeName): array
    {
        switch ($e['action']) {
            case 'applied':
                $onBehalf = $e['actor'] && strcasecmp($e['actor'], $employeeName) !== 0;
                return [$onBehalf ? "Applied on behalf of {$employeeName}" : 'Leave applied', 'applied'];
            case 'tl_approved':
                return ['Approved by Team Leader (Level 1)', 'approved'];
            case 'tl_rejected':
                return ['Rejected by Team Leader (Level 1)', 'rejected'];
            default:
                $to = strtolower((string) $e['to']);
                $text = self::STATUS_TEXT[$to] ?? ucfirst(str_replace('_', ' ', $to));
                $from = strtolower((string) $e['from']);
                $changed = $from && $from !== 'pending' && $from !== $to
                    ? ' (was ' . str_replace('_', ' ', $from) . ')'
                    : '';
                return ["{$text} by Manager (Level 2){$changed}", $to ?: 'pending'];
        }
    }

    /** "2d 3h", "4h 15m", "12m", "less than a minute". */
    public static function duration(Carbon $from, Carbon $to): string
    {
        $minutes = (int) floor(abs($to->getTimestamp() - $from->getTimestamp()) / 60);
        if ($minutes < 1) {
            return 'less than a minute';
        }
        $d = intdiv($minutes, 1440);
        $h = intdiv($minutes % 1440, 60);
        $m = $minutes % 60;

        if ($d > 0) {
            return $d . 'd' . ($h ? " {$h}h" : '');
        }
        if ($h > 0) {
            return $h . 'h' . ($m ? " {$m}m" : '');
        }

        return $m . 'm';
    }
}
