<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\Employee;
use App\Models\Project;
use App\Models\TaskFollowUp;
use App\Models\TaskStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyTaskController extends Controller
{
    public function index(Request $request)
    {
        $query = DailyTask::with(['project', 'employee', 'creator', 'followUps']);

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
            $teamLeaderDepartment = auth()->user()->employee->department ?? null;

            $departmentEmployeeIds = Employee::active()->where('department', $teamLeaderDepartment)
                ->pluck('id');

            $query->whereIn('employee_id', $departmentEmployeeIds);

            $employees = Employee::active()->where('department', $teamLeaderDepartment)->get();

            $projects = Project::where(function ($q) use ($departmentEmployeeIds) {
                foreach ($departmentEmployeeIds as $employeeId) {
                    $q->orWhereJsonContains('members', (string) $employeeId);
                }
            })->orderBy('name')->get();
        } elseif (!$isAdmin) {
            $employeeId = auth()->user()->employee_id;

            $query->where('employee_id', $employeeId);

            $employees = Employee::active()->where('id', $employeeId)->get();

            $projects = Project::whereJsonContains('members', (string) $employeeId)
                ->orderBy('name')
                ->get();
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

        $tasks = $query->latest()->paginate($perPage)->withQueryString();

        return view('projects.tasks.index', compact('tasks', 'projects', 'employees', 'isAdmin', 'perPage'));
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

    public function destroy(DailyTask $dailyTask)
    {
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $adminRoles = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head', 'team_leader'];
        $isAdmin = in_array($role, $adminRoles);

        $project = $dailyTask->project;
        $isLead = $project && is_array($project->leaders) && in_array(auth()->user()->employee_id, $project->leaders);
        $isOwner = auth()->user()->employee_id == $dailyTask->employee_id;

        if (!$isAdmin && !$isLead && !$isOwner) {
            return back()->with('error', 'Unauthorized action.');
        }

        $dailyTask->delete();
        return back()->with('success', 'Task deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->ids;
        if ($ids && is_array($ids)) {
            DailyTask::whereIn('id', $ids)->delete();
            return response()->json(['success' => 'Tasks deleted successfully!']);
        }
        return response()->json(['error' => 'No tasks selected.'], 400);
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
            ->with('project')
            ->latest()
            ->get()
            ->map(function ($followUp) {
                $followUp->employee_name = $followUp->reference_name ?: 'Employee';
                $followUp->employee = null;
                $followUp->project_name = $followUp->project?->name;
                return $followUp;
            });

        return response()->json($followUps);
    }

    public function destroyFollowUp($id)
    {
        $followUp = TaskFollowUp::findOrFail($id);
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

        if (!$isAdmin && !$isLead && !$isOwner) {
            return response()->json(['error' => 'Only Admin, Project Lead or Task Owner can change status.'], 403);
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

        if (!$isAdmin && !$isLead && !$isOwner) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $dailyTask->update(['priority' => $validated['priority']]);

        return response()->json(['success' => 'Task priority updated successfully!']);
    }
}
