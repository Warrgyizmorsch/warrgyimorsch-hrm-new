<?php

namespace App\Http\Controllers;

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

        $selectedMonthStart = Carbon::createFromDate($year, $month, 1);

        if ($isAdmin) {
            $employees = Employee::active()->whereDate('date_of_joining', '<', $selectedMonthStart)->orderBy('name', 'asc')->get();
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

        if ($isTeamLeader) {
            $monthDate = Carbon::createFromDate((int) $year, (int) $month, 1);
            $balances = $this->calculateBalances($employees, $monthDate);
            return view('leave.team_balance', compact('balances', 'selectedMonth'));
        }

        $monthDate = Carbon::createFromDate((int) $year, (int) $month, 1);
        $balances = $this->calculateBalances($employees, $monthDate);

        return view('leave.allotment', compact('employees', 'allotments', 'selectedMonth', 'history', 'isAdmin', 'balances'));
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
