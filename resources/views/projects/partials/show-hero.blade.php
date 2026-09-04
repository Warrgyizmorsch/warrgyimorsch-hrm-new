@php
    $progressVal = $projectMetrics['progress'] ?? $project->display_progress;
    $timelineProgress = $projectMetrics['timeline_progress'] ?? $project->progress;
    $normalizedStatus = $project->normalized_status;
    $ringOffset = 263.89 * (1 - $progressVal / 100);
    $totalHours = $projectMetrics['total_hours'] ?? 0;
    $hoursDisplay = floor($totalHours) . 'h' . (round(($totalHours - floor($totalHours)) * 60) > 0 ? ' ' . round(($totalHours - floor($totalHours)) * 60) . 'm' : '');
@endphp

<div class="pd-hero">
    <div class="pd-hero-main">
        <div class="pd-progress-ring" title="{{ $progressVal }}% task completion">
            <svg width="72" height="72" viewBox="0 0 100 100" aria-hidden="true">
                <circle cx="50" cy="50" r="42" fill="none" stroke="#eef1f5" stroke-width="10"></circle>
                <circle cx="50" cy="50" r="42" fill="none" stroke="#1070e0"
                        stroke-width="10" stroke-dasharray="263.89"
                        stroke-dashoffset="{{ $ringOffset }}" stroke-linecap="round"></circle>
            </svg>
            <span class="pd-progress-ring__value">{{ $progressVal }}%</span>
        </div>

        <div class="pd-hero-meta">
            <div class="pd-hero-title-row">
                <h1 class="pd-hero-title">{{ $project->name }}</h1>
                <span class="pm-status pm-status--{{ $project->status_tone }}">{{ $normalizedStatus }}</span>
                @if($project->is_overdue)
                    <span class="pm-badge pm-badge--overdue">Overdue</span>
                @elseif($project->end_date && $project->end_date->isBetween(now(), now()->addDays(7)))
                    <span class="pm-badge pm-badge--due-soon">Due soon</span>
                @endif
            </div>

            <div class="pd-hero-tags">
                @if($project->technology)
                    <span class="pm-tag pm-tag--tech">{{ $project->technology }}</span>
                @endif
                @if($project->departments->isNotEmpty())
                    <span class="pm-tag pm-tag--dept">{{ $project->departments->pluck('name')->implode(', ') }}</span>
                @endif
                @if(($project->type ?? null))
                    <span class="pm-tag pm-tag--muted">{{ $project->type }}</span>
                @endif
            </div>

            <div class="pd-hero-submeta">
                <span><i class="feather-calendar"></i> Started {{ $project->start_date ? $project->start_date->format('d M Y') : '—' }}</span>
                <span><i class="feather-flag"></i> Due {{ $project->end_date ? $project->end_date->format('d M Y') : 'No deadline' }}</span>
                @if($normalizedStatus !== 'Completed')
                    <span class="pd-timer task-timer"
                          @if($project->end_date) data-end="{{ $project->end_date->toIso8601String() }}"
                          @elseif($project->start_date) data-start="{{ $project->start_date->toIso8601String() }}" @endif>
                        Calculating...
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="pd-insights-row">
        <div class="pd-insight-card">
            <strong>{{ $taskStats['total'] ?? 0 }}</strong>
            <span>Total tasks</span>
        </div>
        <div class="pd-insight-card pd-insight-card--success">
            <strong>{{ $taskStats['completed'] ?? 0 }}</strong>
            <span>Completed</span>
        </div>
        <div class="pd-insight-card pd-insight-card--process">
            <strong>{{ $taskStats['in_process'] ?? 0 }}</strong>
            <span>In process</span>
        </div>
        <div class="pd-insight-card pd-insight-card--pending">
            <strong>{{ $taskStats['pending'] ?? 0 }}</strong>
            <span>Pending</span>
        </div>
        <div class="pd-insight-card">
            <strong>{{ $hoursDisplay }}</strong>
            <span>Hours logged</span>
        </div>
        <div class="pd-insight-card">
            <strong>{{ $projectMetrics['team_size'] ?? 0 }}</strong>
            <span>Team members</span>
        </div>
    </div>
</div>
