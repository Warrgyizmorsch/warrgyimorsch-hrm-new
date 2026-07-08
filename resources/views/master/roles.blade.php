@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
@endpush

@section('content')
@php
    $primaryAction = '<button type="button" class="zoho-btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addRoleModal" title="Add role"><i class="feather-plus"></i> Add</button>';
@endphp

<div class="zoho-page-shell master-page attendance-page">
    @include('master.partials.list-header', [
        'masterTitle' => 'Master Module',
        'masterViewLabel' => 'Roles',
        'masterActive' => 'roles',
        'primaryAction' => $primaryAction,
    ])

    <div class="main-content zoho-module-content">
        @include('master.partials.filter-panel', [
            'filterRoute' => route('master.roles'),
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
        ])

        @if ($message = Session::get('success'))
            <div class="attendance-alert" role="alert">
                <i class="feather-check-circle"></i>
                <span>{{ $message }}</span>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="zoho-people-table-card">
            <div class="zoho-people-table-toolbar">
                <form method="GET" action="{{ route('master.roles') }}" class="zoho-people-table-search">
                    <i class="feather-search"></i>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search in list..."
                           onkeydown="if(event.key==='Enter') this.form.submit()">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    @if(request('show'))
                        <input type="hidden" name="show" value="{{ request('show') }}">
                    @endif
                </form>
                <div class="zoho-list-bar mb-0 border-0 bg-transparent p-0">
                    <span class="text-muted small fw-bold text-uppercase">Show</span>
                    <div class="dropdown">
                        <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown"
                                style="width: 80px; height: 38px; padding: 0 12px;">
                            {{ $perPage ?? 20 }}
                        </button>
                        <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 10px;">
                            @foreach([20, 50, 100] as $size)
                                <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 20) == $size ? 'active' : '' }}"
                                   href="{{ request()->fullUrlWithQuery(['show' => $size, 'page' => 1]) }}">{{ $size }}</a>
                            @endforeach
                        </div>
                    </div>
                    <span class="text-muted small fw-bold text-uppercase">entries</span>
                </div>
            </div>

            <div class="card-body p-0 zoho-list-body">
                <div class="table-responsive zoho-table-wrap">
                    <table class="table zoho-data-table mb-0">
                        <thead>
                            <tr>
                                <th class="col-num">Sr. No.</th>
                                <th>Role Name</th>
                                <th>System Slug</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                                <tr>
                                    <td class="text-muted fw-semibold">{{ ($roles->currentPage() - 1) * $roles->perPage() + $loop->iteration }}</td>
                                    <td><span class="mst-name-cell">{{ $role->name }}</span></td>
                                    <td><span class="mst-slug-badge">{{ $role->slug }}</span></td>
                                    <td>
                                        @if($role->is_active)
                                            <span class="mst-status-badge mst-status-badge--active">Active</span>
                                        @else
                                            <span class="mst-status-badge mst-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="mst-row-actions">
                                            <button type="button"
                                                    class="zoho-icon-btn"
                                                    onclick='editRole(@json($role))'
                                                    title="Edit role">
                                                <i class="feather-edit-2"></i>
                                            </button>
                                            <form action="{{ route('master.role.destroy', $role->id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return deleteData(event);">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="zoho-icon-btn zoho-icon-btn--danger"
                                                        title="Delete role">
                                                    <i class="feather-trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="attendance-empty">
                                            <i class="feather-shield"></i>
                                            <p>{{ request('search') || request('status') ? 'No roles match your filters.' : 'No roles recorded yet.' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($roles->hasPages())
                    <div class="mst-list-footer d-flex justify-content-center">
                        {{ $roles->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="addRoleModal">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Add Role</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <form action="{{ route('master.role.store') }}" method="POST" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body flex-grow-1">
            <div class="mst-offcanvas-card">
                <div class="mst-offcanvas-card-head">
                    <i class="feather-shield"></i>
                    <div>
                        <h3>Role Details</h3>
                        <p>Define a system role for access control</p>
                    </div>
                </div>
                <div class="mst-offcanvas-card-body">
                    <div class="mst-field mb-0">
                        <label>Role Name <span class="req">*</span></label>
                        <div class="mst-input-wrap">
                            <i class="feather-shield"></i>
                            <input type="text" name="name" class="form-control" placeholder="e.g. HR Manager" required>
                        </div>
                    </div>
                    <div class="mst-form-hint mt-3 mb-0">
                        <i class="feather-info"></i>
                        <span>System slug is generated automatically from the role name.</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="mst-offcanvas-foot">
            <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="zoho-btn-primary flex-fill">
                <i class="feather-save"></i> Save
            </button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="editRoleModal">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Edit Role</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <form id="editRoleForm" method="POST" class="d-flex flex-column h-100">
        @csrf
        @method('PUT')
        <div class="offcanvas-body flex-grow-1">
            <div class="mst-offcanvas-card">
                <div class="mst-offcanvas-card-head">
                    <i class="feather-edit-2"></i>
                    <div>
                        <h3>Role Details</h3>
                        <p>Update role information</p>
                    </div>
                </div>
                <div class="mst-offcanvas-card-body">
                    <div class="mst-field">
                        <label for="editRoleName">Role Name <span class="req">*</span></label>
                        <div class="mst-input-wrap">
                            <i class="feather-shield"></i>
                            <input type="text" name="name" id="editRoleName" class="form-control" required>
                        </div>
                    </div>
                    <div class="mst-form-hint">
                        <i class="feather-info"></i>
                        <span>Updating the name will regenerate the system slug.</span>
                    </div>
                    <div class="mst-edit-switch mb-0">
                        <div>
                            <div class="mst-edit-switch-label">Active status</div>
                            <p class="mst-edit-switch-desc">Inactive roles cannot be assigned to users</p>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editRoleActive" value="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mst-offcanvas-foot">
            <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="zoho-btn-primary flex-fill">
                <i class="feather-check"></i> Update
            </button>
        </div>
    </form>
</div>

<script>
    function editRole(data) {
        document.getElementById('editRoleForm').action = "{{ url('/master/role') }}/" + data.id;
        document.getElementById('editRoleName').value = data.name || '';
        document.getElementById('editRoleActive').checked = !!data.is_active;
        new bootstrap.Offcanvas(document.getElementById('editRoleModal')).show();
    }
</script>
@endsection
