@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<style>
    .status-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 4px;
        text-transform: uppercase;
    }
    .status-badge-success {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #d1fae5;
    }
    .status-badge-info {
        background: #f0f9ff;
        color: #0284c7;
        border: 1px solid #e0f2fe;
    }
</style>
@endpush

@section('content')
    <div class="zoho-page-shell">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Login Activity',
            'viewLabel' => 'Login Activity',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'Login Activity', 'url' => route('login-activity.index'), 'active' => true],
            ],
        ])

        <div class="main-content zoho-module-content">
            <div class="attendance-filter-panel">
                <form method="GET" action="{{ route('login-activity.index') }}" class="attendance-filter-grid">
                    @if ($canFilterByEmployee)
                        <div class="attendance-filter-field">
                            <label>Employee</label>
                            <select name="employee_id" class="form-control">
                                <option value="">All Employees</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="attendance-filter-field">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>Search (IP / City)</label>
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                    <div class="attendance-filter-actions">
                        <button type="submit" class="zoho-btn-primary">
                            <i class="feather-search"></i> Apply
                        </button>
                        <a href="{{ route('login-activity.index') }}" class="zoho-btn-outline">Reset</a>
                    </div>
                </form>
            </div>

            <div class="zoho-people-table-card">
                <div class="table-responsive zoho-table-wrap">
                    <table class="table zoho-data-table mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Login Time</th>
                                <th>Logout Time</th>
                                <th>Duration</th>
                                <th>Location</th>
                                <th>IP Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activities as $activity)
                                <tr>
                                    <td>{{ $activity->employee->name ?? $activity->user->name ?? '--' }}</td>
                                    <td>{{ $activity->login_at?->format('d M Y, h:i A') }}</td>
                                    <td>{{ $activity->logout_at?->format('d M Y, h:i A') ?? '--' }}</td>
                                    <td>{{ $activity->formatted_duration }}</td>
                                    <td>{{ $activity->location_label }}</td>
                                    <td>{{ $activity->ip_address ?? '--' }}</td>
                                    <td>
                                        @if ($activity->is_active)
                                            <span class="badge status-badge status-badge-success">Active</span>
                                        @else
                                            <span class="badge status-badge status-badge-info">Ended</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="attendance-empty">
                                            <i class="feather-clock"></i>
                                            <p>No login activity found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($activities->hasPages())
                    <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                        {{ $activities->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
