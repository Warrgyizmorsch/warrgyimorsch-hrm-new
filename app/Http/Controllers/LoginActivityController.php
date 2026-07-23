<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LoginActivity;
use Illuminate\Http\Request;

class LoginActivityController extends Controller
{
    /**
     * Roles that see every employee's login activity.
     */
    private const FULL_ACCESS_ROLES = ['super_admin', 'manager', 'hr_executive'];

    public function index(Request $request)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isFullAccess = in_array($role, self::FULL_ACCESS_ROLES, true);
        $isTeamLeader = $role === 'team_leader';

        $query = LoginActivity::with(['employee', 'user'])
            ->orderByDesc('login_at');

        if ($isFullAccess) {
            $employees = Employee::orderBy('name')->get(['id', 'name']);
            $canFilterByEmployee = true;
        } elseif ($isTeamLeader) {
            $department = $user->employee->department ?? null;

            $teamEmployees = $department
                ? Employee::where('department', $department)
                    ->whereRaw("LOWER(REPLACE(role, ' ', '_')) = 'employee'")
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect();

            // Team leaders see their own login activity plus their team's — but not other
            // leaders/managers/HR who happen to share the department.
            $employees = $user->employee
                ? $teamEmployees->push($user->employee)->unique('id')->sortBy('name')->values()
                : $teamEmployees;

            $query->where(function ($q) use ($department, $user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('employee', function ($eq) use ($department) {
                        $eq->where('department', $department)
                            ->whereRaw("LOWER(REPLACE(role, ' ', '_')) = 'employee'");
                    });
            });

            $canFilterByEmployee = true;
        } else {
            $employees = collect();
            $canFilterByEmployee = false;

            $query->where('user_id', $user->id);
        }

        if ($canFilterByEmployee && $request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('login_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('login_at', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('name', 'like', "%{$search}%");
                })->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $activities = $query->paginate($perPage)->withQueryString();

        return view('login-activity.index', compact('activities', 'employees', 'canFilterByEmployee'));
    }

    public function heartbeat(Request $request)
    {
        $activityId = $request->session()->get('login_activity_id');

        if ($activityId) {
            $activity = LoginActivity::find($activityId);

            if ($activity && $activity->is_active) {
                $activity->touchHeartbeat();
            }
        }

        return response()->json(['ok' => true]);
    }
}
