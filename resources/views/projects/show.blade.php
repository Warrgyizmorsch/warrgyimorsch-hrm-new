@extends('layouts.app')

@section('title', $project->name . ' — Project')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/project-detail.css') }}?v={{ filemtime(public_path('assets/css/project-detail.css')) ?: time() }}">
@endpush

@section('content')
@php
    $progressVal = $projectMetrics['progress'] ?? $project->display_progress;
    $normalizedStatus = $project->normalized_status;
@endphp

<div class="zoho-page-shell project-detail-page">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Project Details',
        'viewLabel' => $project->name,
        'scopeLinks' => [
            ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Projects', 'url' => route('projects.index'), 'active' => false],
            ['label' => $project->name, 'url' => route('projects.show', $project), 'active' => true],
        ],
        'primaryAction' => '<a href="' . route('projects.index') . '" class="zoho-btn-outline"><i class="feather-arrow-left"></i> All Projects</a>',
        'extraActions' => '
            <a href="' . route('daily-tasks.index', ['project_id' => $project->id]) . '" class="zoho-btn-outline"><i class="feather-check-square"></i> Daily Tasks</a>
            <a href="' . route('projects.edit', $project) . '" class="zoho-btn-primary"><i class="feather-edit-3"></i> Edit Project</a>
        ',
    ])

    <div class="main-content zoho-module-content">
        @include('projects.partials.show-hero')

        <div class="pd-tabs-wrap">
            <ul class="nav pd-tabs" id="projectDetailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pdOverview" type="button">Overview</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pdTasks" type="button">
                        Tasks
                        <span class="pd-tab-badge">{{ $taskStats['total'] ?? 0 }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pdActivity" type="button">Activity</button>
                </li>
            </ul>
        </div>

        <div class="tab-content pd-tab-content">
            <div class="tab-pane fade show active" id="pdOverview">
                @include('projects.partials.show-overview')
            </div>
            <div class="tab-pane fade" id="pdTasks">
                @include('projects.partials.show-tasks')
            </div>
            <div class="tab-pane fade" id="pdActivity">
                @include('projects.partials.show-activity')
            </div>
        </div>
    </div>
</div>

@include('projects.partials.projects-modals')
@endsection

@push('scripts')
    @include('projects.partials.show-scripts')
@endpush
