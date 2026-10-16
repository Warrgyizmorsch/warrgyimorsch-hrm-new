<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeProfileRequest;
use App\Models\LeaveApplication;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything the header bell shows, in one place: used for the first page render and by the
 * live feed (/notifications/feed) that refreshes the bell without reloading the page.
 */
class HeaderNotificationService
{
    private const ADMIN_ROLES = ['super_admin', 'manager', 'hr_executive'];

    public function __construct(private LeaveApprovalService $approval)
    {
    }

    /**
     * @return array{notifications: Collection, pendingProfileApprovals: Collection, awaitingLeaves: Collection, roleUpper: string, count: int}
     */
    public function build(User $user): array
    {
        $roleUpper = strtoupper((string) $user->role);
        $role = str_replace(' ', '_', strtolower((string) $user->role));
        $celebrations = $this->celebrations();

        if (in_array($roleUpper, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN'], true)) {
            // Only the newest few are shown, so only fetch the newest few.
            $leaveNotifications = LeaveApplication::with('employee')->latest()->take(5)->get();
            $payrollNotifications = Payroll::with('employee')
                ->whereNotNull('remarks')
                ->where('remarks', '!=', '')
                ->latest('updated_at')
                ->take(5)
                ->get();

            $notifications = $celebrations
                ->concat($leaveNotifications)
                ->concat($payrollNotifications)
                ->sortByDesc(function ($item) {
                    return isset($item->type) && $item->type == 'celebration'
                        ? now()
                        : (isset($item->remarks) ? $item->updated_at : $item->created_at);
                })->take(5)->values();
        } else {
            $myLeaves = LeaveApplication::where('employee_id', $user->employee_id)
                ->whereIn('status', ['approved', 'rejected', 'Approved', 'Rejected'])
                ->where('updated_at', '>=', now()->subDays(3))
                ->latest()
                ->take(5)
                ->get();

            $notifications = $celebrations
                ->concat($myLeaves)
                ->sortByDesc('updated_at')
                ->take(5)
                ->values();
        }

        // Employee self-service profiles waiting for HR (Employees → Profile Approvals).
        $pendingProfileApprovals = in_array($role, self::ADMIN_ROLES, true)
            ? EmployeeProfileRequest::pending()->with('employee')->oldest('submitted_at')->get()
            : collect();

        // Two-level leave approval: requests waiting for this user's decision.
        $awaitingQuery = $this->approval->awaitingQuery($user);
        $awaitingLeaves = $awaitingQuery
            ? $awaitingQuery->with('employee')->oldest('created_at')->take(10)->get()
            : collect();

        return [
            'notifications' => $notifications,
            'pendingProfileApprovals' => $pendingProfileApprovals,
            'awaitingLeaves' => $awaitingLeaves,
            'roleUpper' => $roleUpper,
            'count' => $notifications->count() + $pendingProfileApprovals->count() + $awaitingLeaves->count(),
        ];
    }

    /**
     * Stable key + short title per item, so the browser can tell which ones are new.
     *
     * @return array<int, array{key: string, title: string, body: string, url: string}>
     */
    public function keys(array $bell): array
    {
        $items = [];

        foreach ($bell['awaitingLeaves'] as $leave) {
            $items[] = [
                'key' => "await-{$leave->id}-{$leave->approval_stage}",
                'title' => ($leave->employee->name ?? 'An employee') . ' — leave awaiting your approval',
                'body' => trim(($leave->leave_category ?? '') . ' · ' . optional($leave->start_date)->format('d M')
                    . ($leave->end_date && $leave->end_date->gt($leave->start_date) ? ' – ' . $leave->end_date->format('d M') : '')),
                'url' => route('leave.history', ['leave' => $leave->id]),
            ];
        }
        foreach ($bell['pendingProfileApprovals'] as $req) {
            $items[] = [
                'key' => "profile-{$req->id}",
                'title' => ($req->employee->name ?? 'An employee') . ' submitted profile details for approval',
                'body' => 'Waiting for your approval',
                'url' => route('profile-approvals.show', $req),
            ];
        }
        foreach ($bell['notifications'] as $item) {
            if (isset($item->type) && $item->type === 'celebration') {
                $items[] = ['key' => (string) $item->id, 'title' => $item->message, 'body' => 'Happening today', 'url' => route('employees.employeeDays')];
            } elseif (isset($item->remarks)) {
                $items[] = [
                    'key' => 'payroll-' . $item->id . '-' . optional($item->updated_at)->timestamp,
                    'title' => ($item->employee->name ?? 'Someone') . ' commented on payroll',
                    'body' => \Illuminate\Support\Str::limit((string) $item->remarks, 120),
                    'url' => route('payroll.index'),
                ];
            } else {
                $isAdmin = in_array($bell['roleUpper'], ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN'], true);
                $items[] = [
                    'key' => 'leave-' . $item->id . '-' . strtolower((string) $item->status),
                    'title' => $isAdmin
                        ? (($item->employee->name ?? 'Someone') . ' applied for leave')
                        : ('Your leave was ' . strtolower((string) $item->status)),
                    'body' => trim(($item->leave_category ?? $item->leave_type ?? '') . ' · ' . optional($item->start_date)->format('d M Y')),
                    'url' => $isAdmin ? route('leave.history', ['leave' => $item->id]) : route('profile.leave-history', ['leave' => $item->id]),
                ];
            }
        }

        return $items;
    }

    private function celebrations(): Collection
    {
        $todayDate = now();
        $today = $todayDate->format('m-d');

        return Employee::where(function ($q) use ($todayDate) {
                $q->where(function ($q2) use ($todayDate) {
                    $q2->whereMonth('date_of_birth', $todayDate->month)
                        ->whereDay('date_of_birth', $todayDate->day);
                })->orWhere(function ($q2) use ($todayDate) {
                    $q2->whereMonth('date_of_joining', $todayDate->month)
                        ->whereDay('date_of_joining', $todayDate->day);
                });
            })
            ->get()
            ->map(function ($emp) use ($today) {
                $isBirthday = Carbon::parse($emp->date_of_birth)->format('m-d') == $today;
                $type = $isBirthday ? 'Birthday' : 'Work Anniversary';
                $years = (int) Carbon::parse($emp->date_of_joining)->diffInYears(now());

                return (object) [
                    'id' => 'celebration-' . $emp->id,
                    'type' => 'celebration',
                    'event_type' => $type,
                    'employee' => $emp,
                    'years' => $years,
                    'message' => $isBirthday
                        ? "Today is {$emp->name}'s Birthday! 🎂"
                        : "Today is {$emp->name}'s {$years}" . ($years == 1 ? 'st' : ($years == 2 ? 'nd' : ($years == 3 ? 'rd' : 'th'))) . ' Work Anniversary! 🏆',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });
    }
}
