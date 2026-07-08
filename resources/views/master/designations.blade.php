@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
@endpush

@section('content')
@php
    $primaryAction = '<button type="button" class="zoho-btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addDesgModal" title="Add designation"><i class="feather-plus"></i> Add</button>';
@endphp

<div class="zoho-page-shell master-page attendance-page">
    @include('master.partials.list-header', [
        'masterTitle' => 'Master Module',
        'masterViewLabel' => 'Designations',
        'masterActive' => 'designations',
        'primaryAction' => $primaryAction,
    ])

    <div class="main-content zoho-module-content">
        @include('master.partials.filter-panel', [
            'filterRoute' => route('master.designations'),
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
                <form method="GET" action="{{ route('master.designations') }}" class="zoho-people-table-search">
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
                                <th>Designation Name</th>
                                <th>Short Name</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($designations as $desg)
                                <tr>
                                    <td class="text-muted fw-semibold">{{ ($designations->currentPage() - 1) * $designations->perPage() + $loop->iteration }}</td>
                                    <td><span class="mst-name-cell">{{ $desg->name }}</span></td>
                                    <td>
                                        @if($desg->short_name)
                                            <span class="mst-meta-badge">{{ strtoupper($desg->short_name) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($desg->is_active)
                                            <span class="mst-status-badge mst-status-badge--active">Active</span>
                                        @else
                                            <span class="mst-status-badge mst-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="mst-row-actions">
                                            <button type="button"
                                                    class="zoho-icon-btn"
                                                    onclick='editDesg(@json($desg))'
                                                    title="Edit designation">
                                                <i class="feather-edit-2"></i>
                                            </button>
                                            <form action="{{ route('master.designation.destroy', $desg->id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return deleteData(event);">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="zoho-icon-btn zoho-icon-btn--danger"
                                                        title="Delete designation">
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
                                            <i class="feather-briefcase"></i>
                                            <p>{{ request('search') || request('status') ? 'No designations match your filters.' : 'No designations recorded yet.' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($designations->hasPages())
                    <div class="mst-list-footer d-flex justify-content-center">
                        {{ $designations->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="addDesgModal">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Add Designation</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <form action="{{ route('master.designation.store') }}" method="POST" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body flex-grow-1">
            <div class="mst-offcanvas-card">
                <div class="mst-offcanvas-card-head">
                    <i class="feather-briefcase"></i>
                    <div>
                        <h3>Designation Details</h3>
                        <p>Enter job title and optional short code</p>
                    </div>
                </div>
                <div class="mst-offcanvas-card-body">
                    <div class="mst-field">
                        <label>Designation Name <span class="req">*</span></label>
                        <div class="mst-input-wrap">
                            <i class="feather-briefcase"></i>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Frontend Developer" required>
                        </div>
                    </div>
                    <div class="mst-field mb-0">
                        <label>Short Name</label>
                        <div class="mst-input-wrap">
                            <i class="feather-hash"></i>
                            <input type="text" name="short_name" class="form-control" placeholder="e.g. FE Dev">
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

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="editDesgModal">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Edit Designation</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <form id="editDesgForm" method="POST" class="d-flex flex-column h-100">
        @csrf
        @method('PUT')
        <div class="offcanvas-body flex-grow-1">
            <div class="mst-offcanvas-card">
                <div class="mst-offcanvas-card-head">
                    <i class="feather-edit-2"></i>
                    <div>
                        <h3>Designation Details</h3>
                        <p>Update designation information</p>
                    </div>
                </div>
                <div class="mst-offcanvas-card-body">
                    <div class="mst-field">
                        <label for="editDesgName">Designation Name <span class="req">*</span></label>
                        <div class="mst-input-wrap">
                            <i class="feather-briefcase"></i>
                            <input type="text" name="name" id="editDesgName" class="form-control" required>
                        </div>
                    </div>
                    <div class="mst-field">
                        <label for="editDesgShortName">Short Name</label>
                        <div class="mst-input-wrap">
                            <i class="feather-hash"></i>
                            <input type="text" name="short_name" id="editDesgShortName" class="form-control">
                        </div>
                    </div>
                    <div class="mst-edit-switch mb-0">
                        <div>
                            <div class="mst-edit-switch-label">Active status</div>
                            <p class="mst-edit-switch-desc">Inactive designations are hidden from new assignments</p>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editDesgActive" value="1">
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
    function editDesg(data) {
        document.getElementById('editDesgForm').action = "{{ url('/master/designation') }}/" + data.id;
        document.getElementById('editDesgName').value = data.name || '';
        document.getElementById('editDesgShortName').value = data.short_name || '';
        document.getElementById('editDesgActive').checked = !!data.is_active;
        new bootstrap.Offcanvas(document.getElementById('editDesgModal')).show();
    }
</script>
@endsection
