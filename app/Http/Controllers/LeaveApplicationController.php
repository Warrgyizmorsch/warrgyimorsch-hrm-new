<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Exports\LeaveApplicationsExport;
use App\Services\LeaveBalanceService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
// use App\Mail\LeaveApplicationMail;
// use App\Mail\LeaveStatusUpdatedMail;

class LeaveApplicationController extends Controller
{
    public function __construct(private LeaveBalanceService $leaveBalanceService)
    {
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
            'hr_executive',
            'hr_intern',
            'business_operation_head'
        ]);

        $isTeamLeader = in_array($role, [
            'team_leader'
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
        if ($request->filled('from_date')) {
            $query->whereDate('start_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('start_date', '<=', $request->to_date);
        }

        if ($isAdmin) {
            $employees = Employee::active()->get();
        } elseif ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                // Same department AND plain "employee" role only — not other team leaders,
                // managers, HR, etc. who happen to share the department.
                $query->whereHas('employee', function ($q) use ($department) {
                    $q->where('department', $department)
                        ->whereRaw("LOWER(REPLACE(role, ' ', '_')) = 'employee'");
                });
                $employees = Employee::active()
                    ->where('department', $department)
                    ->whereRaw("LOWER(REPLACE(role, ' ', '_')) = 'employee'")
                    ->get();
            } else {
                $employees = collect();
            }
        } else {
            $query->where('employee_id', $user->employee_id);
            $employees = Employee::active()->where('id', $user->employee_id)->get();
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
        $holidays = Holiday::pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->values();

        return view('leave.history', compact('leaves', 'employees', 'holidays', 'perPage', 'leaveStats', 'isAdmin', 'isTeamLeader'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'nullable|string',
            'leave_category' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable',
            'reason' => 'required',
        ]);

        $data = $request->only(['employee_id', 'leave_type', 'leave_category', 'start_date', 'end_date', 'reason', 'message', 'total_days', 'start_time', 'end_time']);
        $data['status'] = 'pending';

        // Ensure employee_id is set (fallback for non-admin users)
        if (empty($data['employee_id'])) {
            $data['employee_id'] = auth()->user()->employee_id;
        }

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

        $employee = Employee::active()->findOrFail($data['employee_id']);

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

        if (auth()->user()->role == 'team_leader') {
            $query->whereHas('employee', function ($q) {
                $q->where('department', auth()->user()->employee->department);
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
        if ($request->filled('from_date')) {
            $query->whereDate('start_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('start_date', '<=', $request->to_date);
        }

        $leaves = $query->orderBy('created_at', 'desc')->get();
        $filename = "leave_applications_" . date('Y-m-d_H-i-s') . ".xlsx";

        return Excel::download(new LeaveApplicationsExport($leaves), $filename);
    }

    public function updateAction(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|exists:leave_applications,id',
            'status' => 'required|in:pending,approved,rejected,on_hold,unauthorised,unpaid',
        ]);

        $leave = LeaveApplication::findOrFail($request->leave_id);
        $oldStatus = $leave->status;
        $newStatus = $request->status;

        if ($newStatus === 'approved' && $oldStatus !== 'approved') {
            $startDate = Carbon::parse($leave->start_date);
            $endDate = $leave->end_date ? Carbon::parse($leave->end_date) : $startDate->copy();
            $holidayDates = $this->getHolidayDatesBetween($startDate, $endDate);

            if ($startDate->equalTo($endDate)) {
                $endDate->addDay();
            }

            for ($date = $startDate->copy(); $date->lt($endDate); $date->addDay()) {
                if (
                    $this->shouldSkipNonWorkingDays($leave)
                    && ($date->isSunday() || in_array($date->toDateString(), $holidayDates, true))
                ) {
                    continue;
                }

                $existing = Attendance::where('employee_id', $leave->employee_id)
                    ->where('attendance_date', $date->format('Y-m-d'))
                    ->first();

                // A gatepass/early-leave (or any leave) approved for a day that already has
                // real biometric punches must not overwrite them — the employee was
                // genuinely present that day. Only fill in the leave-derived status/hours
                // when there's no real attendance data to lose.
                if ($existing && ($existing->check_in !== null || $existing->check_out !== null)) {
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
        } elseif ($oldStatus === 'approved' && $newStatus !== 'approved') {
            $startDate = Carbon::parse($leave->start_date);
            $endDate = $leave->end_date ? Carbon::parse($leave->end_date) : $startDate->copy();

            if ($startDate->equalTo($endDate)) {
                $endDate->addDay();
            }

            Attendance::where('employee_id', $leave->employee_id)
                ->where('attendance_date', '>=', $startDate->format('Y-m-d'))
                ->where('attendance_date', '<', $endDate->format('Y-m-d'))
                ->whereIn('status', ['leave', 'half_day', 'wfh', 'early_leave'])
                ->delete();
        }

        $leave->update(['status' => $request->status]);

        $employee = Employee::active()->find($leave->employee_id);

        // if ($employee && $employee->email) {
        //     Mail::to($employee->email)
        //         ->send(new LeaveStatusUpdatedMail($leave, $employee));
        // }

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
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
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);

        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            $targetRole = str_replace(' ', '_', strtolower($leave->employee->role ?? ''));
            if (!$department || ($leave->employee->department ?? null) !== $department || $targetRole !== 'employee') {
                return response()->json(['message' => 'Unauthorized access to employee data.'], 403);
            }
        } elseif (!$isAdmin && $leave->employee_id != $user->employee_id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json(array_merge(
            $leave->toArray(),
            $this->leaveBalanceService->getEmployeeBalanceSummary((int) $leave->employee_id)
        ));
    }

    public function getEmployeeLeaves($employeeId)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);

        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            $targetEmployee = Employee::find($employeeId);
            $targetRole = $targetEmployee ? str_replace(' ', '_', strtolower($targetEmployee->role ?? '')) : null;
            if (!$department || !$targetEmployee || $targetEmployee->department !== $department || $targetRole !== 'employee') {
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
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);

        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            $targetEmployee = Employee::find($employeeId);
            $targetRole = $targetEmployee ? str_replace(' ', '_', strtolower($targetEmployee->role ?? '')) : null;
            if (!$department || !$targetEmployee || $targetEmployee->department !== $department || $targetRole !== 'employee') {
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
