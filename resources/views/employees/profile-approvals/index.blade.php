@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
@endpush

@section('content')
<div class="zoho-page-shell master-page attendance-page m-2 p-2">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Employees',
        'viewLabel' => 'Profile Approvals',
        'scopeLinks' => [
            ['label' => 'All Employees', 'url' => route('employees.index'), 'active' => false],
            ['label' => 'Profile Approvals', 'url' => route('profile-approvals.index'), 'active' => true],
        ],
        'primaryAction' => '<a href="' . route('employees.create') . '" class="zoho-btn-primary"><i class="feather-plus"></i> Add Employee</a>',
    ])

    <div class="main-content zoho-module-content">
        <p class="text-muted small mb-3">Details employees filled in themselves. New accounts stay limited to the profile page until you approve; changes from active employees apply only after approval.</p>

        @if ($message = Session::get('success'))
            <div class="attendance-alert" role="alert">
                <i class="feather-check-circle"></i><span>{{ $message }}</span>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="zoho-people-table-card">
            <div class="card-body p-0 zoho-list-body">
                <div class="table-responsive zoho-table-wrap">
                    <table class="table zoho-data-table mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                                <tr>
                                    <td><span class="mst-name-cell">{{ $req->employee->name }}</span> <span class="text-muted small">({{ $req->employee->employee_code }})</span></td>
                                    <td>{{ $req->employee->departmentRef->name ?? '—' }}</td>
                                    <td>{{ $req->type === 'onboarding' ? 'New joiner profile' : 'Change request' }}</td>
                                    <td>{{ $req->submitted_at?->format('d M Y, h:i A') }}</td>
                                    <td>
                                        @if($req->status === 'pending')
                                            <span class="mst-status-badge" style="background:rgba(245,158,11,.12);color:#a16207;">Waiting for you</span>
                                        @else
                                            <span class="mst-status-badge mst-status-badge--inactive" title="{{ $req->review_note }}">Sent back to employee</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('profile-approvals.show', $req) }}" class="zoho-btn-outline btn-sm">{{ $req->status === 'pending' ? 'Review' : 'View' }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">
                                    <div class="attendance-empty"><i class="feather-check-circle"></i><p>Nothing waiting for approval.</p></div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
