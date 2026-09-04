<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\TaskFollowUp;
use App\Models\TaskStatusHistory;
use App\Services\AttendanceHistoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyTaskController extends Controller
{
    public function index(Request $request)
    {
        $query = DailyTask::with(['project', 'employee.departmentRef', 'creator', 'followUps'])->orderBy('start_date', 'desc');

        $ownEmployeeId = null;
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $adminRoles = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head', 'team_leader'];
        $isAdmin = in_array($role, $adminRoles);

        $otherProject = Project::where('name', 'OTHER')->first();
        if (!$otherProject) {
            $otherProject = Project::create([
                'name' => 'OTHER',
                'slug' => 'other',
                'status' => 'Ongoing',
                'description' => 'General tasks not related to a specific project',
            ]);
        }

        $otherProject->update(['members' => Employee::active()->pluck('id')->toArray()]);

        if ($role == 'team_leader') {
            $teamLeaderDepartmentIds = auth()->user()->employee?->ledDepartmentIds() ?? [];

            $departmentEmployeeIds = Employee::active()->whereIn('department_id', $teamLeaderDepartmentIds)
                ->pluck('id');

            $query->whereIn('employee_id', $departmentEmployeeIds);

            $employees = Employee::active()->whereIn('department_id', $teamLeaderDepartmentIds)->get();

            $projects = Project::where(function ($q) use ($departmentEmployeeIds, $teamLeaderDepartmentIds) {
                foreach ($departmentEmployeeIds as $employeeId) {
                    $q->orWhereJsonContains('members', (string) $employeeId);
                }
                if (!empty($teamLeaderDepartmentIds)) {
                    $q->orWhereHas('departments', fn ($dq) => $dq->whereIn('departments.id', $teamLeaderDepartmentIds));
                }
            })->orderBy('name')->get();
        } elseif (!$isAdmin) {
            $employeeId = auth()->user()->employee_id;

            $query->where('employee_id', $employeeId);

            $employees = Employee::active()->where('id', $employeeId)->get();

            $projects = Project::whereJsonContains('members', (string) $employeeId)
                ->orderBy('name')
                ->get();

            $ownEmployeeId = $employeeId;
        } else {
            $projects = Project::orderBy('name')->get();
            $employees = Employee::active()->orderBy('name')->get();
        }

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->status) {
            if ($request->status === 'Incomplete') {
                $query->where('status', 'Completed')
                    ->where(function ($sub) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw('COALESCE(SUM(CAST(time_taken AS DECIMAL(10,2))), 0)'))
                            ->from('task_follow_ups')
                            ->whereColumn('task_follow_ups.daily_task_id', 'daily_tasks.id');
                    }, '<', 8);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->from_date) {
            $query->where('start_date', '>=', $request->from_date);
        }
        if ($request->upto_date) {
            $query->where('end_date', '<=', $request->upto_date);
        }

        $selectedMonth = $request->get('month') ?: now()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        if (!$request->filled('from_date') && !$request->filled('upto_date')) {
            $query->where('start_date', '>=', $monthStart->toDateString())
                ->where('start_date', '<=', $monthEnd->toDateString());
        }

        // The employee stat cards must reflect whatever date range is actually
        // filtering the visible task list — prefer the explicit From/Upto range
        // over the month picker's default when both could apply.
        if ($request->filled('from_date') && $request->filled('upto_date')) {
            $statsRangeStart = Carbon::parse($request->from_date)->startOfDay();
            $statsRangeEnd = Carbon::parse($request->upto_date)->endOfDay();
        } else {
            $statsRangeStart = $monthStart;
            $statsRangeEnd = $monthEnd;
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('task_title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('project', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $statsBase = clone $query;
        $todayCount = (clone $statsBase)->whereDate('start_date', today())->count();
        $pendingCount = (clone $statsBase)->where('status', 'Pending')->count();
        $taskIds = (clone $statsBase)->pluck('id');
        $loggedHours = TaskFollowUp::whereIn('daily_task_id', $taskIds)->sum('time_taken');
        $lh = floor($loggedHours);
        $lm = round(($loggedHours - $lh) * 60);
        $loggedParts = [];
        if ($lh > 0) {
            $loggedParts[] = $lh . 'h';
        }
        if ($lm > 0) {
            $loggedParts[] = $lm . 'm';
        }
        $loggedHoursDisplay = count($loggedParts) > 0 ? implode(' ', $loggedParts) : '0m';

        $employeeMonthlyStats = null;
        $statsEmployeeId = $ownEmployeeId ?? $request->employee_id;
        if ($statsEmployeeId) {
            $history = app(AttendanceHistoryService::class)
                ->buildMonthlyHistory((int) $statsEmployeeId, $statsRangeStart, $statsRangeEnd);

            $presentDates = collect($history)
                ->filter(fn ($row) => in_array($row['status_key'], [
                    'present', 'present_activity', 'late',
                    'half_day', 'half_day_leave', 'wfh', 'early_out', 'early_leave',
                ], true))
                ->pluck('date_key');

            // Pulled from the Leave module directly (not derived from attendance status) —
            // attendance can fall back to a punch-based status like "half_day" for a day that
            // was actually an approved half-day leave, understating the real leave count.
            // Early Leave is excluded from the day total the same way the Leave Applications
            // page excludes it — it's shown there as an hour count, not a day of leave.
            $overlappingLeaves = LeaveApplication::where('employee_id', $statsEmployeeId)
                ->whereIn('status', ['approved', 'unpaid'])
                ->whereDate('start_date', '<=', $statsRangeEnd->toDateString())
                ->whereDate('end_date', '>=', $statsRangeStart->toDateString())
                ->get(['leave_type', 'start_date', 'end_date', 'total_days']);

            $leaveDayCount = $overlappingLeaves->where('leave_type', '!=', 'Early Leave')->sum('total_days');

            // Every calendar date an approved leave covers — attendance has no punch record
            // (or even a row) for a leave day, so buildMonthlyHistory's fallback would
            // otherwise mislabel these as "Absent" on top of already counting them as leave.
            $approvedLeaveDates = $overlappingLeaves->flatMap(function ($leave) {
                $dates = [];
                for ($d = $leave->start_date->copy(); $d->lte($leave->end_date); $d->addDay()) {
                    $dates[] = $d->format('Y-m-d');
                }

                return $dates;
            })->unique();

            $absentDates = collect($history)
                ->filter(fn ($row) => in_array($row['status_key'], ['absent', 'unauthorised', 'missing_punch'], true))
                ->pluck('date_key')
                ->diff($approvedLeaveDates);

            // A day can be genuinely split — e.g. worked the morning, took an approved
            // half-day leave for the afternoon — and attendance still shows it as a normal
            // "half_day" (worked-hours-based) status. That day would otherwise be credited
            // as a full present day AND its 0.5 leave day, double-counting the same date.
            // So present credit for a date is capped at (1 - the leave fraction that date).
            $leaveFractionByDate = [];
            foreach ($overlappingLeaves->where('leave_type', '!=', 'Early Leave') as $leave) {
                $daysInLeave = $leave->start_date->diffInDays($leave->end_date) + 1;
                $perDayFraction = $daysInLeave > 0 ? $leave->total_days / $daysInLeave : 0;

                for ($d = $leave->start_date->copy(); $d->lte($leave->end_date); $d->addDay()) {
                    $dateKey = $d->format('Y-m-d');
                    $leaveFractionByDate[$dateKey] = ($leaveFractionByDate[$dateKey] ?? 0) + $perDayFraction;
                }
            }

            $presentDayCount = $presentDates->sum(
                fn ($dateKey) => max(0, 1 - ($leaveFractionByDate[$dateKey] ?? 0))
            );

            // Grouped by the task's own date, not by when the follow-up was clicked/submitted —
            // an employee who logs several days of work in one late-night session should have
            // each of those task-days count as reported, not just the day they hit submit.
            $dailyLogged = DB::table('daily_tasks')
                ->join('task_follow_ups', 'task_follow_ups.daily_task_id', '=', 'daily_tasks.id')
                ->where('daily_tasks.employee_id', $statsEmployeeId)
                ->whereDate('daily_tasks.start_date', '>=', $statsRangeStart->toDateString())
                ->whereDate('daily_tasks.start_date', '<=', $statsRangeEnd->toDateString())
                ->selectRaw('DATE(daily_tasks.start_date) as log_date, SUM(CAST(task_follow_ups.time_taken AS DECIMAL(10,2))) as total_hours')
                ->groupBy('log_date')
                ->pluck('total_hours', 'log_date');

            $totalDays = (int) $statsRangeStart->copy()->startOfDay()->diffInDays($statsRangeEnd->copy()->startOfDay()) + 1;

            $sundayCount = collect($history)
                ->filter(fn ($row) => Carbon::parse($row['date_key'])->isSunday())
                ->count();

            $holidayDates = Holiday::whereDate('date', '>=', $statsRangeStart->toDateString())
                ->whereDate('date', '<=', $statsRangeEnd->toDateString())
                ->pluck('date')
                ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'));

            // A holiday that lands on a Sunday shouldn't be double-subtracted from working days.
            $holidayNotOnSunday = $holidayDates->filter(fn ($date) => !Carbon::parse($date)->isSunday())->count();

            $employeeMonthlyStats = [
                'present_days' => $presentDayCount,
                'submitted_days' => $dailyLogged->count(),
                'missed_days' => $presentDates->diff($dailyLogged->keys())->count(),
                'under_target_days' => $dailyLogged->filter(fn ($hrs) => (float) $hrs < 8)->count(),
                'total_days' => $totalDays,
                'sunday_count' => $sundayCount,
                'holiday_count' => $holidayDates->count(),
                'working_days' => $totalDays - $sundayCount - $holidayNotOnSunday,
                'leave_count' => $leaveDayCount,
                'absent_count' => $absentDates->count(),
                'range_label' => $statsRangeStart->isSameDay($statsRangeStart->copy()->startOfMonth())
                    && $statsRangeEnd->isSameDay($statsRangeEnd->copy()->endOfMonth())
                    && $statsRangeStart->isSameMonth($statsRangeEnd)
                    ? $statsRangeStart->format('F Y')
                    : $statsRangeStart->format('d M Y') . ' – ' . $statsRangeEnd->format('d M Y'),
            ];
        }

        $tasks = $query->latest()->paginate($perPage)->withQueryString();

        return view('projects.tasks.index', compact(
            'tasks',
            'projects',
            'employees',
            'isAdmin',
            'perPage',
            'todayCount',
            'pendingCount',
            'loggedHoursDisplay',
            'selectedMonth',
            'employeeMonthlyStats'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'task_title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'priority' => 'required|string',
            'status' => 'required|string',
            'employee_id' => 'required|exists:employees,id',
            'description' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,bmp,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar|max:10240',
        ]);

        $validated['assigned_by'] = Auth::id();

        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $adminRoles = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head', 'team_leader'];
        $isAdmin = in_array($role, $adminRoles);

        if (!$isAdmin) {
            if (!empty($validated['project_id'])) {
                $project = Project::find($validated['project_id']);
                $isLeader = $project && is_array($project->leaders) && in_array(auth()->user()->employee_id, $project->leaders);

                if (!$isLeader) {
                    $validated['employee_id'] = auth()->user()->employee_id;
                } else {
                    $allowed = array_merge((array) ($project->leaders ?? []), (array) ($project->members ?? []));
                    if (!in_array($validated['employee_id'], $allowed)) {
                        return response()->json(['error' => 'You can only assign tasks to project members.'], 403);
                    }
                }
            } else {
                $validated['employee_id'] = auth()->user()->employee_id;
            }
        }

        if (isset($validated['end_date']) && $validated['end_date']) {
            $validated['end_date'] = \Carbon\Carbon::parse($validated['end_date'])->endOfDay();
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('daily_tasks', 'public');
            $validated['photo'] = $path;
        }

        DailyTask::create($validated);

        return response()->json(['success' => 'Task created successfully!']);
    }

    /**
     * Quick-assign an ad-hoc task to any employee from the dashboard "Quick Notes" widget.
     * Unlike store(), this is intentionally open to every authenticated employee and every
     * target employee (same or different department) — it is not restricted to project members.
     */
    public function quickAssign(Request $request)
    {
        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'employee_id' => 'required|exists:employees,id',
            'priority' => 'nullable|string|in:Hard,Medium,Low',
            'remind_at' => 'nullable|date',
        ]);

        $assignee = Employee::active()->find($validated['employee_id']);
        if (!$assignee) {
            return response()->json(['error' => 'Selected employee is not available.'], 422);
        }

        $startDate = now();
        $endDate = !empty($validated['remind_at'])
            ? \Carbon\Carbon::parse($validated['remind_at'])->endOfDay()
            : $startDate->copy()->endOfDay();

        $task = DailyTask::create([
            'project_id' => null,
            'task_title' => $validated['task_title'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'priority' => $validated['priority'] ?? 'Medium',
            'status' => 'Pending',
            'employee_id' => $assignee->id,
            'assigned_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task assigned to ' . $assignee->name . '.',
            'task' => [
                'id' => $task->id,
                'task_title' => $task->task_title,
                'employee_name' => $assignee->name,
            ],
        ]);
    }

    public function update(Request $request, DailyTask $dailyTask)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'task_title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'priority' => 'required|string',
            'status' => 'required|string',
            'employee_id' => 'required|exists:employees,id',
            'description' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,bmp,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar|max:10240',
        ]);

        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $adminRoles = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head', 'team_leader'];
        $isAdmin = in_array($role, $adminRoles);

        $project = $dailyTask->project;
        $isLead = $project && is_array($project->leaders) && in_array(auth()->user()->employee_id, $project->leaders);
        $isOwner = auth()->user()->employee_id == $dailyTask->employee_id;

        if (!$isAdmin && !$isLead && !$isOwner) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        if ($request->hasFile('photo')) {
            if ($dailyTask->photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($dailyTask->photo);
            }
            $path = $request->file('photo')->store('daily_tasks', 'public');
            $validated['photo'] = $path;
        }

        if (isset($validated['end_date']) && $validated['end_date']) {
            $validated['end_date'] = \Carbon\Carbon::parse($validated['end_date'])->endOfDay();
        }

        $oldStatus = $dailyTask->status;
        $newStatus = $validated['status'];

        if (strcasecmp((string) $oldStatus, (string) $newStatus) !== 0) {
            $validated['status_changed_at'] = now();
        }

        $dailyTask->update($validated);

        return response()->json(['success' => 'Task updated successfully!']);
    }

    /**
     * Whether the current user may delete this task. A task's assignee can update its
     * status/priority, but for an ad-hoc task (no project) assigned by someone else, only
     * that assigner — or an admin/lead — can delete it outright; the assignee alone isn't
     * enough. Project tasks keep the original owner-can-delete behavior.
     */
    private function canDeleteDailyTask(DailyTask $dailyTask): bool
    {
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $adminRoles = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head', 'team_leader'];
        $isAdmin = in_array($role, $adminRoles);

        $project = $dailyTask->project;
        $isLead = $project && is_array($project->leaders) && in_array(auth()->user()->employee_id, $project->leaders);
        $isOwner = auth()->user()->employee_id == $dailyTask->employee_id;
        $isAssigner = auth()->id() == $dailyTask->assigned_by;
        $canDeleteAsOwner = $isOwner && $dailyTask->project_id !== null;

        return $isAdmin || $isLead || $isAssigner || $canDeleteAsOwner;
    }

    /**
     * Whether the current user may edit/delete this work-progress entry. TaskFollowUp has no
     * employee_id of its own (only a free-text reference_name), so ownership is derived from
     * the parent task it was logged against — same admin/lead/owner/assigner rule as the task.
     */
    private function canManageFollowUp(TaskFollowUp $followUp): bool
    {
        $task = $followUp->dailyTask;
        if (!$task) {
            return false;
        }

        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $adminRoles = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head', 'team_leader'];
        $isAdmin = in_array($role, $adminRoles);

        $project = $task->project;
        $isLead = $project && is_array($project->leaders) && in_array(auth()->user()->employee_id, $project->leaders);
        $isOwner = auth()->user()->employee_id == $task->employee_id;
        $isAssigner = auth()->id() == $task->assigned_by;

        if (!($isAdmin || $isLead || $isOwner || $isAssigner)) {
            return false;
        }

        // Once a task is Completed, its progress log is locked — only an admin can still
        // correct it. Everyone else (including the task owner/lead/assigner) must reopen
        // the task's status first if a genuine change is needed.
        if (strcasecmp((string) $task->status, 'Completed') === 0 && !$isAdmin) {
            return false;
        }

        return true;
    }

    private function followUpLockMessage(TaskFollowUp $followUp, string $action): string
    {
        $task = $followUp->dailyTask;

        if ($task && strcasecmp((string) $task->status, 'Completed') === 0) {
            return 'This task is marked Completed — its progress log is locked. Reopen the task status, or ask an admin, to ' . $action . ' this entry.';
        }

        return "Only the task owner, project lead, assigner, or an admin can {$action} this entry.";
    }

    public function destroy(DailyTask $dailyTask)
    {
        if (!$this->canDeleteDailyTask($dailyTask)) {
            return back()->with('error', 'Only the person who assigned this task, or an admin, can delete it.');
        }

        $dailyTask->delete();
        return back()->with('success', 'Task deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->ids;
        if (!$ids || !is_array($ids)) {
            return response()->json(['error' => 'No tasks selected.'], 400);
        }

        $tasks = DailyTask::with('project')->whereIn('id', $ids)->get();
        $deletableIds = $tasks->filter(fn (DailyTask $task) => $this->canDeleteDailyTask($task))->pluck('id');

        if ($deletableIds->isEmpty()) {
            return response()->json(['error' => 'You are not authorized to delete the selected tasks.'], 403);
        }

        DailyTask::whereIn('id', $deletableIds)->delete();

        $skipped = count($ids) - $deletableIds->count();
        $message = $skipped > 0
            ? $deletableIds->count() . ' task(s) deleted. ' . $skipped . ' skipped (not authorized).'
            : 'Tasks deleted successfully!';

        return response()->json(['success' => $message]);
    }

    public function storeFollowUp(Request $request)
    {
        $rows = collect((array) $request->input('work_description', []))
            ->keys()
            ->map(function ($index) use ($request) {
                return [
                    'input_index' => $index,
                    'project_id' => $request->input("project_id.$index"),
                    'work_description' => trim((string) $request->input("work_description.$index", '')),
                    'hours' => $request->input("hours.$index"),
                    'minutes' => $request->input("minutes.$index"),
                    'has_photo' => $request->hasFile("photo.$index"),
                ];
            })
            ->filter(function ($row) {
                return ($row['project_id'] !== null && $row['project_id'] !== '')
                    || $row['work_description'] !== ''
                    || ($row['hours'] !== null && $row['hours'] !== '')
                    || ($row['minutes'] !== null && $row['minutes'] !== '')
                    || $row['has_photo'];
            })
            ->values();

        if ($rows->isEmpty()) {
            return response()->json([
                'errors' => [
                    'work_description' => ['Please add at least one work progress row.'],
                ],
            ], 422);
        }

        $missingTimeRow = $rows->search(function ($row) {
            return ($row['hours'] === null || $row['hours'] === '')
                && ($row['minutes'] === null || $row['minutes'] === '');
        });

        if ($missingTimeRow !== false) {
            throw ValidationException::withMessages([
                "hours.$missingTimeRow" => ['Enter time.'],
            ]);
        }

        $request->merge([
            'project_id' => $rows->pluck('project_id')->all(),
            'work_description' => $rows->pluck('work_description')->all(),
            'hours' => $rows->map(fn ($row) => $row['hours'] === '' || $row['hours'] === null ? 0 : $row['hours'])->all(),
            'minutes' => $rows->map(fn ($row) => $row['minutes'] === '' || $row['minutes'] === null ? 0 : $row['minutes'])->all(),
        ]);

        $validated = $request->validate([
            'daily_task_id' => 'required|exists:daily_tasks,id',
            'project_id' => 'required|array|min:1',
            'project_id.*' => 'required|exists:projects,id',
            'work_description' => 'required|array|min:1',
            'work_description.*' => 'required|string',
            'hours' => 'required|array|min:1',
            'hours.*' => 'nullable|numeric|min:0',
            'minutes' => 'required|array|min:1',
            'minutes.*' => 'nullable|numeric|min:0|max:59',
            'photo' => 'nullable|array',
            'photo.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,bmp,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar|max:10240',
        ]);

        $task = DailyTask::with('employee')->findOrFail($validated['daily_task_id']);
        $referenceName = $task->employee->name ?? auth()->user()->name ?? 'Employee';

        $lastProjectId = null;

        DB::transaction(function () use ($request, $rows, $task, $referenceName, &$lastProjectId) {
            foreach ($rows as $row) {
                $inputIndex = $row['input_index'];
                $projectId = $row['project_id'];
                $rawDescription = $row['work_description'];
                $hours = (int) ($row['hours'] ?? 0);
                $minutes = (int) ($row['minutes'] ?? 0);
                $decimalTime = $hours + ($minutes / 60);

                $project = Project::find($projectId);
                $formattedHtmlBlock = $this->buildFormattedWorkDescription($project?->name, $rawDescription, $hours, $minutes);
                $photoPath = null;

                if ($request->hasFile("photo.$inputIndex")) {
                    $photoPath = $request->file("photo.$inputIndex")->store('task_followups', 'public');
                }

                TaskFollowUp::create([
                    'daily_task_id' => $task->id,
                    'project_id' => $projectId,
                    'reference_name' => $referenceName,
                    'work_description' => $formattedHtmlBlock,
                    'time_taken' => (string) $decimalTime,
                    'photo' => $photoPath,
                ]);

                $lastProjectId = $projectId;
            }

            if ($lastProjectId) {
                $task->update(['project_id' => $lastProjectId]);
            }
        });

        return response()->json(['success' => 'Reply submitted successfully!']);
    }

    public function updateFollowUp(Request $request, $id)
    {
        $followUp = TaskFollowUp::findOrFail($id);

        if (!$this->canManageFollowUp($followUp)) {
            return response()->json(['error' => $this->followUpLockMessage($followUp, 'edit')], 403);
        }

        $projectId = $request->input('project_id.0', $request->input('project_id', $followUp->project_id));
        $description = trim((string) $request->input('work_description.0', $request->input('work_description', '')));
        $hours = $request->input('hours.0', $request->input('hours'));
        $minutes = $request->input('minutes.0', $request->input('minutes'));

        if (($hours === null || $hours === '') && ($minutes === null || $minutes === '')) {
            throw ValidationException::withMessages([
                'hours.0' => ['Enter time.'],
            ]);
        }

        $request->merge([
            'project_id' => $projectId,
            'work_description' => $description,
            'time_taken' => ((int) ($hours ?: 0)) + (((int) ($minutes ?: 0)) / 60),
        ]);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'work_description' => 'required|string',
            'time_taken' => 'nullable|numeric|min:0',
            'photo' => 'nullable|array',
            'photo.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,bmp,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar|max:10240',
        ]);

        $project = Project::find($validated['project_id']);
        $validated['work_description'] = $this->buildFormattedWorkDescription(
            $project?->name,
            $validated['work_description'],
            (int) ($hours ?: 0),
            (int) ($minutes ?: 0)
        );
        $validated['time_taken'] = (string) $validated['time_taken'];

        if ($request->hasFile('photo.0')) {
            if ($followUp->photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($followUp->photo);
            }
            $validated['photo'] = $request->file('photo.0')->store('task_followups', 'public');
        }

        $followUp->update($validated);

        return response()->json(['success' => 'Task history updated successfully!']);
    }

    private function buildFormattedWorkDescription(?string $projectName, string $rawDescription, int $hours, int $minutes): string
    {
        $projectName = $projectName ?: 'Project';
        $lines = preg_split('/\r\n|\r|\n/', $rawDescription) ?: [];
        $listItemsHtml = '';

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            $cleanLine = preg_replace('/^(\-+|>+|•+|\*+)\s*/u', '', $trimmedLine);
            $listItemsHtml .= '<li style="margin-bottom: 6px;">' . e($cleanLine) . '</li>';
        }

        if ($listItemsHtml === '') {
            $listItemsHtml = '<li style="margin-bottom: 6px;">' . e($rawDescription) . '</li>';
        }

        $timeLabel = $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');

        return '
            <div class="mb-4" style="padding-left: 8px; font-family: system-ui, sans-serif;">
                <p class="mb-3" style="font-size: 16px; color: #1e293b; margin-bottom: 10px; font-weight: 700;">
                    <span style="color: #1e293b;">&bull; ' . e($projectName) . '</span>
                    <span style="color: #3858f9; font-weight: 700;"> - ' . e($timeLabel) . '</span>
                </p>
                <ol class="text-muted" style="font-size: 14px; line-height: 1.9; padding-left: 24px; color: #64748b; margin: 0;">
                    ' . $listItemsHtml . '
                </ol>
            </div>';
    }

    public function getFollowUps($taskId)
    {
        $followUps = TaskFollowUp::where('daily_task_id', $taskId)
            ->with('project', 'dailyTask')
            ->latest()
            ->get()
            ->map(function ($followUp) {
                $followUp->employee_name = $followUp->reference_name ?: 'Employee';
                $followUp->employee = null;
                $followUp->project_name = $followUp->project?->name;
                $followUp->can_manage = $this->canManageFollowUp($followUp);
                return $followUp;
            });

        return response()->json($followUps);
    }

    public function destroyFollowUp($id)
    {
        $followUp = TaskFollowUp::findOrFail($id);

        if (!$this->canManageFollowUp($followUp)) {
            return response()->json(['error' => $this->followUpLockMessage($followUp, 'delete')], 403);
        }

        if ($followUp->photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($followUp->photo);
        }
        $followUp->delete();
        return response()->json(['success' => 'Task history description deleted successfully!']);
    }

    public function updateStatus(Request $request, DailyTask $dailyTask)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Pending,In Process,Completed,On Hold,Review,Rework,Reassign',
            'comment' => 'nullable|string',
            'employee_id' => 'nullable',
        ]);

        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $adminRoles = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head', 'team_leader'];
        $isAdmin = in_array($role, $adminRoles);

        $project = $dailyTask->project;
        $isLead = false;
        if ($project && is_array($project->leaders)) {
            $isLead = in_array(auth()->user()->employee_id, $project->leaders);
        }

        $isOwner = auth()->user()->employee_id == $dailyTask->employee_id;
        $isAssigner = auth()->id() == $dailyTask->assigned_by;

        if (!$isAdmin && !$isLead && !$isOwner && !$isAssigner) {
            return response()->json(['error' => 'Only Admin, Project Lead, Task Owner or the person who assigned it can change status.'], 403);
        }

        $updateData = ['status' => $validated['status']];
        if ($request->status == 'Reassign' && $request->employee_id) {
            $updateData['employee_id'] = $request->employee_id;
        }

        if (strcasecmp((string) $dailyTask->status, (string) $validated['status']) !== 0) {
            $updateData['status_changed_at'] = now();
        }

        TaskStatusHistory::create([
            'task_id' => $dailyTask->id,
            'old_status' => $dailyTask->status,
            'new_status' => $request->status,
            'comment' => $request->comment,
            'updated_by' => auth()->id(),
        ]);
        $dailyTask->update($updateData);

        return response()->json(['success' => 'Task status updated successfully!']);
    }

    public function statusHistory(DailyTask $task)
    {
        return response()->json(
            $task->statusHistory()
                ->with('user')
                ->latest()
                ->get()
        );
    }

    public function updatePriority(Request $request, DailyTask $dailyTask)
    {
        $validated = $request->validate([
            'priority' => 'required|string|in:Hard,Medium,Low,Normal',
        ]);

        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $adminRoles = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head', 'team_leader'];
        $isAdmin = in_array($role, $adminRoles);

        $project = $dailyTask->project;
        $isLead = $project && is_array($project->leaders) && in_array(auth()->user()->employee_id, $project->leaders);
        $isOwner = auth()->user()->employee_id == $dailyTask->employee_id;
        $isAssigner = auth()->id() == $dailyTask->assigned_by;

        if (!$isAdmin && !$isLead && !$isOwner && !$isAssigner) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $dailyTask->update(['priority' => $validated['priority']]);

        return response()->json(['success' => 'Task priority updated successfully!']);
    }
}
