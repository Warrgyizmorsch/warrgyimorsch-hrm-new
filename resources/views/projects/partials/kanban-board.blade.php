@php
    $boardColumns = [
        ['status' => 'Pending', 'class' => 'pending', 'label' => 'Pending'],
        ['status' => 'In Process', 'class' => 'process', 'label' => 'In Process'],
        ['status' => 'Review', 'class' => 'review', 'label' => 'Review'],
        ['status' => 'On Hold', 'class' => 'hold', 'label' => 'On Hold'],
        ['status' => 'Rework', 'class' => 'rework', 'label' => 'Rework'],
        ['status' => 'Completed', 'class' => 'completed', 'label' => 'Completed'],
    ];

    $boardProjects = $projects instanceof \Illuminate\Pagination\AbstractPaginator
        ? collect($projects->items())
        : collect($projects);

    $grouped = $boardProjects->groupBy(fn ($p) => $p->normalized_status);
@endphp

<div class="pm-kanban-wrap {{ ($view ?? 'list') === 'board' ? 'is-active' : '' }}" id="pmKanbanWrap">
    <div class="pm-kanban-board">
        @foreach($boardColumns as $col)
            @php $colProjects = $grouped->get($col['status'], collect()); @endphp
            <div class="pm-kanban-col pm-kanban-col--{{ $col['class'] }}"
                 data-status="{{ $col['status'] }}"
                 ondragover="pmKanbanAllowDrop(event)"
                 ondragleave="pmKanbanDragLeave(event)"
                 ondrop="pmKanbanDrop(event, '{{ $col['status'] }}')">
                <div class="pm-kanban-col__head">
                    <span class="pm-kanban-col__title">{{ $col['label'] }}</span>
                    <span class="pm-kanban-col__count">{{ $colProjects->count() }}</span>
                </div>
                <div class="pm-kanban-col__body">
                    @forelse($colProjects as $project)
                        @php
                            $progressVal = $project->display_progress;
                            $taskTotal = (int) ($project->tasks_count ?? 0);
                            $taskDone = (int) ($project->completed_tasks_count ?? 0);
                        @endphp
                        <div class="pm-kanban-card"
                             draggable="true"
                             data-slug="{{ $project->slug }}"
                             ondragstart="pmKanbanDragStart(event)">
                            <div class="pm-kanban-card__head">
                                <a href="{{ route('projects.show', $project) }}" class="pm-kanban-card__title">{{ $project->name }}</a>
                                @if($project->is_overdue)
                                    <span class="pm-badge pm-badge--overdue">Overdue</span>
                                @endif
                            </div>
                            <div class="pm-kanban-card__meta">
                                @if($project->technology)
                                    <span class="pm-tag pm-tag--tech">{{ $project->technology }}</span>
                                @endif
                                @if($taskTotal > 0)
                                    <span class="pm-tag pm-tag--tasks">{{ $taskDone }}/{{ $taskTotal }} tasks</span>
                                @endif
                            </div>
                            <div class="pm-kanban-card__foot">
                                <div class="pm-kanban-progress" title="{{ $progressVal }}% complete">
                                    <span style="width: {{ $progressVal }}%;"></span>
                                </div>
                                <div class="pm-kanban-card__actions">
                                    <a href="{{ route('daily-tasks.index', ['project_id' => $project->id]) }}" class="pm-kanban-link" title="Tasks">
                                        <i class="feather-check-square"></i>
                                    </a>
                                    <span class="small fw-bold text-primary">{{ $progressVal }}%</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">No projects</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
