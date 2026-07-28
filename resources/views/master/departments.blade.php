@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
@endpush

@section('content')
@php
    $primaryAction = '<button type="button" class="zoho-btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addDeptModal" title="Add department"><i class="feather-plus"></i> Add</button>';
@endphp

<div class="zoho-page-shell master-page attendance-page">
    @include('master.partials.list-header', [
        'masterTitle' => 'Master Module',
        'masterViewLabel' => 'Departments',
        'masterActive' => 'departments',
        'primaryAction' => $primaryAction,
    ])

    <div class="main-content zoho-module-content">
        @include('master.partials.filter-panel', [
            'filterRoute' => route('master.departments'),
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
                <form method="GET" action="{{ route('master.departments') }}" class="zoho-people-table-search">
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
                                <th>Department Name</th>
                                <th>Short Name</th>
                                <th>Projects</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $dept)
                                <tr>
                                    <td class="text-muted fw-semibold">{{ ($departments->currentPage() - 1) * $departments->perPage() + $loop->iteration }}</td>
                                    <td><span class="mst-name-cell">{{ $dept->name }}</span></td>
                                    <td>
                                        @if($dept->short_name)
                                            <span class="mst-meta-badge">{{ strtoupper($dept->short_name) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $deptProjects = $projectsByDepartment[$dept->id] ?? collect(); @endphp
                                        @if($deptProjects->isEmpty())
                                            <span class="text-muted">—</span>
                                        @else
                                            <div class="dropdown">
                                                <button class="mst-meta-badge dropdown-toggle" type="button" data-bs-toggle="dropdown" style="cursor: pointer; border: none;">
                                                    {{ $deptProjects->count() }} {{ Str::plural('project', $deptProjects->count()) }}
                                                </button>
                                                <ul class="dropdown-menu shadow-lg border-0" style="min-width: 220px; max-height: 260px; overflow-y: auto;">
                                                    @foreach($deptProjects as $proj)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('projects.show', $proj->slug) }}">
                                                                {{ $proj->name }}
                                                                <span class="text-muted small d-block">{{ $proj->status }}</span>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($dept->is_active)
                                            <span class="mst-status-badge mst-status-badge--active">Active</span>
                                        @else
                                            <span class="mst-status-badge mst-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="mst-row-actions">
                                            <button type="button"
                                                    class="zoho-icon-btn"
                                                    onclick='editDept(@json($dept))'
                                                    title="Edit department">
                                                <i class="feather-edit-2"></i>
                                            </button>
                                            <form action="{{ route('master.department.destroy', $dept->id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return deleteData(event);">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="zoho-icon-btn zoho-icon-btn--danger"
                                                        title="Delete department">
                                                    <i class="feather-trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="attendance-empty">
                                            <i class="feather-layers"></i>
                                            <p>{{ request('search') || request('status') ? 'No departments match your filters.' : 'No departments recorded yet.' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($departments->hasPages())
                    <div class="mst-list-footer d-flex justify-content-center">
                        {{ $departments->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="addDeptModal">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Add Department</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <form action="{{ route('master.department.store') }}" method="POST" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body flex-grow-1">
            <div class="mst-offcanvas-card">
                <div class="mst-offcanvas-card-head">
                    <i class="feather-layers"></i>
                    <div>
                        <h3>Department Details</h3>
                        <p>Enter department name and optional short code</p>
                    </div>
                </div>
                <div class="mst-offcanvas-card-body">
                    <div class="mst-field">
                        <label>Department Name <span class="req">*</span></label>
                        <div class="mst-input-wrap">
                            <i class="feather-layers"></i>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Finance" required>
                        </div>
                    </div>
                    <div class="mst-field mb-0">
                        <label>Short Name</label>
                        <div class="mst-input-wrap">
                            <i class="feather-hash"></i>
                            <input type="text" name="short_name" class="form-control" placeholder="e.g. FIN">
                        </div>
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

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="editDeptModal">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Edit Department</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <form id="editDeptForm" method="POST" class="d-flex flex-column h-100">
        @csrf
        @method('PUT')
        <div class="offcanvas-body flex-grow-1">
            <div class="mst-offcanvas-card">
                <div class="mst-offcanvas-card-head">
                    <i class="feather-edit-2"></i>
                    <div>
                        <h3>Department Details</h3>
                        <p>Update department information</p>
                    </div>
                </div>
                <div class="mst-offcanvas-card-body">
                    <div class="mst-field">
                        <label for="editDeptName">Department Name <span class="req">*</span></label>
                        <div class="mst-input-wrap">
                            <i class="feather-layers"></i>
                            <input type="text" name="name" id="editDeptName" class="form-control" required>
                        </div>
                    </div>
                    <div class="mst-field">
                        <label for="editDeptShortName">Short Name</label>
                        <div class="mst-input-wrap">
                            <i class="feather-hash"></i>
                            <input type="text" name="short_name" id="editDeptShortName" class="form-control">
                        </div>
                    </div>
                    <div class="mst-edit-switch mb-0">
                        <div>
                            <div class="mst-edit-switch-label">Active status</div>
                            <p class="mst-edit-switch-desc">Inactive departments are hidden from new assignments</p>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editDeptActive" value="1">
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
    function editDept(data) {
        document.getElementById('editDeptForm').action = "{{ url('/master/department') }}/" + data.id;
        document.getElementById('editDeptName').value = data.name || '';
        document.getElementById('editDeptShortName').value = data.short_name || '';
        document.getElementById('editDeptActive').checked = !!data.is_active;
        new bootstrap.Offcanvas(document.getElementById('editDeptModal')).show();
    }
</script>
@endsection
