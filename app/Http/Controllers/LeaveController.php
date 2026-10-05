<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Employee;
use App\Models\LeaveAllotment;
// use App\Models\Attendance;
use App\Exports\LeaveBalancesExport;
use App\Services\LeaveBalanceService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function __construct(private LeaveBalanceService $leaveBalanceService)
    {
    }

    public function index()
    {
        return redirect()->route('leave.allotment');
    }

    public function allotment(Request $request)
    {
        $selectedMonth = $request->get('month', Carbon::now()->format('m'));
        $year = Carbon::now()->format('Y');
        $month = $selectedMonth;
        $monthVariants = array_values(array_unique([
            (string) $month,
            sprintf('%02d', (int) $month),
            (string) (int) $month,
        ]));

        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, [
            'super_admin',
            'manager',
            'hr_executive',
            'hr_intern',
            'business_operation_head'
        ]);

        $isTeamLeader = in_array($role, [
            'team_leader'
        ]);

        $selectedMonthStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $selectedMonthEnd = $selectedMonthStart->copy()->endOfMonth();

        if ($isAdmin) {
            // Include employees who join during the selected month too; they are
            // flagged as new joiners below and listed on top.
            $employees = Employee::active()
                ->where(function ($q) use ($selectedMonthEnd) {
                    $q->whereNull('date_of_joining')
                        ->orWhereDate('date_of_joining', '<=', $selectedMonthEnd);
                })
                ->orderBy('name', 'asc')
                ->get();
            $allotments = LeaveAllotment::whereIn('month', $monthVariants)
                ->where('year', $year)
                ->get()
                ->keyBy('employee_id');

            $history = LeaveAllotment::with('employee')
                ->whereIn('month', $monthVariants)
                ->where('year', $year)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($isTeamLeader) {
            $department = $user->employee->department_id ?? null;
            if ($department) {
                $employees = Employee::active()->where('department_id', $department)->whereDate('date_of_joining', '<', $selectedMonthStart)->orderBy('name', 'asc')->get();
                $employeeIds = $employees->pluck('id');
                
                $allotments = LeaveAllotment::whereIn('month', $monthVariants)
                    ->where('year', $year)
                    ->whereIn('employee_id', $employeeIds)
                    ->get()
                    ->keyBy('employee_id');

                $history = LeaveAllotment::with('employee')
                    ->whereIn('month', $monthVariants)
                    ->where('year', $year)
                    ->whereIn('employee_id', $employeeIds)
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $employees = collect();
                $allotments = collect();
                $history = collect();
            }
        } else {
            $employee_id = $user->employee_id;
            $employees = Employee::active()->where('id', $employee_id)->get();
            $allotments = LeaveAllotment::whereIn('month', $monthVariants)
                ->where('year', $year)
                ->where('employee_id', $employee_id)
                ->get()
                ->keyBy('employee_id');

            $history = LeaveAllotment::with('employee')
                ->whereIn('month', $monthVariants)
                ->where('year', $year)
                ->where('employee_id', $employee_id)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $selectedYear = (int) $request->get('year', Carbon::now()->format('Y'));
        $yearlyMatrix = $this->leaveBalanceService->getBulkYearlyBalanceMatrix($employees, $selectedYear);
        $availableYears = range((int) Carbon::now()->format('Y') + 1, (int) Carbon::now()->format('Y') - 2);

        if ($isTeamLeader) {
            $monthDate = Carbon::createFromDate((int) $year, (int) $month, 1);
            $balances = $this->calculateBalances($employees, $monthDate);
            return view('leave.team_balance', compact('balances', 'selectedMonth'));
        }

        $monthDate = Carbon::createFromDate((int) $year, (int) $month, 1);
        $balances = $this->calculateBalances($employees, $monthDate);

        $eligibleStatuses = Employee::leaveEligibleStatuses();
        $allotmentRows = $this->buildAllotmentRows($employees, $allotments, $selectedMonthStart, $eligibleStatuses);
        $employmentStatuses = Employee::EMPLOYMENT_STATUSES;

        return view('leave.allotment', compact('employees', 'allotments', 'selectedMonth', 'history', 'isAdmin', 'balances', 'yearlyMatrix', 'selectedYear', 'availableYears', 'allotmentRows', 'eligibleStatuses', 'employmentStatuses'));
    }

    /**
     * One row per employee for the Monthly Allotment panel: status, whether they
     * have completed a month of service, and the leave count to prefill. Employees
     * under one month are listed first.
     */
    private function buildAllotmentRows($employees, $allotments, Carbon $monthStart, array $eligibleStatuses)
    {
        // Under one month of service as of the start of the selected month.
        $newJoinerCutoff = $monthStart->copy()->subMonthNoOverflow();

        return $employees->map(function ($emp) use ($allotments, $newJoinerCutoff, $eligibleStatuses) {
            $status = $emp->employment_status ?: 'working';
            $joined = $emp->date_of_joining ? Carbon::parse($emp->date_of_joining) : null;
            $isNewJoiner = $joined && $joined->gt($newJoinerCutoff);

            $eligible = in_array($status, $eligibleStatuses, true)
                && (!$isNewJoiner || in_array(Employee::NEW_JOINER, $eligibleStatuses, true));

            $saved = $allotments[$emp->id] ?? null;
            $count = $saved ? (float) $saved->leave_count : ($eligible ? (float) $emp->leave : 0.0);

            return (object) [
                'employee' => $emp,
                'status' => $status,
                'status_label' => Employee::EMPLOYMENT_STATUSES[$status] ?? 'Working',
                'is_new_joiner' => $isNewJoiner,
                'joined_on' => $joined,
                'eligible' => $eligible,
                'master_leave' => (float) $emp->leave,
                'count' => $count,
                'is_saved' => (bool) $saved,
            ];
        })->sortBy(fn ($row) => [$row->is_new_joiner ? 0 : 1, strtolower($row->employee->name)])->values();
    }

    private function canManageAllotments(): bool
    {
        $roleId = DB::table('roles_master')
            ->where('slug', auth()->user()->role)
            ->value('id');

        return in_array($roleId, [1, 2, 3, 4]);
    }

    public function updateEmploymentStatus(Request $request, Employee $employee)
    {
        if (!$this->canManageAllotments()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'employment_status' => 'required|in:' . implode(',', array_keys(Employee::EMPLOYMENT_STATUSES)),
        ]);

        $employee->update(['employment_status' => $validated['employment_status']]);

        return response()->json(['success' => true, 'message' => 'Status updated']);
    }

    public function updateAllotmentRules(Request $request)
    {
        if (!$this->canManageAllotments()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $allowed = array_merge(array_keys(Employee::EMPLOYMENT_STATUSES), [Employee::NEW_JOINER]);
        $validated = $request->validate([
            'eligible_statuses' => 'present|array',
            'eligible_statuses.*' => 'in:' . implode(',', $allowed),
        ]);

        AppSetting::setValue(Employee::LEAVE_ELIGIBILITY_SETTING, array_values(array_unique($validated['eligible_statuses'])));

        return response()->json(['success' => true, 'message' => 'Allotment rules saved']);
    }

    public function storeAllotment(Request $request)
    {

        // echo "<pre>";print_r($request);exit;

        $roleSlug = auth()->user()->role; // e.g. "manager"

        $roleId = DB::table('roles_master')
            ->where('slug', $roleSlug)
            ->value('id');

        $isAdmin = in_array($roleId, [1, 2, 3, 4]);
        if (!$isAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $month = $request->input('month');
        $year = Carbon::now()->format('Y');

        $allotments = $request->input('allotments', []);
        $employeeIds = array_keys($allotments);

        // Remove allotments for employees who were removed from the list in UI
        LeaveAllotment::where('month', $month)
            ->where('year', $year)
            ->whereNotIn('employee_id', $employeeIds)
            ->delete();

        foreach ($allotments as $employeeId => $count) {
            LeaveAllotment::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'leave_count' => $count ?? 0,
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Leaves allotted successfully']);
    }

    public function balanceList()
    {
        $balances = $this->calculateBalances();
        return view('leave.balance', compact('balances'));
    }

    public function apiBalanceList()
    {
        $balances = $this->calculateBalances();
        return response()->json($balances);
    }

    public function exportBalances()
    {
        $balances = $this->calculateBalances();
        $filename = "leave_balances_" . date('Y-m-d_H-i-s') . ".xlsx";
        return Excel::download(new LeaveBalancesExport($balances), $filename);
    }

    private function calculateBalances($employees = null, ?Carbon $monthDate = null)
    {
        $employees = $employees ?? Employee::active()->orderBy('name', 'asc')->get();
        $monthDate = ($monthDate ?? now())->copy()->startOfMonth();
        $summaries = $this->leaveBalanceService->getBulkEmployeeBalanceSummaries($employees, $monthDate);
        $balances = [];

        foreach ($employees as $employee) {
            $summary = $summaries[$employee->id] ?? [
                'total_allotted' => 0,
                'total_taken' => 0,
                'balance' => 0,
                'unpaid_leave_days' => 0,
            ];

            $balances[] = (object) [
                'id' => $employee->id,
                'name' => $employee->name,
                'total_allotted' => $summary['total_allotted'],
                'total_taken' => $summary['total_taken'],
                'balance' => $summary['balance'],
                'unpaid_leave_days' => $summary['unpaid_leave_days'],
            ];
        }

        return $balances;
    }
}
