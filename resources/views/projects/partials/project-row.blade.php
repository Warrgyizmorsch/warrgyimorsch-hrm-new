@php
    $normalizedStatus = $project->normalized_status;
    $progressVal = $project->display_progress;
    $taskTotal = (int) ($project->tasks_count ?? 0);
    $taskDone = (int) ($project->completed_tasks_count ?? 0);
    $leaders = is_array($project->leaders) ? $project->leaders : [];
    $members = is_array($project->members) ? $project->members : [];
    $leaderNames = collect($leaders)->map(fn ($id) => $employeesById->get($id)?->name)->filter()->values();
    $memberNames = collect($members)->map(fn ($id) => $employeesById->get($id)?->name)->filter()->values();
    $currentLeads = $leaderNames->isNotEmpty() ? $leaderNames->take(2)->join(', ') : 'Assign lead';
    $currentMembers = $memberNames->isNotEmpty() ? $memberNames->take(2)->join(', ') : 'Assign team';
    $statusSlug = strtolower(str_replace(' ', '-', $normalizedStatus));
    $projectDepartmentNames = $project->departments->pluck('name');
    $projectDepartmentIds = $project->departments->pluck('id')->all();
    $searchBlob = strtolower(implode(' ', array_filter([
        $project->name,
        $project->technology,
        $projectDepartmentNames->implode(' '),
        strip_tags($project->description ?? ''),
    ])));
