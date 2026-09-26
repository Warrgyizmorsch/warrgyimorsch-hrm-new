@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="zoho-page-shell master-page attendance-page m-2 p-2">
    @php
        $primaryAction = '<button type="button" class="zoho-btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addSopModal" title="Add SOP"><i class="feather-plus"></i> Add</button>';
    @endphp
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Performance',
        'viewLabel' => 'SOPs',
        'scopeLinks' => [
            ['label' => 'KRA', 'url' => route('kra.index'), 'active' => false],
            ['label' => 'KPIs', 'url' => route('kpis.index'), 'active' => false],
            ['label' => 'KPI Assignments', 'url' => route('kpi-assignments.index'), 'active' => false],
            ['label' => 'SOPs', 'url' => route('sops.index'), 'active' => true],
        ],
        'primaryAction' => $primaryAction,
    ])

    <div class="main-content zoho-module-content">
        <p class="text-muted small mb-3">Department/role-scoped documents. Editing content bumps the version and requires re-acknowledgement.</p>

        @if ($message = Session::get('success'))
            <div class="attendance-alert" role="alert">
                <i class="feather-check-circle"></i>
                <span>{{ $message }}</span>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @include('master.partials.filter-panel', [
            'filterRoute' => route('sops.index'),
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
        ])

        <div class="zoho-people-table-card">
            <div class="zoho-people-table-toolbar">
                <form method="GET" action="{{ route('sops.index') }}" class="zoho-people-table-search">
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
                                <th>Title</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sops as $sop)
                                <tr>
                                    <td><span class="mst-name-cell">{{ $sop->title }}</span></td>
                                    <td>{{ $sop->department->name ?? 'All Departments' }}</td>
                                    <td>{{ $sop->role ?: 'All Roles' }}</td>
                                    <td><span class="mst-meta-badge">v{{ $sop->version }}</span></td>
                                    <td>
                                        @if($sop->status)
                                            <span class="mst-status-badge mst-status-badge--active">Active</span>
                                        @else
                                            <span class="mst-status-badge mst-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="mst-row-actions">
                                            <button type="button" class="zoho-icon-btn" onclick='showSopHistory({{ $sop->id }})' title="Revision history">
                                                <i class="feather-clock"></i>
                                            </button>
                                            <button type="button" class="zoho-icon-btn" onclick='showSopRecipients({{ $sop->id }})' title="Who acknowledged">
                                                <i class="feather-users"></i>
                                            </button>
                                            <button type="button" class="zoho-icon-btn" onclick='editSop(@json($sop))' title="Edit">
                                                <i class="feather-edit-2"></i>
                                            </button>
                                            <form action="{{ route('sops.destroy', $sop->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this SOP?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="zoho-icon-btn zoho-icon-btn--danger" title="Delete">
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
                                            <i class="feather-file-text"></i>
                                            <p>{{ request('search') || request('status') ? 'No SOPs match your filters.' : 'No SOPs configured yet.' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($sops->hasPages())
                    <div class="mst-list-footer d-flex justify-content-center">
                        {{ $sops->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@php $departmentsList = $departments; @endphp

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="addSopModal" style="width: 640px;">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Add SOP</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close"><i class="feather-x"></i></button>
    </div>
    <form action="{{ route('sops.store') }}" method="POST" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body flex-grow-1">
            @include('performance.sop.fields', ['prefix' => 'add'])
        </div>
        <div class="mst-offcanvas-foot">
            <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="zoho-btn-primary flex-fill"><i class="feather-save"></i> Save</button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="editSopModal" style="width: 640px;">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Edit SOP</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close"><i class="feather-x"></i></button>
    </div>
    <form id="editSopForm" method="POST" class="d-flex flex-column h-100">
        @csrf @method('PUT')
        <div class="offcanvas-body flex-grow-1">
            @include('performance.sop.fields', ['prefix' => 'edit'])
            <p class="text-muted small mt-2">Changing the content bumps the version and clears everyone's acknowledgement for it.</p>
            <div class="mst-edit-switch mb-0">
                <div>
                    <div class="mst-edit-switch-label">Active status</div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="status" id="editSopStatus" value="1">
                </div>
            </div>
        </div>
        <div class="mst-offcanvas-foot">
            <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="zoho-btn-primary flex-fill"><i class="feather-check"></i> Update</button>
        </div>
    </form>
</div>

<div class="modal fade" id="sopRecipientsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sopRecipientsTitle">Acknowledgements</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="sopRecipientsBody">Loading...</div>
        </div>
    </div>
</div>

<div class="modal fade" id="sopHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sopHistoryTitle">Revision History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <label class="form-label small fw-bold text-muted mb-0">Compare version</label>
                    <select id="sopHistoryCompareSelect" class="form-select form-select-sm w-auto" onchange="renderSopHistoryCompare()"></select>
                    <span class="text-muted small">vs Current</span>
                </div>
                <div class="row g-3" id="sopHistoryBody">Loading...</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    const sopEditorToolbar = [
        ['style', ['style']],
        ['font', ['bold', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link']],
        ['view', ['codeview', 'fullscreen']],
    ];

    $(function () {
        $('#addContent').summernote({ height: 220, placeholder: 'Write the procedure...', toolbar: sopEditorToolbar });
        $('#editContent').summernote({ height: 220, placeholder: 'Write the procedure...', toolbar: sopEditorToolbar });

        // Native <form> submit reads the underlying textarea's value, so sync the editor's
        // HTML back into it right before the browser submits.
        $('#addSopModal form').on('submit', function () {
            $('#addContent').val($('#addContent').summernote('code'));
        });
        $('#editSopForm').on('submit', function () {
            $('#editContent').val($('#editContent').summernote('code'));
        });
    });

    function wghrmFilterItems(input) {
        const filter = input.value.toLowerCase();
        const items = input.closest('.wghrm-custom-dropdown-menu').querySelectorAll('.wghrm-custom-dropdown-item');
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.setProperty('display', text.includes(filter) ? 'block' : 'none', 'important');
        });
    }

    function editSop(data) {
        document.getElementById('editSopForm').action = "{{ url('/sops') }}/" + data.id;
        document.getElementById('editDepartmentId').value = data.department_id || '';
        document.getElementById('editDepartmentBtn').innerText = data.department ? data.department.name : 'All Departments';
        document.getElementById('editRole').value = data.role || '';
        document.getElementById('editTitle').value = data.title || '';
        $('#editContent').summernote('code', data.content || '');
        document.getElementById('editSopStatus').checked = !!data.status;
        new bootstrap.Offcanvas(document.getElementById('editSopModal')).show();
    }

    let currentSopRecipientsId = null;

    function showSopRecipients(id) {
        currentSopRecipientsId = id;
        // Reuse the existing modal instance if one is already open (e.g. refreshing after
        // "Mark acknowledged") instead of creating a second one, which stacks a duplicate
        // backdrop that never gets cleaned up when the modal is closed.
        const modalEl = document.getElementById('sopRecipientsModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        document.getElementById('sopRecipientsBody').innerHTML = 'Loading...';
        modal.show();

        loadSopRecipients(id);
    }

    function loadSopRecipients(id) {
        fetch(`{{ url('/sops') }}/${id}/recipients`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('sopRecipientsTitle').innerText = data.sop.title;

                let ackList = data.acknowledged.length
                    ? '<ul class="list-unstyled mb-0">' + data.acknowledged.map(a =>
                        `<li class="mb-2"><strong>${a.name}</strong> — <span class="text-muted small">${a.acknowledged_at}</span></li>`
                      ).join('') + '</ul>'
                    : '<p class="text-muted small mb-0">No one has acknowledged this version yet.</p>';

                let pendingList = data.pending.length
                    ? '<ul class="list-unstyled mb-0">' + data.pending.map(p =>
                        `<li class="mb-2 d-flex justify-content-between align-items-center">
                            <span>${p.name}</span>
                            <button type="button" class="zoho-btn-outline btn-sm py-0 px-2" onclick="markSopAcknowledgedFor(${p.id})">Mark acknowledged</button>
                        </li>`
                      ).join('') + '</ul>'
                    : '<p class="text-muted small mb-0">Everyone applicable has acknowledged.</p>';

                document.getElementById('sopRecipientsBody').innerHTML = `
                    <div class="mst-offcanvas-card mb-3">
                        <div class="mst-offcanvas-card-body">
                            <div class="row g-2 small">
                                <div class="col-6"><strong>Version:</strong> v${data.sop.version}</div>
                                <div class="col-6"><strong>Status:</strong> ${data.sop.status}</div>
                                <div class="col-6"><strong>Department:</strong> ${data.sop.department}</div>
                                <div class="col-6"><strong>Role:</strong> ${data.sop.role}</div>
                                <div class="col-12"><strong>Applicable employees:</strong> ${data.applicable_count}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <h6 class="text-success">Acknowledged (${data.acknowledged.length})</h6>
                            <div style="max-height: 260px; overflow-y: auto;">${ackList}</div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-danger">Pending (${data.pending.length})</h6>
                            <div style="max-height: 260px; overflow-y: auto;">${pendingList}</div>
                        </div>
                    </div>
                `;
            });
    }

    function markSopAcknowledgedFor(employeeId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        fetch(`{{ url('/sops') }}/${currentSopRecipientsId}/acknowledge-for/${employeeId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    loadSopRecipients(currentSopRecipientsId);
                } else {
                    alert(data.message || 'Could not mark as acknowledged.');
                }
            });
    }

    let sopHistoryData = null;

    function showSopHistory(id) {
        const modal = new bootstrap.Modal(document.getElementById('sopHistoryModal'));
        document.getElementById('sopHistoryBody').innerHTML = 'Loading...';
        document.getElementById('sopHistoryCompareSelect').innerHTML = '';
        modal.show();

        fetch(`{{ url('/sops') }}/${id}/history`)
            .then(r => r.json())
            .then(data => {
                sopHistoryData = data;
                document.getElementById('sopHistoryTitle').innerText = data.sop.title + ' — Revision History';

                const select = document.getElementById('sopHistoryCompareSelect');
                const pastVersions = data.versions.filter(v => !v.is_current);
                if (pastVersions.length === 0) {
                    select.innerHTML = '<option value="">No earlier versions</option>';
                    document.getElementById('sopHistoryBody').innerHTML = '<div class="col-12"><p class="text-muted mb-0">This SOP has never been edited — only the current version exists.</p></div>';
                    return;
                }
                select.innerHTML = pastVersions.map(v => `<option value="${v.version}">v${v.version} — ${v.changed_at}</option>`).join('');
                renderSopHistoryCompare();
            });
    }

    function renderSopHistoryCompare() {
        if (!sopHistoryData) return;
        const selectedVersion = parseInt(document.getElementById('sopHistoryCompareSelect').value, 10);
        const oldVersion = sopHistoryData.versions.find(v => v.version === selectedVersion);
        const current = sopHistoryData.versions.find(v => v.is_current);
        if (!oldVersion || !current) return;

        document.getElementById('sopHistoryBody').innerHTML = `
            <div class="col-6">
                <h6 class="text-muted">v${oldVersion.version} <span class="small">(${oldVersion.changed_at} by ${oldVersion.changed_by})</span></h6>
                <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                    <strong>${oldVersion.title}</strong>
                    <div class="mt-2">${oldVersion.content}</div>
                </div>
            </div>
            <div class="col-6">
                <h6 class="text-success">v${current.version} — Current <span class="small text-muted">(${current.changed_at} by ${current.changed_by})</span></h6>
                <div class="border border-success rounded p-3" style="max-height: 400px; overflow-y: auto;">
                    <strong>${current.title}</strong>
                    <div class="mt-2">${current.content}</div>
                </div>
            </div>
        `;
    }
</script>
@endpush
