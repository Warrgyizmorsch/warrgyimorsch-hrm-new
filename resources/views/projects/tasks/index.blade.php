@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/daily-tasks-management.css') }}?v={{ filemtime(public_path('assets/css/daily-tasks-management.css')) ?: time() }}">
@endpush

@section('content')
    <style>
        /* Custom Dropdown Arrow Color to match Field Text */
        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23334155' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
            background-size: 12px 12px !important;
        }
    </style>

    <div class="zoho-page-shell daily-tasks-page attendance-page">
        @include('projects.tasks.partials.page-header')

        <div class="main-content zoho-module-content">
            @include('projects.tasks.partials.filter-panel')

            @if ($message = Session::get('success'))
                <div class="attendance-alert" role="alert">
                    <i class="feather-check-circle"></i>
                    <span>{{ $message }}</span>
                    <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="zoho-people-table-card">
                <div class="zoho-people-table-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="zoho-people-table-search">
                        <i class="feather-search"></i>
                        <input type="text" id="taskSearch"
                            onkeyup="searchTable()"
                            value="{{ request('search') }}"
                            placeholder="Search in list...">
                    </div>
                    <div class="zoho-list-bar mb-0 border-0 bg-transparent p-0">
                        <span class="text-muted small fw-bold text-uppercase">Show</span>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" id="showEntriesBtn"
                                style="width: 80px; height: 38px; padding: 0 12px;">
                                {{ $perPage ?? 20 }}
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 10px;">
                                <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 20) == 20 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}">20</a>
                                <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 50) == 50 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}">50</a>
                                <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 100) == 100 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}">100</a>
                            </div>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase">entries</span>
                    </div>
                </div>

                <div id="taskTableContainer">
                    <div id="taskTableContainerBodyPart">
                    <div class="card-body p-0 zoho-list-body">
                        <div class="table-responsive zoho-table-wrap">
                            <table class="table zoho-data-table mb-0" id="tasksTable">
                                <thead>
                                    <tr>
                                        <th style="width: 44px;"><input type="checkbox" id="selectAllTasks"
                                                class="form-check-input shadow-none"></th>
                                        <th>Main Task</th>
                                        <th>Work Progress</th>
                                        <th>Status</th>
                                        <th class="text-end" style="min-width: 200px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tasks as $index => $task)
                                        <tr class="task-row">
                                            <td><input type="checkbox"
                                                    class="form-check-input task-checkbox shadow-none" value="{{ $task->id }}">
                                            </td>
                                            <td>
                                                @php
                                                    $p = strtolower($task->priority);
                                                    $priorityClass = 'dt-priority-chip--' . (
                                                        $p == 'hard' ? 'hard' :
                                                        ($p == 'medium' ? 'medium' :
                                                        ($p == 'low' ? 'low' : 'normal'))
                                                    );
                                                @endphp
                                                <div class="dt-main-task">
                                                    <div class="dt-main-task-icon">
                                                        <i class="feather-sunrise"></i>
                                                    </div>
                                                    <div class="dt-main-task-body">
                                                        <div class="dt-main-task-label">Morning Plan</div>
                                                        <div class="dt-main-task-title">{{ $task->task_title }}</div>
                                                        <div class="dt-main-task-meta">
                                                            <span class="dt-meta-chip">
                                                                <i class="feather-calendar"></i>
                                                                {{ $task->start_date->format('d M Y') }}
                                                            </span>
                                                            @if($task->project)
                                                                <span class="dt-meta-chip">
                                                                    <i class="feather-briefcase"></i>
                                                                    {{ Str::limit($task->project->name, 18) }}
                                                                </span>
                                                            @endif
                                                            <span class="dt-priority-chip {{ $priorityClass }}">{{ $task->priority }}</span>
                                                            @if($isAdmin)
                                                                <span class="dt-meta-chip dt-meta-chip--person" title="Assigned to">
                                                                    <i class="feather-user"></i>
                                                                    {{ Str::limit($task->employee->name ?? '—', 16) }}
                                                                </span>
                                                            @endif
                                                            @if($task->employee->departmentRef->name ?? null)
                                                                <span class="dt-meta-chip">{{ $task->employee->departmentRef->name }}</span>
                                                            @endif
                                                        </div>
                                                        @if($task->photo)
                                                            <a href="javascript:void(0)"
                                                                onclick="viewAttachmentPopup('{{ asset('storage/'.$task->photo) }}')"
                                                                class="dt-file-link">
                                                                <i class="feather-paperclip"></i> Attachment
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $progressCount = $task->followUps->count();
                                                    $totalHours = $task->total_time;
                                                    $targetHours = 8;
                                                    $pct = min(100, ($totalHours / $targetHours) * 100);
                                                    $isLowHours = $task->status === 'Completed' && $totalHours < 8;
                                                    $latestProgress = $task->followUps->sortByDesc('created_at')->first();
                                                @endphp
                                                <div class="dt-progress-cell">
                                                    <div class="dt-progress-top">
                                                        <span class="dt-time-chip {{ $isLowHours ? 'dt-time-chip--low' : '' }}">
                                                            <i class="feather-clock"></i>
                                                            {{ $task->formatted_total_time }}
                                                        </span>
                                                        <span class="dt-entry-chip">{{ $progressCount }} {{ Str::plural('entry', $progressCount) }}</span>
                                                    </div>
                                                    <div class="dt-progress-target">
                                                        <span>8h target</span>
                                                        <strong>{{ round($pct) }}%</strong>
                                                    </div>
                                                    <div class="dt-progress-bar-wrap">
                                                        <div class="dt-progress-bar {{ $progressCount === 0 ? 'dt-progress-bar--empty' : ($isLowHours ? 'dt-progress-bar--low' : '') }}"
                                                             style="width: {{ $progressCount === 0 ? 0 : $pct }}%"></div>
                                                    </div>
                                                    @if($latestProgress)
                                                        <div class="dt-progress-latest">{!! Str::limit(strip_tags($latestProgress->work_description), 90) !!}</div>
                                                    @else
                                                        <div class="dt-progress-empty">No progress logged yet — use Add Progress</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="dt-status-cell align-middle">
                                                @php
                                                    $s = $task->status;
                                                    $statusSlug = strtolower(str_replace(' ', '-', $s));
                                                    $statusClass = 'status-' . $statusSlug;
                                                @endphp

                                                <div class="status-wrapper">
                                                    <div class="d-flex align-items-center gap-2">

                                                        <button
                                                            class="btn-status {{ $statusClass }}"
                                                            onclick="openStatusModal(
                                                                {{ $task->id }},
                                                                '{{ $s }}',
                                                                {{ $task->project_id }}
                                                            )">

                                                            <span>{{ $s }}</span>

                                                            <i class="feather-chevron-down"></i>

                                                        </button>


                                                        <button
                                                            class="history-btn"
                                                            onclick="showHistory({{ $task->id }})">

                                                            <i class="feather-eye"></i>

                                                        </button>

                                                    </div>


                                                    @if($task->status_changed_at)
                                                        <div class="status-time">
                                                            @php
                                                                $hours = floor($task->total_time);
                                                                $isIncomplete = $s === 'Completed' && $hours < 8;
                                                            @endphp
                                                            <i class="feather-clock"></i>
                                                            {{ $task->status_changed_at->format('d M Y h:i A') }}
                                                            @if($isIncomplete)
                                                                <span class="badge bg-danger ms-1">Incomplete</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="dt-actions">
                                                    <button type="button"
                                                        class="zoho-btn-primary btn-sm dt-btn-progress"
                                                        title="Add Work Progress"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#followUpModal"
                                                        onclick="openFollowUpModal({{ $task->id }}, '{{ addslashes($task->project->name ?? 'N/A') }}', 'add', '{{ addslashes($task->task_title) }}', {{ $task->employee_id ?? 'null' }}, '{{ addslashes($task->employee->name ?? auth()->user()->name ?? 'Employee') }}', {{ $task->project_id ?? 'null' }})">
                                                        <i class="feather-plus"></i> Progress
                                                    </button>
                                                    <button type="button"
                                                        class="zoho-icon-btn"
                                                        title="View Details & History"
                                                        onclick="showTaskDesc({{ $task->id }})">
                                                        <i class="feather-eye"></i>
                                                    </button>
                                                    <template id="task_desc_{{ $task->id }}">
                                                        <div class="dt-detail-section">
                                                            <div class="dt-detail-section-head">
                                                                <i class="feather-sunrise"></i>
                                                                <div>
                                                                    <h4>Morning Plan</h4>
                                                                    <p>Original task for the day</p>
                                                                </div>
                                                            </div>
                                                            <div class="dt-detail-title">{{ $task->task_title ?: 'No task title provided.' }}</div>
                                                            @if($task->description)
                                                                <div class="dt-detail-desc">{!! $task->description !!}</div>
                                                            @else
                                                                <div class="dt-detail-desc dt-detail-desc--empty">No description provided.</div>
                                                            @endif
                                                        </div>

                                                        @php
                                                        $groupedFollowups = $task->followUps
                                                            ->sortByDesc('created_at')
                                                            ->groupBy(function($item){
                                                                return ($item->reference_name ?? 'Employee') . '_' .
                                                                $item->created_at->format('d-m-Y');
                                                            });
                                                        $allTaskHours = $task->followUps->sum('time_taken');
                                                        $th = floor($allTaskHours);
                                                        $tm = round(($allTaskHours - $th) * 60);
                                                        $totalDisplay = [];
                                                        if($th > 0) $totalDisplay[] = $th.'h';
                                                        if($tm > 0) $totalDisplay[] = $tm.'m';
                                                        @endphp

                                                        <div class="dt-detail-section">
                                                            <div class="dt-detail-section-head dt-detail-section-head--progress">
                                                                <i class="feather-activity"></i>
                                                                <div>
                                                                    <h4>Work Progress</h4>
                                                                    <p>Logged entries for this task</p>
                                                                </div>
                                                                <span class="dt-detail-total-time">
                                                                    <i class="feather-clock"></i>
                                                                    {{ count($totalDisplay) ? implode(' ', $totalDisplay) : '0m' }}
                                                                </span>
                                                            </div>

                                                            @forelse($groupedFollowups as $group)
                                                                @php
                                                                    $first = $group->first();
                                                                    $groupHours = $group->sum('time_taken');
                                                                    $gh = floor($groupHours);
                                                                    $gm = round(($groupHours - $gh) * 60);
                                                                    $groupTime = [];
                                                                    if($gh > 0) $groupTime[] = $gh.'h';
                                                                    if($gm > 0) $groupTime[] = $gm.'m';
                                                                @endphp
                                                                <div class="dt-timeline-item">
                                                                    <div class="dt-timeline-dot"></div>
                                                                    <div class="dt-timeline-card">
                                                                        <div class="dt-timeline-card-head">
                                                                            <div>
                                                                                <strong>{{ $first->reference_name }}</strong>
                                                                                <span>{{ $first->created_at->format('d M Y') }}</span>
                                                                            </div>
                                                                            @if(count($groupTime))
                                                                                <span class="dt-timeline-time">{{ implode(' ', $groupTime) }}</span>
                                                                            @endif
                                                                        </div>
                                                                        <div class="dt-timeline-body">
                                                                            @foreach($group as $fu)
                                                                                <div class="dt-timeline-entry">{!! $fu->work_description !!}</div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <div class="dt-detail-empty">
                                                                    <i class="feather-edit-3"></i>
                                                                    <p>No work progress logged yet.</p>
                                                                </div>
                                                            @endforelse
                                                        </div>
                                                    </template>

                                                    <button type="button"
                                                        class="zoho-icon-btn"
                                                        title="Edit Main Task"
                                                        onclick="editTask({{ json_encode(array_merge($task->toArray(), ['start_date_formatted' => optional($task->start_date)->format('Y-m-d'), 'end_date_formatted' => optional($task->end_date)->format('Y-m-d')])) }})">
                                                        <i class="feather-edit-2"></i>
                                                    </button>
                                                    @php
                                                        $taskProject = $task->project;
                                                        $isTaskLead = $taskProject && is_array($taskProject->leaders) && in_array(auth()->user()->employee_id, $taskProject->leaders);
                                                        $isTaskOwner = auth()->user()->employee_id == $task->employee_id;
                                                        $isTaskAssigner = auth()->id() == $task->assigned_by;
                                                        $canDeleteTask = $isAdmin || $isTaskLead || $isTaskAssigner || ($isTaskOwner && $task->project_id !== null);
                                                    @endphp
                                                    @if($canDeleteTask)
                                                        <form action="{{ route('daily-tasks.destroy', $task->id) }}" method="POST"
                                                            class="delete-form d-inline" onsubmit="deleteRecord(event, this)">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                class="zoho-icon-btn zoho-icon-btn--danger"
                                                                title="Delete Task">
                                                                <i class="feather-trash-2"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                <div class="dt-empty-state">
                                                    <i class="feather-check-square"></i>
                                                    <p>{{ request()->hasAny(['search', 'project_id', 'employee_id', 'status', 'from_date', 'upto_date']) ? 'No tasks match your filters.' : 'No daily tasks yet. Add your morning plan to get started.' }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($tasks->hasPages())
                            <div class="dt-list-footer d-flex justify-content-center">
                                {{ $tasks->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-body p-4">
                    <h4 class="fw-bold text-center mb-4">
                        Update Task Status
                    </h4>
                    <input type="hidden" id="statusTaskId">
                    <input type="hidden" id="statusProjectId">
                    <div class="mb-3">
                        <label class="fw-semibold">Select Status</label>
                        <select class="form-select" id="statusTaskStatus">
                            <option value="Pending">Pending</option>
                            <option value="In Process">In Process</option>
                            <option value="Completed">Complete</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Review">Review</option>
                            <option value="Rework">Rework</option>
                            <option value="Reassign">Reassign</option>
                        </select>
                    </div>
                    <div id="assignSection" style="display:none" class="mb-3">
                        <label class="fw-semibold">Assign To</label>
                        <select class="form-select" id="assignTo">
                            <option value="">Select Employee</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="fw-semibold">Comment</label>
                        <textarea class="form-control" id="comment" rows="3" placeholder="Enter comment"></textarea>
                    </div>
                    <div class="text-center">
                        <button class="btn btn-primary px-5" onclick="submitStatus()">
                            Confirm Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="historyModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Status Tracking</h5>
                </div>
                <div class="modal-body" id="historyBody"></div>
            </div>
        </div>
    </div>  
@endsection

@section('modals')
    <!-- SIDE PANEL: CREATE/EDIT TASK -->
    <div class="offcanvas offcanvas-end dt-task-offcanvas shadow-lg" tabindex="-1" id="taskOffcanvas" aria-labelledby="taskOffcanvasLabel">
        <div class="offcanvas-header zoho-offcanvas-head border-bottom">
            <h5 class="offcanvas-title zoho-offcanvas-title" id="taskOffcanvasLabel">Add Main Task</h5>
            <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="feather-x"></i>
            </button>
        </div>
        <form id="taskForm" class="d-flex flex-column flex-grow-1 min-h-0">
                @csrf
                <div id="methodField"></div>
                <input type="hidden" name="id" id="taskId">
                <div class="flex-grow-1 overflow-auto p-3">
                    <div class="dt-form-card">
                        <div class="dt-form-card-head">
                            <span class="dt-form-card-head-icon"><i class="feather-sunrise"></i></span>
                            <div>
                                <h3>Morning Plan</h3>
                                <p>What will you work on today?</p>
                            </div>
                        </div>
                        <div class="dt-form-card-body">
                            <div class="col-md-4 d-none">
                                <select name="project_id" id="taskProjectId" class="form-select premium-select"
                                    data-placeholder="Select Project...">
                                    <option value="">Select Project...</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="dt-field">
                                <label>Task Title <span class="req">*</span></label>
                                <div class="dt-input-wrap">
                                    <i class="feather-target"></i>
                                    <input type="text" name="task_title" id="taskTitle" class="form-control"
                                        placeholder="Enter today's main task..." required>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="dt-field">
                                        <label>Priority <span class="req">*</span></label>
                                        <div class="dt-input-wrap">
                                            <i class="feather-flag"></i>
                                            <select name="priority" id="taskPriority" class="form-select" required>
                                                <option value="">Select priority...</option>
                                                <option value="Hard">High</option>
                                                <option value="Medium" selected>Medium</option>
                                                <option value="Low">Low</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dt-field">
                                        <label>Start Date <span class="req">*</span></label>
                                        <div class="dt-input-wrap">
                                            <i class="feather-calendar"></i>
                                            <input type="date" name="start_date" id="taskStartDate" class="form-control"
                                                value="{{ now()->format('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dt-field mb-md-0">
                                        <label>End Date</label>
                                        <div class="dt-input-wrap">
                                            <i class="feather-calendar"></i>
                                            <input type="date" name="end_date" id="taskEndDate" class="form-control"
                                                value="{{ now()->format('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-none">
                                <select name="status" id="taskStatus" class="form-select premium-select" required>
                                    <option value="Pending" selected>Pending</option>
                                    <option value="In Process">In Process</option>
                                    <option value="Completed">Completed</option>
                                    <option value="On Hold">On Hold</option>
                                    <option value="Review">Review</option>
                                    <option value="Rework">Rework</option>
                                </select>
                            </div>
                            @if ($isAdmin)
                                <div class="dt-field">
                                    <label>Assign To <span class="req">*</span></label>
                                    <div class="dt-input-wrap">
                                        <i class="feather-user"></i>
                                        <select name="employee_id" id="taskEmployeeId" class="form-select" required>
                                            @if(count($employees) > 1)
                                                <option value="">Employee name</option>
                                            @endif
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}" {{ count($employees) == 1 ? 'selected' : '' }}>
                                                    {{ $employee->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="employee_id" value="{{ auth()->user()->employee_id }}">
                            @endif
                            <div class="dt-field">
                                <label>Task Description</label>
                                <textarea name="description" id="taskDesc" class="form-control dt-desc-editor" rows="3"
                                    placeholder="Brief plan for the day..."></textarea>
                            </div>
                            <div class="dt-field mb-0">
                                <label>Attachment (Optional)</label>
                                <div class="dt-upload-zone">
                                    <input type="file" name="photo" id="mainTaskPhoto" class="form-control bg-transparent border-0 shadow-none">
                                    <div id="mainTaskFilePreview" class="mt-2 d-none">
                                        <span class="badge bg-soft-primary text-primary fw-bold" id="mainTaskFileName"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dt-offcanvas-foot">
                    <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="button" id="submitTaskBtn" class="zoho-btn-primary flex-fill">
                        <i class="feather-save"></i> Submit Task
                    </button>
                </div>
            </form>
    </div>

    <!-- TASK DETAIL DRAWER -->
    <div class="offcanvas offcanvas-end dt-detail-offcanvas shadow-lg" tabindex="-1" id="taskDetailDrawer">
        <div class="offcanvas-header zoho-offcanvas-head border-bottom">
            <div>
                <h5 class="offcanvas-title zoho-offcanvas-title mb-1">Task Details & Progress</h5>
                <p class="dt-detail-drawer-sub mb-0">Morning plan and logged work history</p>
            </div>
            <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="feather-x"></i>
            </button>
        </div>
        <div class="offcanvas-body" id="taskDetailBody"></div>
    </div>

    <!-- TASK FOLLOW-UP MODAL (WORK PROGRESS) -->
    <div class="modal fade dt-progress-modal" id="followUpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="followUpModalLabel">Add Work Progress</h5>
                        <span id="followUpTaskTitle" class="dt-modal-task-badge"></span>
                    </div>
                    <button type="button" class="zoho-offcanvas-close zoho-offcanvas-close--light" data-bs-dismiss="modal" aria-label="Close">
                        <i class="feather-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="dt-fu-intro">
                        <i class="feather-edit-3"></i>
                        <div>
                            <strong>Log your work</strong>
                            <p>Add project, time spent, and what you accomplished today.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-12" id="followUpHistoryColumn">
                            <div class="dt-fu-toolbar">
                                <span class="dt-fu-toolbar-label">Work history</span>
                                <input type="text" id="modalSearch" class="form-control form-control-sm"
                                    placeholder="Search history..." style="max-width: 220px;"
                                    oninput="filterModalHistory()">
                            </div>
                            <div id="followUpHistoryBody"
                                style="max-height: 480px; overflow-y: auto; border: 1px solid #eef2f7; border-radius: 10px;"></div>
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <small class="text-muted" id="modalEntriesInfo"></small>
                                <div class="d-flex gap-1" id="modalPaginationButtons"></div>
                            </div>
                        </div>
                        <div class="col-lg-8 d-none" id="followUpFormColumn">
                            <form id="followUpForm" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="daily_task_id" id="followUpTaskId">
                                <input type="hidden" name="follow_up_id" id="followUpId">
                                <input type="hidden" name="time_taken" id="totalFollowUpHours" value="0">

                                <div class="dt-fu-toolbar">
                                    <span class="dt-fu-toolbar-label">Progress entries</span>
                                    <button type="button" onclick="addTaskClone()" class="zoho-btn-outline btn-sm">
                                        <i class="feather-plus"></i> Add entry
                                    </button>
                                </div>

                                <div id="taskAddContainer"></div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer dt-modal-foot d-none" id="followUpModalFooter">
                    <button type="button" class="zoho-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="followUpForm" id="submitReplyBtn" class="zoho-btn-primary">
                        <i class="feather-check"></i> Submit Progress
                    </button>
                </div>
            </div>
        </div>
    </div>
        <!-- CUSTOM ATTACHMENT VIEWER MODAL -->
        <div id="customAttachmentModal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:999999; background:rgba(0,0,0,0.7); align-items:center; justify-content:center; backdrop-filter: blur(4px);">
            <div
                style="position:relative; width:85%; max-width:1000px; background:#ffffff; border-radius:16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); display: flex; flex-direction: column;">
                <div
                    style="padding: 15px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <h5 class="fw-bold text-primary m-0" style="font-size: 18px;"><i
                            class="feather-paperclip me-2"></i>Attached File</h5>
                    <button onclick="document.getElementById('customAttachmentModal').style.display='none'"
                        style="background:none; border:none; font-size:28px; line-height: 1; cursor:pointer; color: #64748b;">&times;</button>
                </div>
                <div id="customAttachmentContent" style="padding: 20px; text-align:center; overflow:hidden;"></div>
            </div>
        </div>
@endsection

    @push('scripts')
        <!-- Summernote CDN -->
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

        <style>
            .bg-soft-primary {
                background: rgba(56, 88, 249, 0.08) !important;
                color: #3858f9;
            }

            .bg-soft-success {
                background: rgba(34, 197, 94, 0.08) !important;
                color: #22c55e;
            }

            .bg-soft-info {
                background: rgba(13, 202, 240, 0.08) !important;
                color: #0dcaf0;
            }

            .bg-soft-warning {
                background: rgba(245, 158, 11, 0.08) !important;
                color: #f59e0b;
            }

            .bg-soft-danger {
                background: rgba(239, 68, 68, 0.08) !important;
                color: #ef4444;
            }

            .bg-soft-secondary {
                background: rgba(100, 116, 139, 0.08) !important;
                color: #64748b;
            }

            .form-control:focus,
            .form-select:focus {
                border: 1.5px solid #3858f9 !important;
                box-shadow: 0 0 0 0.2rem rgba(56, 88, 249, 0.1) !important;
                background-color: #ffffff !important;
            }

            .form-select {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%233858f9' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
                background-size: 12px 12px !important;
                background-position: right 1rem center !important;
                cursor: pointer;
            }

            .form-control,
            .form-select {
                transition: all 0.2s ease-in-out !important;
                border: 1px solid #e2e8f0 !important;
            }

            .form-control:hover,
            .form-select:hover {
                border-color: #cbd5e1 !important;
                background-color: #f1f5f9 !important;
            }

            .modal {
                transform: none !important;
                filter: none !important;
                position: fixed !important;
            }

            .modal-backdrop {
                background-color: rgba(15, 23, 42, 0.75) !important;
                transition: none !important;
            }

            .modal-content {
                filter: none !important;
                border: none !important;
                transform: none !important;
            }

            .page-link {
                color: #64748b;
                font-weight: 700;
                transition: all 0.2s;
                border: 1px solid #e2e8f0 !important;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                min-width: 38px;
                height: 38px;
                border-radius: 8px !important;
            }

            /* Prevent shaking/shifting */
            .timer-display,
            .task-timer {
                font-variant-numeric: tabular-nums;
                min-width: 140px;
                display: inline-block;
                white-space: nowrap;
            }

            .task-row {
                transition: background-color 0.2s ease;
            }

            .task-row:hover {
                background-color: #f8fafc !important;
            }

            .table> :not(caption)>*>* {
                background-color: transparent !important;
                box-shadow: none !important;
            }

            /* Hide Number Input Arrows */
            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            input[type=number] {
                -moz-appearance: textfield;
            }

            .active>.page-link {
                background-color: #3858f9 !important;
                border-color: #3858f9 !important;
                color: #ffffff !important;
            }

            .page-link:hover:not(.text-white) {
                border-color: #3858f9 !important;
                color: #3858f9;
            }

            .custom-html-content ul {
                list-style-type: disc !important;
                padding-left: 30px !important;
                margin-bottom: 1rem !important;
                list-style-position: outside !important;
                display: block !important;
            }

            .custom-html-content ol {
                list-style-type: decimal !important;
                padding-left: 30px !important;
                margin-bottom: 1.1rem !important;
                list-style-position: outside !important;
                display: block !important;
            }

            .custom-html-content li {
                display: list-item !important;
                margin-bottom: 0.6rem !important;
                list-style-type: inherit !important;
            }

            .custom-html-content p {
                margin-bottom: 1rem !important;
                line-height: 1.6 !important;
            }

            .custom-html-content img {
                max-width: 100% !important;
                height: auto !important;
                border-radius: 8px;
                margin: 10px 0;
                display: block;
            }

            .custom-html-content ol {
                padding-left: 25px !important;
                list-style-type: decimal !important;
                margin-bottom: 15px;
            }

            .custom-html-content ul {
                padding-left: 25px !important;
                list-style-type: disc !important;
                margin-bottom: 15px;
            }

            .custom-html-content p {
                margin-bottom: 10px;
            }

            .custom-html-content {
                text-align: left;
                font-size: 15px;
                line-height: 1.6;
                color: #1e293b;
                word-wrap: break-word;
                word-break: break-word;
                overflow-wrap: break-word;
                padding: 15px 20px 15px 25px !important;
                background: #fff !important;
                overflow-x: hidden !important;
                width: 100%;
            }

            /* Summernote point indentation fix */
            .note-editable ul {
                list-style-type: disc !important;
                padding-left: 30px !important;
                list-style-position: outside !important;
                display: block !important;
            }

            .note-editable ol {
                list-style-type: decimal !important;
                padding-left: 30px !important;
                list-style-position: outside !important;
                display: block !important;
            }

            .note-editable li {
                display: list-item !important;
                list-style-type: inherit !important;
            }

            .note-editable {
                min-height: 200px;
                padding: 25px !important;
                background: white !important;
            }

            /* Smooth Collapse Animation for Filter */
            .collapse {
                transition: height 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            .collapsing {
                transition: height 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
                height: 0;
                overflow: hidden;
            }

            .table-responsive .table tr td {
                padding: 15px 8px;
            }
        </style>

        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <style>
            /* Premium Select2 Theme */
            .select2-container--default .select2-selection--single {
                background-color: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 10px !important;
                height: 48px !important;
                display: flex !important;
                align-items: center !important;
                padding: 0 12px !important;
                transition: all 0.2s ease !important;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #1e293b !important;
                font-weight: 700 !important;
                font-size: 14px !important;
                padding-left: 0 !important;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 48px !important;
                right: 12px !important;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow b {
                border-color: #3858f9 transparent transparent transparent !important;
                border-width: 6px 4px 0 4px !important;
            }

            .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
                border-color: transparent transparent #3858f9 transparent !important;
                border-width: 0 4px 6px 4px !important;
            }

            .select2-dropdown {
                border: none !important;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
                border-radius: 12px !important;
                padding: 8px !important;
                background: #ffffff !important;
                z-index: 1070 !important;
            }

            .select2-results__option {
                padding: 10px 15px !important;
                border-radius: 8px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: #475569 !important;
                margin-bottom: 2px !important;
            }

            .select2-results__option--highlighted[aria-selected] {
                background-color: #3858f9 !important;
                color: #ffffff !important;
            }

            .select2-results__option[aria-selected=true] {
                background-color: #f1f5f9 !important;
                color: #3858f9 !important;
            }

            .select2-search--dropdown {
                padding: 10px !important;
            }

            .select2-search--dropdown .select2-search__field {
                border: 1px solid #e2e8f0 !important;
                border-radius: 8px !important;
                padding: 8px 12px !important;
                background: #f8fafc !important;
                font-weight: 600 !important;
            }

            /* Remove global z-index to prevent backdrop overlap */
            .select2-container {
                z-index: auto !important;
            }

            .btn-status{
                min-width:120px;
                height:20px;
                border:none;
                border-radius:12px;
                font-size:12px;
                font-weight:700;
                display:flex;
                align-items:center;
                justify-content:center;
                gap:6px;
                transition:all .3s ease;
                box-shadow:0 3px 10px rgba(0,0,0,.08);
            }

            .btn-status:hover{
                transform:translateY(-2px);
                box-shadow:0 8px 18px rgba(0,0,0,.15);
            }

            .btn-status i{
                font-size:11px;
            }


            /* Pending */

            .status-pending{
                background:#f3f4f6;
                color:#6b7280;
            }


            /* In Process */

            .status-in-process{
                background:#eef2ff;
                color:#4f46e5;
            }


            /* Completed */

            .status-completed{
                background:#ecfdf5;
                color:#16a34a;
            }


            /* On Hold */

            .status-on-hold{
                background:#fff7ed;
                color:#ea580c;
            }


            /* Review */

            .status-review{
                background:#ecfeff;
                color:#0891b2;
            }


            /* Rework */

            .status-rework{
                background:#fef2f2;
                color:#dc2626;
            }


            /* Reassign */

            .status-reassign{
                background:#f5f3ff;
                color:#7c3aed;
            }

            .timeline-card{
                padding:16px;
                background:#f8fafc;
                border-radius:12px;
                margin-bottom:16px;
                border-left:4px solid #3858f9;
                box-shadow:0 2px 8px rgba(0,0,0,.05);
                transition:.3s;
            }

            .timeline-card:hover{
                transform:translateY(-2px);
            }

            .timeline-card .badge{
                font-size:11px;
                padding:8px 12px;
                border-radius:30px;
            }

            .status-wrapper{
                display:flex;
                flex-direction:column;
                gap:8px;
            }

            .status-action-row{
                display:flex;
                align-items:center;
                gap:8px;
            }

            .history-btn{
                width:30px;
                height:30px;
                border:none;
                border-radius:8px;
                background:#f8fafc;
                color:#64748b;
                display:flex;
                align-items:center;
                justify-content:center;
                transition:all .3s ease;
                box-shadow:0 2px 6px rgba(0,0,0,.06);
            }

            .history-btn:hover{
                background:#3858f9;
                color:#fff;
                transform:translateY(-2px);
            }

            .status-time{
                display:flex;
                align-items:center;
                gap:5px;
                margin-left:5px;
                color:#64748b;
                font-size:12px;
                font-weight:500;
            }

            .status-time i{
                font-size:11px;
            }

        .wghrm-custom-select-btn {
                background-color: #fff !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 12px !important;
                color: #1e293b !important;
                padding: 10px 16px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                width: 100% !important;
                height: 48px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                transition: all 0.2s !important;
                text-align: left !important;
            }
            .wghrm-custom-select-btn:focus {
                border-color: #3858f9 !important;
                box-shadow: 0 0 0 4px rgba(56, 88, 249, 0.1) !important;
                outline: none !important;
            }
            .wghrm-custom-dropdown-menu {
                border-radius: 12px !important;
                box-shadow: 0 15px 40px rgba(0,0,0,0.12) !important;
                padding: 8px !important;
                border: 1px solid #e2e8f0 !important;
                min-width: 200px !important;
                z-index: 9999 !important;
            }
            .wghrm-custom-search-box {
                padding: 4px;
                margin-bottom: 8px;
                border-bottom: 1px solid #f1f5f9;
            }
            .wghrm-custom-search-input {
                width: 100% !important;
                padding: 8px 12px !important;
                border-radius: 8px !important;
                border: 1px solid #e2e8f0 !important;
                font-size: 13px !important;
                outline: none !important;
                background: #f8fafc !important;
            }
            .wghrm-custom-dropdown-item {
                border-radius: 8px !important;
                padding: 10px 15px !important;
                font-weight: 600 !important;
                font-size: 13px !important;
                color: #475569 !important;
                cursor: pointer !important;
                display: block !important;
                text-decoration: none !important;
            }
            .wghrm-custom-dropdown-item:hover, .wghrm-custom-dropdown-item.active {
                background: #f1f5f9 !important;
                color: #3858f9 !important;
            }

            .wghrm-search-dropdown {
                position: relative;
                width: 100%;
                overflow: visible;
            }

            .wghrm-dropdown-trigger {
                width: 100%;
                border: 1px solid #e2e8f0;
                background: #fff;
                border-radius: 12px;
                padding: 10px 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
                position: relative;
                z-index: 1;
            }

            .wghrm-dropdown-trigger.open {
                border-color: #3858f9;
                box-shadow: 0 0 0 4px rgba(56, 88, 249, 0.08);
            }

            .wghrm-trigger-text {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .wghrm-dropdown-menu {
                position: absolute;
                top: calc(100% + 8px);
                left: 0;
                right: 0;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
                z-index: 9999 !important;
                padding: 10px;
                display: none;
                min-width: 100%;
                width: auto;
                pointer-events: auto !important;
                visibility: visible !important;
            }

            .wghrm-dropdown-menu.show {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }

            .wghrm-search-container {
                position: relative;
                margin-bottom: 10px;
                display: block !important;
                visibility: visible !important;
            }

            .wghrm-search-icon {
                position: absolute;
                top: 50%;
                left: 12px;
                transform: translateY(-50%);
                width: 14px;
                height: 14px;
                color: #94a3b8;
            }

            .wghrm-search-dropdown .wghrm-search-input {
                width: 100%;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
                padding: 10px 12px 10px 34px;
                font-size: 13px;
                font-weight: 600;
                color: #334155;
                outline: none;
            }

            .wghrm-search-dropdown .wghrm-search-input:focus {
                border-color: #3858f9;
                box-shadow: 0 0 0 3px rgba(56, 88, 249, 0.08);
            }

            .wghrm-items-list {
                max-height: 220px;
                overflow-y: auto;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            .wghrm-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                border-radius: 10px;
                padding: 10px 12px;
                color: #475569;
                cursor: pointer;
                transition: all 0.18s ease;
            }

            .wghrm-item:hover,
            .wghrm-item.selected {
                background: #f1f5f9;
                color: #3858f9;
            }

            .wghrm-item-check {
                opacity: 0;
                transition: opacity 0.18s ease;
            }

            .wghrm-item.selected .wghrm-item-check {
                opacity: 1;
            }
        </style>

        <script>
            let globalFollowUps = [];
            let modalCurrentPage = 1;
            let modalPageSize = 5;
            let currentTaskPage = 1;
            let myFollowUpModal = null;
            let currentModalEmployeeFilter = 'All';

            document.addEventListener('DOMContentLoaded', function () {
                myFollowUpModal = new bootstrap.Modal(document.getElementById('followUpModal'));

                // Project-based Employee Filtering for Task Assignment
                const projectEmployees = @json($projects->mapWithKeys(fn($p) => [$p->id => array_merge((array)($p->leaders ?? []), (array)($p->members ?? []))]));
                const projectLeadersMap = @json($projects->mapWithKeys(fn($p) => [$p->id => (array)($p->leaders ?? [])]));
                const allEmployeesMap = @json($employees->keyBy('id'));
                const currentEmpId = {{ auth()->user()->employee_id ?? 0 }};
                const isSysAdmin = {{ $isAdmin ? 'true' : 'false' }};

                window.taskAssignmentData = {
                    projectEmployees,
                    projectLeadersMap,
                    allEmployeesMap,
                    currentEmpId,
                    isSysAdmin
                };

                // $('#taskProjectId').on('change', function() {
                //     const projectId = $(this).val();
                //     const $empSelect = $('#taskEmployeeId');
                    
                //     if (!projectId) {
                //         $empSelect.empty().append('<option value="">Select Employee...</option>').trigger('change');
                //         return;
                //     }

                //     const allowedIds = projectEmployees[projectId] || [];
                //     const leaders = projectLeadersMap[projectId] || [];
                //     const isLeaderOfProject = leaders.includes(currentEmpId.toString()) || leaders.includes(currentEmpId);
                    
                //     const currentSelectedVal = $empSelect.val();
                //     $empSelect.empty().append('<option value="">Select Employee...</option>');
                    
                //     let count = 0;
                //     Object.entries(allEmployeesMap).forEach(([id, emp]) => {
                //         const isMember = allowedIds.includes(parseInt(id)) || allowedIds.includes(id.toString());
                        
                //         if (isSysAdmin || isLeaderOfProject) {
                //             if (isMember) {
                //                 $empSelect.append(`<option value="${id}">${emp.name}</option>`);
                //                 count++;
                //             }
                //         } else {
                //             if (isMember && id == currentEmpId) {
                //                 $empSelect.append(`<option value="${id}">${emp.name}</option>`);
                //                 count++;
                //             }
                //         }
                //     });

                //     const hasPreviousSelection = currentSelectedVal && $empSelect.find(`option[value="${currentSelectedVal}"]`).length;

                //     if (hasPreviousSelection) {
                //         $empSelect.val(currentSelectedVal);
                //     } else if (count === 1) {
                //         $empSelect.find('option').last().prop('selected', true);
                //     }

                //     if (window.jQuery && $.fn.select2) {
                //         $empSelect.trigger('change');
                //     }
                // });

                // Initialize Select2 with Premium Styling
                if (window.jQuery && $.fn.select2) {
                    $('.form-select:not(.select-small)').select2({
                        width: '100%',
                        placeholder: function () { return $(this).attr('placeholder') || 'Select Option'; },
                        dropdownParent: $('body')
                    });

                    // Keep the task status default stable after Select2 replaces the native select UI.
                    $('#taskStatus').val($('#taskStatus').val() || 'In Process').trigger('change');
                    $('#taskPriority').val($('#taskPriority').val() || 'Medium').trigger('change');

                    $('.select-small').select2({
                        width: 'element',
                        minimumResultsForSearch: Infinity,
                        dropdownParent: $('body')
                    });

                    // Re-init for specific containers if needed
                    $('#taskOffcanvas, #followUpModal').on('shown.bs.modal shown.bs.offcanvas', function () {
                        $(this).find('.form-select').select2({
                            width: '100%',
                            dropdownParent: $(this)
                        });

                        if (this.id === 'taskOffcanvas' && !document.getElementById('taskId').value) {
                            $('#taskStatus').val($('#taskStatus').val() || 'Pending').trigger('change');
                            $('#taskPriority').val($('#taskPriority').val() || 'Medium').trigger('change');
                        }
                    });
                }

                // Robust Backdrop Cleanup
                document.getElementById('followUpModal').addEventListener('hidden.bs.modal', function () {
                    const backdrops = document.getElementsByClassName('modal-backdrop');
                    while (backdrops.length > 0) {
                        backdrops[0].parentNode.removeChild(backdrops[0]);
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                });

                // Fix dropdown clipping globally for this page
                var dropdownEls = document.querySelectorAll('.dropdown-toggle');
                dropdownEls.forEach(function (el) {
                    new bootstrap.Dropdown(el, {
                        boundary: 'viewport',
                        popperConfig: { strategy: 'fixed' }
                    });
                });

                // Handle Check All Functionality for Tasks
                $(document).on('change', '#selectAllTasks', function () {
                    $('.task-checkbox').prop('checked', $(this).prop('checked'));
                    toggleTaskBulkAction();
                });

                $(document).on('change', '.task-checkbox', function () {
                    toggleTaskBulkAction();
                });

                function toggleTaskBulkAction() {
                    const checkedCount = $('.task-checkbox:checked').length;
                    if (checkedCount > 0) {
                        $('#btn-bulk-delete-tasks').fadeIn();
                    } else {
                        $('#btn-bulk-delete-tasks').fadeOut();
                        $('#selectAllTasks').prop('checked', false);
                    }
                }
            });

            function previewFollowUpAttachment(input, existingUrl = '') {
                const row = input.closest('.followup-row');
                if (!row) return;

                const preview = row.querySelector('.followup-photo-preview');
                const docPreview = row.querySelector('.followup-doc-preview');
                const container = row.querySelector('.followup-preview-container');
                const existingField = row.querySelector('.followup-existing-photo');

                if (!preview || !docPreview || !container) return;

                if (existingField) {
                    existingField.value = '';
                }

                const file = input.files && input.files[0] ? input.files[0] : null;

                if (!file && !existingUrl) {
                    container.classList.add('d-none');
                    preview.style.display = 'none';
                    docPreview.style.display = 'none';
                    preview.removeAttribute('src');
                    docPreview.innerHTML = '';
                    return;
                }

                container.classList.remove('d-none');

                if (file) {
                    const isImage = file.type.startsWith('image/');

                    if (isImage) {
                        preview.style.display = 'block';
                        docPreview.style.display = 'none';
                        const reader = new FileReader();
                        reader.onload = function (e) { preview.src = e.target.result; };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                        docPreview.style.display = 'block';
                        docPreview.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center p-3">
                                                        <i class="feather-file-text mb-2" style="font-size: 32px; color: #3858f9;"></i>
                                                        <span class="text-dark small">${file.name}</span>
                                                    </div>`;
                    }

                    return;
                }

                const isImage = existingUrl.match(/\.(jpeg|jpg|gif|png|webp)$/i) != null;
                if (isImage) {
                    preview.style.display = 'block';
                    docPreview.style.display = 'none';
                    preview.src = existingUrl;
                } else {
                    preview.style.display = 'none';
                    docPreview.style.display = 'block';
                    docPreview.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center p-3">
                                                    <i class="feather-file-text mb-2" style="font-size: 32px; color: #3858f9;"></i>
                                                    <span class="text-dark small">Existing Attachment</span>
                                                </div>`;
                }
            }

            function clearFollowUpAttachment(button) {
                const row = button.closest('.followup-row');
                if (!row) return;

                const input = row.querySelector('.followup-photo-input');
                const existingField = row.querySelector('.followup-existing-photo');
                const container = row.querySelector('.followup-preview-container');
                const preview = row.querySelector('.followup-photo-preview');
                const docPreview = row.querySelector('.followup-doc-preview');

                if (input) input.value = '';
                if (existingField) existingField.value = '';
                if (container) container.classList.add('d-none');
                if (preview) {
                    preview.style.display = 'none';
                    preview.removeAttribute('src');
                }
                if (docPreview) {
                    docPreview.style.display = 'none';
                    docPreview.innerHTML = '';
                }
            }

            function removePreview() {
                document.querySelectorAll('#taskAddContainer .followup-row .followup-remove-attachment').forEach(button => {
                    clearFollowUpAttachment(button);
                });
            }

            function bulkDelete() {
                const checked = Array.from(document.querySelectorAll('.task-checkbox:checked')).map(cb => cb.value);
                if (checked.length === 0) { Toast.fire({ icon: 'warning', title: 'Please select tasks to delete.' }); return; }
                Swal.fire({
                    title: 'Are you sure?', text: `You are about to delete ${checked.length} tasks!`, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ url("/daily-tasks/bulk-delete") }}', {
                            method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ ids: checked })
                        }).then(res => res.json()).then(data => { if (data.success) { Toast.fire({ icon: 'success', title: data.success }).then(() => location.reload()); } });
                    }
                });
            }

            function editTask(task) {
                document.getElementById('taskForm').reset();
                document.getElementById('taskOffcanvasLabel').innerText = 'Edit Main Task';
                document.getElementById('submitTaskBtn').innerText = 'UPDATE TASK';

                // Set basic fields
                document.getElementById('taskId').value = task.id || '';
                document.getElementById('taskTitle').value = task.task_title || '';
                document.getElementById('taskStartDate').value = task.start_date_formatted || (task.start_date ? task.start_date.substring(0, 10) : '');
                document.getElementById('taskEndDate').value = task.end_date_formatted || (task.end_date ? task.end_date.substring(0, 10) : '');
                document.getElementById('taskPriority').value = task.priority || 'Medium';

                window.onload = function () {
                    document.getElementById('taskStatus').value = task.status || 'Pending';
                };

                // Handle existing attachment
                const filePreview = document.getElementById('mainTaskFilePreview');
                const fileName = document.getElementById('mainTaskFileName');
                document.getElementById('mainTaskPhoto').value = '';

                if (task.photo) {
                    filePreview.classList.remove('d-none');
                    const baseName = task.photo.split('/').pop();
                    fileName.innerHTML = `<i class="feather-paperclip me-1"></i> Current File: <a href="javascript:void(0);" onclick="viewAttachmentPopup('/storage/${task.photo}')" class="text-primary text-decoration-underline">${baseName}</a>`;
                } else {
                    filePreview.classList.add('d-none');
                }

                // Set Select fields (Project & Employee)
                if (window.jQuery && $.fn.select2) {
                    $('#taskProjectId').val(task.project_id).trigger('change');
                    setTimeout(() => {
                        $('#taskEmployeeId').val(task.employee_id).trigger('change');
                    }, 0);
                    $('#taskPriority').val(task.priority || 'Medium').trigger('change');
                    $('#taskStatus').val(task.status || 'Pending').trigger('change');
                } else {
                    const projSelect = document.getElementById('taskProjectId');
                    if (projSelect) projSelect.value = task.project_id || '';

                    const empSelect = document.getElementById('taskEmployeeId');
                    if (empSelect) empSelect.value = task.employee_id || '';
                    const prioritySelect = document.getElementById('taskPriority');
                    if (prioritySelect) prioritySelect.value = task.priority || 'Medium';
                }

                // Summernote description
                try {
                    const desc = task.description || '';
                    if ($('#taskDesc').length && typeof $.fn.summernote === 'function') {
                        $('#taskDesc').summernote('code', desc);
                    } else {
                        document.getElementById('taskDesc').value = desc;
                    }
                } catch (e) {
                    console.error('Summernote load error', e);
                    if (document.getElementById('taskDesc')) document.getElementById('taskDesc').value = task.description || '';
                }

                // Form action and method
                document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
                const formObj = document.getElementById('taskForm');
                if (formObj) formObj.action = `/daily-tasks/${task.id}`;

                // Show Offcanvas
                const offElement = document.getElementById('taskOffcanvas');
                if (offElement) {
                    const bOff = bootstrap.Offcanvas.getInstance(offElement) || new bootstrap.Offcanvas(offElement);
                    bOff.show();
                }
            }

            function resetTaskForm() {
                document.getElementById('taskForm').reset();
                document.getElementById('taskForm').action = `{{ url('/daily-tasks') }}`;
                document.getElementById('taskOffcanvasLabel').innerText = 'Add Main Task';
                document.getElementById('submitTaskBtn').innerText = 'SUBMIT TASK';
                document.getElementById('taskId').value = '';
                document.getElementById('methodField').innerHTML = '';
                document.getElementById('mainTaskPhoto').value = '';
                document.getElementById('mainTaskFilePreview').classList.add('d-none');

                document.getElementById('taskForm').querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.getElementById('taskForm').querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                try {
                    if ($('#taskDesc').length && typeof $.fn.summernote === 'function') {
                        $('#taskDesc').summernote('code', '');
                    } else {
                        document.getElementById('taskDesc').value = '';
                    }
                    if (window.jQuery && $.fn.select2) {
                        $('.form-select').val('').trigger('change');
                        $('#taskPriority').val('Medium').trigger('change');
                        $('#taskStatus').val('Pending').trigger('change');
                    } else {
                        const prioritySelect = document.getElementById('taskPriority');
                        if (prioritySelect) prioritySelect.value = 'Medium';
                        const statusSelect = document.getElementById('taskStatus');
                        if (statusSelect) statusSelect.value = 'Pending';
                    }
                } catch (e) { }
            }

            function buildFollowUpRow(selectedProjectId = '', existingPhotoUrl = '') {
                return `
                <div class="followup-row dt-fu-row">
                    <div class="dt-fu-row-head">
                        <span class="followup-row-title dt-fu-row-title">Work entry</span>
                        <button type="button" onclick="removeTaskClone(this)" class="zoho-icon-btn zoho-icon-btn--danger followup-remove-btn" title="Remove entry">
                            <i class="feather-trash-2"></i>
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="dt-fu-label">Project</label>
                            <div class="dt-input-wrap">
                                <i class="feather-briefcase"></i>
                                <select name="project_id[]" class="form-select followup-project-select" required>
                                    <option value="">Select project...</option>
                                    @foreach($projects as $project)
                                    <option value="{{ $project->id }}" ${selectedProjectId == '{{ $project->id }}' ? 'selected' : ''}>{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="dt-fu-label">Hours</label>
                            <div class="dt-input-wrap">
                                <i class="feather-clock"></i>
                                <input type="number" name="hours[]" class="form-control followup-hours" placeholder="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="dt-fu-label">Minutes</label>
                            <div class="dt-input-wrap">
                                <i class="feather-watch"></i>
                                <input type="number" name="minutes[]" class="form-control followup-minutes" placeholder="0" min="0" max="59">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="dt-fu-label">Work description <span class="text-danger">*</span></label>
                            <div class="dt-input-wrap dt-input-wrap--textarea">
                                <i class="feather-align-left"></i>
                                <textarea name="work_description[]" rows="3" class="form-control" placeholder="What did you work on?" required></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="dt-fu-label">Attachment</label>
                            <div class="dt-upload-zone">
                                <input type="hidden" class="followup-existing-photo" value="${existingPhotoUrl}">
                                <input type="file" name="photo[]" class="form-control followup-photo-input bg-transparent border-0 shadow-none" onchange="previewFollowUpAttachment(this)" accept=".jpeg,.jpg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                                <div class="followup-preview-container mt-2 d-none">
                                    <img class="followup-photo-preview" alt="Preview" style="display:none; max-height:120px; border-radius:8px;">
                                    <div class="followup-doc-preview fw-bold text-primary" style="display:none;"></div>
                                    <button type="button" class="btn btn-sm btn-outline-danger followup-remove-attachment mt-2" onclick="clearFollowUpAttachment(this)">
                                        <i class="feather-x"></i> Remove file
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            }

            function resetFollowUpRows(selectedProjectId = '') {
                const container = document.getElementById('taskAddContainer');
                if (!container) return;
                container.innerHTML = buildFollowUpRow(selectedProjectId || '');
                syncFollowUpRowActions();
            }

            function syncFollowUpRowActions() {
                const rows = Array.from(document.querySelectorAll('#taskAddContainer .followup-row'));

                rows.forEach((row, index) => {
                    const title = row.querySelector('.followup-row-title');
                    const removeBtn = row.querySelector('.followup-remove-btn');

                    if (title) {
                        title.innerText = `Work Item ${index + 1}`;
                    }

                    if (removeBtn) {
                        const singleRow = rows.length === 1;
                        removeBtn.disabled = singleRow;
                        removeBtn.classList.toggle('disabled', singleRow);
                    }
                });
            }

            function openFollowUpModal(taskId, taskProjectName, mode = 'history', taskTitle = '', assignedEmpId = null, assignedEmpName = '', selectedProjectId = null) {
                document.getElementById('followUpTaskId').value = taskId;

                let headerTitle = taskTitle || 'Work Progress';
                const titleElement = document.getElementById('followUpTaskTitle');
                titleElement.innerText = headerTitle;
                titleElement.setAttribute('title', headerTitle);
                document.getElementById('followUpForm').reset();
                resetFollowUpRows(selectedProjectId);
                document.getElementById('followUpId').value = '';
                document.getElementById('totalFollowUpHours').value = 0;
                document.getElementById('submitReplyBtn').innerHTML = '<i class="feather-check"></i> Submit Progress';
                try { $('#workDesc').summernote('code', ''); } catch (e) { }
                removePreview();
                modalCurrentPage = 1;
                loadFollowUpHistory(taskId);

                const formCol = document.getElementById('followUpFormColumn');
                const modalFooter = document.getElementById('followUpModalFooter');
                const historyCol = document.getElementById('followUpHistoryColumn');
                const modalEl = document.getElementById('followUpModal');

                if (mode === 'add') {
                    formCol.classList.remove('d-none');
                    if (modalFooter) modalFooter.classList.remove('d-none');
                    if (historyCol) {
                        historyCol.classList.remove('col-lg-12');
                        historyCol.classList.add('col-lg-4');
                    }
                    if (modalEl) modalEl.classList.add('dt-progress-modal--wide');
                    document.getElementById('followUpModalLabel').innerText = 'Add Work Progress';
                } else {
                    formCol.classList.add('d-none');
                    if (modalFooter) modalFooter.classList.add('d-none');
                    if (historyCol) {
                        historyCol.classList.remove('col-lg-4');
                        historyCol.classList.add('col-lg-12');
                    }
                    if (modalEl) modalEl.classList.remove('dt-progress-modal--wide');
                    document.getElementById('followUpModalLabel').innerText = 'Work History';
                }
            }

            function loadFollowUpHistory(taskId) {
                fetch(`/daily-tasks/${taskId}/follow-ups`)
                    .then(res => res.json())
                    .then(data => {
                        globalFollowUps = data;
                        renderModalTable();
                    });
            }

            function addTaskClone() {
                $('#taskAddContainer').append(buildFollowUpRow());
                syncFollowUpRowActions();
            }

            function removeTaskClone(button) {
                const rows = document.querySelectorAll('#taskAddContainer .followup-row');
                if (rows.length <= 1) {
                    return;
                }

                button.closest('.followup-row')?.remove();
                syncFollowUpRowActions();
            }

            function addQuickTaskToDesc() {
                const titleInput = document.getElementById('quickTaskTitle');
                const hoursInput = document.getElementById('quickTaskHours');
                const minsInput = document.getElementById('quickTaskMins');
                const title = titleInput.value;
                const hours = parseFloat(hoursInput.value) || 0;
                const mins = parseFloat(minsInput.value) || 0;

                if (!title) {
                    Toast.fire({
                        icon: 'warning',
                        title: 'Enter sub-task name'
                    });
                    return;
                }
                if (hours === 0 && mins === 0) {
                    Toast.fire({
                        icon: 'warning',
                        title: 'Enter time'
                    });
                    return;
                }

                // 1. Calculate added decimal
                let addedDecimal = hours + (mins / 60);

                // 2. Format Display String
                let timeStrParts = [];
                if (hours > 0) timeStrParts.push(`${hours}h`);
                if (mins > 0) timeStrParts.push(`${mins}m`);
                let formattedTime = timeStrParts.join(' ');

                const timeStr = formattedTime ? ` — <b style="color: #3858f9;">${formattedTime}</b>` : '';
                const html = `<div class="sub-task-item mb-4" data-time="${addedDecimal}" style="border-left: 4px solid #3858f9; padding-left: 20px;">
                                                            <p class="mb-2" style="font-size: 16px; color: #1e293b;"><strong>• ${title.toUpperCase()}</strong>${timeStr}</p>
                                                            <ol class="text-muted" style="font-size: 14px; line-height: 1.7;">
                                                                <li>&nbsp;</li>
                                                            </ol>
                                                          </div><hr class="sub-task-hr" style="border-top: 2px solid #f1f5f9; margin: 20px 0;">`;

                if ($('#workDesc').length && typeof $.fn.summernote === 'function') {
                    const currentContent = $('#workDesc').summernote('code');
                    // Prepend new task to the TOP
                    $('#workDesc').summernote('code', html + currentContent);
                } else {
                    const el = document.getElementById('workDesc');
                    el.value = html + el.value;
                }

                // Clear inputs
                titleInput.value = '';
                hoursInput.value = '';
                minsInput.value = '';

                // Recalculate based on ACTUAL content in editor (handles manual deletions)
                const newTotal = recalculateTotalTime();
                Toast.fire({ icon: 'success', title: `Task added. Total: ${newTotal.toFixed(2)} hrs` });
            }

            // function recalculateTotalTime() {
            //     if (!$('#workDesc').length) return 0;
            //     const content = $('#workDesc').summernote('code');
            //     const tempDiv = $('<div>').html(content);
            //     let totalTime = 0;
                
            //     tempDiv.find('.sub-task-item').each(function() {
            //         let time = 0;
            //         let bTag = $(this).find('b');
            //         // Prefer parsing from text in <b> tag (respects manual edits)
            //         if (bTag.length && bTag.text().trim() !== "") {
            //             time = parseTimeFromText(bTag.text());
            //         }
                    
            //         // Fallback to data-time only if text is not empty
            //         if (time === 0) {
            //             if ($(this).text().trim().length > 5) {
            //                 time = parseFloat($(this).attr('data-time')) || 0;
            //             }
            //         }
            //         totalTime += time;
            //     });
                
            //     const hiddenHoursField = document.getElementById('totalFollowUpHours');
            //     if (hiddenHoursField) {
            //         hiddenHoursField.value = totalTime.toFixed(2);
            //     }
            //     return totalTime;
            // }

            function recalculateTotalTime() {
                let totalHours = 0;

                // Target every dynamic row inside the container wrapper
                document.querySelectorAll('#taskAddContainer .followup-row').forEach(row => {
                    const hoursInput = row.querySelector('.followup-hours');
                    const minsInput = row.querySelector('.followup-minutes');

                    const hoursValue = parseFloat(hoursInput ? hoursInput.value : 0) || 0;
                    const minsValue = parseFloat(minsInput ? minsInput.value : 0) || 0;

                    // Convert minutes into decimal hours fraction (e.g. 30 mins = 0.5 hours)
                    totalHours += hoursValue + (minsValue / 60);
                });
                const hiddenHoursField = document.getElementById('totalFollowUpHours');
                if (hiddenHoursField) {
                    hiddenHoursField.value = totalHours.toFixed(2); // Saves as standard decimal format string (e.g., "2.50")
                }

                return totalHours;
            }

            function parseTimeFromText(text) {
                let hours = 0;
                let mins = 0;
                let hMatch = text.match(/(\d+)\s*h/i);
                let mMatch = text.match(/(\d+)\s*m/i);
                if (hMatch) hours = parseFloat(hMatch[1]);
                if (mMatch) mins = parseFloat(mMatch[1]);
                return hours + (mins / 60);
            }

            function renderModalTable() {
                const body = document.getElementById('followUpHistoryBody');
                const searchInput = document.getElementById('modalSearch');
                const entriesInfo = document.getElementById('modalEntriesInfo');
                const paginationButtons = document.getElementById('modalPaginationButtons');

                if (!body || !searchInput || !entriesInfo || !paginationButtons) {
                    return;
                }

                const searchTerm = searchInput.value.toLowerCase().trim();

                let filtered = globalFollowUps.filter(fu => {
                    const description = (fu.work_description || '').toLowerCase();
                    const employeeName = (fu.employee_name || fu.reference_name || '').toLowerCase();
                    const matchesSearch = description.includes(searchTerm) || employeeName.includes(searchTerm);
                    return matchesSearch;
                });

                const totalItems = filtered.length;
                const totalPages = Math.ceil(totalItems / modalPageSize) || 1;
                if (modalCurrentPage > totalPages) modalCurrentPage = totalPages;
                const startIdx = (modalCurrentPage - 1) * modalPageSize;
                const paginated = filtered.slice(startIdx, startIdx + modalPageSize);

                body.innerHTML = '';
                paginated.forEach((fu, index) => {
                    const employeeName = fu.employee_name || fu.reference_name || 'Employee';
                    const employeeInitial = employeeName.charAt(0).toUpperCase();
                    let timeDisplay = fu.time_taken || '-';
                    if (fu.time_taken && !isNaN(fu.time_taken)) {
                        let totalHours = parseFloat(fu.time_taken);
                        let h = Math.floor(totalHours);
                        let m = Math.round((totalHours - h) * 60);

                        let display = [];
                        if (h > 0) display.push(h + 'h');
                        if (m > 0) display.push(m + 'm');
                        timeDisplay = display.length > 0 ? display.join(' ') : '0m';
                    }

                    let editBtn = fu.can_manage
                        ? `<a href="javascript:void(0);" onclick="editFollowUp(${fu.id})" class="avatar-text avatar-md bg-soft-primary text-primary rounded-circle shadow-none me-2" title="Edit Entry" style="width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none;"><i class="feather-edit-3" style="font-size:14px;"></i></a>`
                        : '';
                    let delBtn = fu.can_manage
                        ? `<a href="javascript:void(0);" onclick="deleteFollowUp(${fu.id})" class="avatar-text avatar-md bg-soft-danger text-danger rounded-circle shadow-none" title="Delete" style="width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none;"><i class="feather-trash-2" style="font-size:14px;"></i></a>`
                        : '';

                    body.innerHTML += `
                                        <div class="work-history-feed-item"
                                            style="
                                                padding: 22px 25px;
                                                border-bottom: 1px solid #eef2f7;
                                                background: #fff;
                                            ">

                                            <div class="d-flex justify-content-between align-items-start gap-3 w-100">

                                                <!-- Left Section -->
                                                <div class="d-flex align-items-start gap-3 w-100">

                                                    <!-- Profile Image -->
                                                    <div>
                                                        ${fu.employee?.photo
                                                            ? `
                                                            <img src="/storage/${fu.employee.photo}" 
                                                                alt="Profile"
                                                                style="
                                                                    width: 42px;
                                                                    height: 42px;
                                                                    border-radius: 50%;
                                                                    object-fit: cover;
                                                                    border: 2px solid #e2e8f0;
                                                                ">
                                                            `
                                                            : `
                                                            <div style="
                                                                width: 42px;
                                                                height: 42px;
                                                                border-radius: 50%;
                                                                background: #4361ee;
                                                                color: white;
                                                                display: flex;
                                                                align-items: center;
                                                                justify-content: center;
                                                                font-weight: 700;
                                                                font-size: 14px;
                                                            ">
                                                                ${employeeInitial}
                                                            </div>
                                                        `}
                                                    </div>

                                                    <!-- Content -->
                                                    <div class="flex-grow-1">

                                                        <!-- Name -->
                                                        <div class="fw-bold text-dark"
                                                            style="font-size: 15px;">
                                                            ${employeeName}
                                                        </div>

                                                        <!-- Time -->
                                                        <div class="text-muted mt-1"
                                                            style="font-size: 12px;">
                                                            ${timeDisplay} • 
                                                            ${new Date(fu.created_at).toLocaleDateString('en-GB', {
                                                                day: '2-digit',
                                                                month: 'short',
                                                                year: 'numeric'
                                                            })}
                                                        </div>

                                                        <!-- Subtask -->
                                                        ${fu.task_title ? `
                                                            <div class="mt-3 fw-bold text-primary"
                                                                style="
                                                                    font-size: 14px;
                                                                    text-transform: uppercase;
                                                                    letter-spacing: .3px;
                                                                ">
                                                                • ${fu.task_title}
                                                            </div>
                                                        ` : ''}

                                                        <!-- Task Points -->
                                                        <div class="mt-2"
                                                            style="
                                                                font-size: 14px;
                                                                line-height: 1.9;
                                                                color: #475569;
                                                            ">
                                                            ${fu.work_description || `
                                                                <span class="text-muted">No task points added.</span>
                                                            `}
                                                        </div>

                                                        <!-- Attachment -->
                                                        ${fu.photo ? `
                                                            <div class="mt-3">
                                                                <a href="javascript:void(0);" 
                                                                    onclick="viewAttachmentPopup('/storage/${fu.photo}')" 
                                                                    class="btn btn-sm btn-soft-primary fw-bold"
                                                                    style="border-radius: 8px;">
                                                                    <i class="feather-image me-1"></i>
                                                                    View Attached File
                                                                </a>
                                                            </div>
                                                        ` : ''}

                                                        <!-- Action Buttons -->
                                                        <div class="d-flex align-items-center gap-2 mt-3">
                                                            ${editBtn}
                                                            ${delBtn}
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                });
                if (totalItems === 0) {
                    body.innerHTML = `
                        <div class="py-5 text-center" style="background: #fff;">
                            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px; border-radius: 16px; background: #f8fafc; color: #94a3b8;">
                                <i class="feather-inbox" style="font-size: 22px;"></i>
                            </div>
                            <div class="fw-bold text-muted">No history found.</div>
                        </div>
                    `;
                }

                entriesInfo.innerText = `Showing ${totalItems === 0 ? 0 : startIdx + 1} to ${Math.min(startIdx + modalPageSize, totalItems)} of ${totalItems} entries`;

                const pgnBtn = paginationButtons;
                let pgnHtml = `<button type="button" class="btn btn-sm ${modalCurrentPage === 1 ? 'btn-light text-muted' : 'btn-outline-primary'}" ${modalCurrentPage === 1 ? 'disabled' : ''} onclick="changeModalPage(${modalCurrentPage - 1})"><i class="feather-chevron-left"></i></button>`;
                for (let i = 1; i <= totalPages; i++) {
                    pgnHtml += `<button type="button" class="btn btn-sm ${i === modalCurrentPage ? 'btn-primary' : 'btn-light text-dark'}" onclick="changeModalPage(${i})">${i}</button>`;
                }
                pgnHtml += `<button type="button" class="btn btn-sm ${modalCurrentPage === totalPages || totalItems === 0 ? 'btn-light text-muted' : 'btn-outline-primary'}" ${(modalCurrentPage === totalPages || totalItems === 0) ? 'disabled' : ''} onclick="changeModalPage(${modalCurrentPage + 1})"><i class="feather-chevron-right"></i></button>`;
                pgnBtn.innerHTML = pgnHtml;
            }

            function changeModalPage(page) {
                const totalPages = Math.max(1, Math.ceil(globalFollowUps.length / modalPageSize));
                modalCurrentPage = Math.min(Math.max(page, 1), totalPages);
                renderModalTable();
            }
            function changeModalEntries() {
                const limitSelect = document.getElementById('modalEntriesLimit');
                if (!limitSelect) return;
                modalPageSize = parseInt(limitSelect.value);
                modalCurrentPage = 1;
                renderModalTable();
            }
            function filterModalHistory() { modalCurrentPage = 1; renderModalTable(); }

            function editFollowUp(id) {
                const fu = globalFollowUps.find(f => f.id == id);
                if (!fu) return;

                document.getElementById('followUpId').value = fu.id;
                resetFollowUpRows(fu.project_id || '');
                
                // Prefill time inputs
                const totalHours = parseFloat(fu.time_taken) || 0;
                const h = Math.floor(totalHours);
                const m = Math.round((totalHours - h) * 60);
                const firstRow = document.querySelector('#taskAddContainer .followup-row');
                if (firstRow) {
                    const hoursInput = firstRow.querySelector('.followup-hours');
                    const minutesInput = firstRow.querySelector('.followup-minutes');
                    const descriptionInput = firstRow.querySelector('[name="work_description[]"]');

                    if (hoursInput) hoursInput.value = h > 0 ? h : '';
                    if (minutesInput) minutesInput.value = m > 0 ? m : '';
                    if (descriptionInput) {
                        // work_description is stored as the fully formatted display block
                        // ("• ProjectName - 4h" summary line + a <li> per description line).
                        // Pull just the <li> text back out so editing doesn't re-inject that
                        // summary line as if it were part of the original typed description.
                        const $parsed = $('<div>').html(fu.work_description || '');
                        const items = $parsed.find('li').map(function () {
                            return $(this).text().trim();
                        }).get();
                        descriptionInput.value = items.length ? items.join('\n') : $parsed.text().trim();
                    }
                }
                document.getElementById('totalFollowUpHours').value = totalHours;
                recalculateTotalTime();
                
                // Show existing file preview if any
                if (fu.photo) {
                    const fileInput = firstRow?.querySelector('.followup-photo-input');
                    const existingField = firstRow?.querySelector('.followup-existing-photo');
                    if (existingField) {
                        existingField.value = `/storage/${fu.photo}`;
                    }
                    if (fileInput) {
                        previewFollowUpAttachment(fileInput, `/storage/${fu.photo}`);
                    }
                } else {
                    removePreview();
                }
                
                document.getElementById('submitReplyBtn').innerText = 'UPDATE PROGRESS';
                
                // Ensure form is visible
                document.getElementById('followUpFormColumn').classList.remove('d-none');
                const historyCol = document.getElementById('followUpHistoryColumn');
                if (historyCol) {
                    historyCol.classList.remove('col-lg-12');
                    historyCol.classList.add('col-lg-4');
                }
                document.getElementById('followUpModal').classList.add('dt-progress-modal--wide');
            }

            let searchTimeout;
            function searchTable() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 400);
            }

            function performSearch() {
                const searchVal = document.getElementById('taskSearch').value;
                let url = new URL(window.location.href);
                if (searchVal) {
                    url.searchParams.set('search', searchVal);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.set('page', 1);

                window.history.pushState(null, '', url.toString());

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newBody = doc.getElementById('taskTableContainerBodyPart');
                    const currentBody = document.getElementById('taskTableContainerBodyPart');
                    if (newBody && currentBody) {
                        currentBody.innerHTML = newBody.innerHTML;
                    }
                    
                    const newHeader = doc.getElementById('taskTableContainerHeaderPart');
                    const currentHeader = document.getElementById('taskTableContainerHeaderPart');
                    if (newHeader && currentHeader) {
                        currentHeader.innerHTML = newHeader.innerHTML;
                    }

                    if (window.feather) {
                        feather.replace();
                    }
                })
                .catch(err => console.error('Error fetching search results:', err));
            }

            // AJAX navigation for pagination and entries dropdown inside container
            document.addEventListener('click', function(e) {
                const ajaxLink = e.target.closest('#taskTableContainerBodyPart a, #taskTableContainerHeaderPart a');
                if (ajaxLink && (ajaxLink.closest('.pagination') || ajaxLink.closest('.dropdown-menu'))) {
                    const targetUrl = ajaxLink.getAttribute('href');
                    if (targetUrl && targetUrl !== 'javascript:void(0)' && !targetUrl.startsWith('#')) {
                        e.preventDefault();
                        
                        window.history.pushState(null, '', targetUrl);
                        
                        fetch(targetUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newBody = doc.getElementById('taskTableContainerBodyPart');
                            const currentBody = document.getElementById('taskTableContainerBodyPart');
                            if (newBody && currentBody) {
                                currentBody.innerHTML = newBody.innerHTML;
                                currentBody.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                            const newHeader = doc.getElementById('taskTableContainerHeaderPart');
                            const currentHeader = document.getElementById('taskTableContainerHeaderPart');
                            if (newHeader && currentHeader) {
                                currentHeader.innerHTML = newHeader.innerHTML;
                            }
                            if (window.feather) {
                                feather.replace();
                            }
                        })
                        .catch(err => console.error('Error fetching dynamic content:', err));
                    }
                }
            });

            document.getElementById('followUpForm').addEventListener('submit', function (e) {
                e.preventDefault();

                const rows = Array.from(document.querySelectorAll('#taskAddContainer .followup-row'));
                const activeRows = rows.filter(row => {
                    const project = row.querySelector('[name="project_id[]"]')?.value;
                    const description = row.querySelector('[name="work_description[]"]')?.value.trim();
                    const hours = row.querySelector('[name="hours[]"]')?.value ?? '';
                    const minutes = row.querySelector('[name="minutes[]"]')?.value ?? '';
                    const photo = row.querySelector('[name="photo[]"]')?.files?.length ?? 0;
                    const existingPhoto = row.querySelector('.followup-existing-photo')?.value ?? '';

                    return project || description || hours !== '' || minutes !== '' || photo > 0 || existingPhoto;
                });

                const hasValidRow = activeRows.some(row => {
                    const project = row.querySelector('[name="project_id[]"]')?.value;
                    const description = row.querySelector('[name="work_description[]"]')?.value.trim();
                    return project && description;
                });

                if (!hasValidRow) {
                    Toast.fire({ icon: 'error', title: 'Please select a project and enter work description before submitting.' });
                    return;
                }

                const missingTimeRow = activeRows.find(row => {
                    const hours = row.querySelector('[name="hours[]"]')?.value ?? '';
                    const minutes = row.querySelector('[name="minutes[]"]')?.value ?? '';
                    return hours === '' && minutes === '';
                });

                if (missingTimeRow) {
                    Toast.fire({ icon: 'error', title: 'Enter time' });
                    missingTimeRow.querySelector('[name="hours[]"]')?.focus();
                    return;
                }

                recalculateTotalTime();

                const btn = document.getElementById('submitReplyBtn');
                const origText = btn.innerText;
                btn.innerText = 'SUBMITTING...'; btn.disabled = true;

                const followUpId = document.getElementById('followUpId').value;
                const url = followUpId ? `/daily-tasks/follow-up/${followUpId}` : '{{ url("/daily-tasks/follow-up") }}';
                const formData = new FormData(this);
                if (followUpId) {
                    formData.append('_method', 'PUT');
                }

                fetch(url, { 
                    method: 'POST', 
                    body: formData, 
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                        'Accept': 'application/json' 
                    } 
                })
                .then(res => {
                    // Intercept HTTP status codes with a JSON payload worth showing to the user
                    if (res.ok || res.status === 422 || res.status === 403) {
                        return res.json();
                    }
                    // Force non-JSON crashes (e.g. 500 Internal Server Error) straight to the catch handler
                    throw new Error('Server Exception');
                })
                .then(data => {
                    btn.innerText = origText;
                    btn.disabled = false;

                    if (data.error) {
                        Toast.fire({ icon: 'error', title: data.error });
                    } else if (data.success) {
                        this.reset();
                        resetFollowUpRows();
                        if ($('#workDesc').length) {
                            $('#workDesc').summernote('code', '');
                        }
                        removePreview();
                        Toast.fire({ icon: 'success', title: data.success }).then(() => location.reload());
                        if (typeof myFollowUpModal !== 'undefined' && myFollowUpModal) {
                            myFollowUpModal.hide();
                        }
                    } else if (data.errors) {
                        const firstError = Object.values(data.errors)[0];
                        if (Array.isArray(firstError) && firstError[0]) {
                            Toast.fire({ icon: 'error', title: firstError[0] });
                        }

                        // Clear old stale errors before rendering new ones
                        this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                        this.querySelectorAll('.invalid-feedback-custom').forEach(el => el.remove());

                        for (const [key, value] of Object.entries(data.errors)) {
                            const input = this.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const errorDiv = document.createElement('div');
                                // Added a distinct target class 'invalid-feedback-custom' to clear safely on next submit
                                errorDiv.className = 'invalid-feedback-custom d-block fw-bold small text-danger mb-1';
                                errorDiv.innerText = value[0];
                                input.parentNode.insertBefore(errorDiv, input);
                            } else if (value[0]) {
                                Toast.fire({ icon: 'error', title: value[0] });
                            }
                        }
                    } else if (data.message) {
                        Toast.fire({ icon: 'error', title: data.message });
                    }
                })
                .catch(err => { 
                    btn.innerText = origText; 
                    btn.disabled = false; 
                    Toast.fire({ icon: 'error', title: 'Submission failed. Please check your network connection or file size limit.' }); 
                });
            });

            document.getElementById('submitTaskBtn').addEventListener('click', function () {
                const form = document.getElementById('taskForm');

                // Clear previous errors
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const url = document.getElementById('methodField').innerHTML !== '' ? `/daily-tasks/${document.getElementById('taskId').value}` : '/daily-tasks';
                const formData = new FormData(form);
                
                // Add summernote content manually if needed, though FormData usually catches textarea
                const descVal = $('#taskDesc').summernote('code');
                formData.set('description', descVal);

                fetch(url, { 
                    method: 'POST', 
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, 
                    body: formData 
                })
                    .then(res => res.json()).then(result => {
                        if (result.success) {
                            Toast.fire({ icon: 'success', title: result.success }).then(() => location.reload());
                        } else if (result.errors) {
                            for (const [key, value] of Object.entries(result.errors)) {
                                const input = form.querySelector(`[name="${key}"]`);
                                if (input) {
                                    input.classList.add('is-invalid');
                                    const errorDiv = document.createElement('div');
                                    errorDiv.className = 'invalid-feedback d-block fw-bold small text-danger mb-1';
                                    errorDiv.innerText = value[0];
                                    input.parentNode.insertBefore(errorDiv, input);
                                }
                            }
                        } else if (result.message) {
                            Toast.fire({ icon: 'error', title: result.message });
                        }
                    });
            });

            // Task Timer Logic
            function updateTaskTimers() {
                const now = new Date();
                const timers = document.querySelectorAll('.task-timer');

                timers.forEach(timer => {
                    const dataEnd = timer.getAttribute('data-end');
                    const dataStart = timer.getAttribute('data-start');

                    if (dataEnd) {
                        const end = new Date(dataEnd);
                        if (now < end) {
                            let diff = end - now;
                            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                            diff -= days * (1000 * 60 * 60 * 24);
                            const hours = Math.floor(diff / (1000 * 60 * 60));
                            diff -= hours * (1000 * 60 * 60);
                            const mins = Math.floor(diff / (1000 * 60));
                            diff -= mins * (1000 * 60);
                            const secs = Math.floor(diff / 1000);

                            timer.innerHTML = `
                                        <span class="text-primary">${days}d</span> 
                                        <span class="text-secondary">${hours}h ${mins}m ${secs}s</span>
                                        <span class="text-muted small ms-1" style="font-size:9px;">LEFT</span>
                                    `;
                        } else {
                            let diff = now - end;
                            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                            diff -= days * (1000 * 60 * 60 * 24);
                            const hours = Math.floor(diff / (1000 * 60 * 60));
                            diff -= hours * (1000 * 60 * 60);
                            const mins = Math.floor(diff / (1000 * 60));
                            diff -= mins * (1000 * 60);
                            const secs = Math.floor(diff / 1000);

                            timer.innerHTML = `
                                        <span class="text-danger">${days}d</span> 
                                        <span class="text-danger small">${hours}h ${mins}m ${secs}s</span>
                                        <span class="text-danger fw-bold ms-1" style="font-size:9px;">OVERDUE</span>
                                    `;
                        }
                    } else if (dataStart) {
                        const start = new Date(dataStart);
                        let diff = now - start;
                        if (diff < 0) diff = 0;

                        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                        diff -= days * (1000 * 60 * 60 * 24);
                        const hours = Math.floor(diff / (1000 * 60 * 60));
                        diff -= hours * (1000 * 60 * 60);
                        const mins = Math.floor(diff / (1000 * 60));
                        diff -= mins * (1000 * 60);
                        const secs = Math.floor(diff / 1000);

                        timer.innerHTML = `
                                    <span class="text-info">${days}d</span> 
                                    <span class="text-info small">${hours}h ${mins}m ${secs}s</span>
                                    <span class="text-info fw-bold ms-1" style="font-size:9px;">ELAPSED</span>
                                `;
                    }
                });
            }

            setInterval(updateTaskTimers, 1000);
            document.addEventListener('DOMContentLoaded', () => {
                updateTaskTimers();
            });

            $(document).ready(function () {
                $('#taskDesc').summernote({
                    placeholder: 'Enter Description...',
                    tabsize: 2,
                    height: 100,
                    maximumImageFileSize: 1024 * 1024 * 5,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'video']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ],
                    callbacks: {
                        onImageUpload: function (files) {
                            for (let i = 0; i < files.length; i++) {
                                if (files[i].size > 1024 * 1024 * 5) {
                                    Toast.fire({ icon: 'error', title: 'Image too large (Max 5MB)' });
                                    continue;
                                }
                                let reader = new FileReader();
                                reader.onload = (e) => {
                                    $(this).summernote('insertImage', e.target.result);
                                };
                                reader.readAsDataURL(files[i]);
                            }
                        },
                        onChange: function (contents) {
                            $(this).val(contents);
                        }
                    }
                });
            });

            function showTaskDesc(id) {
                const html = document.getElementById('task_desc_' + id).innerHTML;
                document.getElementById('taskDetailBody').innerHTML = html;
                new bootstrap.Offcanvas(document.getElementById('taskDetailDrawer')).show();
            }

            function viewAttachmentPopup(url) {
                const isImage = url.match(/\.(jpeg|jpg|gif|png|webp)$/i) != null;
                let htmlContent = isImage
                    ? `<img src="${url}" style="width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px;">`
                    : `<iframe src="${url}" style="width: 100%; height: 70vh; border: none; border-radius: 8px;"></iframe>`;

                Swal.fire({
                    title: 'Attachment Preview',
                    html: htmlContent,
                    width: '900px',
                    showCloseButton: true,
                    showConfirmButton: false
                });
            }

            function deleteFollowUp(id) {
                Swal.fire({
                    title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/daily-tasks/follow-up/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        }).then(res => res.json()).then(data => {
                            if (data.success) {
                                Toast.fire({ icon: 'success', title: data.success });
                                loadFollowUpHistory(document.getElementById('followUpTaskId').value);
                            } else if (data.error) {
                                Toast.fire({ icon: 'error', title: data.error });
                            }
                        });
                    }
                });
            }

            function deleteRecord(e, form) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?', text: "You won't be able to revert this action!", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }

            // function updateTaskStatus(id, status) {
            //     fetch(`/daily-tasks/${id}/status`, {
            //         method: 'PATCH',
            //         headers: {
            //             'X-CSRF-TOKEN': '{{ csrf_token() }}',
            //             'Content-Type': 'application/json',
            //             'Accept': 'application/json'
            //         },
            //         body: JSON.stringify({ status: status })
            //     })
            //         .then(res => res.json())
            //         .then(data => {
            //             if (data.success) {
            //                 Toast.fire({ icon: 'success', title: data.success }).then(() => location.reload());
            //             } else {
            //                 Toast.fire({ icon: 'error', title: 'Update failed' });
            //             }
            //         });
            // }

            let statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            let currentProjectId=null;

            function openStatusModal( id, status, projectId ){
                $("#statusTaskId").val(id);
                $("#statusTaskStatus").val(status).trigger('change');
                $("#statusProjectId").val(projectId);
                $("#comment").val('');
                $("#assignSection").hide();
                $("#assignTo").html(`<option value="">Select Employee</option>`);
                statusModal.show();
            }

            function populateReassignEmployees(projectId) {
                const assignmentData = window.taskAssignmentData || {};
                const projectEmployees = assignmentData.projectEmployees || {};
                const projectLeadersMap = assignmentData.projectLeadersMap || {};
                const allEmployeesMap = assignmentData.allEmployeesMap || {};
                const currentEmpId = assignmentData.currentEmpId || 0;
                const isSysAdmin = !!assignmentData.isSysAdmin;

                const allowedIds = projectEmployees[projectId] || [];
                const leaders = projectLeadersMap[projectId] || [];
                const isLeaderOfProject = leaders.includes(currentEmpId) || leaders.includes(currentEmpId.toString());

                let options = '<option value="">Select Employee</option>';

                Object.entries(allEmployeesMap).forEach(([id, emp]) => {
                    const isMember = allowedIds.includes(parseInt(id)) || allowedIds.includes(id.toString());

                    if (!isMember) {
                        return;
                    }

                    if (isSysAdmin || isLeaderOfProject || id == currentEmpId) {
                        options += `<option value="${id}">${emp.name}</option>`;
                    }
                });

                $("#assignTo").html(options);
            }

            $(document).on("change","#statusTaskStatus",function(){

                if($(this).val()=="Reassign"){

                    $("#assignSection").slideDown();

                    let projectId=$("#statusProjectId").val();
                    populateReassignEmployees(projectId);

                }else{

                    $("#assignSection").slideUp();

                    $("#assignTo").html(
                        '<option value="">Select Employee</option>'
                    );

                }

            });


            function loadProjectMembers(){
                fetch(`/project-members/${currentProjectId}`)
                .then(res=>res.json())
                .then(data=>{
                    let options=`<option value="">Select Employee</option>`;
                    data.forEach(emp=>{options +=`<option value="${emp.id}">${emp.name}</option>`;});
                    $("#assignTo").html(options);
                });
            }



            function submitStatus(){
                let id=$("#statusTaskId").val();

                let status=$("#statusTaskStatus").val();

                let comment=$("#comment").val();

                let employee_id = $("#assignTo").val();

                fetch(`/daily-tasks/${id}/status`,
                {
                    method:'PATCH',
                    headers:{
                        'X-CSRF-TOKEN':
                        '{{ csrf_token() }}',

                        'Content-Type':
                        'application/json',

                        'Accept':
                        'application/json'
                    },

                    body:JSON.stringify({
                        status:status,
                        comment:comment,
                        employee_id:employee_id
                    })

                })

                .then(res=>res.json())
                .then(data=>{
                    if(data.success){
                        statusModal.hide();
                        Toast.fire({icon:'success', title:data.success})
                        .then(()=>location.reload());
                    }
                });
            }

            function showHistory(id){
                fetch(`/daily-task-history/${id}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Failed to load history');
                    }
                    return res.json();
                })
                .then(data=>{
                    let html='';
                    if(data.length===0){
                        html=`<div class="text-center text-muted py-4">No Tracking Found</div>`;
                    }

                data.forEach(item=>{
                    html +=`<div class="timeline-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <b>${item.user?.name ?? 'Unknown'}</b>
                                        changed status
                                    </div>
                                <div class="small text-muted">${new Date(item.created_at).toLocaleString()}</div>
                            </div>

                            <div class="mt-2">
                                <span class="badge bg-secondary">${item.old_status ?? '-'}</span>
                                <i class="feather-arrow-right mx-2"></i>
                                <span class="badge bg-success">${item.new_status}</span>
                            </div>

                            ${item.comment ? `
                                <div class="mt-3">
                                    <div class="small fw-bold">Comment</div>
                                    <div class="text-muted">${item.comment}</div>
                                </div>
                            ` : '' }

                        </div>`;

                });

                $("#historyBody").html(html);
                new bootstrap.Modal(document.getElementById("historyModal")).show();
                })
                .catch(() => {
                    Toast.fire({ icon: 'error', title: 'Unable to load task history.' });
                });
            }

            function updateTaskPriority(id, priority) {
                fetch(`/daily-tasks/${id}/priority`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ priority: priority })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Toast.fire({ icon: 'success', title: data.success }).then(() => location.reload());
                        } else {
                            Toast.fire({ icon: 'error', title: 'Update failed' });
                        }
                    });
            }
        </script>

        @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
                });
            </script>
        @endif
    @endpush
