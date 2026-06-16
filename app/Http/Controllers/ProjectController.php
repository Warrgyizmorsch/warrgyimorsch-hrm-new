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

        $query = Project::with(['tasks.employee']);
        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $query->where(function ($q) use ($department) {
                    $q->whereJsonContains('department', $department)
                      ->orWhere('department', 'like', '%' . $department . '%');
                });
            } else {
                $query->whereRaw('1=0'); // Force empty if no department
            }
        }
        $projects = $query->latest()->paginate($perPage)->withQueryString();
        
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
        return view('projects.index', compact('projects', 'employees', 'departments', 'isAdmin', 'perPage'));
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
            'department' => 'required|string',
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
            'department' => $request->department,
            'description' => $request->description,
            'technology' => $request->technology,
            'leaders' => $request->leaders,
            'members' => $request->members,
            'documents' => $documents,
            // 'type' => $request->type,
            // 'manage' => $request->manage,
        ]);

        return redirect()->route('projects.index')->with('success', 'Project created successfully');
    }

    public function show(Project $project)
    {
        $employees = \App\Models\Employee::active()->get();
        $departments = \App\Models\Department::all();

        // 1. Get all tasks created for this project
        $tasks = DailyTask::where('project_id', $project->id)
            ->with(['employee', 'creator', 'followUps'])
            ->get();

        $dayGroups = [];

        foreach ($tasks as $task) {
            // Task Creation Event
            $date = $task->created_at->format('d M Y');
            $dayGroups[$date][$task->id]['task'] = $task;
            $dayGroups[$date][$task->id]['events'][] = (object)[
                'type' => 'creation',
                'created_at' => $task->created_at,
                'description' => $task->description,
                'photo' => $task->photo,
                'time_taken' => null,
            ];

            // Follow-up (Progress) Events
            foreach ($task->followUps as $fu) {
                $fuDate = $fu->created_at->format('d M Y');
                $dayGroups[$fuDate][$task->id]['task'] = $task;
                $dayGroups[$fuDate][$task->id]['events'][] = (object)[
                    'type' => 'progress',
                    'created_at' => $fu->created_at,
                    'description' => $fu->work_description,
                    'photo' => $fu->photo,
                    'time_taken' => $fu->time_taken,
                    'reference_name' => $fu->reference_name,
                ];
            }
        }

        // Sort events within each task by time and calculate daily task totals
        foreach ($dayGroups as $date => &$tasksInDay) {
            foreach ($tasksInDay as $taskId => &$data) {
                $dailyTaskTime = 0;
                foreach ($data['events'] as $event) {
                    if ($event->type == 'progress' && $event->time_taken) {
                        $dailyTaskTime += (float)$event->time_taken;
                    }
                }
                $data['daily_total_time'] = $dailyTaskTime;

                usort($data['events'], function($a, $b) {
                    return $b->created_at <=> $a->created_at; // Latest first
                });
            }
        }

        // Sort days descending
        uksort($dayGroups, function($a, $b) {
            return strtotime($b) - strtotime($a);
        });

        return view('projects.show', compact('project', 'employees', 'departments', 'dayGroups'));
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
            'department' => 'required|string',
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
            'department' => $request->department,
            'description' => $request->description,
            'technology' => $request->technology,
            'leaders' => $request->leaders,
            'members' => $request->members,
            'documents' => $documents,
            // 'type' => $request->type,
            // 'manage' => $request->manage,
        ]);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully');
    }

    public function updateField(Request $request, Project $project)
    {
        $fields = ['status', 'members', 'leaders'];
        $role = strtoupper(auth()->user()->role ?? 'USER');
        $isLead = false;
        if (is_array($project->leaders)) {
            $isLead = in_array(auth()->user()->employee_id, $project->leaders);
        }

        foreach ($fields as $field) {
            if ($request->has($field)) {
                // Only Admin or Lead can change status
                if ($field === 'status' && $role !== 'ADMIN' && $role !== 'SUPER ADMIN' && !$isLead) {
                    continue;
                }
                $project->$field = $request->$field;
            }
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
