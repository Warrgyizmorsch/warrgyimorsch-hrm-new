@extends('layouts.app')

@section('title', 'Projects')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/projects-management.css') }}?v={{ filemtime(public_path('assets/css/projects-management.css')) ?: time() }}">
@endpush

@section('content')
    <div class="zoho-page-shell projects-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Project Management',
            'viewLabel' => ($view ?? 'list') === 'board' ? 'Board View' : 'All Projects',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'Projects', 'url' => route('projects.index'), 'active' => true],
            ],
            'primaryAction' => '<a href="' . route('daily-tasks.index') . '" class="zoho-btn-outline"><i class="feather-check-square"></i> Daily Tasks</a>',
            'extraActions' => '<a href="' . route('projects.create') . '" class="zoho-btn-primary"><i class="feather-plus"></i> New Project</a>',
        ])

        <div class="main-content zoho-module-content">
            @include('projects.partials.index-filters')

            <div class="pm-list-wrap {{ ($view ?? 'list') === 'board' ? 'is-hidden' : '' }}" id="pmListWrap">
                @include('projects.partials.project-table')
            </div>

            @include('projects.partials.kanban-board')
        </div>
    </div>

    @include('projects.partials.projects-modals')
@endsection

@push('scripts')
    @include('projects.partials.projects-scripts')
@endpush
