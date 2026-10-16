<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationLog;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Exports\LeaveApplicationsExport;
use App\Services\LeaveApprovalService;
use App\Services\LeaveBalanceService;
use App\Services\LeaveNotificationService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
// use App\Mail\LeaveApplicationMail;
// use App\Mail\LeaveStatusUpdatedMail;

class LeaveApplicationController extends Controller
{
    public function __construct(
        private LeaveBalanceService $leaveBalanceService,
        private LeaveApprovalService $approval,
        private LeaveNotificationService $notifications,
    ) {
    }

    /**
     * Can this team leader see this employee's leave data? (Plain employees in any
     * department they lead — own department plus additionally led ones.)
     */
    private function teamLeaderCanView($user, ?Employee $employee): bool
    {
        return $this->approval->isTeamLeaderOf($user, $employee);
    }

    private function getHolidayDatesBetween(Carbon $startDate, Carbon $endDate): array
    {
        return Holiday::whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }

    /**
     * @return string[] Y-m-d dates, excluding Sundays and holidays.
     */
    private function getWorkingDatesBetween(string $startDate, ?string $endDate = null): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate ?: $startDate)->startOfDay();

        if ($end->lt($start)) {
            return [];
        }

        $holidayDates = $this->getHolidayDatesBetween($start, $end);
        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isSunday() || in_array($date->toDateString(), $holidayDates, true)) {
                continue;
            }

            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    private function calculateWorkingLeaveDays(string $startDate, ?string $endDate = null): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate ?: $startDate)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $holidayDates = $this->getHolidayDatesBetween($start, $end);
        $count = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isSunday()) {
                continue;
            }

            if (in_array($date->toDateString(), $holidayDates, true)) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    private function shouldSkipNonWorkingDays(LeaveApplication $leave): bool
    {
        $normalizedCategory = strtolower($leave->leave_category);
        $normalizedType = strtolower($leave->leave_type ?? '');

        return !str_contains($normalizedCategory, 'gatepass')
            && !str_contains($normalizedType, 'half');
    }

    private function applyCategoryFilter($query, string $category): void
    {
        $normalizedCategory = strtolower(trim($category));

        if ($normalizedCategory === 'early leave') {
            $query->where('leave_category', 'LIKE', '%Gatepass Leave%');
            return;
        }

        if ($normalizedCategory === 'wfh') {
            $query->where('leave_category', 'LIKE', '%wfh%');
            return;
        }

        $query->where('leave_category', 'LIKE', '%' . $category . '%');
    }

    private function getAttendanceStatusFromLeave(string $leaveCategory, ?string $leaveType = null): string
    {
        $normalizedCategory = strtolower($leaveCategory);
        $normalizedType = strtolower($leaveType ?? '');

        if (str_contains($normalizedType, 'half')) {
            return 'half_day';
        }

        if (str_contains($normalizedCategory, 'wfh')) {
            return 'wfh';
        }

        if (str_contains($normalizedCategory, 'gatepass')) {
            return 'early_leave';
        }

        return 'leave';
    }

    private function getAttendanceHoursFromLeave(string $leaveCategory, ?string $leaveType = null): int
    {
        $normalizedCategory = strtolower($leaveCategory);
        $normalizedType = strtolower($leaveType ?? '');

        if (str_contains($normalizedType, 'half')) {
            return 4;
        }

        if (str_contains($normalizedCategory, 'wfh')) {
            return 8;
        }

        return 0;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, [
            'super_admin',
            'manager',
            'hr_executive'
        ]);

        $isTeamLeader = in_array($role, [
            'team_leader',
            'business_operation_head'
        ]);

        $query = LeaveApplication::with('employee');

        // Search Filters
        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->search . '%');
            });
        }
        if ($request->filled('category')) {
            $this->applyCategoryFilter($query, $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Overlap match: include leaves that span into the range, not only ones starting in it.
        if ($request->filled('from_date')) {
            $query->whereDate(DB::raw('COALESCE(end_date, start_date)'), '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('start_date', '<=', $request->to_date);
        }

        if ($isAdmin) {
            $employees = Employee::active()->get();
        } elseif ($isTeamLeader) {
            // Plain "employee" role only, in every department this TL leads (own + additional) —
            // not other team leaders, managers, HR, etc. who happen to share the department.
            $query->whereHas('employee', fn ($q) => $this->approval->scopeEmployeesForTeamLeader($q, $user));
            $employees = $this->approval->scopeEmployeesForTeamLeader(Employee::active(), $user)->get();
        } else {
            $query->where('employee_id', $user->employee_id);
            $employees = Employee::active()->where('id', $user->employee_id)->get();
        }

        // Two-level approval: "Awaiting my approval" filter + count for TLs and managers.
        $awaitingQuery = $this->approval->awaitingQuery($user);
        $awaitingCount = $awaitingQuery ? (clone $awaitingQuery)->count() : 0;
        if ($request->boolean('awaiting') && $awaitingQuery) {
            $query->whereIn('id', (clone $awaitingQuery)->select('id'));
        }

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $leaveStats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->whereIn('status', ['rejected', 'unauthorised'])->count(),
        ];

        $leaves = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Rows this TL can act on right now (Level 1).
        $tlActionableIds = $isTeamLeader
            ? $leaves->getCollection()->filter(fn ($l) => $this->approval->canTeamLeaderAct($user, $l))->pluck('id')->all()
            : [];
        // Rows this user can take the Level 2 decision on.
        $managerActionableIds = $leaves->getCollection()
            ->filter(fn ($l) => $this->approval->canManagerAct($user, $l))
            ->pluck('id')->all();
        $holidays = Holiday::pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->values();

        return view('leave.history', compact('leaves', 'employees', 'holidays', 'perPage', 'leaveStats', 'isAdmin', 'isTeamLeader', 'awaitingCount', 'tlActionableIds', 'managerActionableIds'));
    }

    public function store(Request $request)
    {
        // Who is the leave for? Employees can only apply for themselves; team leaders for
        // themselves or their own team; admins/HR for anyone. Never trust a posted ID blindly.
        $user = auth()->user();
        $role = LeaveApprovalService::role($user->role);
        $isAdminRole = in_array($role, LeaveApprovalService::MANAGER_ROLES, true);
        $postedEmployeeId = $request->input('employee_id');

        if ($isAdminRole) {
            $employeeId = $postedEmployeeId;
        } elseif (in_array($role, ['team_leader', 'business_operation_head'], true) && $postedEmployeeId && (int) $postedEmployeeId !== (int) $user->employee_id) {
            if (!$this->approval->isTeamLeaderOf($user, Employee::find($postedEmployeeId))) {
                return response()->json(['success' => false, 'message' => 'You can only apply leave for yourself or your own team members.'], 403);
            }
            $employeeId = $postedEmployeeId;
        } else {
            $employeeId = $user->employee_id;
        }

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'errors' => ['employee_id' => [$isAdminRole
                    ? 'Please select the employee.'
                    : 'Your login is not linked to an employee profile, so leave cannot be applied. Please contact HR.']],
                'message' => 'Please select the employee.',
            ], 422);
        }
        $request->merge(['employee_id' => $employeeId]);

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'nullable|string|max:100',
            'leave_category' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => ['nullable', 'required_if:leave_category,Gatepass Leave', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'reason' => 'required|string|max:2000',
            'message' => 'nullable|string|max:5000',
        ], [
            'employee_id.required' => 'Please select the employee.',
            'employee_id.exists' => 'The selected employee was not found.',
            'leave_category.required' => 'Please select a leave category.',
            'start_date.required' => 'Please choose the start date.',
            'start_date.date' => 'Please choose a valid start date.',
            'end_date.after_or_equal' => 'End date cannot be before the start date.',
            'start_time.required_if' => 'Please choose the time for early leave.',
            'start_time.regex' => 'Please choose a valid time for early leave.',
            'reason.required' => 'Please enter the reason for leave.',
            'reason.max' => 'Reason is too long (max 2000 characters).',
        ]);

        $data = $request->only(['employee_id', 'leave_type', 'leave_category', 'start_date', 'end_date', 'reason', 'message', 'total_days', 'start_time', 'end_time']);
        $data['status'] = 'pending';

        if ($request->leave_category === 'Gatepass Leave') {
            $data['leave_type'] = 'Early Leave';
            $data['total_days'] = 0.125; // 1 hour
        } elseif (str_contains(strtolower($request->leave_type ?? ''), 'half')) {
            $data['total_days'] = 0.5;
        } else {
            $data['total_days'] = $this->calculateWorkingLeaveDays(
                $request->start_date,
                $request->end_date ?? $request->start_date
            );
        }

        if ($request->leave_category === 'Gatepass Leave') {
            $data['end_date'] = $request->start_date;
            if ($request->filled('start_time')) {
                try {
                    $startTime = \Carbon\Carbon::parse($request->start_time);
                    $data['end_time'] = $startTime->copy()->addHour()->format('H:i');
                } catch (\Exception $e) {
                    // Fallback or ignore if time format is invalid
                }
            }
        } else {
            $data['end_date'] = $request->end_date ?? $request->start_date;
        }

        if ((float) $data['total_days'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'The selected dates fall only on Sundays/holidays — there are no working days to apply leave for.',
            ], 422);
        }

        $employee = Employee::active()->find($data['employee_id']);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'This employee is not active, so leave cannot be applied for them.',
            ], 422);
        }

        // Level 1 (Team Leader) first when the employee has one, otherwise straight to the manager.
        $data = array_merge($data, $this->approval->initialState($employee));

        // Serialise submissions per employee so a double click / two tabs can't both
        // pass the duplicate check before either row is written.
        $lock = Cache::lock('leave-apply:' . $employee->id, 10);
        if (!$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'A leave request for this employee is already being submitted. Please wait.',
            ], 429);
        }

        try {
            if ($conflict = $this->findConflictingLeave($data)) {
                $when = $conflict->start_date->format('d M Y')
                    . ($conflict->end_date && $conflict->end_date->gt($conflict->start_date) ? ' – ' . $conflict->end_date->format('d M Y') : '');

                return response()->json([
                    'success' => false,
                    'duplicate' => true,
                    'message' => "{$employee->name} already has a {$conflict->leave_category} request for {$when} ("
                        . str_replace('_', ' ', $conflict->status) . '). It was not submitted again.',
                ], 409);
            }

            // The quota overflow can split one request into two applications — e-mail each one.
            $lastIdBefore = (int) LeaveApplication::max('id');
            $response = $this->createLeaveApplication($request, $data);

            LeaveApplication::where('employee_id', $employee->id)
                ->where('id', '>', $lastIdBefore)
                ->get()
                ->each(function (LeaveApplication $created) {
                    LeaveApplicationLog::record($created, 'applied', 'employee', null, 'pending');
                    $this->notifications->applied($created);
                });

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * An existing live application (anything not rejected) that clashes with the new one:
     *  - WFH only clashes with WFH on overlapping days;
     *  - early leave (gatepass) clashes with an early leave at the same time, or a full-day leave that day;
     *  - half day clashes with the same half, or a full-day leave that day;
     *  - a full-day leave clashes with any other leave on overlapping days.
     * Category is ignored on purpose: a quota overflow may have saved the first request as Casual Leave.
     */
    private function findConflictingLeave(array $data): ?LeaveApplication
    {
        $start = Carbon::parse($data['start_date'])->toDateString();
        $end = Carbon::parse($data['end_date'] ?? $data['start_date'])->toDateString();
        $kind = fn ($category, $type) => match (true) {
            str_contains(strtolower((string) $category), 'wfh') => 'wfh',
            str_contains(strtolower((string) $category), 'gatepass') => 'gatepass',
            str_contains(strtolower((string) $type), 'half') => 'half',
            default => 'full',
        };
        $newKind = $kind($data['leave_category'] ?? '', $data['leave_type'] ?? '');

        $candidates = LeaveApplication::where('employee_id', $data['employee_id'])
            ->whereNotIn('status', ['rejected', 'Rejected'])
            ->whereDate('start_date', '<=', $end)
            ->whereRaw('DATE(COALESCE(end_date, start_date)) >= ?', [$start])
            ->orderBy('start_date')
            ->get();

        foreach ($candidates as $existing) {
            $oldKind = $kind($existing->leave_category, $existing->leave_type);

            if (($newKind === 'wfh') !== ($oldKind === 'wfh')) {
                continue; // WFH and actual leave may overlap (leave wins for that day)
            }
            if ($newKind === 'wfh' || $newKind === 'full' || $oldKind === 'full') {
                return $existing;
            }
            if ($newKind === 'gatepass' && $oldKind === 'gatepass') {
                $sameTime = empty($data['start_time']) || !$existing->start_time
                    || Carbon::parse($existing->start_time)->format('H:i') === Carbon::parse($data['start_time'])->format('H:i');
                if ($sameTime) {
                    return $existing;
                }
                continue;
            }
            if ($newKind === 'half' && $oldKind === 'half'
                && strcasecmp(trim((string) $existing->leave_type), trim((string) ($data['leave_type'] ?? ''))) === 0) {
                return $existing; // same half of the same day
            }
        }

        return null;
    }

    private function createLeaveApplication(Request $request, array $data)
    {
        // Only Paid/Sick Leave draw down the shared monthly quota and can overflow.
        // Casual Leave, Gatepass (early leave) and WFH are never restricted.
        $quotaCheckedCategories = ['Paid Leave', 'Sick Leave'];

        if (in_array($request->leave_category, $quotaCheckedCategories, true) && (float) $data['total_days'] > 0) {
            $balance = $this->leaveBalanceService->getEmployeeBalanceSummary(
                $data['employee_id'],
                Carbon::parse($data['start_date'])
            )['balance'];

            if ((float) $data['total_days'] > $balance) {
                $this->createLeaveWithOverflowAsCasual($data, $balance);

                return response()->json([
                    'success' => true,
                    'message' => 'Leave quota exceeded — the remaining days were submitted as Casual Leave.',
                ]);
            }
        }

        LeaveApplication::create($data);

        // Mail::to(env('LEAVE_APPROVER_EMAIL'))
        //     ->send((new LeaveApplicationMail($leave, $employee))->replyTo($employee->email));

        return response()->json(['success' => true, 'message' => 'Leave application submitted successfully']);
    }

    /**
     * Split a Paid/Sick Leave request that exceeds the remaining balance: the days that
     * fit stay under the original category, the rest are submitted as Casual Leave
     * (which has no quota cap). Half-day requests aren't split — they convert wholesale.
     */
    private function createLeaveWithOverflowAsCasual(array $data, float $balance): void
    {
        $isHalfDay = (float) $data['total_days'] === 0.5;

        if ($isHalfDay) {
            if ($balance >= 0.5) {
                LeaveApplication::create($data);
                return;
            }

            $casualData = $data;
            $casualData['leave_category'] = 'Casual Leave';
            LeaveApplication::create($casualData);
            return;
        }

        $workingDates = $this->getWorkingDatesBetween($data['start_date'], $data['end_date']);
        $takeCount = max(0, (int) floor($balance));

        if ($takeCount <= 0) {
            $casualData = $data;
            $casualData['leave_category'] = 'Casual Leave';
            LeaveApplication::create($casualData);
            return;
        }

        if ($takeCount >= count($workingDates)) {
            // Shouldn't happen (we only get here when total_days > balance), but fall
            // back to the original single application rather than lose the request.
            LeaveApplication::create($data);
            return;
        }

        $originalData = $data;
        $originalData['end_date'] = $workingDates[$takeCount - 1];
        $originalData['total_days'] = $takeCount;
        LeaveApplication::create($originalData);

        $casualData = $data;
        $casualData['leave_category'] = 'Casual Leave';
        $casualData['start_date'] = $workingDates[$takeCount];
        $casualData['total_days'] = count($workingDates) - $takeCount;
        LeaveApplication::create($casualData);
    }

    public function export(Request $request)
    {
        $query = LeaveApplication::with('employee');

        if (in_array(str_replace(' ', '_', strtolower((string) auth()->user()->role)), ['team_leader', 'business_operation_head'], true)) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', auth()->user()->employee->department_id);
            });
        }
        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->search . '%');
            });
        }
        if ($request->filled('category')) {
            $this->applyCategoryFilter($query, $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Overlap match: include leaves that span into the range, not only ones starting in it.
        if ($request->filled('from_date')) {
            $query->whereDate(DB::raw('COALESCE(end_date, start_date)'), '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('start_date', '<=', $request->to_date);
        }

        $leaves = $query->orderBy('created_at', 'desc')->get();
        $filename = "leave_applications_" . date('Y-m-d_H-i-s') . ".xlsx";

        return Excel::download(new LeaveApplicationsExport($leaves), $filename);
    }

    /**
     * Fill in Attendance rows for every day of an approved leave. Idempotent: uses
     * updateOrCreate and skips any day that already has real biometric punches.
     */
    public function applyApprovedLeaveToAttendance(LeaveApplication $leave): void
    {
        $startDate = Carbon::parse($leave->start_date);
        $endDate = $leave->end_date ? Carbon::parse($leave->end_date) : $startDate->copy();
        $holidayDates = $this->getHolidayDatesBetween($startDate, $endDate);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (
                $this->shouldSkipNonWorkingDays($leave)
                && ($date->isSunday() || in_array($date->toDateString(), $holidayDates, true))
            ) {
                continue;
            }

            // A WFH range must not overwrite days inside it that have an approved actual leave.
            if ($leave->isWfh() && $this->hasApprovedActualLeaveOn($leave, $date->toDateString())) {
                continue;
            }

            $existing = Attendance::where('employee_id', $leave->employee_id)
                ->where('attendance_date', $date->format('Y-m-d'))
                ->first();

            $hasBothPunches = $existing && $existing->check_in !== null && $existing->check_out !== null;

            // A full-day leave approved for a day that already has real biometric
            // punches must not overwrite them — the employee was genuinely present
            // that day. Half-day leave and Gatepass (early leave) are different:
            // partial punches are expected there, so creditApprovedLeave() corrects
            // the status to reflect the approved leave without touching the real
            // check-in/out/hours. This mirrors the credit applied on every biometric
            // re-sync (AttendanceService::processEmployeePunchSet), so it isn't
            // reverted the next time attendance syncs.
            if ($existing && ($existing->check_in !== null || $existing->check_out !== null)) {
                if ($hasBothPunches) {
                    $existing->update([
                        'status' => \App\Services\AttendanceStatusService::creditApprovedLeave(
                            $existing->status ?? '',
                            (float) ($existing->total_hours ?? 0),
                            true,
                            $leave->leave_type ?? '',
                            $leave->leave_category ?? '',
                            \App\Services\AttendanceStatusService::fullDayHoursForRecord($existing)
                        ),
                    ]);
                }
                continue;
            }

            Attendance::updateOrCreate(
                [
                    'employee_id' => $leave->employee_id,
                    'attendance_date' => $date->format('Y-m-d')
                ],
                [
                    'status' => $this->getAttendanceStatusFromLeave(
                        $leave->leave_category,
                        $leave->leave_type
                    ),
                    'total_hours' => $this->getAttendanceHoursFromLeave(
                        $leave->leave_category,
                        $leave->leave_type
                    ),
                    'check_in' => null,
                    'check_out' => null
                ]
            );
        }
    }

    private function hasApprovedActualLeaveOn(LeaveApplication $leave, string $date): bool
    {
        $winner = LeaveApplication::where('employee_id', $leave->employee_id)
            ->where('status', 'approved')
            ->where('id', '!=', $leave->id)
            ->coveringDate($date)
            ->preferActualLeave()
            ->first();

        return $winner && !$winner->isWfh();
    }

    // After an approval is revoked and its attendance rows cleared, re-apply other
    // approved applications overlapping that range (e.g. a WFH around a revoked leave).
    private function reapplyOverlappingApprovedLeaves(LeaveApplication $revoked, Carbon $start, Carbon $end): void
    {
        LeaveApplication::where('employee_id', $revoked->employee_id)
            ->where('status', 'approved')
            ->where('id', '!=', $revoked->id)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->where(function ($q) use ($start) {
                $q->whereDate('end_date', '>=', $start->toDateString())
                    ->orWhere(function ($q2) use ($start) {
                        $q2->whereNull('end_date')->whereDate('start_date', '>=', $start->toDateString());
                    });
            })
            ->get()
            ->each(fn (LeaveApplication $other) => $this->applyApprovedLeaveToAttendance($other));
    }

    public function updateAction(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|exists:leave_applications,id',
            'status' => 'required|in:pending,approved,rejected,on_hold,unauthorised,unpaid',
            'remarks' => 'required|string|min:3|max:2000',
        ], [
            'remarks.required' => 'Please enter remarks / reason for this decision.',
        ]);

        $leave = LeaveApplication::with('employee')->findOrFail($request->leave_id);

        // Level 2: Manager for employees / TLs; HR or Super Admin for a manager's own leave.
        if (!$this->approval->canManagerAct(auth()->user(), $leave)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot take the final decision on this leave. A manager\'s leave is approved by HR or Super Admin, and nobody can approve their own leave.',
            ], 403);
        }

        $oldStatus = $leave->status;
        $newStatus = $request->status;

        if ($newStatus === 'approved' && $oldStatus !== 'approved') {
            $this->applyApprovedLeaveToAttendance($leave);
        } elseif ($oldStatus === 'approved' && $newStatus !== 'approved') {
            $startDate = Carbon::parse($leave->start_date);
            $endDate = $leave->end_date ? Carbon::parse($leave->end_date) : $startDate->copy();

            Attendance::where('employee_id', $leave->employee_id)
                ->where('attendance_date', '>=', $startDate->format('Y-m-d'))
                ->where('attendance_date', '<=', $endDate->format('Y-m-d'))
                ->whereIn('status', ['leave', 'half_day', 'wfh', 'early_leave'])
                ->delete();
        }

        // Level 2 (manager) decision with remarks; marks the TL level as bypassed if still open.
        $leave->update(array_merge(
            ['status' => $request->status],
            $this->approval->managerUpdate($leave, auth()->user(), $newStatus, trim($request->remarks))
        ));

        if ($oldStatus === 'approved' && $newStatus !== 'approved') {
            $this->reapplyOverlappingApprovedLeaves($leave, $startDate, $endDate);
        }

        LeaveApplicationLog::record($leave, 'manager_update', 'manager', strtolower((string) $oldStatus), $newStatus, trim($request->remarks));

        $this->notifications->managerDecided($leave, strtolower((string) $oldStatus));

        $employee = Employee::active()->find($leave->employee_id);

        // if ($employee && $employee->email) {
        //     Mail::to($employee->email)
        //         ->send(new LeaveStatusUpdatedMail($leave, $employee));
        // }

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }

    /** Level 1: the employee's Team Leader approves or rejects, with mandatory remarks. */
    public function teamLeaderAction(Request $request)
    {
        $validated = $request->validate([
            'leave_id' => 'required|exists:leave_applications,id',
            'decision' => 'required|in:approved,rejected',
            'remarks' => 'required|string|min:3|max:2000',
        ], [
            'remarks.required' => 'Please enter remarks / reason for this decision.',
        ]);

        $user = auth()->user();
        $leave = LeaveApplication::with('employee')->findOrFail($validated['leave_id']);

        if (!$this->approval->canTeamLeaderAct($user, $leave)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only act on pending Level 1 requests from your own team.',
            ], 403);
        }

        $decided = $this->approval->teamLeaderDecide($leave, $user, $validated['decision'], trim($validated['remarks']));
        $this->notifications->teamLeaderDecided($decided);

        return response()->json([
            'success' => true,
            'message' => $validated['decision'] === 'approved'
                ? 'Approved at Level 1 — sent to the manager for final approval.'
                : 'Leave request rejected.',
        ]);
    }

    public function destroy($id)
    {
        $leave = LeaveApplication::findOrFail($id);
        $leave->delete();
        return response()->json(['success' => true, 'message' => 'Leave application deleted']);
    }

    public function getDetails($id)
    {
        $leave = LeaveApplication::with('employee')->findOrFail($id);

        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive']);
        $isTeamLeader = in_array($role, ['team_leader', 'business_operation_head']);

        if ($isTeamLeader) {
            if (!$this->teamLeaderCanView($user, $leave->employee)) {
                return response()->json(['message' => 'Unauthorized access to employee data.'], 403);
            }
        } elseif (!$isAdmin && $leave->employee_id != $user->employee_id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json(array_merge(
            $leave->toArray(),
            $this->leaveBalanceService->getEmployeeBalanceSummary((int) $leave->employee_id),
            [
                'approval' => $this->approval->trail($leave),
                'can_tl_act' => $this->approval->canTeamLeaderAct($user, $leave),
                'can_manager_act' => $this->approval->canManagerAct($user, $leave),
            ]
        ));
    }

    public function getEmployeeLeaves($employeeId)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive']);
        $isTeamLeader = in_array($role, ['team_leader', 'business_operation_head']);

        if ($isTeamLeader) {
            if (!$this->teamLeaderCanView($user, Employee::find($employeeId))) {
                return response()->json(['message' => 'Unauthorized access to employee data.'], 403);
            }
        } elseif (!$isAdmin && $employeeId != $user->employee_id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $leaves = LeaveApplication::where('employee_id', $employeeId)
            ->orderBy('start_date', 'desc')
            ->get();
        return response()->json($leaves);
    }

    /**
     * Current Paid/Sick Leave balance for the apply-leave form, so the employee sees
     * their remaining quota before submitting. Casual Leave/WFH/Gatepass aren't quota
     * checked, so this balance is only meaningful for Paid/Sick Leave requests.
     */
    public function getEmployeeBalance($employeeId)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive']);
        $isTeamLeader = in_array($role, ['team_leader', 'business_operation_head']);

        if ($isTeamLeader) {
            if (!$this->teamLeaderCanView($user, Employee::find($employeeId))) {
                return response()->json(['message' => 'Unauthorized access to employee data.'], 403);
            }
        } elseif (!$isAdmin && $employeeId != $user->employee_id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json(
            $this->leaveBalanceService->getEmployeeBalanceSummary((int) $employeeId)
        );
    }
}