@endphp
<tr class="pm-row single-item"
    data-name="{{ strtolower($project->name) }}"
    data-search="{{ $searchBlob }}"
    data-status="{{ $normalizedStatus }}"
    data-department="{{ $projectDepartmentNames->implode(',') }}">
    <td class="pm-col-check">
        <input type="checkbox" class="form-check-input shadow-none pm-project-check"
               value="{{ $project->id }}" data-project-id="{{ $project->id }}">
    </td>
    <td class="pm-col-project">
        <div class="pm-project-cell">
            <div class="pm-progress-ring" title="{{ $progressVal }}% complete">
                @php $ringOffset = 263.89 * (1 - $progressVal / 100); @endphp
                <svg width="48" height="48" viewBox="0 0 100 100" aria-hidden="true">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#eef1f5" stroke-width="10"></circle>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#1070e0"
                            stroke-width="10" stroke-dasharray="263.89"
                            stroke-dashoffset="{{ $ringOffset }}" stroke-linecap="round"></circle>
                </svg>
                <span class="pm-progress-ring__pct">{{ $progressVal }}%</span>
            </div>
            <div class="pm-project-meta">
                <div class="pm-project-title-row">
                    <a href="{{ route('projects.show', $project) }}" class="pm-project-name">{{ $project->name }}</a>
                    @if($project->is_overdue)
                        <span class="pm-badge pm-badge--overdue">Overdue</span>
                    @elseif($project->end_date && $project->end_date->isBetween(now(), now()->addDays(7)))
                        <span class="pm-badge pm-badge--due-soon">Due soon</span>
                    @endif
                </div>
                <div class="pm-project-tags">
                    @if($project->technology)
                        <span class="pm-tag pm-tag--tech">{{ $project->technology }}</span>
                    @endif
                    @if($projectDepartmentNames->isNotEmpty())
                        <span class="pm-tag pm-tag--dept">{{ $projectDepartmentNames->implode(', ') }}</span>
                    @endif
                    @if($taskTotal > 0)
                        <a href="{{ route('daily-tasks.index', ['project_id' => $project->id]) }}" class="pm-tag pm-tag--tasks pm-tag--link">
                            {{ $taskDone }}/{{ $taskTotal }} tasks
                        </a>
                    @else
                        <span class="pm-tag pm-tag--muted">No tasks</span>
                    @endif
                </div>
                @if($project->description)
                    <div class="pm-project-desc">{!! strip_tags($project->description) !!}</div>
                @endif
            </div>
        </div>
    </td>
    <td class="pm-col-lead">
        <div class="dropdown pm-team-dropdown" data-bs-auto-close="outside">
            <button class="pm-team-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                @if($leaderNames->isNotEmpty())
                    <span class="pm-team-avatar">{{ strtoupper(substr($leaderNames->first(), 0, 1)) }}</span>
                @endif
                <span class="text-truncate">{{ $currentLeads }}</span>
            </button>
            <ul class="dropdown-menu pm-dropdown-menu">
                <li class="pm-dropdown-search">
                    <input type="text" class="form-control pm-dropdown-search-input lead-search"
                           oninput="window.filterLeadList(this)" placeholder="Search team...">
                </li>
                <li><a class="dropdown-item pm-dropdown-item lead-item" href="javascript:void(0);"
                       onclick="updateProjectLeads('{{ $project->slug }}', [])">No leads</a></li>
                @foreach($employees as $emp)
                    @if(in_array($emp->department_id, $projectDepartmentIds, true))
                        <li><a class="dropdown-item pm-dropdown-item lead-item {{ in_array($emp->id, $leaders) ? 'active' : '' }}"
                               href="javascript:void(0);"
                               onclick='toggleProjectLead("{{ $project->slug }}", @json($leaders), {{ $emp->id }})'>{{ $emp->name }}</a></li>
                    @endif
                @endforeach
            </ul>
        </div>
    </td>
    <td class="pm-col-team">
        <div class="dropdown pm-team-dropdown" data-bs-auto-close="outside">
            <button class="pm-team-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                @if($memberNames->isNotEmpty())
                    <div class="pm-member-stack">
                        @foreach($memberNames->take(3) as $name)
                            <span class="pm-member-avatar" title="{{ $name }}">{{ strtoupper(substr($name, 0, 1)) }}</span>
                        @endforeach
                        @if($memberNames->count() > 3)
                            <span class="pm-member-avatar pm-member-avatar--more">+{{ $memberNames->count() - 3 }}</span>
                        @endif
                    </div>
                @endif
                <span class="text-truncate">{{ $currentMembers }}</span>
            </button>
            <ul class="dropdown-menu pm-dropdown-menu">
                <li class="pm-dropdown-search">
                    <input type="text" class="form-control pm-dropdown-search-input lead-search"
                           oninput="window.filterLeadList(this)" placeholder="Search team...">
                </li>
                <li><a class="dropdown-item pm-dropdown-item lead-item" href="javascript:void(0);"
                       onclick='updateProjectMembers("{{ $project->slug }}", [])'>No members</a></li>
                @foreach($employees as $emp)
                    @if(in_array($emp->department_id, $projectDepartmentIds, true))
                        <li><a class="dropdown-item pm-dropdown-item lead-item {{ in_array($emp->id, $members) ? 'active' : '' }}"
                               href="javascript:void(0);"
                               onclick='toggleProjectMember("{{ $project->slug }}", @json($members), {{ $emp->id }})'>{{ $emp->name }}</a></li>
                    @endif
                @endforeach
            </ul>
        </div>
    </td>
    <td class="pm-col-status">
        <div class="dropdown pm-status-dropdown">
            <button class="pm-status pm-status--{{ $project->status_tone }} dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" data-bs-boundary="viewport">
                {{ $normalizedStatus }}
            </button>
            <ul class="dropdown-menu pm-dropdown-menu pm-dropdown-menu--compact">
                @foreach(['Pending', 'In Process', 'Review', 'On Hold', 'Rework', 'Completed'] as $opt)
                    <li><a class="dropdown-item pm-dropdown-item pm-status-option pm-status-option--{{ strtolower(str_replace(' ', '-', $opt)) }}"
                           href="javascript:void(0);"
                           onclick="updateProjectStatus('{{ $project->slug }}', '{{ $opt }}')">{{ $opt }}</a></li>
                @endforeach
            </ul>
        </div>
    </td>
    <td class="pm-col-dates">
        <div class="pm-timeline">
            <span><i class="feather-play"></i> {{ $project->start_date ? $project->start_date->format('d M Y') : '—' }}</span>
            <span class="{{ $project->is_overdue ? 'text-danger fw-semibold' : '' }}">
                <i class="feather-flag"></i> {{ $project->end_date ? $project->end_date->format('d M Y') : 'No deadline' }}
            </span>
        </div>
    </td>
    <td class="pm-col-actions text-end">
        <div class="pm-row-actions">
            <a href="javascript:void(0);" class="zoho-icon-btn zoho-icon-btn--warning btn-quick-view"
               data-project='@json($project)' title="Quick view">
                <i class="feather-info"></i>
            </a>
            <a href="javascript:void(0);" onclick="showTaskProgress('{{ $project->slug }}')"
               class="zoho-icon-btn zoho-icon-btn--success" title="Task analysis">
                <i class="feather-clipboard"></i>
            </a>
            <a href="{{ route('daily-tasks.index', ['project_id' => $project->id]) }}"
               class="zoho-icon-btn zoho-icon-btn--primary" title="Daily tasks">
                <i class="feather-check-square"></i>
            </a>
            <a href="{{ route('projects.show', $project) }}" class="zoho-icon-btn zoho-icon-btn--info" title="View">
                <i class="feather-eye"></i>
            </a>
            <a href="{{ route('projects.edit', $project->slug) }}" class="zoho-icon-btn zoho-icon-btn--edit" title="Edit">
                <i class="feather-edit-3"></i>
            </a>
            <form action="{{ route('projects.destroy', $project->slug) }}" method="POST" class="d-inline">
                @csrf @method('DELETE')
                <button type="button" class="zoho-icon-btn zoho-icon-btn--danger"
                        onclick="confirmDeleteProject(this.closest('form'), '{{ addslashes($project->name) }}')" title="Delete">
                    <i class="feather-trash-2"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
