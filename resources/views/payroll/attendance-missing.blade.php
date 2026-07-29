@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
@endpush

@section('content')
    <div class="zoho-page-shell attendance-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Attendance Management',
            'viewLabel' => 'Missing Punches',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'Attendance List', 'url' => route('payroll.attendance'), 'active' => false],
                ['label' => 'Missing Punches', 'url' => route('payroll.attendance.missing'), 'active' => true],
            ],
            'primaryAction' => '<a href="' . route('payroll.attendance') . '" class="zoho-btn-outline"><i class="feather-arrow-left"></i> Back to Attendance List</a>',
        ])

        <div class="main-content zoho-module-content">
            {{-- Filters --}}
            <div class="attendance-filter-panel" id="filterSection">
                <div class="attendance-filter-grid">
                    <div class="attendance-filter-field">
                        <label>Start Date</label>
                        <input type="date" id="startDate" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>End Date</label>
                        <input type="date" id="endDate" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="attendance-filter-actions">
                        <button type="button" class="zoho-btn-primary" onclick="applyMissingFilters()">
                            <i class="feather-search"></i> Apply
                        </button>
                        <a href="{{ route('payroll.attendance.missing') }}" class="zoho-btn-outline">Reset</a>
                    </div>
                </div>
            </div>

            @if ($message = Session::get('success'))
                <div class="attendance-alert" role="alert">
                    <i class="feather-check-circle"></i>
                    <span>{{ $message }}</span>
                    <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="zoho-people-table-card">
                <div class="d-none d-lg-block">
                    <div class="zoho-people-table-toolbar justify-content-end">
                        <div class="zoho-list-bar mb-0 border-0 bg-transparent p-0">
                            <span class="text-muted small fw-bold text-uppercase">Show</span>
                            <div class="dropdown">
                                <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" id="showEntriesBtn"
                                    style="width: 80px; height: 38px; padding: 0 12px;">
                                    {{ $perPage ?? 20 }}
                                </button>
                                <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 10px;">
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 20) == 20 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}">20</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 50) == 50 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}">50</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 100) == 100 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}">100</a>
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
                                        <th style="width: 160px;">Date</th>
                                        <th>Missing Punches</th>
                                        <th class="text-end" style="width: 180px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($attendance as $att)
                                        @php $date = \Carbon\Carbon::parse($att->attendance_date)->format('Y-m-d'); @endphp
                                        <tr>
                                            <td><span class="attendance-date">{{ \Carbon\Carbon::parse($att->attendance_date)->format('d M Y') }}</span></td>
                                            <td>
                                                <span class="att-stat-chip att-stat-chip--absent">Missing Punch <strong>{{ $att->missing_count }}</strong></span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('payroll.attendance.missing.editByDate', $date) }}" class="zoho-btn-primary btn-sm">
                                                    <i class="feather-edit-2"></i> Fix All
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">
                                                <div class="attendance-empty">
                                                    <i class="feather-check-circle"></i>
                                                    <p>No missing punches found. Everything's in order.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($attendance->hasPages())
                            <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                                {{ $attendance->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function applyMissingFilters() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const params = new URLSearchParams(window.location.search);

        if (start) { params.set('start_date', start); } else { params.delete('start_date'); }
        if (end) { params.set('end_date', end); } else { params.delete('end_date'); }
        params.set('page', 1);

        window.location.search = params.toString();
    }
</script>
@endpush
