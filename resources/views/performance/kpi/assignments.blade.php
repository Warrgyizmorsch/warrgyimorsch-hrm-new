@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
@endpush

@section('content')
<div class="zoho-page-shell master-page attendance-page m-2 p-2">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Performance',
        'viewLabel' => 'KPI Assignments',
        'scopeLinks' => [
            ['label' => 'KRA', 'url' => route('kra.index'), 'active' => false],
            ['label' => 'KPIs', 'url' => route('kpis.index'), 'active' => false],
            ['label' => 'KPI Assignments', 'url' => route('kpi-assignments.index'), 'active' => true],
            ['label' => 'SOPs', 'url' => route('sops.index'), 'active' => false],
        ],
    ])

    <div class="main-content zoho-module-content">
        <p class="text-muted small mb-3">Assign an employee's department KPIs for a target month, and record actual achievement. Manage templates under <a href="{{ route('kpis.index') }}">KPIs</a>.</p>

        @if ($message = Session::get('success'))
            <div class="attendance-alert" role="alert">
                <i class="feather-check-circle"></i>
                <span>{{ $message }}</span>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="zoho-people-table-card mb-3">
            <div class="card-body">
                <h6 class="mb-3">Assign KPIs</h6>
                <form method="POST" action="{{ route('kpi-assignments.assign') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted mb-1">Employee</label>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="kpiEmployeeBtn">
                                Select employee
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                <div class="wghrm-custom-search-box">
                                    <input type="text" class="wghrm-custom-search-input w-100" placeholder="Search employee..."
                                        onkeyup="wghrmFilterItems(this)" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                </div>
                                <div style="max-height: 260px; overflow-y: auto;">
                                    @foreach($employees as $emp)
                                        <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);"
                                            onclick="document.getElementById('kpiEmployeeId').value='{{ $emp->id }}'; document.getElementById('kpiEmployeeBtn').innerText='{{ addslashes($emp->name) }}'; bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle')).hide();">
                                            {{ $emp->name }} ({{ $emp->departmentRef->name ?? '—' }})
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="kpiEmployeeId" name="employee_id" value="">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Target Month</label>
                        <input type="month" name="month" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="zoho-btn-primary w-100"><i class="feather-plus"></i> Assign</button>
                    </div>
                </form>
            </div>
        </div>

        @foreach($assignments as $a)
            <div class="zoho-people-table-card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-0">{{ $a->employee->name ?? '—' }} — {{ $a->month }}</h6>
                            <span class="text-muted small">{{ $a->employee->departmentRef->name ?? '—' }}</span>
                            @if($a->acknowledged_at)
                                <span class="mst-status-badge mst-status-badge--active ms-2">Acknowledged {{ $a->acknowledged_at->format('d M Y') }}</span>
                            @else
                                <span class="mst-status-badge mst-status-badge--inactive ms-2">Not acknowledged</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if($a->overall_score !== null)
                                <span class="mst-meta-badge">Overall: {{ $a->overall_score }}%</span>
                            @endif
                            @if($a->items->contains(fn($i) => $i->auto_metric))
                                <form action="{{ route('kpi-assignments.recompute', $a->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="zoho-btn-outline btn-sm" title="Re-run auto-calculated KPIs">
                                        <i class="feather-refresh-cw"></i> Recompute
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('kpi-assignments.destroy', $a->id) }}" method="POST" onsubmit="return confirm('Remove this KPI assignment?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="zoho-icon-btn zoho-icon-btn--danger" title="Remove"><i class="feather-trash-2"></i></button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('kpi-assignments.actuals', $a->id) }}">
                        @csrf
                        <div class="table-responsive">
                            <table class="table zoho-data-table mb-2">
                                <thead>
                                    <tr>
                                        <th>KPI</th>
                                        <th>Target</th>
                                        <th>Weightage</th>
                                        <th style="width: 160px;">Actual</th>
                                        <th>Achieved %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($a->items as $item)
                                        <tr>
                                            <td>
                                                {{ $item->title }}
                                                @if($item->auto_metric)
                                                    <span class="mst-meta-badge" title="Auto-calculated"><i class="feather-zap"></i> Auto</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($item->target_value, 2) }} {{ $item->unit }}</td>
                                            <td>{{ number_format($item->weightage, 2) }}%</td>
                                            <td>
                                                @if($item->auto_metric)
                                                    <input type="number" step="0.01" min="0" name="actual_value[{{ $item->id }}]"
                                                        class="form-control form-control-sm" value="{{ $item->actual_value }}"
                                                        title="Auto-calculated — edit if you need to override it manually.">
                                                @else
                                                    <input type="number" step="0.01" min="0" name="actual_value[{{ $item->id }}]"
                                                        class="form-control form-control-sm" value="{{ $item->actual_value }}">
                                                @endif
                                            </td>
                                            <td>{{ $item->percentage !== null ? $item->percentage . '%' : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="zoho-btn-primary btn-sm"><i class="feather-save"></i> Save Actuals</button>
                    </form>
                </div>
            </div>
        @endforeach

        @if($assignments->isEmpty())
            <div class="attendance-empty">
                <i class="feather-bar-chart-2"></i>
                <p>No KPI assignments yet.</p>
            </div>
        @endif

        @if($assignments->hasPages())
            <div class="mst-list-footer d-flex justify-content-center">
                {{ $assignments->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function wghrmFilterItems(input) {
        const filter = input.value.toLowerCase();
        const items = input.closest('.wghrm-custom-dropdown-menu').querySelectorAll('.wghrm-custom-dropdown-item');
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.setProperty('display', text.includes(filter) ? 'block' : 'none', 'important');
        });
    }
</script>
@endpush
@endsection
