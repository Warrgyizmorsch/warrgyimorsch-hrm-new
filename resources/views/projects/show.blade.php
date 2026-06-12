@extends('layouts.app')

@section('title', 'Project Details')

@section('content')
    <!-- [ page-header ] start -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Projects</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                <li class="breadcrumb-item">Project Details</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">
                        <i class="feather-edit-3 me-2"></i>
                        <span>Edit Project</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- [ page-header ] end -->

    <div class="bg-white py-3 border-bottom rounded-0 p-md-0 mb-0">
        <div class="d-flex align-items-center justify-content-between">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs nav-tabs-custom-style" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overviewTab">Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#activityTab">Activity</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#discussionsTab">Discussions</button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- [ Main Content ] start -->
    <div class="main-content">
        <div class="tab-content">
            <!-- Overview Tab -->
            <div class="tab-pane fade active show" id="overviewTab">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card stretch stretch-full overflow-hidden shadow-sm border-0">
                            <div class="card-body p-4">
                                <div class="d-md-flex align-items-center justify-content-between">
                                    <div class="hstack gap-4">
                                        <div class="avatar-image border-0 position-relative">
                                            <!-- Premium SVG Circular Progress -->
                                            <div class="progress-ring-wrapper"
                                                style="position: relative; width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                                                @php $progressVal = $project->progress; @endphp
                                                <svg width="70" height="70" viewBox="0 0 100 100"
                                                    style="position: absolute; transform: rotate(-90deg);">
                                                    <!-- Background Track -->
                                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#f1f5f9"
                                                        stroke-width="10"></circle>
                                                    <!-- Progress Bar -->
                                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#1d4ed8"
                                                        stroke-width="10" stroke-dasharray="263.89"
                                                        stroke-dashoffset="{{ 263.89 * (1 - $progressVal / 100) }}"
                                                        stroke-linecap="round"
                                                        style="transition: stroke-dashoffset 0.8s ease-in-out;"></circle>
                                                </svg>
                                                <div class="avatar-text avatar-xl bg-white text-primary rounded-circle shadow-sm"
                                                    style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; z-index: 1; border: 1px solid rgba(0,0,0,0.05);">
                                                    <i class="feather-briefcase fs-30"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="fw-bold text-dark mb-1">{{ $project->name }}</h3>
                                            <div class="hstack gap-3 fs-12 text-muted">
                                                <span
                                                    class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill">{{ $project->status }}</span>
                                                <span class="vr"></span>
                                                <span><i class="feather-calendar me-1"></i>
                                                    {{ $project->start_date ? $project->start_date->format('M d, Y') : 'N/A' }}</span>
                                                <span class="vr"></span>
                                                @if($project->status != 'Completed')
                                                    @if($project->end_date)
                                                        <span class="task-timer fw-bold text-primary"
                                                            data-end="{{ $project->end_date->toIso8601String() }}">Calculating...</span>
                                                    @else
                                                        <span class="task-timer fw-bold text-info"
                                                            data-start="{{ $project->start_date->toIso8601String() }}">Calculating...</span>
                                                    @endif
                                                @endif
                                                <span class="vr"></span>
                                                <span><i class="feather-tag me-1"></i> {{ $project->technology }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 mt-md-0 d-flex gap-2">
                                        <a href="{{ route('projects.edit', $project) }}"
                                            class="btn btn-light-brand border shadow-sm px-4">
                                            <i class="feather-edit-3 me-2"></i><span>Edit</span>
                                        </a>
                                        <button class="btn btn-primary px-4 shadow-sm">
                                            <i class="feather-share-2 me-2"></i><span>Share</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $progress = $project->progress;
                    @endphp

                    <div class="col-xl-12">
                        <div class="card stretch stretch-full border-0 shadow-sm mb-4">
                            <div class="card-header border-bottom-0 pt-4 px-4">
                                <h5 class="card-title fw-bold">Overview & Description</h5>
                            </div>
                            <div class="card-body p-4 pt-0">
                                <div class="row g-4 mb-5">
                                    <div class="col-sm-4">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-10">
                                            <span
                                                class="fs-11 text-muted text-uppercase fw-bold d-block mb-1">Department</span>
                                            <span class="fw-bold text-dark">{{implode(', ', $project->department)}}</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-10">
                                            <span class="fs-11 text-muted text-uppercase fw-bold d-block mb-1">Start
                                                Date</span>
                                            <span
                                                class="fw-bold text-dark">{{ $project->start_date ? $project->start_date->format('M d, Y') : 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-10">
                                            <span class="fs-11 text-muted text-uppercase fw-bold d-block mb-1">Due
                                                Date</span>
                                            @if($project->end_date)
                                                <span
                                                    class="fw-bold text-dark">{{ $project->end_date->format('M d, Y') }}</span>
                                            @else
                                                <span class="fw-bold text-info">Ongoing</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-4 mb-5">
                                    <div class="col-sm-6">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-10">
                                            <span class="fs-11 text-muted text-uppercase fw-bold d-block mb-1">Project
                                                Type</span>
                                            <span class="fw-bold text-dark"><i
                                                    class="feather-{{ strtolower($project->type) == 'personal' ? 'user' : 'users' }} me-2 text-primary"></i>{{ $project->type ?? 'Standard' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-10">
                                            <span class="fs-11 text-muted text-uppercase fw-bold d-block mb-1">Manage
                                                Access</span>
                                            <span class="fw-bold text-dark"><i
                                                    class="feather-{{ strtolower($project->manage) == 'everyone' ? 'globe' : 'shield' }} me-2 text-primary"></i>{{ $project->manage ?? 'Everyone' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="fw-bold text-dark mb-3">Project Description</h6>
                                <div class="text-muted lh-lg fs-14">
                                    @php
                                        // Clean up description to remove empty bullet points or paragraphs added by editor
                                        $cleanDescription = $project->description;
                                        // Remove empty tags like <p><br></p> or <li><br></li> or <li>&nbsp;</li>
                                        $cleanDescription = preg_replace('/<(p|li|div|span)[^>]*>\s*(<br\/?>|&nbsp;|\s)*<\/\1>/i', '', $cleanDescription);
                                        // Remove trailing empty tags
                                        $cleanDescription = preg_replace('/(<br\/?>|&nbsp;|\s)+$/i', '', $cleanDescription);
                                    @endphp
                                    {!! $cleanDescription !!}
                                </div>

                                @if(is_array($project->documents) && count($project->documents) > 0)
                                    <div class="mt-5 border-top pt-4">
                                        <h6 class="fw-bold text-dark mb-3">Attached Documents</h6>
                                        <div class="row g-3">
                                            @foreach($project->documents as $doc)
                                                @php
                                                    $fileName = basename($doc);
                                                    $fileUrl = asset('storage/' . $doc);
                                                @endphp
                                                <div class="col-md-4">
                                                    <div class="d-flex align-items-center p-3 border rounded-3 bg-light bg-opacity-10" style="min-height: 52px;">
                                                        <i class="feather-file-text text-primary me-2 flex-shrink-0 fs-18"></i>
                                                        <a href="{{ $fileUrl }}" target="_blank" class="text-primary text-decoration-none fw-bold text-truncate small" title="{{ $fileName }}">
                                                            {{ $fileName }}
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="row g-4">
                            <!-- Progress Graph Card -->
                            <div class="col-xl-4">
                                <div class="card stretch stretch-full border-0 shadow-sm">
                                    <div class="card-body p-4 text-center">
                                        <h6 class="fw-bold text-dark mb-4">Timeline Progress</h6>
                                        <div id="project-progress-chart"></div>
                                        <div
                                            class="hstack justify-content-center gap-3 text-muted fs-11 mt-3 pt-3 border-top">
                                            <span>Status: <strong
                                                    class="text-primary">{{ $project->status }}</strong></span>
                                            <span class="vr"></span>
                                            <span>Progress: <strong class="text-dark">{{ $progress }}%</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Team Details Card -->
                            <div class="col-xl-8">
                                <div class="card stretch stretch-full border-0 shadow-sm">
                                    <div class="card-header border-bottom-0 pt-4 px-4">
                                        <h6 class="card-title fw-bold">Project Team</h6>
                                    </div>
                                    <div class="card-body p-4 pt-2">
                                        <div class="row">
                                            <div class="col-md-6 border-end">
                                                <span class="fs-10 text-muted text-uppercase fw-bold d-block mb-3">Project
                                                    Leads</span>
                                                @php $leaders = is_array($project->leaders) ? $project->leaders : []; @endphp
                                                @forelse($employees->whereIn('id', $leaders) as $emp)
                                                    <div class="hstack gap-3 mb-3">
                                                        <div
                                                            class="avatar-text avatar-md bg-soft-primary text-primary rounded-circle">
                                                            {{ substr($emp->name, 0, 1) }}
                                                        </div>
                                                        <div>
                                                            <div class="fs-13 fw-bold text-dark">{{ $emp->name }}</div>
                                                            <div class="fs-11 text-muted">Lead Designer</div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <span class="text-muted fs-12">No leads assigned.</span>
                                                @endforelse
                                            </div>
                                            <div class="col-md-6">
                                                <span class="fs-10 text-muted text-uppercase fw-bold d-block mb-3">Team
                                                    Members</span>
                                                @php $members = is_array($project->members) ? $project->members : []; @endphp
                                                <div class="row g-3">
                                                    @forelse($employees->whereIn('id', $members) as $emp)
                                                        <div class="col-6">
                                                            <div class="hstack gap-2">
                                                                <div
                                                                    class="avatar-text avatar-sm bg-soft-info text-info rounded-circle">
                                                                    {{ substr($emp->name, 0, 1) }}
                                                                </div>
                                                                <div class="fs-12 fw-medium text-dark">{{ $emp->name }}</div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="col-12 text-muted fs-12">No members assigned.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Tab -->
            <div class="tab-pane fade" id="activityTab">
                <style>
                    .activity-description ul {
                        list-style-type: disc !important;
                        padding-left: 20px !important;
                        margin-bottom: 10px !important;
                        display: block !important;
                    }

                    .activity-description ol {
                        list-style-type: decimal !important;
                        padding-left: 20px !important;
                        margin-bottom: 10px !important;
                        display: block !important;
                    }

                    .activity-description li {
                        margin-bottom: 6px !important;
                        list-style-position: inside !important;
                    }

                    .activity-description ol,
                    .activity-description ul {
                        padding-left: 15px !important;
                        margin-bottom: 10px !important;
                    }

                    .activity-description p {
                        margin-bottom: 8px !important;
                        line-height: 1.6;
                    }

                    .activity-description .main-task-header {
                        font-weight: 700 !important;
                        color: #334155 !important;
                        font-size: 14px !important;
                        margin-bottom: 8px !important;
                        display: block !important;
                    }

                    .activity-description .sub-task-point {
                        display: flex !important;
                        align-items: start !important;
                        gap: 10px !important;
                        margin-bottom: 6px !important;
                        padding-left: 18px !important;
                        color: #64748b !important;
                        font-size: 13px !important;
                    }
                </style>
                <div class="activity-feed">
                    @forelse($dayGroups as $date => $tasksInDay)
                        <div class="date-header d-flex align-items-center justify-content-between mb-3 mt-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-text avatar-xs bg-soft-primary text-primary rounded-circle"
                                    style="width: 28px; height: 28px;">
                                    <i class="feather-calendar" style="font-size: 12px;"></i>
                                </div>
                                <h6 class="mb-0 fw-bold text-dark" style="font-size: 14px;">{{ $date }}</h6>
                            </div>
                            @php
                                $totalDayTime = 0;
                                foreach ($tasksInDay as $tData) {
                                    foreach ($tData['events'] as $e) {
                                        if ($e->type == 'progress') {
                                            $totalDayTime += (float) preg_replace('/[^0-9.]/', '', $e->time_taken);
                                        }
                                    }
                                }
                            @endphp
                            <span class="badge bg-soft-secondary text-secondary fw-bold"
                                style="font-size: 10px; letter-spacing: 0.5px; padding: 6px 12px; border-radius: 6px;">TOTAL DAY
                                WORK: {{ number_format($totalDayTime, 1) }} HOURS</span>
                        </div>

                        @foreach($tasksInDay as $taskId => $data)
                            @php $task = $data['task']; @endphp
                            <div class="card mb-4 border shadow-none"
                                style="border-radius: 12px; background: #fff; overflow: hidden;">
                                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between"
                                    style="border-top: 4px solid #3858f9;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-text avatar-md bg-soft-primary text-primary rounded-circle fw-bold shadow-sm"
                                            style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; font-size: 14px; border: 2px solid #fff;">
                                            {{ substr($task->employee->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2"
                                                style="font-size: 16px;">
                                                {{ $task->task_title }}
                                                @php
                                                    $s = $task->status;
                                                    $statusSlug = strtolower(str_replace(' ', '-', $s));
                                                    $statusClass = 'status-' . $statusSlug;
                                                @endphp
                                                <span class="badge {{ $statusClass }}"
                                                    style="font-size: 9px; padding: 4px 8px; border-radius: 6px; text-transform: uppercase;">{{ $s }}</span>
                                            </h6>
                                            <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 11.5px;">
                                                <div><span class="text-uppercase fw-bold text-primary"
                                                        style="font-size: 9.5px; letter-spacing: 0.5px;">ASSIGNED TO:</span> <span
                                                        class="ms-1 fw-bold text-dark">{{ $task->employee->name ?? 'Unknown' }}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-1"><i class="feather-clock"
                                                        style="font-size: 12px;"></i> <span
                                                        class="fw-bold">{{ $task->created_at->format('h:i A') }}</span></div>
                                            </div>
                                            @if($task->description)
                                                <div class="mt-2 text-muted small p-2 bg-light bg-opacity-50 rounded"
                                                    style="font-size: 12px; border-left: 3px solid #3858f9;">
                                                    <div class="main-task-description">
                                                        @php
                                                            $desc = html_entity_decode($task->description);
                                                            $descLines = explode("\n", str_replace(['<p>', '</p>', '<br>', '<div>', '</div>', '<li>', '</li>'], ["\n", "\n", "\n", "\n", "\n", "\n", "\n"], $desc));
                                                            foreach ($descLines as $dLine) {
                                                                $dLine = trim(strip_tags(html_entity_decode($dLine), '<b><strong><i><u>'));
                                                                if (empty($dLine))
                                                                    continue;
                                                                echo '<div class="d-flex align-items-start gap-1 mb-1">
                                                                                                                        <i class="feather-circle mt-1" style="font-size: 5px; color: #3858f9;"></i>
                                                                                                                        <span>' . $dLine . '</span>
                                                                                                                      </div>';
                                                            }
                                                        @endphp
                                                    </div>
                                                </div>
                                            @endif
                                            @if($task->photo)
                                                <div class="mt-2">
                                                    <button onclick="viewAttachmentPopup('{{ asset('storage/' . $task->photo) }}')"
                                                        class="btn btn-sm btn-soft-primary fw-bold px-3 py-1"
                                                        style="border-radius: 6px; font-size: 11px;">
                                                        <i class="feather-paperclip me-1"></i> VIEW MAIN ATTACHMENT
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end d-flex flex-column align-items-end gap-2">
                                        @if(isset($data['daily_total_time']) && $data['daily_total_time'] > 0)
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted fw-bold" style="font-size: 10px;">TOTAL WORK:</span>
                                                <span
                                                    class="badge bg-soft-info text-info border border-info border-opacity-25 px-3 py-2"
                                                    style="font-size: 11px; border-radius: 8px;">
                                                    @php
                                                        $totalDecimal = $data['daily_total_time'];
                                                        $h = floor($totalDecimal);
                                                        $m = round(($totalDecimal - $h) * 60);
                                                        $timeDisplay = "";
                                                        if ($h > 0)
                                                            $timeDisplay .= $h . "h ";
                                                        if ($m > 0)
                                                            $timeDisplay .= $m . "m";
                                                        if ($h == 0 && $m == 0)
                                                            $timeDisplay = "0m";
                                                    @endphp
                                                    {{ trim($timeDisplay) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    <div class="timeline-activity px-4 py-2">
                                        @foreach($data['events'] as $index => $event)
                                            @if($event->type == 'creation') @continue @endif
                                            <div class="activity-item py-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar-text avatar-xs bg-soft-info text-info rounded-circle"
                                                            style="width: 24px; height: 24px;">
                                                            <i class="feather-edit-2" style="font-size: 10px;"></i>
                                                        </div>
                                                        <span class="fw-bold text-dark small">Work Progress Updated</span>
                                                        @if($event->time_taken)
                                                            <span class="badge bg-soft-primary text-primary ms-2"
                                                                style="font-size: 9px;">{{ $event->time_taken }} HRS</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted fw-bold small">
                                                        <i class="feather-clock me-1" style="font-size: 11px;"></i>
                                                        {{ $event->created_at->format('h:i A') }}
                                                    </div>
                                                </div>

                                                <div class="activity-description text-muted mb-3"
                                                    style="font-size: 13.5px; line-height: 1.6; padding-left: 34px;">
                                                    @php
                                                        $rawDesc = html_entity_decode($event->description);

                                                        // Split by lines and process
                                                        $lines = explode("\n", str_replace(['<p>', '</p>', '<br>', '<div>', '</div>', '<li>', '</li>'], ["\n", "\n", "\n", "\n", "\n", "\n", "\n"], $rawDesc));
                                                        $processedLines = [];
                                                        $isFirstLine = true;

                                                        foreach ($lines as $line) {
                                                            $line = trim(strip_tags(html_entity_decode($line), '<span><b><strong>'));
                                                            if (empty($line))
                                                                continue;

                                                            // Re-apply badge format for time mentions in text
                                                            $line = preg_replace('/—\s*(\d*\.?\d+)\s*(Hours|Hour|hrs|hr)/i', '<span class="badge bg-soft-info text-info ms-2" style="font-size: 10px;">$1 $2</span>', $line);

                                                            // Remove leading bullets/points to re-format consistently
                                                            $cleanLine = preg_replace('/^[•\*\-\·\d\.\s]+/u', '', $line);

                                                            if ($isFirstLine) {
                                                                $processedLines[] = '<div class="main-task-header">• ' . $cleanLine . '</div>';
                                                                $isFirstLine = false;
                                                            } else {
                                                                $processedLines[] = '<div class="sub-task-point"><i class="feather-check text-success mt-1" style="font-size: 11px;"></i><span>' . $cleanLine . '</span></div>';
                                                            }
                                                        }
                                                        $finalDesc = implode('', $processedLines);
                                                    @endphp
                                                    {!! $finalDesc !!}
                                                </div>

                                                @if($event->photo)
                                                    <div class="mt-2" style="padding-left: 34px;">
                                                        <button onclick="viewAttachmentPopup('{{ asset('storage/' . $event->photo) }}')"
                                                            class="btn btn-sm btn-soft-primary fw-bold px-3 py-2"
                                                            style="border-radius: 8px;">
                                                            <i class="feather-paperclip me-1"></i> VIEW ATTACHMENT
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @empty
                        <div class="text-center py-5">
                            <div class="avatar-text avatar-xl bg-soft-secondary text-secondary mx-auto mb-3 rounded-circle">
                                <i class="feather-activity"></i>
                            </div>
                            <h5 class="fw-bold text-dark">No Activity Yet</h5>
                            <p class="text-muted small">There is no activity on this project</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Discussions Tab -->
            <div class="tab-pane fade" id="discussionsTab">
                <div class="card stretch stretch-full border-0 shadow-sm">
                    <div class="card-header border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
                        <h6 class="card-title fw-bold mb-0">Project Discussions & Meetings</h6>
                        <button class="btn btn-primary btn-sm"><i class="feather-plus me-1"></i> New Meeting</button>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center py-5">
                            <div class="avatar-text avatar-xl bg-soft-primary text-primary mx-auto mb-4 rounded-circle">
                                <i class="feather-message-square fs-30"></i>
                            </div>
                            <h5 class="fw-bold text-dark">No Discussions Yet</h5>
                            <p class="text-muted fs-13">Start a new discussion or log a meeting related to this project.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendors/js/apexcharts.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            var options = {
                series: [{{ $progress }}],
                chart: {
                    height: 250,
                    type: 'radialBar',
                },
                plotOptions: {
                    radialBar: {
                        hollow: { size: '70%', },
                        dataLabels: {
                            name: { show: false },
                            value: {
                                offsetY: 10,
                                fontSize: '22px',
                                fontWeight: 'bold',
                                formatter: function (val) { return val + "%"; }
                            }
                        }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'horizontal',
                        shadeIntensity: 0.5,
                        gradientToColors: ['#3454d1'],
                        inverseColors: true,
                        opacityFrom: 1,
                        opacityTo: 1,
                        stops: [0, 100]
                    }
                },
                stroke: { lineCap: 'round' },
                labels: ['Progress'],
            };

            var chart = new ApexCharts(document.querySelector("#project-progress-chart"), options);
            chart.render();

            // Project Timer Logic
            function updateProjectTimers() {
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

                            timer.innerHTML = `<span class="text-primary">${days}d</span> <span class="text-secondary">${hours}h ${mins}m ${secs}s</span> <span class="text-muted small ms-1" style="font-size:9px;">LEFT</span>`;
                        } else {
                            let diff = now - end;
                            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                            diff -= days * (1000 * 60 * 60 * 24);
                            const hours = Math.floor(diff / (1000 * 60 * 60));
                            diff -= hours * (1000 * 60 * 60);
                            const mins = Math.floor(diff / (1000 * 60));
                            diff -= mins * (1000 * 60);
                            const secs = Math.floor(diff / 1000);

                            timer.innerHTML = `<span class="text-danger">${days}d</span> <span class="text-danger small">${hours}h ${mins}m ${secs}s</span> <span class="text-danger fw-bold ms-1" style="font-size:9px;">OVERDUE</span>`;
                        }
                    } else if (dataStart && dataStart !== '') {
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

                        timer.innerHTML = `<span class="text-info">${days}d</span> <span class="text-info small">${hours}h ${mins}m ${secs}s</span> <span class="text-info fw-bold ms-1" style="font-size:9px;">ELAPSED</span>`;
                    }
                });
            }

            setInterval(updateProjectTimers, 1000);
            updateProjectTimers();
        });

        function viewAttachmentPopup(url) {
            const isImage = url.match(/\.(jpeg|jpg|gif|png|webp)$/i) != null;
            let htmlContent = isImage ? `<img src="${url}" style="width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px;">` : `<iframe src="${url}" style="width: 100%; height: 70vh; border: none; border-radius: 8px;"></iframe>`;
            Swal.fire({ title: 'Attachment Preview', html: htmlContent, width: '900px', showCloseButton: true, showConfirmButton: false });
        }
    </script>
@endpush