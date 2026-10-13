<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Support\HtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Milestones / phases for a project (Jira-style epics). Each phase groups daily tasks
 * through daily_tasks.phase_id; tasks with no phase form the project backlog.
 */
class ProjectPhaseController extends Controller
{
    private const ADMIN_ROLES = [
        'super_admin',
        'manager',
        'hr_executive',
        'hr_intern',
        'business_operation_head',
    ];

    private function roleFlags(Project $project): array
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $employeeId = $user->employee_id;

        $isAdmin = in_array($role, self::ADMIN_ROLES, true);
        $isLeader = $employeeId && in_array((int) $employeeId, array_map('intval', (array) $project->leaders), true);
        $isMember = $employeeId && in_array((int) $employeeId, array_map('intval', (array) $project->members), true);
        $isTeamLeaderRole = $role === 'team_leader';

        return compact('isAdmin', 'isLeader', 'isMember', 'isTeamLeaderRole');
    }

    private function assertProjectMember(Project $project): void
    {
        $f = $this->roleFlags($project);

        if (!$f['isAdmin'] && !$f['isLeader'] && !$f['isMember'] && !$f['isTeamLeaderRole']) {
            abort(403, 'You are not a member of this project.');
        }
    }

    /** Admins, project leaders and team leaders can create/edit phases and assign tasks to others. */
    private function canManage(Project $project): bool
    {
        $f = $this->roleFlags($project);

        return $f['isAdmin'] || $f['isLeader'] || $f['isTeamLeaderRole'];
    }

    private function assertCanManage(Project $project): void
    {
        $this->assertProjectMember($project);

        if (!$this->canManage($project)) {
            abort(403, 'Only project leaders and admins can manage milestones.');
        }
    }

    private function projectMemberIds(Project $project): array
    {
        return collect((array) $project->leaders)
            ->merge((array) $project->members)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function formatTask(DailyTask $task): array
    {
        $isDone = in_array(strtolower($task->status ?? ''), ProjectPhase::DONE_TASK_STATUSES, true);

        return [
            'id' => $task->id,
            'title' => $task->task_title,
            'status' => $task->status ?? 'Pending',
            'priority' => $task->priority,
            'employee_id' => $task->employee_id,
            'assignee' => $task->employee?->name,
            'start_date' => $task->start_date?->toDateString(),
            'end_date' => $task->end_date?->toDateString(),
            'end_date_label' => $task->end_date?->format('d M'),
            'is_done' => $isDone,
            'is_overdue' => !$isDone && $task->end_date && $task->end_date->copy()->endOfDay()->isPast(),
            'phase_id' => $task->phase_id,
            'can_edit' => $this->canEditTask($task),
        ];
    }

    private function canEditTask(DailyTask $task): bool
    {
        if ($task->project && $this->canManage($task->project)) {
            return true;
        }

        return (int) $task->employee_id === (int) auth()->user()->employee_id;
    }

    public function index(Project $project)
    {
        $this->assertProjectMember($project);

        $phases = $project->phases()
            ->with(['owner:id,name', 'tasks' => fn ($q) => $q->with(['employee:id,name', 'project'])->orderBy('end_date')->orderBy('id')])
            ->get();

        $backlog = DailyTask::where('project_id', $project->id)
            ->whereNull('phase_id')
            ->with(['employee:id,name', 'project'])
            ->latest()
            ->get();

        $canManage = $this->canManage($project);

        $members = Employee::whereIn('id', $this->projectMemberIds($project))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'phases' => $phases->map(function (ProjectPhase $phase) {
                $tasks = $phase->tasks->map(fn ($t) => $this->formatTask($t));

                return [
                    'id' => $phase->id,
                    'name' => $phase->name,
                    'description' => $phase->description, // sanitized HTML
                    'documents' => $this->formatDocuments($phase),
                    'start_date' => $phase->start_date?->toDateString(),
                    'due_date' => $phase->due_date?->toDateString(),
                    'start_label' => $phase->start_date?->format('d M Y'),
                    'due_label' => $phase->due_date?->format('d M Y'),
                    'status' => $phase->status,
                    'owner_id' => $phase->owner_id,
                    'owner_name' => $phase->owner?->name,
                    'progress' => $phase->progress,
                    'is_overdue' => $phase->is_overdue,
                    'task_total' => $tasks->count(),
                    'task_done' => $tasks->where('is_done', true)->count(),
                    'task_overdue' => $tasks->where('is_overdue', true)->count(),
                    'tasks' => $tasks->values(),
                ];
            })->values(),
            'backlog' => $backlog->map(fn ($t) => $this->formatTask($t))->values(),
            'can_manage' => $canManage,
            'current_employee_id' => auth()->user()->employee_id,
            'members' => $members,
            'statuses' => ProjectPhase::STATUSES,
        ]);
    }

    private const DOCUMENT_DISK = 'public';
    private const DOCUMENT_DIR = 'project-phase-documents';
    private const MAX_DOCUMENTS = 20;

    private function formatDocuments(ProjectPhase $phase): array
    {
        return collect($phase->documents ?? [])->map(fn ($doc) => [
            'path' => $doc['path'],
            'name' => $doc['name'] ?? basename($doc['path']),
            'size' => $doc['size'] ?? null,
            'url' => asset('storage/' . $doc['path']),
        ])->values()->all();
    }

    /**
     * Apply removals and new uploads to the phase's document list. Returns the new list.
     */
    private function syncDocuments(Request $request, ?ProjectPhase $phase): array
    {
        $existing = collect($phase?->documents ?? []);
        $removed = (array) $request->input('removed_documents', []);

        // Only paths that really belong to this milestone can be removed.
        [$toDelete, $kept] = $existing->partition(fn ($doc) => in_array($doc['path'], $removed, true));

        $newFiles = (array) $request->file('documents', []);
        if ($kept->count() + count($newFiles) > self::MAX_DOCUMENTS) {
            abort(response()->json(['errors' => ['documents' => ['A milestone can have at most ' . self::MAX_DOCUMENTS . ' documents.']]], 422));
        }

        foreach ($newFiles as $file) {
            $kept->push([
                'path' => $file->store(self::DOCUMENT_DIR, self::DOCUMENT_DISK),
                'name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'size' => $file->getSize(),
            ]);
        }

        if ($toDelete->isNotEmpty()) {
            Storage::disk(self::DOCUMENT_DISK)->delete($toDelete->pluck('path')->all());
        }

        return $kept->values()->all();
    }

    private function phaseRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500000',
            'documents' => 'nullable|array|max:' . self::MAX_DOCUMENTS,
            'documents.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,csv,ppt,pptx,txt,jpg,jpeg,png,gif,webp,zip|max:10240',
            'removed_documents' => 'nullable|array',
            'removed_documents.*' => 'string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['required', Rule::in(ProjectPhase::STATUSES)],
            'owner_id' => 'nullable|exists:employees,id',
        ];
    }

    public function store(Request $request, Project $project)
    {
        $this->assertCanManage($project);

        $validated = $request->validate($this->phaseRules());
        unset($validated['documents'], $validated['removed_documents']);
        $validated['description'] = HtmlSanitizer::clean($validated['description'] ?? null);
        $validated['documents'] = $this->syncDocuments($request, null);

        $phase = ProjectPhase::create($validated + [
            'project_id' => $project->id,
            'sort_order' => (int) $project->phases()->max('sort_order') + 1,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['success' => 'Milestone created.', 'phase' => $phase]);
    }

    public function update(Request $request, ProjectPhase $phase)
    {
        $this->assertCanManage($phase->project);

        $validated = $request->validate($this->phaseRules());
        unset($validated['documents'], $validated['removed_documents']);
        $validated['description'] = HtmlSanitizer::clean($validated['description'] ?? null);
        $validated['documents'] = $this->syncDocuments($request, $phase);

        $phase->update($validated);

        return response()->json(['success' => 'Milestone updated.', 'phase' => $phase]);
    }

    public function destroy(ProjectPhase $phase)
    {
        $this->assertCanManage($phase->project);

        // Tasks are kept: the FK is nullOnDelete, so they drop back into the backlog.
        $paths = collect($phase->documents ?? [])->pluck('path')->filter()->all();
        $phase->delete();
        if ($paths) {
            Storage::disk(self::DOCUMENT_DISK)->delete($paths);
        }

        return response()->json(['success' => 'Milestone deleted. Its tasks moved to the backlog.']);
    }

    public function reorder(Request $request, Project $project)
    {
        $this->assertCanManage($project);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ownIds = $project->phases()->pluck('id')->all();

        DB::transaction(function () use ($validated, $ownIds) {
            foreach (array_values($validated['ids']) as $index => $id) {
                if (in_array((int) $id, $ownIds, true)) {
                    ProjectPhase::whereKey($id)->update(['sort_order' => $index + 1]);
                }
            }
        });

        return response()->json(['success' => 'Order saved.']);
    }

    /** Create a task directly inside a milestone. */
    public function storeTask(Request $request, ProjectPhase $phase)
    {
        $project = $phase->project;
        $this->assertProjectMember($project);

        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'priority' => 'required|in:Hard,Medium,Low',
            'status' => 'required|in:Pending,In Process,Review,Rework,On Hold,Completed',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        if ($this->canManage($project)) {
            if (empty($validated['employee_id'])) {
                return response()->json(['errors' => ['employee_id' => ['Choose who this task is for.']]], 422);
            }
            if (!in_array((int) $validated['employee_id'], $this->projectMemberIds($project), true)) {
                return response()->json(['errors' => ['employee_id' => ['You can only assign tasks to project members.']]], 422);
            }
        } else {
            // Regular members can only add tasks for themselves (same rule as Daily Tasks).
            $validated['employee_id'] = auth()->user()->employee_id;
        }

        // Fall back to the milestone due date, then the start date, so end_date is never empty.
        $endDate = $validated['end_date'] ?? $phase->due_date?->toDateString() ?? $validated['start_date'];

        $task = DailyTask::create([
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'task_title' => $validated['task_title'],
            'employee_id' => $validated['employee_id'],
            'assigned_by' => Auth::id(),
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date' => Carbon::parse($endDate)->endOfDay(),
            'description' => HtmlSanitizer::clean($validated['description'] ?? null),
        ]);

        return response()->json(['success' => 'Task added to milestone.', 'task_id' => $task->id]);
    }

    /** Move a task to another milestone of the same project, or back to the backlog (phase_id = null). */
    public function moveTask(Request $request, DailyTask $task)
    {
        $project = $task->project;
        if (!$project) {
            abort(422, 'This task is not linked to a project.');
        }

        $this->assertProjectMember($project);
        if (!$this->canEditTask($task)) {
            abort(403, 'Only the assignee or a project leader can move this task.');
        }

        $validated = $request->validate([
            'phase_id' => [
                'nullable',
                'integer',
                Rule::exists('project_phases', 'id')->where('project_id', $project->id),
            ],
        ]);

        $task->update(['phase_id' => $validated['phase_id'] ?? null]);

        return response()->json(['success' => 'Task moved.']);
    }
}
