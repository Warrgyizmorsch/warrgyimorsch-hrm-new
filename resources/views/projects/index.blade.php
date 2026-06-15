@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <!-- [ page-header ] start -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Projects</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Projects</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <div id="bulk-action-wrapper" style="display: none;">
                        <a href="javascript:void(0);" id="btn-bulk-delete" class="btn btn-icon btn-soft-danger"
                            style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="feather-trash-2 fs-18"></i>
                        </a>
                    </div>
                    <div class="filter-toggle-wrapper">
                        <a href="javascript:void(0);" class="btn btn-icon btn-light-brand" id="toggleFilter"
                            style="cursor: pointer;">
                            <i class="feather-filter"></i>
                        </a>
                    </div>
                    <a href="{{ route('projects.create') }}" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Project</span>
                    </a>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- <div class="page-header-collapse">
        <div class="accordion-body pb-2">
            <div class="row g-3">
                @php
                    $pendingCount = $projects->filter(fn($p) => in_array(strtolower($p->status), ['pending', 'not started']))->count();
                    $inProgressCount = $projects->filter(fn($p) => in_array(strtolower($p->status), ['in process', 'in progress']))->count();
                    $onHoldCount = $projects->filter(fn($p) => strtolower($p->status) == 'on hold')->count();
                    $reviewCount = $projects->filter(fn($p) => strtolower($p->status) == 'review')->count();
                    $reworkCount = $projects->filter(fn($p) => strtolower($p->status) == 'rework')->count();
                    $completedCount = $projects->filter(fn($p) => in_array(strtolower($p->status), ['completed', 'finished']))->count();
                @endphp
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-secondary">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Pending</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $pendingCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-secondary text-secondary">
                                    <i class="feather-pause-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-primary">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">In Process</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $inProgressCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-primary text-primary">
                                    <i class="feather-play-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-warning">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">On Hold</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $onHoldCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-warning text-warning">
                                    <i class="feather-minus-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-info">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Review</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $reviewCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-info text-info">
                                    <i class="feather-eye"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-danger">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Rework</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $reworkCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-danger text-danger">
                                    <i class="feather-refresh-ccw"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-success">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Completed</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $completedCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-success text-success">
                                    <i class="feather-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
    <!-- [ page-header ] end -->

    <div class="filter-wrapper" id="filterSection" style="display: none;">
        <div class="card stretch stretch-full border-bottom bg-light bg-opacity-10 p-4 mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Project Name</label>
                    <input type="text" id="filterProjectName" class="form-control" placeholder="Search..."
                        onkeyup="applyFilters()">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select" onchange="applyFilters()">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="In Process">In Process</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Review">Review</option>
                        <option value="Rework">Rework</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select id="filterDepartment" class="form-select" onchange="applyFilters()">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->name }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2 align-items-end">
                    <button class="btn btn-primary flex-grow-1 fw-bold" onclick="applyFilters()"
                        style="height: 48px; border-radius: 12px;">APPLY</button>
                    <a href="{{ route('projects.index') }}" class="btn btn-light"
                        style="height: 48px; border-radius: 12px; width: 60px; display: flex; align-items: center; justify-content: center;"><i
                            class="feather-refresh-cw"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Status Dashboard -->
        <div class="project-status-dashboard mt-4 ms-5">
                            <div class="d-flex flex-wrap gap-2">

                                <button class="status-filter-card active border-dark" data-filter="all">
                                    All
                                    <span class="badge bg-dark rounded-pill" id="count-all">0</span>
                                </button>

                                <button class="status-filter-card pending border-secondry" data-filter="Pending">
                                    Pending
                                    <span class="badge bg-secondary rounded-pill" id="count-pending">0</span>
                                </button>

                                <button class="status-filter-card process border-primary" data-filter="In Process">
                                    In Process
                                    <span class="badge bg-primary rounded-pill" id="count-process">0</span>
                                </button>

                                <button class="status-filter-card review border-info" data-filter="Review">
                                    Review
                                    <span class="badge bg-info rounded-pill" id="count-review">0</span>
                                </button>

                                <button class="status-filter-card hold border-warning" data-filter="On Hold">
                                    On Hold
                                    <span class="badge bg-warning rounded-pill" id="count-hold">0</span>
                                </button>

                                <button class="status-filter-card rework border-danger" data-filter="Rework">
                                    Rework
                                    <span class="badge bg-danger rounded-pill" id="count-rework">0</span>
                                </button>

                                <button class="status-filter-card completed border-success" data-filter="Completed">
                                    Completed
                                    <span class="badge bg-success rounded-pill" id="count-completed">0</span>
                                </button>

                            </div>
        </div>

    <!-- [ Main Content ] start -->
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <!-- SHOW ENTRIES -->
                        <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2">
                            <span class="text-muted small fw-bold text-uppercase">Show</span>
                            <div class="dropdown">
                                <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" id="showEntriesBtn"
                                    style="width: 80px; height: 44px; padding: 0 15px;">
                                    {{ $perPage ?? 20 }}
                                </button>
                                <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 12px;">
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 20) == 20 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}">20</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 50) == 50 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}">50</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 100) == 100 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}">100</a>
                                </div>
                            </div>
                            <span class="text-muted small fw-bold text-uppercase">entries</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" id="projectList">
                                <thead>
                                    <tr>
                                        <th class="wd-30">
                                            <div class="ms-1">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input shadow-none"
                                                        id="checkAllProject" style="cursor: pointer;">
                                                </div>
                                            </div>
                                        </th>
                                        <th>Project Details</th>
                                        <!-- <th>Project Name</th>
                                        <th>Technology</th>
                                        <th>Department</th>
                                        <th>Start Date</th> -->
                                        <!-- <th>End Date</th> -->
                                        <th>Lead</th>
                                        <th>Members</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4" style="width: 200px;">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($projects as $project)
                                        @php
                                            $normalizedStatus = $project->status;
                                            if ($normalizedStatus == 'Not Started')
                                                $normalizedStatus = 'Pending';
                                            if ($normalizedStatus == 'In Progress')
                                                $normalizedStatus = 'In Process';
                                            if ($normalizedStatus == 'Finished')
                                                $normalizedStatus = 'Completed';
                                        @endphp
                                        <tr class="single-item" data-name="{{ strtolower($project->name) }}"
                                            data-status="{{ $normalizedStatus }}" data-department="{{ is_array($project->department) ? implode(',', $project->department) : $project->department }}">
                                            <td>
                                                <div class="item-checkbox ms-1">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input checkbox shadow-none"
                                                            id="checkBox_{{ $project->id }}" style="cursor: pointer;">
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- <td class="project-name-td" style="max-width: 300px;">
                                                <div class="hstack gap-4">
                                                    <div class="avatar-image border-0 position-relative">
                                                        
                                                        <div class="progress-ring-wrapper"
                                                            style="position: relative; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                                            @php $progressVal = $project->progress; @endphp
                                                            <svg width="60" height="60" viewBox="0 0 100 100"
                                                                style="position: absolute; transform: rotate(-90deg);">
                                                            
                                                                <circle cx="50" cy="50" r="42" fill="none" stroke="#f1f5f9"
                                                                    stroke-width="10"></circle>
                                                            
                                                                <circle cx="50" cy="50" r="42" fill="none" stroke="#1d4ed8"
                                                                    stroke-width="10" stroke-dasharray="263.89"
                                                                    stroke-dashoffset="{{ 263.89 * (1 - $progressVal / 100) }}"
                                                                    stroke-linecap="round"
                                                                    style="transition: stroke-dashoffset 0.8s ease-in-out;">
                                                                </circle>
                                                            </svg>
                                                            <div class="avatar-text bg-white text-primary rounded-circle shadow-sm"
                                                                style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; z-index: 1; border: 1px solid rgba(0,0,0,0.05);">
                                                                <i class="feather-briefcase fs-18"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="project-info-wrapper">
                                                        <a href="{{ route('projects.show', $project) }}"
                                                            class="fw-bold text-dark fs-14 mb-1 d-block">{{ $project->name }}</a>
                                                        <div class="fs-12 text-muted text-truncate-1-line mb-1"
                                                            style="max-width: 250px;">
                                                            {!! strip_tags($project->description) !!}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-soft-secondary text-secondary">{{ $project->technology }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('projects.show', $project) }}" class="hstack gap-3">
                                                    <div>
                                                        <span
                                                            class="text-truncate-1-line fw-semibold text-dark"></span>
                                                    </div>
                                                </a>
                                            </td>
                                            <td>{{ $project->start_date ? $project->start_date->format('Y-m-d') : '-' }}</td> -->
                                            <td>
                                                <div class="hstack gap-4 align-items-start">
                                                    <div class="avatar-image border-0 position-relativeflex-shrink-0">
                                                        <div class="progress-ring-wrapper"
                                                            style="position: relative; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                                            @php $progressVal = $project->progress; @endphp
                                                            <svg width="60" height="60" viewBox="0 0 100 100"
                                                                style="position: absolute; transform: rotate(-90deg);">
                                                                <circle cx="50" cy="50" r="42" fill="none" stroke="#f1f5f9" stroke-width="10"></circle>
                                                                <circle cx="50" cy="50" r="42" fill="none" stroke="#1d4ed8"
                                                                    stroke-width="10" stroke-dasharray="263.89"
                                                                    stroke-dashoffset="{{ 263.89 * (1 - $progressVal / 100) }}"
                                                                    stroke-linecap="round"
                                                                    style="transition: stroke-dashoffset 0.8s ease-in-out;">
                                                                </circle>
                                                            </svg>
                                                            <div class="avatar-text bg-white text-primary rounded-circle shadow-sm"
                                                                style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; z-index: 1; border: 1px solid rgba(0,0,0,0.05);">
                                                                <i class="feather-briefcase fs-18"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="project-info-wrapper w-100">
                                                        <a href="{{ route('projects.show', $project) }}" class="fw-bold text-dark fs-14 text-muted text-truncate-1-line mb-1 d-block">{{ $project->name }}</a>
                                                        
                                                        <div class="hstack gap-2 flex-wrap mb-1">
                                                            <span class="badge bg-soft-secondary text-secondary fs-11">{{ $project->technology }}</span>
                                                            <span class="badge bg-soft-primary text-primary fs-11 fw-semibold">{{ is_array($project->department) ? implode(', ', $project->department) : $project->department }}</span>
                                                        </div>

                                                        <div class="fs-12 text-muted text-truncate-1-line mb-1" style="max-width: 280px;">
                                                            {!! strip_tags($project->description) !!}
                                                        </div>

                                                        <div class="fs-11 text-muted">
                                                            <i class="feather-calendar fs-11 me-1"></i>Start: {{ $project->start_date ? $project->start_date->format('Y-m-d') : '-' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- <td>
                                                @if($project->end_date)
                                                    {{ $project->end_date->format('Y-m-d') }}
                                                @else
                                                    <div class="ongoing-timer"
                                                        data-start="{{ $project->start_date ? $project->start_date->toISOString() : '' }}">
                                                        <div class="hstack gap-1 text-info mb-1"
                                                            style="font-size: 12px; font-weight: 700;">
                                                            <i class="feather-play-circle fs-13"></i>
                                                            <span>Ongoing</span>
                                                        </div>
                                                        @if($project->start_date)
                                                            <div class="hstack gap-1 align-items-center">
                                                                <span class="timer-display fw-bold text-info"
                                                                    style="font-size: 11px; white-space: nowrap;">0d 0h 0m 0s</span>
                                                                <span class="text-info opacity-75 fw-bold"
                                                                    style="font-size: 9px; letter-spacing: 0.5px;">ELAPSED</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td> -->
                                            <td>
                                                <div class="dropdown" style="min-width: 140px;" data-bs-auto-close="outside">
                                                    @php
                                                        $leaders = is_array($project->leaders) ? $project->leaders : [];
                                                        $leaderNames = [];
                                                        foreach ($employees as $emp) {
                                                            if (in_array($emp->id, $leaders)) {
                                                                $leaderNames[] = $emp->name;
                                                            }
                                                        }
                                                        $currentLeads = count($leaderNames) > 0 ? implode(', ', $leaderNames) : 'Select Leads...';
                                                    @endphp
                                                    <button class="lead-select-btn dropdown-toggle shadow-none" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false"
                                                        data-bs-boundary="viewport">
                                                        <span class="text-truncate">{{ $currentLeads }}</span>
                                                        <i class="feather-chevron-down fs-10 ms-1"></i>
                                                    </button>
                                                    <ul class="dropdown-menu shadow-lg border-0"
                                                        style="border-radius: 12px; max-height: 350px; overflow-y: auto; min-width: 200px; padding-top: 0">
                                                        <li class="sticky-top bg-white" style="z-index: 10;">
                                                            <div class="p-3 border-bottom mb-2">
                                                                <div class="input-group bg-light border"
                                                                    style="border-radius: 8px; overflow: hidden;">
                                                                    <span
                                                                        class="input-group-text bg-transparent border-0 pe-1"><i
                                                                            class="feather-search fs-12 text-muted"></i></span>
                                                                    <input type="text"
                                                                        class="form-control border-0 bg-transparent shadow-none fw-bold lead-search"
                                                                        oninput="window.filterLeadList(this)"
                                                                        placeholder="Search team member..."
                                                                        style="font-size: 13px; height: 38px;">
                                                                </div>
                                                            </div>
                                                        </li>
                                                        <li><a class="dropdown-item fw-bold lead-item py-2 mb-1 rounded mx-2"
                                                                href="javascript:void(0);"
                                                                onclick="updateProjectLeads('{{ $project->slug }}', [])">No
                                                                Leads</a></li>
                                                        @foreach($employees as $emp)
                                                            @if(in_array($emp->department, (array)$project->department))
                                                                <li><a class="dropdown-item fw-bold lead-item py-2 mb-1 rounded mx-2 {{ in_array($emp->id, $leaders) ? 'active' : '' }}"
                                                                        href="javascript:void(0);"
                                                                        onclick='toggleProjectLead("{{ $project->slug }}", @json($leaders), {{ $emp->id }})'>{{ $emp->name }}</a>
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dropdown" style="min-width: 180px;" data-bs-auto-close="outside">
                                                    @php
                                                        $members = is_array($project->members) ? $project->members : [];
                                                        $memberNames = [];
                                                        foreach ($employees as $emp) {
                                                            if (in_array($emp->id, $members)) {
                                                                $memberNames[] = $emp->name;
                                                            }
                                                        }
                                                        $currentMembers = count($memberNames) > 0 ? implode(', ', $memberNames) : 'Select Members...';
                                                    @endphp
                                                    <button class="lead-select-btn dropdown-toggle shadow-none" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false"
                                                        data-bs-boundary="viewport">
                                                        <span class="text-truncate">{{ $currentMembers }}</span>
                                                        <i class="feather-chevron-down fs-10 ms-1"></i>
                                                    </button>
                                                    <ul class="dropdown-menu shadow-lg border-0"
                                                        style="border-radius: 12px; max-height: 350px; overflow-y: auto; min-width: 240px; padding-top:0">
                                                        <li class="sticky-top bg-white" style="z-index: 10;">
                                                            <div class="p-3 border-bottom mb-2">
                                                                <div class="input-group bg-light border"
                                                                    style="border-radius: 8px; overflow: hidden;">
                                                                    <span
                                                                        class="input-group-text bg-transparent border-0 pe-1"><i
                                                                            class="feather-search fs-12 text-muted"></i></span>
                                                                    <input type="text"
                                                                        class="form-control border-0 bg-transparent shadow-none fw-bold lead-search"
                                                                        oninput="window.filterLeadList(this)"
                                                                        placeholder="Search team member..."
                                                                        style="font-size: 13px; height: 38px;">
                                                                </div>
                                                            </div>
                                                        </li>
                                                        <li><a class="dropdown-item fw-bold lead-item py-2 mb-1 rounded mx-2"
                                                                href="javascript:void(0);"
                                                                onclick='updateProjectMembers("{{ $project->slug }}", [])'>No
                                                                Members</a></li>
                                                        @foreach($employees as $emp)
                                                            @if(in_array($emp->department, (array)$project->department))
                                                                <li><a class="dropdown-item fw-bold lead-item py-2 mb-1 rounded mx-2 {{ in_array($emp->id, $members) ? 'active' : '' }}"
                                                                        href="javascript:void(0);"
                                                                        onclick='toggleProjectMember("{{ $project->slug }}", @json($members), {{ $emp->id }})'>{{ $emp->name }}</a>
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $currentStatus = $project->status;
                                                    if ($currentStatus == 'Not Started')
                                                        $currentStatus = 'Pending';
                                                    if ($currentStatus == 'In Progress')
                                                        $currentStatus = 'In Process';
                                                    if ($currentStatus == 'Finished')
                                                        $currentStatus = 'Completed';

                                                    $statusSlug = strtolower(str_replace(' ', '-', $currentStatus));
                                                    $statusClass = 'status-' . $statusSlug;
                                                @endphp
                                                <div class="dropdown premium-status-dropdown" style="min-width: 110px;">
                                                    <button class="btn-status {{ $statusClass }} dropdown-toggle shadow-none"
                                                        type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                                        data-bs-boundary="viewport">
                                                        {{ $currentStatus }} <i class="feather-chevron-down fs-10 ms-1"></i>
                                                    </button>
                                                    <ul class="dropdown-menu shadow-lg border-0" style="border-radius: 12px;">
                                                        <li><a class="dropdown-item fw-bold status-pending py-2 mb-1 rounded mx-2"
                                                                href="javascript:void(0);"
                                                                onclick="updateProjectStatus('{{ $project->slug }}', 'Pending')">Pending</a>
                                                        </li>
                                                        <li><a class="dropdown-item fw-bold status-in-process py-2 mb-1 rounded mx-2"
                                                                href="javascript:void(0);"
                                                                onclick="updateProjectStatus('{{ $project->slug }}', 'In Process')">In
                                                                Process</a></li>
                                                        <li><a class="dropdown-item fw-bold status-completed py-2 mb-1 rounded mx-2"
                                                                href="javascript:void(0);"
                                                                onclick="updateProjectStatus('{{ $project->slug }}', 'Completed')">Completed</a>
                                                        </li>
                                                        <li><a class="dropdown-item fw-bold status-on-hold py-2 mb-1 rounded mx-2"
                                                                href="javascript:void(0);"
                                                                onclick="updateProjectStatus('{{ $project->slug }}', 'On Hold')">On
                                                                Hold</a></li>
                                                        <li><a class="dropdown-item fw-bold status-review py-2 mb-1 rounded mx-2"
                                                                href="javascript:void(0);"
                                                                onclick="updateProjectStatus('{{ $project->slug }}', 'Review')">Review</a>
                                                        </li>
                                                        <li><a class="dropdown-item fw-bold status-rework py-2 rounded mx-2"
                                                                href="javascript:void(0);"
                                                                onclick="updateProjectStatus('{{ $project->slug }}', 'Rework')">Rework</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="hstack gap-1 justify-content-end">
                                                    <a href="javascript:void(0);"
                                                        class="avatar-text avatar-md bg-soft-warning text-warning rounded-circle btn-quick-view"
                                                        data-project="{{ json_encode($project) }}" title="Quick View">
                                                        <i class="feather-info"></i>
                                                    </a>
                                                    <a href="javascript:void(0);"
                                                        onclick="showTaskProgress('{{ $project->slug }}')"
                                                        class="avatar-text avatar-md bg-soft-success text-success rounded-circle"
                                                        title="Task Analysis">
                                                        <i class="feather-clipboard"></i>
                                                    </a>
                                                    <a href="{{ route('projects.show', $project) }}"
                                                        class="avatar-text avatar-md bg-soft-primary text-primary rounded-circle"
                                                        title="View Details">
                                                        <i class="feather-eye"></i>
                                                    </a>
                                                    <a href="{{ route('projects.edit', $project->slug) }}"
                                                        class="avatar-text avatar-md bg-soft-info text-info rounded-circle"
                                                        title="Edit">
                                                        <i class="feather-edit-3"></i>
                                                    </a>
                                                    <form action="{{ route('projects.destroy', $project->slug) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <a href="javascript:void(0);"
                                                            class="avatar-text avatar-md bg-soft-danger text-danger rounded-circle"
                                                            onclick="confirmDeleteProject(this.closest('form'), '{{ $project->name }}')"
                                                            title="Delete">
                                                            <i class="feather-trash-2"></i>
                                                        </a>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .project-status-dashboard {
            overflow-x: auto;
        }

        .project-status-dashboard .d-flex {
            flex-wrap: wrap;
        }

        .status-filter-card {
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: all .2s ease;
            font-size: 13px;
            font-weight: 600;
            min-width: auto;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-filter-card:hover {
            background: #f8fafc;
        }

        .status-filter-card.active {
            background: #eff6ff;
            border-color: #2563eb;
            color: #2563eb;
        }

        .status-title,
        .status-count {
            display: inline;
            margin: 0;
            font-size: 13px;
            font-weight: 600;
        }

        .status-count {
            opacity: .8;
        }

        /* Progress Circle */
        .progress-circle {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .progress-circle:hover {
            transform: scale(1.05);
        }

        /* Premium Custom Scrollbar for Dropdowns */
        .dropdown-menu {
            scroll-behavior: smooth;
        }

        .dropdown-menu::-webkit-scrollbar {
            width: 5px;
        }

        .dropdown-menu::-webkit-scrollbar-track {
            background: transparent;
        }

        .dropdown-menu::-webkit-scrollbar-thumb {
            background: rgba(56, 88, 249, 0.15);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: rgba(56, 88, 249, 0.4);
        }

        .badge {
            font-size: 10px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-body.p-3 {
            padding: 1.25rem 1.5rem !important;
        }

        /* Dropdown Z-Index & Clipping Fixes */
        .dropdown-menu {
            border-radius: 12px !important;
            z-index: 99999 !important;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2) !important;
        }

        .dropdown-item {
            transition: all 0.2s ease !important;
            border-radius: 8px !important;
            margin: 0 10px 5px 10px !important;
            width: auto !important;
        }

        .dropdown-item.status-pending:hover {
            background-color: rgba(100, 116, 139, 0.2) !important;
        }

        .dropdown-item.status-in-process:hover {
            background-color: rgba(56, 88, 249, 0.2) !important;
        }

        .dropdown-item.status-completed:hover {
            background-color: rgba(34, 197, 94, 0.2) !important;
        }

        .dropdown-item.status-on-hold:hover {
            background-color: rgba(245, 158, 11, 0.2) !important;
        }

        .dropdown-item.status-review:hover {
            background-color: rgba(6, 182, 212, 0.2) !important;
        }

        .dropdown-item.status-rework:hover {
            background-color: rgba(239, 68, 68, 0.2) !important;
        }

        .lead-item {
            background: rgba(56, 88, 249, 0.05) !important;
            color: #334155 !important;
            font-size: 13px !important;
        }

        .lead-item:hover,
        .lead-item.active {
            background: rgba(56, 88, 249, 0.15) !important;
            color: #3858f9 !important;
        }

        .table-responsive .table tr td {
            padding: 9px;
        }

        .table-responsive,
        .card,
        .card-body {
            overflow: visible !important;
        }

        /* Row active state for clarity - Stabilized to prevent shifting */
        tr.single-item {
            border-left: 4px solid transparent !important;
            transition: background-color 0.2s ease, border-left-color 0.2s ease !important;
        }

        tr.single-item.row-active {
            background-color: rgba(56, 88, 249, 0.05) !important;
            border-left-color: #3858f9 !important;
        }

        /* Select2 Premium Styling */
        .select2-container--default .select2-selection--single {
            height: 48px !important;
            border-radius: 12px !important;
            border: 1px solid #ebf0f5 !important;
            display: flex !important;
            align-items: center !important;
            background-color: #fcfdfe !important;
            transition: all 0.2s ease;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1a202c !important;
            font-weight: 600 !important;
            padding-left: 15px !important;
            font-size: 13px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 10px !important;
        }

        .select2-dropdown {
            border: 0 !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
            border-radius: 15px !important;
            overflow: hidden !important;
            margin-top: 8px !important;
            padding: 8px 0 !important;
        }

        .select2-search--dropdown {
            padding: 12px 15px !important;
        }

        .select2-search--dropdown .select2-search__field {
            border-radius: 10px !important;
            border: 1px solid #ebf0f5 !important;
            padding: 10px 15px !important;
            background-color: #f8fafc !important;
            font-size: 13px !important;
        }

        .select2-results__option {
            padding: 10px 15px !important;
            margin: 2px 10px !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            color: #4a5568 !important;
            transition: all 0.2s ease;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: rgba(56, 88, 249, 0.08) !important;
            color: #3858f9 !important;
        }

        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: rgba(56, 88, 249, 0.1) !important;
            color: #3858f9 !important;
        }

        /* Activity Style for Task Analysis */
        .main-task-header {
            font-weight: 700 !important;
            color: #334155 !important;
            font-size: 14px !important;
            margin-bottom: 8px !important;
            display: block !important;
        }

        .sub-task-point {
            display: flex !important;
            align-items: start !important;
            gap: 8px !important;
            margin-bottom: 6px !important;
            padding-left: 12px !important;
            color: #64748b !important;
            font-size: 13px !important;
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
@endsection

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Global variables/functions
        window.filterLeadList = function (input) {
            var value = input.value.toLowerCase().trim();
            var dropdown = input.closest('.dropdown-menu');
            if (!dropdown) return;
            var items = dropdown.querySelectorAll('li:not(.sticky-top)');
            var header = dropdown.querySelector('.sticky-top');
            for (var i = 0; i < items.length; i++) {
                var li = items[i];
                var text = (li.innerText || li.textContent).toLowerCase();
                if (text.indexOf(value) > -1) {
                    li.style.setProperty('display', 'block', 'important');
                    if (value !== "" && header) {
                        header.parentNode.insertBefore(li, header.nextSibling);
                    }
                } else {
                    li.style.setProperty('display', 'none', 'important');
                }
            }
        };

        $(document).ready(function () {
            // Dropdown initialization
            $('.dropdown-toggle').each(function () {
                new bootstrap.Dropdown(this, {
                    popperConfig: { strategy: 'fixed' }
                });
            });

            // Filter logic
            window.applyFilters = function () {
                var name = $('#filterProjectName').val().toLowerCase();
                var status = $('#filterStatus').val();
                var department = $('#filterDepartment').val();

                $('#projectList tbody tr.single-item').each(function () {
                    var rowName = $(this).data('name') || '';
                    var rowStatus = $(this).data('status') || '';
                    var rowDept = String($(this).data('department') || '');
                    var matchName = name === "" || rowName.includes(name);
                    var matchStatus = status === "" || rowStatus === status;
                    var rowDepts = rowDept ? rowDept.split(',') : [];
                    var matchDept = department === "" || rowDepts.includes(department);
                    if (matchName && matchStatus && matchDept) { $(this).show(); } else { $(this).hide(); }
                });
            };

            $('#toggleFilter').on('click', function (e) {
                e.preventDefault();
                $('#filterSection').slideToggle(400);
            });

            // Bulk actions
            $('#checkAllProject').on('change', function () {
                $('.checkbox').prop('checked', $(this).prop('checked'));
                toggleBulkAction();
            });

            $(document).on('change', '.checkbox', function () {
                toggleBulkAction();
            });

            function toggleBulkAction() {
                var checkedCount = $('.checkbox:checked').length;
                if (checkedCount > 0) { $('#bulk-action-wrapper').fadeIn(300); } else { $('#bulk-action-wrapper').fadeOut(300); }
            }

            // Timers
            function updateOngoingTimers() {
                $('.ongoing-timer').each(function () {
                    var startISO = $(this).data('start');
                    if (!startISO) return;
                    var start = new Date(startISO);
                    var now = new Date();
                    var diff = now - start;
                    if (diff < 0) { $(this).find('.timer-display').text('0d 0h 0m 0s'); return; }
                    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    $(this).find('.timer-display').text(days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's');
                });
            }
            setInterval(updateOngoingTimers, 1000);
            updateOngoingTimers();

            $('.form-select').select2({
                width: '100%',
                minimumResultsForSearch: 0,
                placeholder: "Select an option",
                allowClear: true
            });

            // Quick View Click Handler (Safest way)
            $('.btn-quick-view').on('click', function () {
                const project = $(this).data('project');
                showQuickView(project);
            });
        });

        // Global functions outside ready for reliability
        window.showQuickView = function (project) {
            const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
            document.getElementById('qvProjectName').innerText = project.name;
            document.getElementById('qvDescription').innerHTML = project.description || '<p class="text-muted">No description available.</p>';

            // Handle documents display
            const qvDocsContainer = document.getElementById('qvDocumentsContainer');
            const qvDocsList = document.getElementById('qvDocuments');
            qvDocsContainer.style.display = 'none';
            qvDocsList.innerHTML = '';

            const documents = project.documents || [];
            if (Array.isArray(documents) && documents.length > 0) {
                let docsHtml = '<div class="row g-3">';
                documents.forEach((doc, idx) => {
                    const fileName = doc.split('/').pop();
                    const fileUrl = `/storage/${doc}`;
                    docsHtml += `
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-2 border rounded bg-light" style="min-height: 48px;">
                                <i class="feather-file-text text-primary me-2 flex-shrink-0 fs-16"></i>
                                <a href="${fileUrl}" target="_blank" class="text-primary text-decoration-none fw-bold text-truncate small" title="${fileName}">${fileName}</a>
                            </div>
                        </div>
                    `;
                });
                docsHtml += '</div>';
                qvDocsList.innerHTML = docsHtml;
                qvDocsContainer.style.display = 'block';
            }

            const leaders = project.leaders || [];
            const members = project.members || [];
            const allEmps = @json($employees);

            let teamHtml = '';
            if (leaders.length > 0) {
                teamHtml += '<h6 class="fw-bold text-primary mb-3">Project Leads</h6><div class="row g-3 mb-4">';
                allEmps.forEach(emp => {
                    if (leaders.includes(emp.id.toString()) || leaders.includes(emp.id)) {
                        teamHtml += `<div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 p-2 border rounded">
                                    <div class="avatar-text avatar-sm bg-soft-primary text-primary rounded-circle">${emp.name.charAt(0)}</div>
                                    <div class="fw-bold small text-dark">${emp.name}</div>
                                </div>
                            </div>`;
                    }
                });
                teamHtml += '</div>';
            }
            if (members.length > 0) {
                teamHtml += '<h6 class="fw-bold text-info mb-3">Team Members</h6><div class="row g-3">';
                allEmps.forEach(emp => {
                    if (members.includes(emp.id.toString()) || members.includes(emp.id)) {
                        teamHtml += `<div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 p-2 border rounded bg-light">
                                    <div class="avatar-text avatar-xs bg-soft-info text-info rounded-circle" style="width:24px; height:24px; font-size:10px;">${emp.name.charAt(0)}</div>
                                    <div class="fw-medium small text-dark text-truncate">${emp.name}</div>
                                </div>
                            </div>`;
                    }
                });
                teamHtml += '</div>';
            }
            if (teamHtml === '') teamHtml = '<div class="alert alert-soft-secondary text-center">No team members assigned.</div>';
            document.getElementById('qvTeamList').innerHTML = teamHtml;
            modal.show();
        };

        window.confirmDeleteProject = function (form, name) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to delete project: " + name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3858f9',
                cancelButtonColor: '#a3a3a3',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary px-4 py-2 me-2',
                    cancelButton: 'btn btn-secondary px-4 py-2 ms-2'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) { form.submit(); }
            });
        };

        window.showTaskProgress = function (projectId) {
            const modalEl = document.getElementById('taskProgressModal');
            const listContainer = document.getElementById('tpList');
            listContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Analyzing tasks...</p></div>';

            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            fetch(`/projects/${projectId}/tasks-summary`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('tpProjectName').innerText = data.project_name;
                    if (!data.tasks || data.tasks.length === 0) {
                        listContainer.innerHTML = '<div class="alert alert-soft-secondary text-center">No tasks found for this project.</div>';
                        return;
                    }

                    let html = '';
                    const employeeWork = {};

                    data.tasks.forEach(task => {
                        const empName = task.employee ? task.employee.name : 'Unassigned';
                        if (!employeeWork[empName]) employeeWork[empName] = {};

                        if (task.follow_ups && task.follow_ups.length > 0) {
                            task.follow_ups.forEach(fu => {
                                const date = new Date(fu.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                                if (!employeeWork[empName][date]) employeeWork[empName][date] = { tasks: {}, dailyTotal: 0 };

                                const taskTitle = task.task_title;
                                if (!employeeWork[empName][date].tasks[taskTitle]) {
                                    employeeWork[empName][date].tasks[taskTitle] = { updates: [], taskTotal: 0 };
                                }

                                let time = 0;
                                const matches = (fu.time_taken || "").match(/[+-]?([0-9]*[.])?[0-9]+/);
                                if (matches) time = parseFloat(matches[0]);

                                employeeWork[empName][date].tasks[taskTitle].updates.push({
                                    description: fu.work_description,
                                    time: time,
                                    timestamp: new Date(fu.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                                });
                                employeeWork[empName][date].tasks[taskTitle].taskTotal += time;
                                employeeWork[empName][date].dailyTotal += time;
                            });
                        }
                    });

                    for (const [empName, dates] of Object.entries(employeeWork)) {
                        html += `<div class="mb-5">
                                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                    <div class="avatar-text avatar-lg bg-primary text-white rounded-circle shadow">${empName.charAt(0)}</div>
                                    <div>
                                        <h4 class="fw-bold text-dark mb-0">${empName}</h4>
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 1px;">Project Contributor</small>
                                    </div>
                                </div>`;

                        const sortedDates = Object.keys(dates).sort((a, b) => new Date(b) - new Date(a));
                        sortedDates.forEach(date => {
                            const dayData = dates[date];
                            let dTotal = dayData.dailyTotal;
                            let dTh = Math.floor(dTotal);
                            let dTm = Math.round((dTotal - dTh) * 60);
                            let dTimeStr = (dTh > 0 ? dTh + 'h ' : '') + (dTm > 0 ? dTm + 'm' : (dTh === 0 ? '0m' : ''));

                            html += `<div class="ms-4 mb-5">
                                    <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded-4 border">
                                        <div class="fw-bold text-dark fs-15"><i class="feather-calendar text-primary me-2"></i>${date}</div>
                                        <div class="badge bg-dark text-white px-3 py-2 rounded-pill fs-12">Total Day: ${dTimeStr}</div>
                                    </div>
                                    <div class="ms-2 border-start ps-4 position-relative" style="border-left: 2px dashed #cbd5e1 !important;">`;

                            for (const [title, taskData] of Object.entries(dayData.tasks)) {
                                let tTotal = taskData.taskTotal;
                                let tTh = Math.floor(tTotal);
                                let tTm = Math.round((tTotal - tTh) * 60);
                                let tTimeStr = (tTh > 0 ? tTh + 'h ' : '') + (tTm > 0 ? tTm + 'm' : (tTh === 0 ? '0m' : ''));

                                html += `<div class="mb-5 position-relative">
                                        <div class="position-absolute start-0 translate-middle-x bg-white" style="margin-left: -26px; width: 14px; height: 14px; border-radius: 50%; border: 4px solid #3858f9; top: 6px; box-shadow: 0 0 0 5px #fff;"></div>
                                        <div class="task-group-card p-4 rounded-4 border bg-white shadow-sm" style="border-color: #e2e8f0 !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="fw-bold text-dark mb-0 fs-16">${title}</h5>
                                                <span class="badge bg-soft-primary text-primary border border-primary border-opacity-10">${tTimeStr}</span>
                                            </div>
                                            <div class="updates-timeline">`;

                                taskData.updates.forEach(update => {
                                    let uh = Math.floor(update.time);
                                    let um = Math.round((update.time - uh) * 60);
                                    let uTimeStr = (uh > 0 ? uh + 'h ' : '') + (um > 0 ? um + 'm' : (uh === 0 ? '0m' : ''));

                                    let pointsHtml = '';
                                    if (update.description) {
                                        const lines = update.description.split(/\n|<br>|<p>|<\/p>|<div>|<\/div>|<li>|<\/li>/).filter(l => l.trim() !== "");
                                        let isFirstLine = true;
                                        lines.forEach(line => {
                                            const clean = line.replace(/<[^>]*>?/gm, '').replace(/^[•\*\-·\d\.\s]+/u, '').trim();
                                            if (clean) {
                                                if (isFirstLine) {
                                                    pointsHtml += `<div class="main-task-header">• ${clean}</div>`;
                                                    isFirstLine = false;
                                                } else {
                                                    pointsHtml += `<div class="sub-task-point">
                                                            <i class="feather-check text-success mt-1" style="font-size: 11px;"></i>
                                                            <span>${clean}</span>
                                                        </div>`;
                                                }
                                            }
                                        });
                                    }

                                    html += `<div class="update-item mb-4 last-child-mb-0">
                                            <div class="d-flex align-items-center gap-2 mb-2 text-muted" style="font-size: 11px;">
                                                <i class="feather-clock"></i>
                                                <span class="fw-bold text-uppercase">${update.timestamp} Update</span>
                                                <span class="opacity-50">|</span>
                                                <span class="fw-bold text-primary">${uTimeStr}</span>
                                            </div>
                                            <div class="ps-1">
                                                ${pointsHtml || '<div class="text-muted italic fs-13">No details provided</div>'}
                                            </div>
                                        </div>`;
                                });

                                html += `</div></div></div>`;
                            }
                            html += `</div></div>`;
                        });
                        html += `</div>`;
                    }
                    listContainer.innerHTML = html;
                });
        };

        window.updateProjectStatus = function (id, status) {
            updateProjectField(id, { status: status }, 'Status Updated');
            setTimeout(() => location.reload(), 500);
        };

        window.updateProjectLeads = function (id, leaderIds) {
            updateProjectField(id, { leaders: leaderIds }, 'Leads Updated');
            setTimeout(() => location.reload(), 500);
        };

        window.toggleProjectLead = function (id, currentLeaders, leaderId) {
            currentLeaders = Array.isArray(currentLeaders) ? currentLeaders.map(Number) : [];
            leaderId = Number(leaderId);

            var updatedLeaders = currentLeaders.includes(leaderId)
                ? currentLeaders.filter(function (id) { return id !== leaderId; })
                : currentLeaders.concat(leaderId);

            window.updateProjectLeads(id, updatedLeaders);
        };

        window.updateProjectMembers = function (id, memberIds) {
            updateProjectField(id, { members: memberIds }, 'Members Updated');
            setTimeout(() => location.reload(), 500);
        };

        window.toggleProjectMember = function (id, currentMembers, memberId) {
            currentMembers = Array.isArray(currentMembers) ? currentMembers.map(Number) : [];
            memberId = Number(memberId);

            var updatedMembers = currentMembers.includes(memberId)
                ? currentMembers.filter(function (id) { return id !== memberId; })
                : currentMembers.concat(memberId);

            updateProjectMembers(id, updatedMembers);
        };

        function updateProjectField(id, data, successTitle) {
            var url = '{{ route("projects.update-field", ["project" => ":id"]) }}'.replace(':id', id);
            $.ajax({
                url: url, type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: data,
                success: function () { if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: successTitle }); }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {

        const rows = document.querySelectorAll('#projectList tbody tr.single-item');

        const counts = {
            all: 0,
            pending: 0,
            process: 0,
            review: 0,
            hold: 0,
            rework: 0,
            completed: 0
        };

        rows.forEach(row => {

            counts.all++;

            let status = row.dataset.status.trim();

            switch(status) {

                case 'Pending':
                    counts.pending++;
                    break;

                case 'In Process':
                    counts.process++;
                    break;

                case 'Review':
                    counts.review++;
                    break;

                case 'On Hold':
                    counts.hold++;
                    break;

                case 'Rework':
                    counts.rework++;
                    break;

                case 'Completed':
                    counts.completed++;
                    break;
            }
        });

        document.getElementById('count-all').innerText = counts.all;
        document.getElementById('count-pending').innerText = counts.pending;
        document.getElementById('count-process').innerText = counts.process;
        document.getElementById('count-review').innerText = counts.review;
        document.getElementById('count-hold').innerText = counts.hold;
        document.getElementById('count-rework').innerText = counts.rework;
        document.getElementById('count-completed').innerText = counts.completed;

        const filterButtons = document.querySelectorAll('.status-filter-card');

        filterButtons.forEach(btn => {

            btn.addEventListener('click', function () {

                filterButtons.forEach(b => b.classList.remove('active'));

                this.classList.add('active');

                const filter = this.dataset.filter;

                rows.forEach(row => {

                    const rowStatus = row.dataset.status.trim();

                    if(filter === 'all') {
                        row.style.display = '';
                    } else {
                        row.style.display =
                            rowStatus === filter ? '' : 'none';
                    }
                });

            });

        });

    });
    </script>

    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 ps-4 pt-4">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="feather-info text-primary"></i>
                        <span id="qvProjectName">Project Name</span>
                    </h5>
                    <button type="button" class="btn-close shadow-none me-2" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Description</label>
                        <div id="qvDescription" class="p-3 bg-light rounded-3 text-dark fs-14"
                            style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0;">
                        </div>
                    </div>

                    <div class="mb-4" id="qvDocumentsContainer" style="display: none;">
                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Attached Documents</label>
                        <div id="qvDocuments">
                        </div>
                    </div>

                    <hr class="my-4" style="border-style: dashed;">

                    <div id="qvTeamList">
                        <!-- Team details injected here -->
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light-brand w-100 fw-bold" data-bs-dismiss="modal"
                        style="border-radius: 10px;">CLOSE QUICK VIEW</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Progress Analysis Modal -->
    <div class="modal fade" id="taskProgressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 ps-4 pt-4">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="feather-clipboard text-success"></i>
                        <span id="tpProjectName">Project Task Progress</span>
                    </h5>
                    <button type="button" class="btn-close shadow-none me-2" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="tpList" style="max-height: 65vh; overflow-y: auto;">
                        <!-- Content will be injected here -->
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light-brand w-100 fw-bold" data-bs-dismiss="modal"
                        style="border-radius: 10px;">CLOSE ANALYSIS</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Task Progress Analysis Modal -->
    <div class="modal fade" id="taskProgressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 ps-4 pt-4">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="feather-clipboard text-success"></i>
                        <span id="tpProjectName">Project Task Progress</span>
                    </h5>
                    <button type="button" class="btn-close shadow-none me-2" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="tpList" style="max-height: 65vh; overflow-y: auto;">
                        <!-- Content will be injected here -->
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light-brand w-100 fw-bold" data-bs-dismiss="modal"
                        style="border-radius: 10px;">CLOSE ANALYSIS</button>
                </div>
            </div>
        </div>
    </div>
@endpush
