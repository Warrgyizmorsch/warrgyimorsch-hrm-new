<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;

/**
 * One place for "can this user see/touch this person's data?" checks, so controllers stop
 * hand-rolling slightly different role lists. Use it for every endpoint that takes a record id
 * or an employee_id from the URL / request — never trust that id on its own.
 */
class AccessControl
{
    /*
     * Authority levels:  Employee  ->  Team Leader  ->  Manager / HR  ->  Super Admin
     *   - employee, hr_intern, hr_marketing  : own data only
     *   - team_leader, business_operation_head : own data + the departments they lead
     *   - hr_executive (HR manager)           : all HR data (payroll, leave, attendance...)
     *   - manager, super_admin                : everything
     */

    /** Roles that can see every employee's HR data (same list the routes use as $adminRoles). */
    public const ADMIN_ROLES = ['super_admin', 'manager', 'hr_executive'];

    /** Roles that act as team leaders: scoped to their own / led departments. */
    public const TEAM_LEADER_ROLES = ['team_leader', 'business_operation_head'];

    public static function role(?User $user = null): string
    {
        $user ??= auth()->user();

        return str_replace(' ', '_', strtolower(trim((string) ($user->role ?? 'employee'))));
    }

    public static function isAdmin(?User $user = null): bool
    {
        return in_array(self::role($user), self::ADMIN_ROLES, true);
    }

    public static function isTeamLeader(?User $user = null): bool
    {
        return in_array(self::role($user), self::TEAM_LEADER_ROLES, true);
    }

    /** Departments a team leader is responsible for (own department + any extra led ones). */
    public static function ledDepartmentIds(?User $user = null): array
    {
        $user ??= auth()->user();
        $employee = $user?->employee;

        return $employee ? $employee->ledDepartmentIds() : [];
    }

    /**
     * Admins: anyone. Team leaders: themselves + employees in departments they lead.
     * Everyone else: only themselves.
     */
    public static function canViewEmployee($employeeOrId, ?User $user = null): bool
    {
        $user ??= auth()->user();
        if (!$user) {
            return false;
        }

        $employee = $employeeOrId instanceof Employee
            ? $employeeOrId
            : ($employeeOrId ? Employee::withTrashed()->find($employeeOrId) : null);

        if (!$employee) {
            return false;
        }

        if (self::isAdmin($user)) {
            return true;
        }

        if ($user->employee_id && (int) $user->employee_id === (int) $employee->id) {
            return true;
        }

        if (self::isTeamLeader($user)) {
            return $employee->department_id
                && in_array((int) $employee->department_id, array_map('intval', self::ledDepartmentIds($user)), true);
        }

        return false;
    }
}
