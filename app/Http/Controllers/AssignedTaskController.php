<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\Employee;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "My Tasks" hub, built mobile-first:
 *   - Assigned to me : tasks other people gave me (Quick Notes tasks and project tasks), grouped by who gave them
 *   - Assigned by me : tasks I gave others, grouped by the person doing them
 *   - My notes       : personal Quick Notes reminders
 * Every task here is a normal Daily Task, so it also shows in Daily Tasks for the person doing it.
 */
class AssignedTaskController extends Controller
{
    /** Completed tasks older than this are left out to keep the page fast on mobile. */
    public const COMPLETED_DAYS = 90;

    public const VIEWS = ['to', 'by', 'notes'];
    public const FILTERS = ['open', 'overdue', 'completed', 'all'];

    public function index(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        $view = in_array($request->query('view'), self::VIEWS, true) ? $request->query('view') : 'to';
        $filter = in_array($request->query('show'), self::FILTERS, true) ? $request->query('show') : 'open';

        $relations = ['employee.departmentRef', 'creator.employee.departmentRef', 'project:id,name', 'latestStatusHistory.user'];

        $toMe = $user->employee_id
            ? $this->recent(DailyTask::with($relations)
                ->where('employee_id', $user->employee_id)
                ->whereNotNull('assigned_by')
                ->where('assigned_by', '!=', $user->id))
            : collect();

        $byMe = $this->recent(DailyTask::with($relations)
            ->where('assigned_by', $user->id)
            ->where(fn ($q) => $q->whereNull('employee_id')->orWhere('employee_id', '!=', $user->employee_id ?? 0)));

        $notes = Note::where('user_id', $user->id)->orderBy('is_completed')->orderByDesc('created_at')->get();

        $tabs = [
            'to' => ['To me', $toMe->where('status', '!=', 'Completed')->count()],
            'by' => ['By me', $byMe->where('status', '!=', 'Completed')->count()],
            'notes' => ['My notes', $notes->where('is_completed', false)->count()],
        ];

        $data = compact('view', 'filter', 'today', 'tabs', 'notes') + [
            'completedDays' => self::COMPLETED_DAYS,
            // For the Quick Notes "Assign to" picker in the + popup.
            'employees' => Employee::active()->with('departmentRef')->orderBy('name')->get(),
        ];

        if ($view !== 'notes') {
            $tasks = $view === 'to' ? $toMe : $byMe;
            $data += $this->groupTasks($tasks, $filter, $today, $view);
        }

        return view('tasks.assigned', $data);
    }

    /** Open tasks plus anything completed recently, soonest due first. */
    private function recent($query): Collection
    {
        return $query
            ->where(fn ($q) => $q->where('status', '!=', 'Completed')
                ->orWhere('updated_at', '>=', now()->subDays(self::COMPLETED_DAYS)))
            ->orderByRaw("CASE WHEN status = 'Completed' THEN 1 ELSE 0 END")
            ->orderByRaw('end_date IS NULL, end_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Summary numbers, then one group per person: for "to" the person who gave the task,
     * for "by" the person doing it. People with overdue work first.
     */
    private function groupTasks(Collection $tasks, string $filter, Carbon $today, string $view): array
    {
        $isOverdue = fn (DailyTask $t) => $t->status !== 'Completed' && $t->end_date && $t->end_date->lt($today);
        $personKey = fn (DailyTask $t) => $view === 'to' ? (int) $t->assigned_by : (int) $t->employee_id;

        $summary = [
            'open' => $tasks->where('status', '!=', 'Completed')->count(),
            'overdue' => $tasks->filter($isOverdue)->count(),
            'completed' => $tasks->where('status', 'Completed')->count(),
            'people' => $tasks->map($personKey)->filter()->unique()->count(),
        ];

        $visible = match ($filter) {
            'open' => $tasks->where('status', '!=', 'Completed'),
            'overdue' => $tasks->filter($isOverdue),
            'completed' => $tasks->where('status', 'Completed'),
            default => $tasks,
        };

        $groups = $visible->groupBy($personKey)
            ->map(function (Collection $personTasks) use ($tasks, $isOverdue, $personKey, $view) {
                $first = $personTasks->first();
                $all = $tasks->filter(fn ($t) => $personKey($t) === $personKey($first));

                if ($view === 'to') {
                    $employee = $first->creator?->employee;
                    $name = $first->creator->name ?? 'Someone';
                } else {
                    $employee = $first->employee;
                    $name = $employee->name ?? 'Unassigned';
                }

                return [
                    'name' => $name,
                    'employee' => $employee,
                    'tasks' => $personTasks->values(),
                    'open' => $all->where('status', '!=', 'Completed')->count(),
                    'overdue' => $all->filter($isOverdue)->count(),
                    'done' => $all->where('status', 'Completed')->count(),
                    'total' => $all->count(),
                ];
            })
            ->sortBy([
                fn ($a, $b) => $b['overdue'] <=> $a['overdue'],
                fn ($a, $b) => $b['open'] <=> $a['open'],
                fn ($a, $b) => strcasecmp($a['name'], $b['name']),
            ])
            ->values();

        return compact('summary', 'groups');
    }
}
