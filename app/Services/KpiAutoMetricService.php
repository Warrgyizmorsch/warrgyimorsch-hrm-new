<?php

namespace App\Services;

use App\Models\DailyTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiAutoMetricService
{
    /**
     * Compute an auto-metric's value for an employee/month. Returns null when the metric
     * can't be computed (e.g. nothing due that month) — the item is then left for manual entry.
     */
    public static function compute(string $metricKey, int $employeeId, string $month): ?float
    {
        return match ($metricKey) {
            'tasks_completed_on_time' => self::tasksCompletedOnTime($employeeId, $month),
            'daily_reports_submitted' => self::dailyReportsSubmitted($employeeId, $month),
            default => null,
        };
    }

    /**
     * Number of days in the month the employee submitted a work report — the same count as
     * the "Report submitted" card on Daily Tasks: distinct task dates (start_date) that have
     * at least one progress entry. Counted by the task's date, not the day it was typed in.
     */
    private static function dailyReportsSubmitted(int $employeeId, string $month): float
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return (float) DB::table('daily_tasks')
            ->join('task_follow_ups', 'task_follow_ups.daily_task_id', '=', 'daily_tasks.id')
            ->where('daily_tasks.employee_id', $employeeId)
            ->whereDate('daily_tasks.start_date', '>=', $start->toDateString())
            ->whereDate('daily_tasks.start_date', '<=', $start->copy()->endOfMonth()->toDateString())
            ->distinct()
            ->count(DB::raw('DATE(daily_tasks.start_date)'));
    }

    /**
     * % of an employee's tasks DUE in the given month that were marked Completed on or
     * before their deadline (end_date). Completion time comes from the append-only
     * TaskStatusHistory log (last row where new_status = 'Completed'), falling back to
     * status_changed_at for tasks completed via the generic edit path with no history row.
     */
    private static function tasksCompletedOnTime(int $employeeId, string $month): ?float
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $tasks = DailyTask::where('employee_id', $employeeId)
            ->whereBetween('end_date', [$start->toDateString(), $end->toDateString()])
            ->with(['statusHistory' => fn ($q) => $q->where('new_status', 'Completed')->latest()])
            ->get();

        if ($tasks->isEmpty()) {
            return null;
        }

        $onTime = $tasks->filter(function ($task) {
            if (strcasecmp((string) $task->status, 'Completed') !== 0) {
                return false;
            }

            $completedAt = $task->statusHistory->first()?->created_at ?? $task->status_changed_at;
            if (!$completedAt) {
                return false;
            }

            return $completedAt->toDateString() <= $task->end_date->toDateString();
        })->count();

        return round(($onTime / $tasks->count()) * 100, 2);
    }
}
