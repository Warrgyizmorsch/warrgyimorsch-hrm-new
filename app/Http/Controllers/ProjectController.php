<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\DailyTask;
// use App\Models\TaskFollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
// use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
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

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $view = $request->query('view', 'list');
        if (!in_array($view, ['list', 'board'], true)) {
            $view = 'list';
        }

        $query = Project::query()->with(['tasks.employee', 'departments']);

        if ($isTeamLeader) {
            $departmentIds = $user->employee?->ledDepartmentIds() ?? [];
            if (!empty($departmentIds)) {
                $query->whereHas('departments', fn ($q) => $q->whereIn('departments.id', $departmentIds));
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('technology', 'LIKE', '%' . $search . '%')
                  ->orWhere('description', 'LIKE', '%' . $search . '%');
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $status = $request->status;
            $legacyMap = [
                'Pending' => ['Pending', 'Not Started'],
                'In Process' => ['In Process', 'In Progress'],
                'Completed' => ['Completed', 'Finished'],
            ];
            $statuses = $legacyMap[$status] ?? [$status];
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('department_id')) {
            $departmentId = $request->department_id;
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
        }

        $sort = $request->query('sort', 'latest');
        match ($sort) {
            'name' => $query->orderBy('name'),
            'due_soon' => $query->orderByRaw('end_date IS NULL')->orderBy('end_date'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $statsBase = clone $query;
        $projectStats = [
            'total' => (clone $statsBase)->count(),
            'pending' => (clone $statsBase)->whereIn('status', ['Pending', 'Not Started'])->count(),
            'in_process' => (clone $statsBase)->whereIn('status', ['In Process', 'In Progress'])->count(),
            'review' => (clone $statsBase)->where('status', 'Review')->count(),
            'on_hold' => (clone $statsBase)->where('status', 'On Hold')->count(),
            'rework' => (clone $statsBase)->where('status', 'Rework')->count(),
            'completed' => (clone $statsBase)->whereIn('status', ['Completed', 'Finished'])->count(),
        ];

        $projectInsights = [
            'active' => $projectStats['pending'] + $projectStats['in_process'] + $projectStats['review'] + $projectStats['rework'],
            'overdue' => (clone $statsBase)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', now())
                ->whereNotIn('status', ['Completed', 'Finished'])
                ->count(),
            'due_this_week' => (clone $statsBase)
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                ->whereNotIn('status', ['Completed', 'Finished'])
                ->count(),
        ];

        $query->withCount([
            'tasks',
            'tasks as completed_tasks_count' => fn ($q) => $q->whereIn('status', ['Completed', 'Done', 'completed', 'done']),
        ]);

        if ($view === 'board') {
            $projects = $query->limit(200)->get();
        } else {
            $projects = (clone $query)->paginate($perPage)->withQueryString();
        }

        $employees = \App\Models\Employee::active()->get();
        $employeesById = $employees->keyBy('id');
        $departments = \App\Models\Department::all();

        return view('projects.index', compact(
            'projects',
            'employees',
            'employeesById',
            'departments',
            'isAdmin',
            'perPage',
            'view',
            'projectStats',
            'projectInsights',
            'sort'
        ));
    }

    public function create()
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

        $employees = \App\Models\Employee::active()->get();
        // if ($isAdmin) {
        // } elseif ($isTeamLeader) {
        //     $department = $user->employee->department ?? null;
        //     if ($department) {
        //         $employees = \App\Models\Employee::active()->where('department', $department)->get();
        //     } else {
        //         $employees = collect();
        //     }
        // } else {
        //     $employees = \App\Models\Employee::active()->where('id', $user->employee_id)->get();
        // }

        $departments = \App\Models\Department::all();
        return view('projects.create', compact('employees', 'departments'));
    }

    public function checkName(Request $request)
    {
        // Query the database to see if the name field matches the incoming value
        $exists = Project::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->exists();

        return response()->json([
            'exists' => $exists
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'description' => 'required',
            'documents.*' => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx|max:5120',
            // 'type' => 'required',
            // 'manage' => 'required',
        ]);

        $documents = [];

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {

                $fileName = time().'_'.$file->getClientOriginalName();

                $path = $file->storeAs(
                    'project-documents',
                    $fileName,
                    'public'
                );

                $documents[] = $path;
            }
        }

        $project = Project::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'description' => $request->description,
            'technology' => $request->technology,
            'leaders' => $request->leaders,
            'members' => $request->members,
            'documents' => $documents,
            // 'type' => $request->type,
            // 'manage' => $request->manage,
        ]);
        $project->departments()->sync($request->department_ids);

        return redirect()->route('projects.index')->with('success', 'Project created successfully');
    }

    public function show(Project $project)
    {
        $employees = \App\Models\Employee::active()->with('departmentRef')->get();
        $employeesById = $employees->keyBy('id');
        $departments = \App\Models\Department::all();

        $project->loadCount([
            'tasks',
            'tasks as completed_tasks_count' => fn ($q) => $q->whereIn('status', ['Completed', 'Done', 'completed', 'done']),
            'tasks as pending_tasks_count' => fn ($q) => $q->whereIn('status', ['Pending', 'Not Started', 'pending']),
            'tasks as in_process_tasks_count' => fn ($q) => $q->whereIn('status', ['In Process', 'In Progress', 'in process']),
        ]);

        $tasks = DailyTask::where('project_id', $project->id)
            ->with(['employee', 'creator', 'followUps'])
            ->latest()
            ->get();

        $totalHoursWorked = 0;
        $dayGroups = [];

        foreach ($tasks as $task) {
            foreach ($task->followUps as $fu) {
                if (preg_match('/[+-]?([0-9]*[.])?[0-9]+/', $fu->time_taken ?? '', $matches)) {
                    $totalHoursWorked += (float) $matches[0];
                }
            }

            $date = $task->created_at->format('d M Y');
            $dayGroups[$date][$task->id]['task'] = $task;
            $dayGroups[$date][$task->id]['events'][] = (object) [
                'type' => 'creation',
                'created_at' => $task->created_at,
                'description' => $task->description,
                'photo' => $task->photo,
                'time_taken' => null,
            ];

            foreach ($task->followUps as $fu) {
                $fuDate = $fu->created_at->format('d M Y');
                $dayGroups[$fuDate][$task->id]['task'] = $task;
                $dayGroups[$fuDate][$task->id]['events'][] = (object) [
                    'type' => 'progress',
                    'created_at' => $fu->created_at,
                    'description' => $fu->work_description,
                    'photo' => $fu->photo,
                    'time_taken' => $fu->time_taken,
                    'reference_name' => $fu->reference_name,
                ];
            }
        }

        foreach ($dayGroups as $date => &$tasksInDay) {
            foreach ($tasksInDay as $taskId => &$data) {
                $dailyTaskTime = 0;
                foreach ($data['events'] as $event) {
                    if ($event->type === 'progress' && $event->time_taken) {
                        $dailyTaskTime += (float) $event->time_taken;
                    }
                }
                $data['daily_total_time'] = $dailyTaskTime;

                usort($data['events'], fn ($a, $b) => $b->created_at <=> $a->created_at);
            }
        }
        unset($tasksInDay, $data);

        uksort($dayGroups, fn ($a, $b) => strtotime($b) - strtotime($a));

        $leaderIds = is_array($project->leaders) ? $project->leaders : [];
        $memberIds = is_array($project->members) ? $project->members : [];
        $projectLeads = $employees->whereIn('id', $leaderIds)->values();
        $projectMembers = $employees->whereIn('id', $memberIds)->values();

        $taskStats = [
            'total' => (int) $project->tasks_count,
            'completed' => (int) $project->completed_tasks_count,
            'pending' => (int) $project->pending_tasks_count,
            'in_process' => (int) $project->in_process_tasks_count,
        ];

        $projectMetrics = [
            'progress' => $project->display_progress,
            'timeline_progress' => $project->progress,
            'total_hours' => round($totalHoursWorked, 1),
            'team_size' => $projectLeads->count() + $projectMembers->count(),
            'days_remaining' => $project->end_date && !$project->is_overdue && !in_array(strtolower($project->normalized_status), ['completed', 'finished'])
                ? now()->startOfDay()->diffInDays($project->end_date, false)
                : null,
        ];

        return view('projects.show', compact(
            'project',
            'employees',
            'employeesById',
            'departments',
            'dayGroups',
            'tasks',
            'taskStats',
            'projectMetrics',
            'projectLeads',
            'projectMembers'
        ));
    }

    public function edit(Project $project)
    {
        $employees = \App\Models\Employee::active()->get();
        $departments = \App\Models\Department::all();

        return view('projects.edit', compact('project', 'employees', 'departments'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'description' => 'required',
            'documents.*' => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx|max:5120',
            // 'type' => 'required',
            // 'manage' => 'required',
        ]);

        $documents = is_array($project->documents) ? $project->documents : [];

        // Handle deleted documents
        if ($request->has('deleted_documents')) {
            foreach ($request->deleted_documents as $deletedFile) {
                if (($key = array_search($deletedFile, $documents)) !== false) {
                    unset($documents[$key]);
                    // Delete file from disk
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($deletedFile);
                }
            }
            $documents = array_values($documents); // reindex
        }

        // Handle new documents
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $fileName = time().'_'.$file->getClientOriginalName();
                $path = $file->storeAs(
                    'project-documents',
                    $fileName,
                    'public'
                );
                $documents[] = $path;
            }
        }

        $project->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'description' => $request->description,
            'technology' => $request->technology,
            'leaders' => $request->leaders,
            'members' => $request->members,
            'documents' => $documents,
            // 'type' => $request->type,
            // 'manage' => $request->manage,
        ]);
        $project->departments()->sync($request->department_ids);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully');
    }

    public function updateField(Request $request, Project $project)
    {
        $fields = ['status', 'members', 'leaders'];
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $isAdmin = in_array($role, [
            'super_admin',
            'manager',
            'hr_executive',
            'hr_intern',
            'business_operation_head',
        ]);
        $isLead = is_array($project->leaders)
            && in_array(auth()->user()->employee_id, $project->leaders, true);

        $isDepartmentTeamLeader = false;
        if ($role === 'team_leader') {
            $ledDepartmentIds = auth()->user()->employee?->ledDepartmentIds() ?? [];
            $projectDepartmentIds = $project->departments()->pluck('departments.id')->all();
            $isDepartmentTeamLeader = !empty(array_intersect($ledDepartmentIds, $projectDepartmentIds));
        }

        foreach ($fields as $field) {
            if (!$request->has($field)) {
                continue;
            }

            if ($field === 'status' && !$isAdmin && !$isLead && !$isDepartmentTeamLeader) {
                continue;
            }

            $project->$field = $request->$field;
        }

        $project->save();

        return response()->json(['success' => true]);
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (!empty($ids)) {
            Project::whereIn('id', $ids)->delete();
        }
        return response()->json(['success' => true]);
    }

    public function tasksSummary(Project $project)
    {
        $tasks = $project->tasks->map(function($task) {
            $latestFU = $task->followUps->first();
            return [
                'id' => $task->id,
                'task_title' => $task->task_title,
                'status' => $task->status,
                'employee' => $task->employee,
                'follow_ups' => $task->followUps,
                'total_time_calc' => $task->total_time,
                'latest_activity_date' => $latestFU ? $latestFU->created_at->format('d M, h:i A') : 'No updates'
            ];
        });

        return response()->json([
            'project_name' => $project->name,
            'tasks' => $tasks
        ]);
    }
}
