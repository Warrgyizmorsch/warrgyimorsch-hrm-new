@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
@endpush

@section('content')
<div class="zoho-page-shell master-page attendance-page m-2 p-2">
    @php
        $primaryAction = '<button type="button" class="zoho-btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addKpiModal" title="Add KPI"><i class="feather-plus"></i> Add</button>';
    @endphp
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Performance',
        'viewLabel' => 'KPIs',
        'scopeLinks' => [
            ['label' => 'KRA', 'url' => route('kra.index'), 'active' => false],
            ['label' => 'KPIs', 'url' => route('kpis.index'), 'active' => true],
            ['label' => 'KPI Assignments', 'url' => route('kpi-assignments.index'), 'active' => false],
            ['label' => 'SOPs', 'url' => route('sops.index'), 'active' => false],
        ],
        'primaryAction' => $primaryAction,
    ])

    <div class="main-content zoho-module-content">
        <p class="text-muted small mb-3">Department-wise KPI templates. Assign these to employees from <a href="{{ route('kpi-assignments.index') }}">KPI Assignments</a>.</p>

        @if ($message = Session::get('success'))
            <div class="attendance-alert" role="alert">
                <i class="feather-check-circle"></i>
                <span>{{ $message }}</span>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @include('master.partials.filter-panel', [
            'filterRoute' => route('kpis.index'),
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
        ])

        <div class="zoho-people-table-card">
            <div class="zoho-people-table-toolbar">
                <form method="GET" action="{{ route('kpis.index') }}" class="zoho-people-table-search">
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
                                <th>Department</th>
                                <th>Title</th>
                                <th>Target</th>
                                <th>Weightage</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kpis as $kpi)
                                <tr>
                                    <td>{{ $kpi->department->name ?? '—' }}</td>
                                    <td>
                                        <span class="mst-name-cell">{{ $kpi->title }}</span>
                                        @if($kpi->auto_metric)
                                            <span class="mst-meta-badge" title="{{ \App\Models\Kpi::AUTO_METRICS[$kpi->auto_metric] ?? $kpi->auto_metric }}"><i class="feather-zap"></i> Auto</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($kpi->target_value, 2) }} {{ $kpi->unit }}</td>
                                    <td>{{ number_format($kpi->weightage, 2) }}%</td>
                                    <td>
                                        @if($kpi->status)
                                            <span class="mst-status-badge mst-status-badge--active">Active</span>
                                        @else
                                            <span class="mst-status-badge mst-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="mst-row-actions">
                                            <button type="button" class="zoho-icon-btn" onclick='editKpi(@json($kpi))' title="Edit">
                                                <i class="feather-edit-2"></i>
                                            </button>
                                            <form action="{{ route('kpis.destroy', $kpi->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this KPI?');">
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
                                            <i class="feather-bar-chart-2"></i>
                                            <p>{{ request('search') || request('status') ? 'No KPIs match your filters.' : 'No KPIs configured yet.' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($kpis->hasPages())
                    <div class="mst-list-footer d-flex justify-content-center">
                        {{ $kpis->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="addKpiModal">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Add KPI</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close"><i class="feather-x"></i></button>
    </div>
    <form action="{{ route('kpis.store') }}" method="POST" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body flex-grow-1">
            @include('performance.kpi.fields', ['prefix' => 'add', 'departments' => $departments])
        </div>
        <div class="mst-offcanvas-foot">
            <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="zoho-btn-primary flex-fill"><i class="feather-save"></i> Save</button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="editKpiModal">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Edit KPI</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close"><i class="feather-x"></i></button>
    </div>
    <form id="editKpiForm" method="POST" class="d-flex flex-column h-100">
        @csrf @method('PUT')
        <div class="offcanvas-body flex-grow-1">
            @include('performance.kpi.fields', ['prefix' => 'edit', 'departments' => $departments])
            <div class="mst-edit-switch mb-0">
                <div>
                    <div class="mst-edit-switch-label">Active status</div>
                    <p class="mst-edit-switch-desc">Inactive KPIs can't be newly assigned</p>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="status" id="editKpiStatus" value="1">
                </div>
            </div>
        </div>
        <div class="mst-offcanvas-foot">
            <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="zoho-btn-primary flex-fill"><i class="feather-check"></i> Update</button>
        </div>
    </form>
</div>

<script>
    function wghrmFilterItems(input) {
        const filter = input.value.toLowerCase();
        const items = input.closest('.wghrm-custom-dropdown-menu').querySelectorAll('.wghrm-custom-dropdown-item');
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.setProperty('display', text.includes(filter) ? 'block' : 'none', 'important');
        });
    }

    function editKpi(data) {
        document.getElementById('editKpiForm').action = "{{ url('/kpis') }}/" + data.id;
        document.getElementById('editDepartmentId').value = data.department_id || '';
        document.getElementById('editDepartmentBtn').innerText = data.department ? data.department.name : 'Select department';
        document.getElementById('editTitle').value = data.title || '';
        document.getElementById('editUnit').value = data.unit || '';
        document.getElementById('editTargetValue').value = data.target_value || '';
        document.getElementById('editWeightage').value = data.weightage || '';
        document.getElementById('editAutoMetric').value = data.auto_metric || '';
        document.getElementById('editKpiStatus').checked = !!data.status;
        new bootstrap.Offcanvas(document.getElementById('editKpiModal')).show();
    }
</script>
@endsection
