@php
    $cleanDescription = $project->description;
    if ($cleanDescription) {
        $cleanDescription = preg_replace('/<(p|li|div|span)[^>]*>\s*(<br\/?>|&nbsp;|\s)*<\/\1>/i', '', $cleanDescription);
        $cleanDescription = preg_replace('/(<br\/?>|&nbsp;|\s)+$/i', '', $cleanDescription);
    }
    $timelineProgress = $projectMetrics['timeline_progress'] ?? $project->progress;
@endphp

<div class="row g-4">
    <div class="col-xl-8">
        <div class="pd-card">
            <div class="pd-card-head">
                <h2>About this project</h2>
            </div>
            <div class="pd-card-body">
                <div class="pd-meta-grid">
                    <div class="pd-meta-item">
                        <span class="pd-meta-label">Department</span>
                        <span class="pd-meta-value">{{ $project->departments->pluck('name')->implode(', ') ?: '—' }}</span>
                    </div>
                    <div class="pd-meta-item">
                        <span class="pd-meta-label">Technology</span>
                        <span class="pd-meta-value">{{ $project->technology ?: '—' }}</span>
                    </div>
                    <div class="pd-meta-item">
                        <span class="pd-meta-label">Project type</span>
                        <span class="pd-meta-value">{{ $project->type ?? 'Standard' }}</span>
                    </div>
                    <div class="pd-meta-item">
                        <span class="pd-meta-label">Access</span>
                        <span class="pd-meta-value">{{ $project->manage ?? 'Everyone' }}</span>
                    </div>
                    <div class="pd-meta-item">
                        <span class="pd-meta-label">Start date</span>
                        <span class="pd-meta-value">{{ $project->start_date ? $project->start_date->format('d M Y') : '—' }}</span>
                    </div>
                    <div class="pd-meta-item">
                        <span class="pd-meta-label">Due date</span>
                        <span class="pd-meta-value {{ $project->is_overdue ? 'text-danger fw-semibold' : '' }}">
                            {{ $project->end_date ? $project->end_date->format('d M Y') : 'Ongoing' }}
                        </span>
                    </div>
                </div>

                <div class="pd-section">
                    <h3>Description</h3>
                    <div class="pd-description">
                        @if($cleanDescription)
                            {!! $cleanDescription !!}
                        @else
                            <p class="text-muted mb-0">No description provided for this project.</p>
                        @endif
                    </div>
                </div>

                @if(is_array($project->documents) && count($project->documents) > 0)
                    <div class="pd-section">
                        <h3>Documents</h3>
                        <div class="pd-doc-grid">
                            @foreach($project->documents as $doc)
                                @php
                                    $fileName = basename($doc);
                                    $fileUrl = asset('storage/' . $doc);
                                @endphp
                                <a href="{{ $fileUrl }}" target="_blank" class="pd-doc-card">
                                    <i class="feather-file-text"></i>
                                    <span>{{ $fileName }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="pd-card mb-4">
            <div class="pd-card-head">
                <h2>Progress</h2>
            </div>
            <div class="pd-card-body">
                <div class="pd-progress-block">
                    <div class="pd-progress-block__label">
                        <span>Task completion</span>
                        <strong>{{ $projectMetrics['progress'] ?? 0 }}%</strong>
                    </div>
                    <div class="pd-progress-bar">
                        <span style="width: {{ $projectMetrics['progress'] ?? 0 }}%;"></span>
                    </div>
                </div>
                <div class="pd-progress-block">
                    <div class="pd-progress-block__label">
                        <span>Timeline elapsed</span>
                        <strong>{{ $timelineProgress }}%</strong>
                    </div>
                    <div class="pd-progress-bar pd-progress-bar--muted">
                        <span style="width: {{ $timelineProgress }}%;"></span>
                    </div>
                </div>
                <div id="project-progress-chart" class="pd-chart"></div>
            </div>
        </div>

        <div class="pd-card mb-4">
            <div class="pd-card-head pd-card-head--split">
                <h2>Project team</h2>
                <span class="pd-head-count">{{ ($projectLeads->count() + $projectMembers->count()) }} people</span>
            </div>
            <div class="pd-card-body">
                <div class="pd-team-section">
                    <span class="pd-team-label">Leads</span>
                    @forelse($projectLeads as $emp)
                        <div class="pd-team-person">
                            <span class="pd-team-avatar pd-team-avatar--lead">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                            <div>
                                <strong>{{ $emp->name }}</strong>
                                <small>{{ $emp->departmentRef->name ?? 'Lead' }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="pd-empty-line">No leads assigned.</p>
                    @endforelse
                </div>

                <div class="pd-team-section">
                    <span class="pd-team-label">Members</span>
                    @forelse($projectMembers as $emp)
                        <div class="pd-team-person">
                            <span class="pd-team-avatar">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                            <div>
                                <strong>{{ $emp->name }}</strong>
                                <small>{{ $emp->departmentRef->name ?? 'Member' }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="pd-empty-line">No members assigned.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="pd-card">
            <div class="pd-card-head">
                <h2>Quick actions</h2>
            </div>
            <div class="pd-card-body pd-quick-actions">
                <a href="{{ route('daily-tasks.index', ['project_id' => $project->id]) }}" class="pd-action-link">
                    <i class="feather-check-square"></i> View daily tasks
                </a>
                <a href="{{ route('projects.edit', $project) }}" class="pd-action-link">
                    <i class="feather-edit-3"></i> Edit project
                </a>
                <a href="javascript:void(0);" onclick="showTaskProgress('{{ $project->slug }}')" class="pd-action-link">
                    <i class="feather-clipboard"></i> Task analysis
                </a>
                <form action="{{ route('projects.destroy', $project->slug) }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="button" class="pd-action-link pd-action-link--danger w-100 text-start"
                            onclick="confirmDeleteProject(this.closest('form'), '{{ addslashes($project->name) }}')">
                        <i class="feather-trash-2"></i> Delete project
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
