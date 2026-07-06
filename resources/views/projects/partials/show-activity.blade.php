<div class="pd-activity-feed">
    @forelse($dayGroups as $date => $tasksInDay)
        @php
            $totalDayTime = 0;
            foreach ($tasksInDay as $tData) {
                foreach ($tData['events'] as $e) {
                    if ($e->type === 'progress' && $e->time_taken) {
                        $totalDayTime += (float) preg_replace('/[^0-9.]/', '', $e->time_taken);
                    }
                }
            }
            $dayHours = floor($totalDayTime);
            $dayMins = round(($totalDayTime - $dayHours) * 60);
            $dayTimeStr = trim(($dayHours > 0 ? $dayHours . 'h ' : '') . ($dayMins > 0 ? $dayMins . 'm' : ($dayHours === 0 ? '0m' : '')));
        @endphp

        <div class="pd-activity-day">
            <div class="pd-activity-day-head">
                <div class="pd-activity-day-title">
                    <i class="feather-calendar"></i>
                    <strong>{{ $date }}</strong>
                </div>
                <span class="pm-badge pm-badge--muted">Day total: {{ $dayTimeStr }}</span>
            </div>

            @foreach($tasksInDay as $taskId => $data)
                @php $task = $data['task']; @endphp
                <div class="pd-activity-task">
                    <div class="pd-activity-task-head">
                        <div class="pd-activity-task-main">
                            <span class="pd-team-avatar">{{ strtoupper(substr($task->employee->name ?? 'U', 0, 1)) }}</span>
                            <div>
                                <div class="pd-activity-task-title">
                                    {{ $task->task_title }}
                                    <span class="pm-tag pm-tag--muted">{{ $task->status }}</span>
                                </div>
                                <div class="pd-activity-task-meta">
                                    <span>{{ $task->employee->name ?? 'Unassigned' }}</span>
                                    <span>·</span>
                                    <span>{{ $task->created_at->format('h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        @if(($data['daily_total_time'] ?? 0) > 0)
                            @php
                                $totalDecimal = $data['daily_total_time'];
                                $h = floor($totalDecimal);
                                $m = round(($totalDecimal - $h) * 60);
                                $timeDisplay = trim(($h > 0 ? $h . 'h ' : '') . ($m > 0 ? $m . 'm' : ($h === 0 ? '0m' : '')));
                            @endphp
                            <span class="pm-badge pm-badge--info">{{ $timeDisplay }} logged</span>
                        @endif
                    </div>

                    @if($task->description)
                        <div class="pd-activity-task-desc">{!! Str::limit(strip_tags($task->description), 200) !!}</div>
                    @endif

                    @foreach($data['events'] as $event)
                        @if($event->type === 'creation') @continue @endif
                        <div class="pd-activity-event">
                            <div class="pd-activity-event-head">
                                <span><i class="feather-edit-2"></i> Progress update</span>
                                <span>{{ $event->created_at->format('h:i A') }}</span>
                            </div>
                            @if($event->time_taken)
                                <span class="pm-badge pm-badge--info mb-2">{{ $event->time_taken }}</span>
                            @endif
                            <div class="pd-activity-event-body activity-description">
                                {!! $event->description ?: '<em class="text-muted">No details provided</em>' !!}
                            </div>
                            @if($event->photo)
                                <button type="button" onclick="viewAttachmentPopup('{{ asset('storage/' . $event->photo) }}')" class="pd-attach-btn">
                                    <i class="feather-paperclip"></i> View attachment
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @empty
        <div class="pd-empty-state">
            <i class="feather-activity"></i>
            <p>No activity recorded for this project yet.</p>
            <a href="{{ route('daily-tasks.index', ['project_id' => $project->id]) }}" class="zoho-btn-primary btn-sm">Go to daily tasks</a>
        </div>
    @endforelse
</div>
