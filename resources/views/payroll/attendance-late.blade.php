@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<style>
    .late-rule-note { font-size: 13px; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; }
    .late-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .late-summary-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
    .late-summary-card .value { font-size: 20px; font-weight: 700; color: #0f172a; }
    .late-summary-card .label { font-size: 12px; color: #64748b; }
    .late-emp-row { cursor: pointer; }
    .late-emp-row:hover { background: #f8fafc; }
    .late-emp-row .feather-chevron-down { transition: transform .2s; }
    .late-emp-row[aria-expanded="true"] .feather-chevron-down { transform: rotate(180deg); }
    .late-days-table { background: #f8fafc; font-size: 13px; }
    .late-days-table th { font-size: 11px; text-transform: uppercase; color: #64748b; }
    .late-days-table tr.not-counted td { color: #94a3b8; }
</style>
@endpush

@section('content')
    @php
        $allowance = \App\Services\AttendanceStatusService::LATE_ARRIVAL_ALLOWANCE_MINUTES;
        $veryLate = \App\Services\AttendanceStatusService::VERY_LATE_ARRIVAL_MINUTES;
        $badgeClass = [
            'late' => 'att-stat-chip--half',
            'very_late' => 'att-stat-chip--absent',
            'allowance' => 'att-stat-chip--present',
            'holiday' => 'att-stat-chip--weekly',
            'sunday' => 'att-stat-chip--weekly',
            'excused' => 'att-stat-chip--leave',
        ];
        $exportQuery = array_filter(['month' => $month, 'employee_id' => $employeeId]);
    @endphp

    <div class="zoho-page-shell attendance-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Attendance Management',
            'viewLabel' => 'Late Arrivals',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'Attendance List', 'url' => route('payroll.attendance'), 'active' => false],
                ['label' => 'Late Arrivals', 'url' => route('payroll.attendance.late'), 'active' => true],
            ],
            'primaryAction' => '
                <a href="' . route('payroll.attendance.late.export', $exportQuery + ['type' => 'summary']) . '" class="zoho-btn-outline"><i class="feather-download"></i> Summary CSV</a>
                <a href="' . route('payroll.attendance.late.export', $exportQuery) . '" class="zoho-btn-outline"><i class="feather-download"></i> Day-wise CSV</a>
                <a href="' . route('payroll.attendance') . '" class="zoho-btn-outline"><i class="feather-arrow-left"></i> Attendance List</a>',
        ])

        <div class="main-content zoho-module-content">
            <form method="GET" action="{{ route('payroll.attendance.late') }}" class="attendance-filter-panel" id="lateFilterForm">
                <div class="attendance-filter-grid">
                    <div class="attendance-filter-field">
                        <label>Month</label>
                        <input type="month" name="month" class="form-control" value="{{ $month }}" max="{{ now()->format('Y-m') }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>Employee</label>
                        @php $selectedEmp = $employees->firstWhere('id', $employeeId); @endphp
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                {{ $selectedEmp ? $selectedEmp->name : 'All Employees' }}
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                <div class="wghrm-custom-search-box">
                                    <input type="text" class="wghrm-custom-search-input" placeholder="Search name or code..."
                                        onkeyup="lateFilterEmployees(this)">
                                </div>
                                <div class="wghrm-items-container">
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ !$employeeId ? 'active' : '' }}"
                                        href="javascript:void(0);" data-employee-id="" onclick="lateSelectEmployee(this)">All Employees</a>
                                    @foreach($employees as $emp)
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ $employeeId == $emp->id ? 'active' : '' }}"
                                            href="javascript:void(0);" data-employee-id="{{ $emp->id }}" onclick="lateSelectEmployee(this)">
                                            {{ $emp->name }}@if($emp->employee_code) <span class="text-muted">({{ $emp->employee_code }})</span>@endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="employee_id" id="lateEmployeeId" value="{{ $employeeId }}">
                    </div>
                    <div class="attendance-filter-actions">
                        <button type="submit" class="zoho-btn-primary"><i class="feather-search"></i> Apply</button>
                        <a href="{{ route('payroll.attendance.late') }}" class="zoho-btn-outline">Reset</a>
                    </div>
                </div>
            </form>

            <div class="late-rule-note">
                <strong>Rule:</strong> check-in up to {{ $allowance }} min after shift start is not late.
                After that, the day counts as late by the full minutes from shift start (9:45 on a 9:30 shift = 15 min).
                More than {{ $veryLate }} min = <strong>very late</strong>. Sundays (without a Sunday shift), holidays, and days with an approved <strong>Gatepass / Early Leave</strong> or first-half leave are not counted.
                <strong>Stayed after shift</strong> = minutes between shift end and check-out; shown only to help you balance late time manually — it does not reduce the counted late minutes.
                Period: <strong>{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</strong>.
            </div>

            <div class="late-summary">
                <div class="late-summary-card"><div class="value">{{ $report->where('total_days', '>', 0)->count() }}</div><div class="label">Employees late</div></div>
                <div class="late-summary-card"><div class="value">{{ $report->sum('late_days') }}</div><div class="label">Late days ({{ $allowance + 1 }}–{{ $veryLate }} min)</div></div>
                <div class="late-summary-card"><div class="value text-danger">{{ $report->sum('very_late_days') }}</div><div class="label">Very late days ({{ $veryLate }}+ min)</div></div>
                <div class="late-summary-card"><div class="value">{{ \App\Services\LateArrivalService::formatMinutes((int) $report->sum('total_minutes')) }}</div><div class="label">Total late time</div></div>
            </div>

            <div class="zoho-people-table-card">
                <div class="card-body p-0 zoho-list-body">
                    <div class="table-responsive zoho-table-wrap">
                        <table class="table zoho-data-table mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th class="text-center">Late days<br><small class="text-muted">{{ $allowance + 1 }}–{{ $veryLate }} min</small></th>
                                    <th class="text-center">Very late days<br><small class="text-muted">{{ $veryLate }}+ min</small></th>
                                    <th class="text-center">Total late days</th>
                                    <th class="text-center">Total late minutes</th>
                                    <th class="text-center">Not counted<br><small class="text-muted">allowance / gatepass</small></th>
                                    <th class="text-center">Stayed after shift<br><small class="text-muted">after check-out time</small></th>
                                    <th class="text-center">Net<br><small class="text-muted">late − stayed</small></th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report as $i => $row)
                                    <tr class="late-emp-row" data-bs-toggle="collapse" data-bs-target="#lateDays{{ $i }}" aria-expanded="{{ $employeeId ? 'true' : 'false' }}">
                                        <td>
                                            <div class="fw-bold">{{ $row['employee']->name }}</div>
                                            <div class="fs-11 text-muted">Code {{ $row['employee']->employee_code ?? '—' }} · Shift {{ substr($row['employee']->time_in ?? '09:30', 0, 5) }}</div>
                                        </td>
                                        <td class="text-center">{{ $row['late_days'] }} <span class="text-muted fs-11">({{ $row['late_minutes'] }} min)</span></td>
                                        <td class="text-center {{ $row['very_late_days'] ? 'text-danger fw-bold' : '' }}">{{ $row['very_late_days'] }} <span class="text-muted fs-11 fw-normal">({{ $row['very_late_minutes'] }} min)</span></td>
                                        <td class="text-center fw-bold">{{ $row['total_days'] }}</td>
                                        <td class="text-center fw-bold">{{ $row['total_minutes'] }} min<div class="fs-11 text-muted fw-normal">{{ $row['total_duration'] }}</div></td>
                                        <td class="text-center text-muted">{{ $row['allowance_days'] }} / {{ $row['excused_days'] }}</td>
                                        <td class="text-center text-success">{{ $row['stay_minutes'] }} min<div class="fs-11 text-muted">{{ $row['stay_days'] }} days</div></td>
                                        <td class="text-center fw-bold {{ $row['net_minutes'] > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $row['net_minutes'] > 0 ? $row['net_minutes'] . ' min short' : abs($row['net_minutes']) . ' min extra' }}
                                        </td>
                                        <td><i class="feather-chevron-down"></i></td>
                                    </tr>
                                    <tr class="collapse {{ $employeeId ? 'show' : '' }}" id="lateDays{{ $i }}">
                                        <td colspan="9" class="p-0">
                                            <table class="table late-days-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="ps-4">Date</th>
                                                        <th>Shift</th>
                                                        <th>Check-in</th>
                                                        <th>Late by</th>
                                                        <th>Counted late</th>
                                                        <th>Result</th>
                                                        <th>Check-out</th>
                                                        <th>Stayed after shift</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($row['days'] as $day)
                                                        <tr class="{{ $day['counted_minutes'] || $day['stay_minutes'] ? '' : 'not-counted' }}">
                                                            <td class="ps-4">{{ $day['date']->format('d M Y (D)') }}</td>
                                                            <td>{{ $day['shift_start'] }}–{{ $day['shift_end'] }}</td>
                                                            <td>{{ $day['check_in'] }}</td>
                                                            <td>{{ $day['minutes'] ? $day['minutes'] . ' min' : '—' }}</td>
                                                            <td class="fw-bold">{{ $day['counted_minutes'] ? $day['counted_minutes'] . ' min' : '—' }}</td>
                                                            <td>
                                                                @if($day['category'])
                                                                    <span class="att-stat-chip {{ $badgeClass[$day['category']] ?? '' }}">{{ $day['label'] }}</span>
                                                                @else
                                                                    <span class="text-muted">On time</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $day['check_out'] ?? '—' }}</td>
                                                            <td class="{{ $day['stay_minutes'] ? 'text-success fw-bold' : '' }}">
                                                                {{ $day['stay_minutes'] ? '+' . $day['stay_minutes'] . ' min' : '—' }}
                                                                @if($day['covered'])
                                                                    <span class="att-stat-chip att-stat-chip--present ms-1" title="Stayed back at least as long as they were late">Covered</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    <tr>
                                                        <td colspan="4" class="ps-4 text-end fw-bold">Totals</td>
                                                        <td class="fw-bold" colspan="2">Late: {{ $row['total_days'] }} days · {{ $row['total_minutes'] }} min</td>
                                                        <td class="fw-bold" colspan="2">
                                                            Stayed: {{ $row['stay_minutes'] }} min ·
                                                            <span class="{{ $row['net_minutes'] > 0 ? 'text-danger' : 'text-success' }}">
                                                                Net {{ $row['net_minutes'] > 0 ? $row['net_minutes'] . ' min short' : abs($row['net_minutes']) . ' min extra' }}
                                                            </span>
                                                            · {{ $row['covered_days'] }} of {{ $row['total_days'] }} late days covered
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">
                                            <div class="attendance-empty">
                                                <i class="feather-check-circle"></i>
                                                <p>No late arrivals in this period.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function lateFilterEmployees(input) {
        const filter = input.value.toLowerCase();
        input.closest('.wghrm-custom-dropdown-menu').querySelectorAll('.wghrm-custom-dropdown-item').forEach(item => {
            item.style.setProperty('display', item.textContent.toLowerCase().includes(filter) ? 'block' : 'none', 'important');
        });
    }

    function lateSelectEmployee(item) {
        document.getElementById('lateEmployeeId').value = item.dataset.employeeId;
        document.getElementById('lateFilterForm').submit();
    }
</script>
@endpush
