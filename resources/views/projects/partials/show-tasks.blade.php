<div class="pd-card">
    <div class="pd-card-head pd-card-head--split">
        <div>
            <h2>Project tasks</h2>
            <p class="pd-card-subtitle">All daily tasks linked to this project</p>
        </div>
        <a href="{{ route('daily-tasks.index', ['project_id' => $project->id]) }}" class="zoho-btn-primary btn-sm">
            <i class="feather-plus"></i> Manage tasks
        </a>
    </div>
    <div class="pd-card-body p-0">
        <div class="table-responsive">
            <table class="table zoho-data-table mb-0">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assignee</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Time logged</th>
                        <th>Created</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        @php
                            $statusSlug = strtolower(str_replace(' ', '-', $task->status ?? 'pending'));
                            $statusTone = match (strtolower($task->status ?? '')) {
                                'completed', 'done' => 'completed',
                                'in process', 'in progress' => 'process',
                                'pending', 'not started' => 'pending',
                                default => 'default',
                            };
                        @endphp
                        <tr>
                            <td>
                                <strong class="pd-task-title">{{ $task->task_title }}</strong>
                                @if($task->description)
                                    <div class="pd-task-desc">{{ Str::limit(strip_tags($task->description), 80) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($task->employee)
                                    <div class="pd-team-person pd-team-person--compact">
                                        <span class="pd-team-avatar">{{ strtoupper(substr($task->employee->name, 0, 1)) }}</span>
                                        <strong>{{ $task->employee->name }}</strong>
                                    </div>
                                @else
                                    <span class="text-muted">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <span class="pm-status pm-status--{{ $statusTone }}">{{ $task->status ?? 'Pending' }}</span>
                            </td>
                            <td>
                                <span class="pm-tag pm-tag--muted">{{ $task->priority ?? 'Normal' }}</span>
                            </td>
                            <td>{{ $task->formatted_total_time ?? '0m' }}</td>
                            <td>{{ $task->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('daily-tasks.index', ['project_id' => $project->id]) }}" class="zoho-icon-btn" title="Open in daily tasks">
                                    <i class="feather-external-link"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="pd-empty-state">
                                    <i class="feather-check-square"></i>
                                    <p>No tasks yet for this project.</p>
                                    <a href="{{ route('daily-tasks.index', ['project_id' => $project->id]) }}" class="zoho-btn-primary btn-sm">Add tasks</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
