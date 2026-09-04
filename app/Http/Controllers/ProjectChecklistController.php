<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectChecklistTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectChecklistController extends Controller
{
    private function roleFlags(Project $project): array
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, [
            'super_admin',
            'manager',
            'hr_executive',
            'hr_intern',
            'business_operation_head',
        ]);
        $isLeader = is_array($project->leaders) && in_array($user->employee_id, $project->leaders, true);
        $isMember = is_array($project->members) && in_array($user->employee_id, $project->members, true);
        $isTeamLeaderRole = $role === 'team_leader';

        return compact('isAdmin', 'isLeader', 'isMember', 'isTeamLeaderRole');
    }

    private function assertProjectMember(Project $project): void
    {
        ['isAdmin' => $isAdmin, 'isLeader' => $isLeader, 'isMember' => $isMember] = $this->roleFlags($project);

        if (!$isAdmin && !$isLeader && !$isMember) {
            abort(403, 'You are not a member of this project.');
        }
    }

    private function canAssign(Project $project): bool
    {
        ['isAdmin' => $isAdmin, 'isLeader' => $isLeader, 'isTeamLeaderRole' => $isTeamLeaderRole] = $this->roleFlags($project);

        return $isAdmin || $isLeader || $isTeamLeaderRole;
    }

    private function assertCanToggle(ProjectChecklistTemplate $template): void
    {
        $project = $template->project;
        $this->assertProjectMember($project);

        if (!$template->assigned_to) {
            return;
        }

        ['isAdmin' => $isAdmin, 'isLeader' => $isLeader, 'isTeamLeaderRole' => $isTeamLeaderRole] = $this->roleFlags($project);

        if ($isAdmin || $isLeader || $isTeamLeaderRole) {
            return;
        }

        if ((int) $template->assigned_to === (int) auth()->user()->employee_id) {
            return;
        }

        abort(403, 'Only the assignee or a team leader can update this item.');
    }

    private function currentWeekStart(): string
    {
        return Carbon::now()->startOfWeek()->toDateString();
    }

    private const WEEKDAY_LABELS = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
    ];

    public function index(Project $project, Request $request)
    {
        $this->assertProjectMember($project);

        $weekStartCarbon = $request->filled('week')
            ? Carbon::parse($request->query('week'))->startOfWeek()
            : Carbon::now()->startOfWeek();
        $weekStart = $weekStartCarbon->toDateString();
        $isCurrentWeek = $weekStart === $this->currentWeekStart();

        $templates = $project->checklistTemplates()
            ->with(['completions' => function ($q) use ($weekStart) {
                $q->where('week_start', $weekStart);
            }])
            ->get();

        $employeeIds = $templates->pluck('completions.0.completed_by')
            ->merge($templates->pluck('assigned_to'))
            ->filter()
            ->unique();
        $employeeNames = Employee::whereIn('id', $employeeIds)->pluck('name', 'id');

        $today = Carbon::now();

        $items = $templates->map(function ($template) use ($employeeNames, $isCurrentWeek, $weekStartCarbon, $today) {
            $completion = $template->completions->first();
            $isDone = (bool) ($completion->is_done ?? false);

            $isOverdue = false;
            if ($isCurrentWeek && !$isDone && $template->due_weekday) {
                $dueDate = $weekStartCarbon->copy()->addDays($template->due_weekday - 1)->endOfDay();
                $isOverdue = $today->greaterThan($dueDate);
            }

            return [
                'id' => $template->id,
                'label' => $template->label,
                'is_done' => $isDone,
                'note' => $completion->note ?? null,
                'completed_by_name' => $completion && $completion->completed_by
                    ? ($employeeNames[$completion->completed_by] ?? null)
                    : null,
                'completed_at' => $completion?->completed_at?->diffForHumans(),
                'assigned_to' => $template->assigned_to,
                'assigned_to_name' => $template->assigned_to ? ($employeeNames[$template->assigned_to] ?? null) : null,
                'due_weekday' => $template->due_weekday,
                'due_weekday_label' => $template->due_weekday ? (self::WEEKDAY_LABELS[$template->due_weekday] ?? null) : null,
                'is_overdue' => $isOverdue,
            ];
        });

        $canAssign = $this->canAssign($project);
        $assignableEmployees = [];
        if ($canAssign) {
            $memberIds = collect((array) $project->members)->merge((array) $project->leaders)->filter()->unique();
            $assignableEmployees = Employee::whereIn('id', $memberIds)->orderBy('name')->get(['id', 'name']);
        }

        return response()->json([
            'items' => $items,
            'week_start' => $weekStart,
            'is_current_week' => $isCurrentWeek,
            'can_assign' => $canAssign,
            'assignable_employees' => $assignableEmployees,
        ]);
    }

    public function storeTemplate(Request $request, Project $project)
    {
        $this->assertProjectMember($project);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:employees,id',
            'due_weekday' => 'nullable|integer|min:1|max:7',
        ]);

        $canAssign = $this->canAssign($project);

        $nextOrder = (int) $project->checklistTemplates()->max('sort_order') + 1;

        $template = ProjectChecklistTemplate::create([
            'project_id' => $project->id,
            'label' => $validated['label'],
            'assigned_to' => $canAssign ? ($validated['assigned_to'] ?? null) : null,
            'due_weekday' => $canAssign ? ($validated['due_weekday'] ?? null) : null,
            'sort_order' => $nextOrder,
            'created_by' => auth()->user()->employee_id,
        ]);

        return response()->json(['success' => 'Checklist item added.', 'template' => $template]);
    }

    public function updateTemplate(Request $request, ProjectChecklistTemplate $template)
    {
        $project = $template->project;
        $this->assertProjectMember($project);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:employees,id',
            'due_weekday' => 'nullable|integer|min:1|max:7',
        ]);

        $canAssign = $this->canAssign($project);

        $update = ['label' => $validated['label']];
        if ($canAssign) {
            $update['assigned_to'] = $validated['assigned_to'] ?? null;
            $update['due_weekday'] = $validated['due_weekday'] ?? null;
        }

        $template->update($update);

        return response()->json(['success' => 'Checklist item updated.', 'template' => $template]);
    }

    public function destroyTemplate(ProjectChecklistTemplate $template)
    {
        $this->assertProjectMember($template->project);

        $template->delete();

        return response()->json(['success' => 'Checklist item deleted.']);
    }

    public function toggleCompletion(Request $request, ProjectChecklistTemplate $template)
    {
        $this->assertCanToggle($template);

        $validated = $request->validate([
            'is_done' => 'required|boolean',
            'note' => 'nullable|string|max:2000',
        ]);

        $currentWeekStart = $this->currentWeekStart();

        if ($request->filled('week_start') && $request->input('week_start') !== $currentWeekStart) {
            return response()->json(['error' => 'Only the current week can be updated.'], 422);
        }

        $completion = $template->completions()->updateOrCreate(
            ['week_start' => $currentWeekStart],
            [
                'is_done' => $validated['is_done'],
                'note' => $validated['note'] ?? null,
                'completed_by' => auth()->user()->employee_id,
                'completed_at' => now(),
            ]
        );

        return response()->json(['success' => 'Checklist item updated.', 'completion' => $completion]);
    }
}
