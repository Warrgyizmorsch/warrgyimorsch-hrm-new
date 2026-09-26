@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
@endpush

@section('content')
<div class="zoho-page-shell master-page attendance-page m-2 p-2">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Performance',
        'viewLabel' => 'KRA',
        'scopeLinks' => [
            ['label' => 'KRA', 'url' => route('kra.index'), 'active' => true],
            ['label' => 'KPIs', 'url' => route('kpis.index'), 'active' => false],
            ['label' => 'KPI Assignments', 'url' => route('kpi-assignments.index'), 'active' => false],
            ['label' => 'SOPs', 'url' => route('sops.index'), 'active' => false],
        ],
    ])

    <div class="main-content zoho-module-content">
        <p class="text-muted small mb-3">Assign an employee's department KRA criteria for a target month, and track acknowledgement.</p>

        @if ($message = Session::get('success'))
            <div class="attendance-alert" role="alert">
                <i class="feather-check-circle"></i>
                <span>{{ $message }}</span>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div class="zoho-people-table-card mb-3">
            <div class="card-body">
                <h6 class="mb-3">Assign KRA</h6>
                <form method="POST" action="{{ route('kra.assign') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted mb-1">Employee</label>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="kraEmployeeBtn">
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
                                            onclick="document.getElementById('kraEmployeeId').value='{{ $emp->id }}'; document.getElementById('kraEmployeeBtn').innerText='{{ addslashes($emp->name) }}'; bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle')).hide();">
                                            {{ $emp->name }} ({{ $emp->departmentRef->name ?? '—' }})
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="kraEmployeeId" name="employee_id" value="">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Target Month</label>
                        <input type="month" name="month" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="zoho-btn-primary w-100"><i class="feather-plus"></i> Assign</button>
                    </div>
                </form>
                <p class="text-muted small mb-0 mt-2">Pulls that employee's department KRA criteria from Employee Review &gt; Technical Review Criteria at the time of assignment.</p>
            </div>
        </div>

        <div class="zoho-people-table-card">
            <div class="card-body p-0 zoho-list-body">
                <div class="table-responsive zoho-table-wrap">
                    <table class="table zoho-data-table mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Month</th>
                                <th>Criteria</th>
                                <th>Total Points</th>
                                <th>Acknowledged</th>
                                <th class="text-end" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $a)
                                <tr>
                                    <td>{{ $a->employee->name ?? '—' }}</td>
                                    <td>{{ $a->employee->departmentRef->name ?? '—' }}</td>
                                    <td>{{ $a->month }}</td>
                                    <td>{{ $a->items->count() }}</td>
                                    <td>{{ number_format($a->total_max_points, 2) }}</td>
                                    <td>
                                        @if($a->acknowledged_at)
                                            <span class="mst-status-badge mst-status-badge--active">{{ $a->acknowledged_at->format('d M Y') }}</span>
                                        @else
                                            <span class="mst-status-badge mst-status-badge--inactive">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('kra.destroy', $a->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this KRA assignment?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="zoho-icon-btn zoho-icon-btn--danger" title="Remove">
                                                <i class="feather-trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="attendance-empty">
                                            <i class="feather-target"></i>
                                            <p>No KRA assignments yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($assignments->hasPages())
                    <div class="mst-list-footer d-flex justify-content-center">
                        {{ $assignments->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
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
