<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveAllotment;
use App\Models\Broadcast;
use App\Models\Note;
use App\Models\DailyTask;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DashboardController extends Controller
{
    public function __construct(private LeaveBalanceService $leaveBalanceService)
    {
    }

    private function getHolidayDatesBetween(Carbon $from, Carbon $to): array
    {
        return Holiday::whereBetween('date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->pluck('date')
            ->mapWithKeys(fn ($date) => [Carbon::parse($date)->toDateString() => true])
            ->all();
    }

    private function shouldSkipNonWorkingDaysForReport(LeaveApplication $leave): bool
    {
        $normalizedCategory = strtolower($leave->leave_category ?? '');
        $normalizedType = strtolower($leave->leave_type ?? '');

        return !str_contains($normalizedCategory, 'gatepass')
            && !str_contains($normalizedType, 'half');
    }

    private function expandLeaveReportDates(
        LeaveApplication $leave,
        Carbon $from,
        Carbon $to,
        array $holidayDates
    ): array {
        $start = Carbon::parse($leave->start_date)->startOfDay()->max($from->copy()->startOfDay());
        $end = Carbon::parse($leave->end_date ?: $leave->start_date)->startOfDay()->min($to->copy()->startOfDay());

        if ($end->lt($start)) {
            return [];
        }

        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (
                $this->shouldSkipNonWorkingDaysForReport($leave)
                && ($date->isSunday() || isset($holidayDates[$date->toDateString()]))
            ) {
                continue;
            }

            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    private function getAttendanceAnalytics($from, $to, $employeeId = null)
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $query = Attendance::whereBetween('attendance_date', [$fromDate, $toDate]);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        return $query->selectRaw("
            SUM(IF(status IN ('present', 'half_day', 'late', 'wfh') OR check_in IS NOT NULL, 1, 0)) as present_count,
            SUM(IF(status = 'wfh', 1, 0)) as wfh_count,
            SUM(IF(status = 'half_day', 1, 0)) as halfDay_count,
            SUM(IF(status = 'leave', 1, 0)) as leave_count,
            SUM(IF(
                status = 'late'
                OR (
                    check_in IS NOT NULL
                    AND check_out IS NOT NULL
                    AND (
                        (status IN ('half_day', 'half_day_leave') AND total_hours < 4)
                        OR (status NOT IN ('half_day', 'half_day_leave', 'leave', 'wfh', 'absent', 'missing_punch', 'unpaid_leave', 'unauthorised') AND total_hours < 8.5)
                    )
                ),
                1,
                0
            )) as late_count,
            SUM(IF(status = 'early_leave', 1, 0)) as early_count,
            SUM(IF(status = 'absent', 1, 0)) as absent_count
        ")->first();
    }

    private function getEmployeeLeaveTaken(int $employeeId, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $approvedLeaves = LeaveApplication::where('employee_id', $employeeId)
            ->where('status', 'approved');

        if ($from && $to) {
            $approvedLeaves->whereDate('start_date', '<=', $to->toDateString())
                ->where(function ($query) use ($from) {
                    $query->whereDate('end_date', '>=', $from->toDateString())
                        ->orWhereNull('end_date');
                });
        }

        $approvedLeaves = $approvedLeaves->get();

        $totalTaken = 0;

        foreach ($approvedLeaves as $leave) {
            $category = strtolower($leave->leave_category ?? '');
            $type = strtolower($leave->leave_type ?? '');

            if (str_contains($category, 'gatepass') || str_contains($category, 'wfh')) {
                continue;
            }

            if (str_contains($category, 'half') || str_contains($type, 'half')) {
                $totalTaken += 0.5;
                continue;
            }

            if ($from && $to) {
                $startDate = Carbon::parse($leave->start_date)->startOfDay()->max($from->copy()->startOfDay());
                $endDate = Carbon::parse($leave->end_date ?: $leave->start_date)->startOfDay()->min($to->copy()->startOfDay());

                if ($endDate->lt($startDate)) {
                    continue;
                }

                $totalTaken += $startDate->equalTo($endDate)
                    ? 1
                    : $startDate->diffInDays($endDate) + 1;

                continue;
            }

            if ($leave->total_days !== null) {
                $totalTaken += (float) $leave->total_days;
                continue;
            }

            $startDate = Carbon::parse($leave->start_date);
            $endDate = $leave->end_date ? Carbon::parse($leave->end_date) : $startDate->copy();

            $totalTaken += $startDate->equalTo($endDate)
                ? 1
                : $startDate->diffInDays($endDate);
        }

        return $totalTaken;
    }

    public function index(Request $request)
    {
        // $role = strtoupper(auth()->user()->role ?? 'USER');
        //  $isAdmin = in_array($role, ['MANAGER', 'SUPER_ADMIN', 'HR_EXECUTIVE', 'HR_INTERN']);
        $roleSlug = auth()->user()->role;

        $roleId = DB::table('roles_master')
            ->where('slug', $roleSlug)
            ->value('id');

        $isAdmin = in_array($roleId, [1, 2, 3, 4]);
        $isTeamLeader = in_array($roleSlug, ['team_leader']);
        $employee = Employee::active()->where('id', auth()->user()->employee_id)->first();
        $celebration = $this->getTodayCelebration($employee);
        $employeeId = auth()->user()->employee_id;

        $today = Carbon::today()->toDateString();

        // Month Filtering
        $selectedMonth = $request->get('month');
        $hasSelectedMonth = !empty($selectedMonth);

        if (!$hasSelectedMonth) {
            $selectedMonth = Carbon::now()->format('Y-m');
        }

        $selectedDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $selectedMonthLabel = $selectedDate->format('F Y');

        // Employee Metrics
        // $totalEmployees = $isAdmin ? Employee::count() : 1;

        if(!$isAdmin && $employee){
            $totalEmployees = Employee::active()->where('department', $employee->department)->count();
        }
        else{
            $totalEmployees = Employee::active()->count();
        }

        $analyticsEmployeeId = $isAdmin ? null : $employeeId;
        $todayAnalytics = $this->getAttendanceAnalytics($today, $today, $analyticsEmployeeId);
        $todayDashboardAnalytics = $this->getAttendanceAnalytics($today, $today);

        // Attendance Metrics
        $isCurrentMonth = ($selectedMonth == Carbon::now()->format('Y-m'));

        if ($isAdmin) {
            if ($isCurrentMonth) {
                $todayPresent = (int) ($todayAnalytics->present_count ?? 0);
                $todayLeave = Attendance::where('attendance_date', $today)->whereIn('status', ['absent', 'leave'])->count();
                $attendanceRate = $totalEmployees > 0 ? round(($todayPresent / $totalEmployees) * 100, 1) : 0;
            } else {
                $daysInMonth = $selectedDate->daysInMonth;
                $monthPresent = Attendance::whereMonth('attendance_date', $selectedDate->month)
                    ->whereYear('attendance_date', $selectedDate->year)
                    ->whereIn('status', ['present', 'half_day', 'late', 'leave', 'absent'])
                    ->count();

                $todayPresent = $daysInMonth > 0 ? round($monthPresent / $daysInMonth) : 0;
                $todayLeave = 0;
                $attendanceRate = ($totalEmployees > 0 && $daysInMonth > 0) ? round(($monthPresent / ($totalEmployees * $daysInMonth)) * 100, 1) : 0;
            }

            // Payroll Metrics (Selected Month)
            $totalPaidAmount = Payroll::where('month', $selectedMonth)->where('status', 'paid')->sum('net_salary');
            $totalPendingAmount = Payroll::where('month', $selectedMonth)->where('status', 'pending')->sum('net_salary');
            $totalRejectedAmount = Payroll::where('month', $selectedMonth)->where('status', 'rejected')->sum('net_salary');
            $totalNetSalary = Payroll::where('month', $selectedMonth)->sum('net_salary');

            $totalEmpPaid = Payroll::where('month', $selectedMonth)->where('status', 'paid')->count();
            $totalEmpPending = Payroll::where('month', $selectedMonth)->where('status', 'pending')->count();
        } else {
            // Employee specific metrics
            $todayPresent = (int) ($todayAnalytics->present_count ?? 0);
            $todayLeave = Attendance::where('employee_id', $employeeId)->where('attendance_date', $today)->whereIn('status', ['absent', 'leave'])->count();
            $attendanceRate = ($totalEmployees > 0) ? number_format(($todayPresent / $totalEmployees) * 100, 2) : 0;

            $myPayroll = Payroll::where('employee_id', $employeeId)->where('month', $selectedMonth)->first();
            $totalPaidAmount = ($myPayroll && $myPayroll->status == 'paid') ? $myPayroll->net_salary : 0;
            $totalPendingAmount = ($myPayroll && $myPayroll->status == 'pending') ? $myPayroll->net_salary : 0;
            $totalRejectedAmount = ($myPayroll && $myPayroll->status == 'rejected') ? $myPayroll->net_salary : 0;
            $totalNetSalary = $myPayroll ? $myPayroll->net_salary : 0;

            $totalEmpPaid = ($myPayroll && $myPayroll->status == 'paid') ? 1 : 0;
            $totalEmpPending = ($myPayroll && $myPayroll->status == 'pending') ? 1 : 0;
        }

        // Attendance analytics card for all roles.
        $present = (int) ($todayDashboardAnalytics->present_count ?? 0);
        $wfh = (int) ($todayDashboardAnalytics->wfh_count ?? 0);
        $late = (int) ($todayDashboardAnalytics->late_count ?? 0);
        $half_day = (int) ($todayDashboardAnalytics->halfDay_count ?? 0);
        $leave = (int) ($todayDashboardAnalytics->leave_count ?? 0);
        $early = (int) ($todayDashboardAnalytics->early_count ?? 0);
        $absent = (int) ($todayDashboardAnalytics->absent_count ?? 0);
        $attendanceRate = $totalEmployees > 0 ? round(($present / $totalEmployees) * 100, 2) : 0;

        // NEW DATE FILTER ANALYTICS (ADD HERE ONLY)

        if ($request->has('from') || $request->has('filter')) {
            if ($request->filter == 'today') {
                $from = Carbon::today()->toDateString();
                $to = Carbon::today()->toDateString();
            } elseif ($request->filter == 'yesterday') {
                $from = Carbon::yesterday()->toDateString();
                $to = Carbon::yesterday()->toDateString();
            } elseif ($request->filter == 'week') {
                $from = Carbon::now()->subDays(6)->toDateString();
                $to = Carbon::today()->toDateString();
            } elseif ($request->filter == 'month') {
                $from = Carbon::now()->startOfMonth()->toDateString();
                $to = Carbon::today()->toDateString();
            } else {
                $from = $request->from ?? Carbon::today()->toDateString();
                $to = $request->to ?? Carbon::today()->toDateString();
            }

            $analytics = $this->getAttendanceAnalytics($from, $to);

            // $rangePresent = (int) ($analytics->present_count ?? 0);
            // $rangeWFH     = (int) ($analytics->wfh_count ?? 0);
            // $rangeLeave   = (int) ($analytics->leave_count ?? 0);
            // $rangeLate    = (int) ($analytics->late_count ?? 0);
            // $rangeEarly   = (int) ($analytics->early_count ?? 0);
            // $rangeAbsent  = (int) ($analytics->absent_count ?? 0);
            // $rangeHalfday = (int) ($analytics->halfDay_count ?? 0);

            // $rangeCheckedIn = $rangePresent;
            // $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
            // $denominator = $totalEmployees * $days;

            // $rangeAttendanceRate = ($denominator > 0)
            //     ? number_format(($rangeCheckedIn / $denominator) * 100, 2)
            //     : 0;

            $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;

            // Raw totals
            $totalPresent = (int) ($analytics->present_count ?? 0);
            $totalWFH = (int) ($analytics->wfh_count ?? 0);
            $totalLeave = (int) ($analytics->leave_count ?? 0);
            $totalLate = (int) ($analytics->late_count ?? 0);
            $totalEarly = (int) ($analytics->early_count ?? 0);
            $totalAbsent = (int) ($analytics->absent_count ?? 0);
            $totalHalfday = (int) ($analytics->halfDay_count ?? 0);

            // Convert to average per day
            $rangePresent = $days > 0 ? round($totalPresent / $days) : 0;
            $rangeWFH = $days > 0 ? round($totalWFH / $days) : 0;
            $rangeLeave = $days > 0 ? round($totalLeave / $days) : 0;
            $rangeLate = $days > 0 ? round($totalLate / $days) : 0;
            $rangeEarly = $days > 0 ? round($totalEarly / $days) : 0;
            $rangeAbsent = $days > 0 ? round($totalAbsent / $days) : 0;
            $rangeHalfday = $days > 0 ? round($totalHalfday / $days) : 0;

            // Attendance rate (average based)
            $rangeAttendanceRate = ($totalEmployees > 0)
                ? number_format(($rangePresent / $totalEmployees) * 100, 2)
                : 0;
        } else {
            $rangePresent = 0;
            $rangeWFH = 0;
            $rangeLeave = 0;
            $rangeLate = 0;
            $rangeEarly = 0;
            $rangeAbsent = 0;
            $rangeHalfday = 0;

            $rangeAttendanceRate = 0;
        }

        // Chart Data: 6 Months
        $chartMonths = [];
        $chartTotal = [];
        $chartPaid = [];
        $chartPending = [];

        for ($i = 5; $i >= 0; $i--) {
            $m = (clone $selectedDate)->subMonths($i);
            $mLabel = $m->format('M/y');
            $mValue = $m->format('Y-m');

            $chartMonths[] = $mLabel;

            $pQuery = Payroll::where('month', $mValue);
            if (!$isAdmin)
                $pQuery->where('employee_id', $employeeId);

            $chartTotal[] = (clone $pQuery)->sum('net_salary');
            $chartPaid[] = (clone $pQuery)->where('status', 'paid')->sum('net_salary');
            $chartPending[] = (clone $pQuery)->where('status', 'pending')->sum('net_salary');
        }

        // Recent Activity
        $pRecent = Payroll::with('employee')->where('month', $selectedMonth);
        if (!$isAdmin)
            $pRecent->where('employee_id', $employeeId);
        $recentPayrolls = $pRecent->latest()->paginate(10);

        // Upcoming Holidays
        $upcomingHolidays = Holiday::where('date', '>=', $today)->orderBy('date')->limit(20)->get();

        // Selected month for leave report (default = current month)
        $leaveReport = $this->getLeaveReport($request);
        $employees = Employee::active()->get();

        // Employee Leave on Today
        // $todayLeaveEmployees = Attendance::with('employee') 
        //     ->whereDate('attendance_date', $today) 
        //     ->whereIn('status', ['leave'])
        //     // ->when(!$isAdmin, fn($q) => $q->where('employee_id', $employeeId)) 
        //     ->get();

        $approvedLeave = DB::table('leave_applications')
            ->join('employees', 'employees.id', '=', 'leave_applications.employee_id')
            ->whereIn('leave_applications.status', ['approved', 'unauthorised'])
            ->whereDate('leave_applications.start_date', '<=', $today)
            ->whereDate('leave_applications.end_date', '>=', $today)
            ->select(
                'employees.id as employee_id',
                'employees.name as employee_name',
                DB::raw("
                    CASE 
                        WHEN leave_applications.leave_category = 'Full Day' THEN 'Full Day Leave'
                        WHEN LOWER(leave_applications.leave_category) LIKE '%Half%' THEN 'Half Day'
                        WHEN leave_applications.leave_category = 'Gatepass' THEN 'Early Leave'
                        WHEN leave_applications.leave_category = 'WFH' THEN 'Working from Home'
                        ELSE leave_applications.leave_category
                    END as leave_type
                ")
            );

        $attendanceLeave = DB::table('attendances')
            ->join('employees', 'employees.id', '=', 'attendances.employee_id')
            ->whereDate('attendances.attendance_date', $today)
            ->whereIn('attendances.status', ['leave', 'half_day', 'early_leave'])
            ->whereNotIn('attendances.employee_id', function ($q) use ($today) {
                $q->select('employee_id')
                    ->from('leave_applications')
                    ->whereIn('status', ['approved', 'unauthorised'])
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);
            })
            ->select(
                'employees.id as employee_id',
                'employees.name as employee_name',
                DB::raw("
                    CASE 
                        WHEN attendances.status = 'leave' THEN 'Full Day Leave'
                        WHEN attendances.status = 'half_day' THEN 'Half Day'
                        WHEN attendances.status = 'early_leave' THEN 'Early Leave'
                    END as leave_type
                ")
            );

        $absentEmployees = DB::table('attendances')
            ->join('employees', 'employees.id', '=', 'attendances.employee_id')
            ->whereDate('attendances.attendance_date', $today)
            ->where('attendances.status', 'absent')
            ->where(function ($q) {
                $q->whereNull('attendances.check_in')
                    ->orWhereNull('attendances.check_out');
            })
            ->whereNotIn('attendances.employee_id', function ($q) use ($today) {
                $q->select('employee_id')
                    ->from('leave_applications')
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);
            })
            ->select(
                'employees.id as employee_id',
                'employees.name as employee_name',
                DB::raw("'Absent' as leave_type")
            );

        $todayLeaveEmployees = $attendanceLeave
            ->union($approvedLeave)
            ->union($absentEmployees)
            ->get();

        // Late arrival on today
        $todayLateEmployees = $this->getLateEmployeesData();


        $userDepartment = $employee->department ?? auth()->user()->employee?->department ?? null;
        $normalizedUserDepartment = $userDepartment
            ? strtolower(str_replace('_', ' ', trim($userDepartment)))
            : null;

        $announcements = Broadcast::with('readByUsers')
            ->where(function ($query) use ($normalizedUserDepartment) {
                $query->where('department', 'All');

                if ($normalizedUserDepartment) {
                    $query->orWhereRaw(
                        "LOWER(REPLACE(TRIM(department), '_', ' ')) = ?",
                        [$normalizedUserDepartment]
                    );
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $leaveSummary = $this->leaveBalanceService->getEmployeeBalanceSummary($employeeId);
        $totalAllottedLeave = $leaveSummary['total_allotted'];
        $totalUtilizedLeave = $leaveSummary['total_taken'];
        $availableLeaveBalance = $leaveSummary['balance'];
        $currentMonthUtilizedLeave = $leaveSummary['total_taken'];
        $currentMonthAllottedLeave = $leaveSummary['monthly_allotment'];

        // Total Journy
        if ($employee && $employee->date_of_joining) {
            $interval = Carbon::parse($employee->date_of_joining)->diff(now());

            $parts = [];

            if ($interval->y > 0) $parts[] = $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
            if ($interval->m > 0) $parts[] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
            if ($interval->d > 0) $parts[] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');

            $totalJourney = implode(' ', $parts);
        } else {
            $totalJourney = 'N/A';
        }

        $canViewPayrollAnalytics = $this->canViewPayrollAnalytics();

        $myNotes = Note::where('user_id', auth()->id())
            ->orderBy('is_completed')
            ->orderByDesc('created_at')
            ->get();

        $myAdhocTasks = $employeeId
            ? DailyTask::with('creator')
                ->whereNull('project_id')
                ->where('employee_id', $employeeId)
                ->where('status', '!=', 'Completed')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $tasksIAssigned = DailyTask::with(['employee', 'latestStatusHistory'])
            ->whereNull('project_id')
            ->where('assigned_by', auth()->id())
            ->where('status', '!=', 'Completed')
            ->orderByDesc('created_at')
            ->get();

        if (!$isAdmin) {
            return view('userDashboard', compact(
                'totalEmployees',
                'todayPresent',
                'todayLeave',
                'attendanceRate',
                'totalPaidAmount',
                'totalPendingAmount',
                'totalRejectedAmount',
                'totalNetSalary',
                'totalEmpPaid',
                'totalEmpPending',
                'chartMonths',
                'chartTotal',
                'chartPaid',
                'chartPending',
                'selectedMonth',
                'selectedMonthLabel',
                'recentPayrolls',
                'upcomingHolidays',
                'rangePresent',
                'rangeWFH',
                'rangeLeave',
                'rangeLate',
                'rangeEarly',
                'rangeAttendanceRate',
                'rangeAbsent',
                'rangeHalfday',
                'present',
                'wfh',
                'leave',
                'late',
                'early',
                'absent',
                'half_day',
                'leaveReport',
                'employees',
                'todayLeaveEmployees',
                'todayLateEmployees',
                'employee',
                'celebration',
                'announcements',
                'availableLeaveBalance',
                'totalAllottedLeave',
                'totalUtilizedLeave',
                'currentMonthUtilizedLeave',
                'currentMonthAllottedLeave',
                'totalJourney',
                'canViewPayrollAnalytics',
                'myNotes',
                'myAdhocTasks',
                'tasksIAssigned'
            ));
        }

        return view('dashboard', compact(
            'totalEmployees',
            'todayPresent',
            'todayLeave',
            'attendanceRate',
            'totalPaidAmount',
            'totalPendingAmount',
            'totalRejectedAmount',
            'totalNetSalary',
            'totalEmpPaid',
            'totalEmpPending',
            'chartMonths',
            'chartTotal',
            'chartPaid',
            'chartPending',
            'selectedMonth',
            'selectedMonthLabel',
            'recentPayrolls',
            'upcomingHolidays',
            'rangePresent',
            'rangeWFH',
            'rangeLeave',
            'rangeLate',
            'rangeEarly',
            'rangeAttendanceRate',
            'rangeAbsent',
            'rangeHalfday',
            'present',
            'wfh',
            'leave',
            'late',
            'early',
            'absent',
            'half_day',
            'leaveReport',
            'employees',
            'todayLeaveEmployees',
            'todayLateEmployees',
            'employee',
            'celebration',
            'announcements',
            'canViewPayrollAnalytics',
            'myNotes',
            'myAdhocTasks',
            'tasksIAssigned'
        ));
    }

    private function canViewPayrollAnalytics(): bool
    {
        $allowedRoles = [
            'super_admin',
            'manager',
            'hr_executive',
            'hr_intern',
            'business_operation_head',
        ];

        $role = str_replace(' ', '_', strtolower(trim((string) (auth()->user()->role ?? ''))));

        return in_array($role, $allowedRoles, true);
    }

    private function authorizePayrollAnalytics(): void
    {
        if (!$this->canViewPayrollAnalytics()) {
            abort(403, 'Unauthorized access');
        }
    }

    private function getLeaveReport(Request $request)
    {
        // $query = Attendance::join('employees', 'attendances.employee_id', '=', 'employees.id')
        //     ->join('leave_applications', function ($join) {
        //         $join->on('attendances.employee_id', '=', 'leave_applications.employee_id')
        //             ->whereColumn('attendances.attendance_date', '>=', 'leave_applications.start_date')
        //             ->whereColumn('attendances.attendance_date', '<=', 'leave_applications.end_date');
        //     })
        //     ->where('leave_applications.status', 'approved');

        $roleSlug = auth()->user()->role;

        $roleId = DB::table('roles_master')
            ->where('slug', $roleSlug)
            ->value('id');

        $isAdmin = in_array($roleId, [1, 2, 3, 4]);
        $isTeamLeader = ($roleId == 5);
        $employeeId = auth()->user()->employee_id;

        // Logged-in employee department
        $leaderDepartment = Employee::active()->where('id', $employeeId)
            ->value('department');

        $query = LeaveApplication::join('employees', 'leave_applications.employee_id', '=', 'employees.id')
            ->whereIn('leave_applications.status', ['approved', 'unauthorised'])
            ->where('leave_applications.leave_category', 'NOT LIKE', '%WFH%');

        // USER → force own data
        if (!$isAdmin && !$isTeamLeader) {
            $query->where('employees.id', $employeeId);
        }

        // Team Leader → only department employees
        if ($isTeamLeader) {

            $query->where('employees.department', $leaderDepartment);

            // optional employee filter
            if ($request->employee_id) {
                $query->where('employees.id', $request->employee_id);
            }
        }

        // ADMIN → keep old filter
        if ($isAdmin && $request->employee_id) {
            $query->where('employees.id', $request->employee_id);
        }

        $from = null;
        $to = Carbon::today();

        if ($request->leave_filter) {
            switch ($request->leave_filter) {
                case 'week':
                    $from = Carbon::now()->subWeek();
                    break;
                case 'month':
                    $from = Carbon::now()->subMonth()->startOfMonth();
                    $to = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case '3month':
                    $from = Carbon::now()->subMonths(3);
                    break;
                case '6month':
                    $from = Carbon::now()->subMonths(6);
                    break;
                case 'year':
                    $from = Carbon::now()->subYear();
                    break;
            }
        }

        // Custom range
        if ($request->leave_from && $request->leave_to) {
            $from = Carbon::parse($request->leave_from);
            $to = Carbon::parse($request->leave_to);
        }

        // Default = last month
        if (!$request->leave_filter && !$request->leave_from) {
            $from = Carbon::now()->startOfMonth();
            $to = Carbon::now();
        }

        if ($from && $to) {
            // ADMIN → OLD LOGIC (NO CHANGE)
            if ($isAdmin) {
                $query->where(function ($q) use ($from, $to) {
                    $q->whereBetween('leave_applications.start_date', [$from, $to])
                        ->orWhereBetween('leave_applications.end_date', [$from, $to]);
                });
            }

            // Team leader / employee → include any leave overlapping the selected range
            if (!$isAdmin) {
                $query->whereDate('leave_applications.start_date', '<=', $to->toDateString())
                    ->where(function ($q) use ($from) {
                        $q->whereDate('leave_applications.end_date', '>=', $from->toDateString())
                            ->orWhereNull('leave_applications.end_date');
                    });
            }
        }

        // ADMIN → keep old dashboard behavior unchanged
        if ($isAdmin) {
            return $query->selectRaw("
                    employees.id,
                    employees.name,
                    employees.designation,
                    COUNT(DISTINCT leave_applications.id) as leave_count
                ")
                ->groupBy('employees.id', 'employees.name', 'employees.designation')
                ->havingRaw("leave_count > 0")
                ->orderByDesc('leave_count')
                ->get();
        }

        $from = ($from ?: Carbon::now()->startOfMonth())->copy()->startOfDay();
        $to = ($to ?: Carbon::today())->copy()->startOfDay();
        $holidayDates = $this->getHolidayDatesBetween($from, $to);

        if ($isTeamLeader) {
            $leaveApplications = (clone $query)
                ->select(
                    'leave_applications.*',
                    'employees.id as employee_id',
                    'employees.name',
                    'employees.designation'
                )
                ->get();

            return $leaveApplications
                ->groupBy('employee_id')
                ->map(function ($employeeLeaves) use ($from, $to, $holidayDates) {
                    $employee = $employeeLeaves->first();

                    $leaveCount = $employeeLeaves
                        ->flatMap(fn ($leave) => $this->expandLeaveReportDates($leave, $from, $to, $holidayDates))
                        ->unique()
                        ->count();

                    if ($leaveCount === 0) {
                        return null;
                    }

                    return (object) [
                        'id' => $employee->employee_id,
                        'name' => $employee->name,
                        'designation' => $employee->designation,
                        'leave_count' => $leaveCount,
                    ];
                })
                ->filter()
                ->sortByDesc('leave_count')
                ->values();
        }

        $leaveDates = LeaveApplication::where('employee_id', $employeeId)
            ->whereIn('status', ['approved', 'unauthorised'])
            ->where('leave_category', 'NOT LIKE', '%WFH%')
            ->when($from && $to, function ($q) use ($from, $to) {
                $q->whereDate('start_date', '<=', $to->toDateString())
                    ->where(function ($sub) use ($from) {
                        $sub->whereDate('end_date', '>=', $from->toDateString())
                            ->orWhereNull('end_date');
                    });
            })
            ->get()
            ->flatMap(fn ($leave) => $this->expandLeaveReportDates($leave, $from, $to, $holidayDates));

        $attendanceDates = Attendance::where('employee_id', $employeeId)
            ->whereIn('status', ['leave', 'absent'])
            ->when($from && $to, function ($q) use ($from, $to) {
                $q->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()]);
            })
            ->pluck('attendance_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->reject(fn ($date) => Carbon::parse($date)->isSunday() || isset($holidayDates[$date]));

        $totalUniqueDates = $leaveDates
            ->merge($attendanceDates)
            ->unique()
            ->count();

        if ($totalUniqueDates > 0) {
            return collect([
                (object) [
                    'id' => $employeeId,
                    'name' => auth()->user()->name,
                    'designation' => '',
                    'leave_count' => $totalUniqueDates
                ]
            ]);
        }

        return collect();

    }

    private function getLateEmployeesData()
    {
        $range = request('late_range', 'today');
        $employeeFilter = request('late_employee');

        [$startDate, $endDate] = $this->getLateDateRange($range);

        $roleSlug = auth()->user()->role;

        $roleId = DB::table('roles_master')
            ->where('slug', $roleSlug)
            ->value('id');

        $isAdmin = in_array($roleId, [1, 2, 3, 4]);
        $isTeamLeader = ($roleId == 5);
        $employeeId = auth()->user()->employee_id;

        // Logged-in employee department
        $leaderDepartment = Employee::active()->where('id', $employeeId)
            ->value('department');

        $attendanceRecords = Attendance::with('employee')
            ->whereBetween('attendance_date', [$startDate, $endDate])

            // Inactive employees should never surface in the late-arrivals widget.
            ->whereHas('employee.user', function ($q) {
                $q->where('account_status', 'active');
            })

            // For USER → force only logged-in employee
            ->when(!$isAdmin && !$isTeamLeader, function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId);
            })

            // TEAM LEADER → same department employees
            ->when($isTeamLeader, function ($q) use ($leaderDepartment, $employeeFilter) {

                $q->whereHas('employee', function ($sub) use ($leaderDepartment, $employeeFilter) {

                    $sub->where('department', $leaderDepartment);

                    // optional employee filter
                    if ($employeeFilter) {
                        $sub->where('id', $employeeFilter);
                    }
                });
            })

            // For ADMIN → keep old filter behavior
            ->when($isAdmin && $employeeFilter, function ($q) use ($employeeFilter) {
                $q->where('employee_id', $employeeFilter);
            })
            ->get();

        $lateRecords = $attendanceRecords
            ->map(function ($item) {
                $item->late_minutes = $this->getAttendanceLateMinutes($item);

                return $item;
            })
            ->filter(fn ($item) => $item->late_minutes > 0);

        return $lateRecords->groupBy('employee_id')->map(function ($records) {

            $totalLateMinutes = 0;

            foreach ($records as $item) {
                $totalLateMinutes += $item->late_minutes;
            }

            $employee = $records->first()->employee;

            $hours = floor($totalLateMinutes / 60);
            $minutes = $totalLateMinutes % 60;

            return [
                'employee' => $employee,
                'late_duration' => $hours > 0
                    ? $hours . ' hr ' . $minutes . ' min'
                    : $minutes . ' min',
                'late_days' => $records->count(), // ✅ optional but useful
            ];
        });
    }

    /**
     * Late arrival = check-in after the employee's scheduled shift start, full stop.
     * Check-out time and hours worked have no bearing on whether the arrival was late.
     */
    private function getAttendanceLateMinutes(Attendance $attendance): int
    {
        if (!$attendance->employee || !$attendance->check_in) {
            return 0;
        }

        $checkIn = $this->parseAttendancePunch($attendance, $attendance->check_in);
        [$shiftStart] = $this->getAttendanceShiftWindow($attendance);

        return max(intdiv($checkIn->timestamp - $shiftStart->timestamp, 60), 0);
    }

    private function parseAttendancePunch(Attendance $attendance, $time): Carbon
    {
        $date = Carbon::parse($attendance->attendance_date)->toDateString();

        return Carbon::parse($date . ' ' . Carbon::parse($time)->format('H:i:s'));
    }

    private function getAttendanceShiftWindow(Attendance $attendance): array
    {
        $date = Carbon::parse($attendance->attendance_date)->toDateString();
        $employee = $attendance->employee;
        $isSunday = Carbon::parse($date)->isSunday();

        $timeIn = ($isSunday && $employee->sunday_time_in)
            ? $employee->sunday_time_in
            : ($employee->time_in ?? '09:30:00');
        $timeOut = ($isSunday && $employee->sunday_time_out)
            ? $employee->sunday_time_out
            : ($employee->time_out ?? '18:00:00');

        try {
            $shiftStart = Carbon::parse($date . ' ' . Carbon::parse($timeIn)->format('H:i:s'));
            $shiftEnd = Carbon::parse($date . ' ' . Carbon::parse($timeOut)->format('H:i:s'));
        } catch (\Exception $e) {
            $shiftStart = Carbon::parse($date . ' 09:30:00');
            $shiftEnd = Carbon::parse($date . ' 18:00:00');
        }

        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        return [$shiftStart, $shiftEnd];
    }


    /* private function getLateEmployeesData()
    {
        $range = request('late_range', 'today');
        $employeeFilter = request('late_employee');

        [$startDate, $endDate] = $this->getLateDateRange($range);

        $requiredWorkMinutes = 510; // 8h 30m

        $records = Attendance::with('employee')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->when($employeeFilter, fn($q) => $q->where('employee_id', $employeeFilter))
            ->orderBy('attendance_date')
            ->get()
            ->groupBy('employee_id');

        return $records->map(function ($employeeRecords) use ($requiredWorkMinutes) {

            $balanceMinutes = 0; // +ve = extra, -ve = late

            foreach ($employeeRecords as $item) {

                if (!$item->check_in || !$item->check_out) {
                    continue;
                }

                $checkIn = Carbon::parse($item->check_in);
                $checkOut = Carbon::parse($item->check_out);

                // Total worked minutes
                $workedMinutes = $checkIn->diffInMinutes($checkOut);

                // CORE LOGIC (IMPORTANT)
                $balanceMinutes += ($workedMinutes - $requiredWorkMinutes);
            }

            $employee = $employeeRecords->first()->employee;

            // Only negative = late
            $finalLate = abs(min(0, $balanceMinutes));

            // Format
            $hours = floor($finalLate / 60);
            $minutes = $finalLate % 60;

            $lateDuration = $finalLate > 0
                ? ($hours > 0 ? "$hours hr $minutes min" : "$minutes min")
                : '0 min';

            return [
                'employee' => $employee,
                'late_duration' => $lateDuration
            ];
        })
        ->filter(fn($emp) => $emp['late_duration'] !== '0 min');
    } */

    private function getLateDateRange($range)
    {
        $today = Carbon::today();

        switch ($range) {
            case 'yesterday':
                return [
                    $today->copy()->subDay(),
                    $today->copy()->subDay()
                ];
            case 'week':
                return [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()];

            case 'month':
                return [$today->copy()->startOfMonth(), $today];

            case 'last_month':
                return [
                    $today->copy()->subMonth()->startOfMonth(),
                    $today->copy()->subMonth()->endOfMonth()
                ];

            case '3months':
                return [$today->copy()->subMonths(3)->startOfMonth(), $today];

            case 'year':
                return [$today->copy()->startOfYear(), $today];

            case 'custom':
                $start = request('late_custom_start');
                $end = request('late_custom_end');

                return [
                    $start ? Carbon::parse($start) : $today,
                    $end ? Carbon::parse($end) : $today
                ];

            default:
                return [$today, $today];
        }
    }

    private function getTodayCelebration($employee)
    {
        $today = Carbon::today()->startOfDay();
        $currentYear = now()->year;

        $isBirthdayToday = false;
        $isBirthdayThisMonth = false;
        $showBirthdayCard = false;
        $birthday = null;
        $birthdayFormatted = null;
        $daysUntilBirthday = null;
        $daysSinceBirthday = null;

        if ($employee && !empty($employee->date_of_birth)) {
            $dob = Carbon::parse($employee->date_of_birth);
            $birthdayThisYear = $dob->copy()->year($currentYear)->startOfDay();

            $isBirthdayToday = $birthdayThisYear->isToday();
            $isBirthdayThisMonth = $dob->month === now()->month;
            $birthday = $birthdayThisYear;
            $birthdayFormatted = $birthdayThisYear->format('d M');
            $daysUntilBirthday = $today->lt($birthdayThisYear)
                ? $today->diffInDays($birthdayThisYear)
                : 0;
            $daysSinceBirthday = $today->gt($birthdayThisYear)
                ? $birthdayThisYear->diffInDays($today)
                : 0;

            $withinBirthdayWindow = false;
            if ($isBirthdayThisMonth) {
                if ($today->lte($birthdayThisYear)) {
                    $withinBirthdayWindow = true;
                } else {
                    $withinBirthdayWindow = $daysSinceBirthday <= 7;
                }
            }

            $dismissedYear = auth()->user()->birthday_wish_dismissed_year;
            $showBirthdayCard = $withinBirthdayWindow
                && ($dismissedYear === null || (int) $dismissedYear < $currentYear);
        }

        $isAnniversaryToday = false;
        $anniversary = null;
        $years = 0;

        if ($employee && !empty($employee->date_of_joining)) {
            $joiningDate = Carbon::parse($employee->date_of_joining);
            $anniversary = $joiningDate->copy()->year($currentYear);

            if ($anniversary->isPast() && !$anniversary->isToday()) {
                $anniversary->addYear();
            }

            $years = $joiningDate->diffInYears($anniversary);
            $isAnniversaryToday = $anniversary->isToday() && $years > 0;
        }

        return [
            'isBirthdayToday' => $isBirthdayToday,
            'isBirthdayThisMonth' => $isBirthdayThisMonth,
            'showBirthdayCard' => $showBirthdayCard,
            'birthday' => $birthday,
            'birthdayFormatted' => $birthdayFormatted,
            'daysUntilBirthday' => $daysUntilBirthday,
            'daysSinceBirthday' => $daysSinceBirthday,
            'isAnniversaryToday' => $isAnniversaryToday,
            'anniversary' => $anniversary,
            'years' => $years,
        ];
    }

    public function dismissBirthdayWish(Request $request)
    {
        $user = $request->user();
        $employee = Employee::active()->where('id', $user->employee_id)->first();

        if (!$employee || empty($employee->date_of_birth)) {
            return response()->json(['success' => false, 'message' => 'Birthday not found.'], 422);
        }

        $dob = Carbon::parse($employee->date_of_birth);
        $today = Carbon::today()->startOfDay();
        $birthdayThisYear = $dob->copy()->year(now()->year)->startOfDay();

        $withinBirthdayWindow = false;
        if ($dob->month === now()->month) {
            if ($today->lte($birthdayThisYear)) {
                $withinBirthdayWindow = true;
            } else {
                $withinBirthdayWindow = $birthdayThisYear->diffInDays($today) <= 7;
            }
        }

        if (!$withinBirthdayWindow) {
            return response()->json(['success' => false, 'message' => 'Birthday card is not active right now.'], 422);
        }

        $user->update([
            'birthday_wish_dismissed_year' => now()->year,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get Full Year Breakdown (Requested by User)
     */
    public function getFullYearBreakdown(Request $request)
    {
        $this->authorizePayrollAnalytics();

        $year = $request->get('year', date('Y'));

        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $m = Carbon::createFromDate($year, $i, 1)->format('Y-m');
            $mLabel = Carbon::createFromDate($year, $i, 1)->format('F');

            $data[] = [
                'month' => $mLabel,
                'total_gross' => Payroll::where('month', $m)->sum('gross_salary'),
                'total_net' => Payroll::where('month', $m)->sum('net_salary'),
                'staff_count' => Payroll::where('month', $m)->count(),
                'status' => Payroll::where('month', $m)->where('status', 'pending')->exists() ? 'Pending' : 'Completed'
            ];
        }

        return response()->json([
            'success' => true,
            'year' => $year,
            'breakdown' => $data
        ]);
    }

    public function getMonthlySummary(Request $request)
    {
        $this->authorizePayrollAnalytics();

        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));
        $selectedCarbon = Carbon::parse($selectedMonth . '-01');

        $history = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = (clone $selectedCarbon)->subMonths($i);
            $mValue = $m->format('Y-m');
            $mLabel = $m->format('M Y');

            $basic = Payroll::where('month', $mValue)->sum('basic_salary');
            $hra = Payroll::where('month', $mValue)->sum('hra');
            $medical = Payroll::where('month', $mValue)->sum('medical_allowance');
            $conveyance = Payroll::where('month', $mValue)->sum('conveyance_allowance');
            $other_allw = Payroll::where('month', $mValue)->sum('other_allowance');

            $pf = Payroll::where('month', $mValue)->sum('pf_deduction');
            $esi = Payroll::where('month', $mValue)->sum('esi_deduction');
            $other_ded = Payroll::where('month', $mValue)->sum('other_deduction');

            $history[] = [
                'month' => $mLabel,
                'earnings' => $basic + $hra + $medical + $conveyance + $other_allw,
                'deductions' => $pf + $esi + $other_ded,
                'net' => Payroll::where('month', $mValue)->sum('net_salary'),
                'details' => [
                    'basic' => $basic,
                    'hra' => $hra,
                    'medical' => $medical,
                    'conveyance' => $conveyance,
                    'other_allw' => $other_allw,
                    'pf' => $pf,
                    'esi' => $esi,
                    'other_ded' => $other_ded
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'selectedMonth' => $selectedMonth,
            'history' => $history,
            // Keep current for backward compatibility if needed in old scripts
            'current' => end($history)
        ]);
    }

    public function getChartData(Request $request)
    {
        $this->authorizePayrollAnalytics();

        $range = (int) $request->get('range', 6);
        $chartMonths = [];
        $chartTotal = [];
        $chartPaid = [];
        $chartPending = [];
        $chartRejected = [];

        for ($i = ($range - 1); $i >= 0; $i--) {
            $m = Carbon::now()->startOfMonth()->subMonths($i);
            $mLabel = $m->format('M/y');
            $mValue = $m->format('Y-m');

            $chartMonths[] = $mLabel;
            $chartTotal[] = Payroll::where('month', $mValue)->sum('gross_salary');
            $chartPaid[] = Payroll::where('month', $mValue)->where('status', 'paid')->sum('net_salary');
            $chartPending[] = Payroll::where('month', $mValue)->where('status', 'pending')->sum('net_salary');
            $chartRejected[] = Payroll::where('month', $mValue)->where('status', 'rejected')->sum('net_salary');
        }

        return response()->json([
            'success' => true,
            'labels' => $chartMonths,
            'series' => [
                [
                    'name' => 'Total Payroll',
                    'data' => $chartTotal
                ],
                [
                    'name' => 'Paid',
                    'data' => $chartPaid
                ],
                [
                    'name' => 'Pending',
                    'data' => $chartPending
                ]
            ]
        ]);
    }

    public function fetchLeaveReport(Request $request)
    {
        $leaveReport = $this->getLeaveReport($request);

        $employeeLabel = 'All Employees';
        if ($request->employee_id) {
            $employee = Employee::active()->find($request->employee_id);
            $employeeLabel = $employee?->name ?? 'All Employees';
        }

        return response()->json([
            'success' => true,
            'html' => view('dashboard.partials.leave-report-rows', compact('leaveReport'))->render(),
            'employee_label' => $employeeLabel,
            'range_label' => $this->getLeaveRangeLabel($request),
        ]);
    }

    private function getLeaveRangeLabel(Request $request): string
    {
        if ($request->leave_from && $request->leave_to) {
            return Carbon::parse($request->leave_from)->format('d M Y')
                . ' → '
                . Carbon::parse($request->leave_to)->format('d M Y');
        }

        return match ($request->leave_filter) {
            'week' => 'Last Week',
            'month' => 'Last Month',
            '3month' => 'Last 3 Months',
            '6month' => 'Last 6 Months',
            'year' => 'Last 1 Year',
            default => 'Current Month',
        };
    }

    public function fetchLateArrivals(Request $request)
    {
        $todayLateEmployees = $this->getLateEmployeesData();

        $employeeLabel = 'All Employees';
        if ($request->late_employee) {
            $employee = Employee::active()->find($request->late_employee);
            $employeeLabel = $employee?->name ?? 'All Employees';
        }

        return response()->json([
            'success' => true,
            'html' => view('dashboard.partials.late-arrivals-list', compact('todayLateEmployees'))->render(),
            'employee_label' => $employeeLabel,
            'range_label' => $this->getLateRangeLabel($request),
            'count' => $todayLateEmployees->count(),
        ]);
    }

    private function getLateRangeLabel(Request $request): string
    {
        if ($request->late_range === 'custom' && $request->late_custom_start && $request->late_custom_end) {
            return Carbon::parse($request->late_custom_start)->format('d M Y')
                . ' → '
                . Carbon::parse($request->late_custom_end)->format('d M Y');
        }

        return match ($request->get('late_range', 'today')) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'week' => 'Last Week',
            'month' => 'Current Month',
            'last_month' => 'Last Month',
            '3months' => '3 Months',
            'year' => '1 Year',
            default => 'Today',
        };
    }
}
