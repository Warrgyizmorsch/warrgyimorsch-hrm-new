@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<style>
    .late-rule-note { font-size: 13px; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; }
    .late-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .late-summary-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
    .late-summary-card .value { font-size: 20px; font-weight: 700; color: #0f172a; }
    .late-summary-card .label { font-size: 12px; color: #64748b; }
    .late-summary-card.highlight { border-color: #93c5fd; background: #eff6ff; }
    .sheet-table th, .sheet-table td { white-space: nowrap; text-align: center; vertical-align: middle; }
    .sheet-table th:first-child, .sheet-table td:first-child { text-align: left; position: sticky; left: 0; background: #fff; z-index: 1; }
    .sheet-table th small { display: block; font-weight: 400; text-transform: none; }
    .sheet-row { cursor: pointer; }
    .sheet-row:hover td { background: #f8fafc; }
    .sheet-group { border-left: 2px solid #e2e8f0 !important; }
    .late-days-table { font-size: 13px; }
    .late-days-table th { font-size: 11px; text-transform: uppercase; color: #64748b; white-space: nowrap; }
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
        // Day status labels come from the payroll history (e.g. "Holiday (DIWALI)"), so match by keyword.
        $dayStatusClass = function (?string $status) {
            $s = strtolower((string) $status);

            return match (true) {
                str_contains($s, 'holiday'), str_contains($s, 'sunday') => 'att-stat-chip--weekly',
                str_contains($s, 'wfh') => 'att-stat-chip--wfh',
                str_contains($s, 'unpaid'), str_contains($s, 'unauthor'), str_contains($s, 'absent') => 'att-stat-chip--absent',
                str_contains($s, 'leave') && str_contains($s, 'early') => 'att-stat-chip--early',
                str_contains($s, 'leave') => 'att-stat-chip--leave',
                str_contains($s, 'half') => 'att-stat-chip--half',
                str_contains($s, 'missing') => 'att-stat-chip--missing',
                str_contains($s, 'early') => 'att-stat-chip--early',
                str_contains($s, 'present') => 'att-stat-chip--present',
                default => '',
            };
        };
        $exportQuery = array_filter(['month' => $month, 'employee_id' => $employeeId]);
        $selectedRow = $employeeId ? $sheet->first() : null;
        $editUrl = $employeeId
            ? route('payroll.attendance.employee.editByName', $employeeId) . '?start_date=' . $from->toDateString() . '&end_date=' . $to->toDateString()
            : null;
    @endphp

    <div class="zoho-page-shell attendance-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Attendance Management',
            'viewLabel' => 'Monthly Attendance & Late Arrivals',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'Attendance List', 'url' => route('payroll.attendance'), 'active' => false],
                ['label' => 'Monthly Attendance', 'url' => route('payroll.attendance.late'), 'active' => true],
            ],
            'primaryAction' =>
                ($editUrl ? '<a href="' . $editUrl . '" class="zoho-btn-primary"><i class="feather-edit-2"></i> Edit attendance</a>' : '') . '
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
                Period: <strong>{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</strong>.
                <strong>Payable days</strong> are the same figures payroll uses (attendance payable days + paid leave, capped at the days in the period).
                <strong>Late rule:</strong> check-in up to {{ $allowance }} min after shift start is not late; after that it counts as late by the full minutes from shift start;
                more than {{ $veryLate }} min = very late. Sundays (without a Sunday shift), holidays, and days with an approved Gatepass / Early Leave or first-half leave are not counted as late.
                <strong>Stayed after shift</strong> is shown only to help balance late time manually — it doesn't reduce counted late minutes.
            </div>

            @if($employeeId && $selectedRow)
                {{-- ── Single employee: monthly totals + every day ── --}}
                @php
                    $p = $selectedRow['payroll'];
                    $l = $selectedRow['late'];
                    $emp = $selectedRow['employee'];
                @endphp

                <div class="mb-2">
                    <span class="fw-bold fs-15">{{ $emp->name }}</span>
                    <span class="text-muted fs-12">· Code {{ $emp->employee_code ?? '—' }} · Shift {{ substr($emp->time_in ?? '09:30', 0, 5) }}–{{ substr($emp->time_out ?? '18:00', 0, 5) }}</span>
                </div>

                <div class="late-summary">
                    <div class="late-summary-card highlight"><div class="value text-primary">{{ $p['payable_days'] }}</div><div class="label">Payable days</div></div>
                    <div class="late-summary-card"><div class="value {{ $p['unpaid_days'] > 0 ? 'text-danger' : '' }}">{{ $p['unpaid_days'] }}</div><div class="label">Unpaid days</div></div>
                    <div class="late-summary-card"><div class="value">{{ $p['present_count'] }}</div><div class="label">Present</div></div>
                    <div class="late-summary-card"><div class="value">{{ $p['half_day_count'] }}</div><div class="label">Half day</div></div>
                    <div class="late-summary-card"><div class="value">{{ $p['leave_count'] }}</div><div class="label">Leave (full day)@if($l && $l['half_day_leave_days']) <span class="text-dark">+ {{ $l['half_day_leave_days'] }} half-day leave</span>@endif</div></div>
                    <div class="late-summary-card"><div class="value">{{ $p['paid_leave_days'] }}</div><div class="label">Paid leave credited</div></div>
                    <div class="late-summary-card"><div class="value">{{ $p['wfh_count'] }}</div><div class="label">WFH</div></div>
                    <div class="late-summary-card"><div class="value {{ ($l['early_out_days'] ?? 0) ? 'text-danger' : '' }}">{{ $l['early_out_days'] ?? 0 }} <span class="fs-12 fw-normal">({{ $l['early_out_minutes'] ?? 0 }} min)</span></div><div class="label">Early out (left before shift end)@if($l && $l['gatepass_days']) · <span class="text-dark">{{ $l['gatepass_days'] }} gatepass day(s)</span>@endif</div></div>
                    <div class="late-summary-card"><div class="value">{{ $p['missing_punch_count'] }}</div><div class="label">Missing punch</div></div>
                    <div class="late-summary-card"><div class="value {{ $p['absent_count'] ? 'text-danger' : '' }}">{{ $p['absent_count'] }}</div><div class="label">Absent</div></div>
                    <div class="late-summary-card"><div class="value">{{ $p['weekly_off_count'] }}</div><div class="label">Sundays / holidays</div></div>
                    <div class="late-summary-card"><div class="value">{{ $l['total_days'] ?? 0 }} <span class="fs-12 fw-normal">({{ $l['total_minutes'] ?? 0 }} min)</span></div><div class="label">Late days</div></div>
                    <div class="late-summary-card"><div class="value text-danger">{{ $l['very_late_days'] ?? 0 }}</div><div class="label">Very late ({{ $veryLate }}+ min)</div></div>
                    <div class="late-summary-card"><div class="value text-success">{{ $l['stay_minutes'] ?? 0 }} min</div><div class="label">Stayed after shift</div></div>
                </div>

                <div class="zoho-people-table-card">
                    <div class="card-body p-0 zoho-list-body">
                        <div class="table-responsive zoho-table-wrap">
                            <table class="table late-days-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Date</th>
                                        <th>Day status</th>
                                        <th>Payable</th>
                                        <th>Shift</th>
                                        <th>Check-in</th>
                                        <th>Late by</th>
                                        <th>Counted late</th>
                                        <th>Late result</th>
                                        <th>Check-out</th>
                                        <th>Stayed after shift</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($l['days'] ?? [] as $day)
                                        <tr class="{{ ($day['worked'] ?? true) ? '' : 'not-counted' }}">
                                            <td class="ps-4">{{ $day['date']->format('d M Y (D)') }}</td>
                                            <td>
                                                <span class="att-stat-chip {{ $dayStatusClass($day['day_status'] ?? '') }}">{{ $day['day_status'] ?? '—' }}</span>
                                                {{-- A WFH/leave range spanning a Sunday or holiday doesn't apply to that day. --}}
                                                @unless(preg_match('/sunday|holiday/i', $day['day_status'] ?? ''))
                                                    @foreach($day['leaves'] ?? [] as $leaveText)
                                                        <div class="fs-11 text-muted mt-1">{{ $leaveText }}</div>
                                                    @endforeach
                                                @endunless
                                            </td>
                                            <td class="fw-bold {{ ($day['payable'] ?? 1) == 0 ? 'text-danger' : (($day['payable'] ?? 1) < 1 ? 'text-warning' : '') }}">
                                                {{ isset($day['payable']) ? rtrim(rtrim(number_format($day['payable'], 2), '0'), '.') : '—' }}
                                            </td>
                                            <td>{{ $day['shift_start'] }}–{{ $day['shift_end'] }}</td>
                                            <td>{{ $day['check_in'] ?? '—' }}</td>
                                            <td>{{ $day['minutes'] ? $day['minutes'] . ' min' : '—' }}</td>
                                            <td class="fw-bold">{{ $day['counted_minutes'] ? $day['counted_minutes'] . ' min' : '—' }}</td>
                                            <td>
                                                @if(!($day['worked'] ?? true))
                                                    <span class="text-muted">—</span>
                                                @elseif($day['category'])
                                                    <span class="att-stat-chip {{ $badgeClass[$day['category']] ?? '' }}">{{ $day['label'] }}</span>
                                                @else
                                                    <span class="att-stat-chip att-stat-chip--present">On time</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $day['check_out'] ?? '—' }}
                                                @if(($day['early_minutes'] ?? 0) > 0)
                                                    <div class="fs-11 text-danger">left {{ $day['early_minutes'] }} min early</div>
                                                @endif
                                            </td>
                                            <td class="{{ $day['stay_minutes'] ? 'text-success fw-bold' : '' }}">
                                                {{ $day['stay_minutes'] ? '+' . $day['stay_minutes'] . ' min' : '—' }}
                                                @if($day['covered'])
                                                    <span class="att-stat-chip att-stat-chip--present ms-1" title="Stayed back at least as long as they were late">Covered</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10"><div class="attendance-empty"><p>No attendance in this period.</p></div></td></tr>
                                    @endforelse
                                    @if($l)
                                        <tr>
                                            <td colspan="2" class="ps-4 text-end fw-bold">Totals</td>
                                            <td class="fw-bold text-primary">{{ $p['payable_days'] }}</td>
                                            <td colspan="3"></td>
                                            <td class="fw-bold" colspan="2">Late: {{ $l['total_days'] }} days · {{ $l['total_minutes'] }} min</td>
                                            <td class="fw-bold" colspan="2">
                                                Stayed: {{ $l['stay_minutes'] }} min ·
                                                <span class="{{ $l['net_minutes'] > 0 ? 'text-danger' : 'text-success' }}">
                                                    Net {{ $l['net_minutes'] > 0 ? $l['net_minutes'] . ' min short' : abs($l['net_minutes']) . ' min extra' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                {{-- ── All employees: monthly payroll sheet ── --}}
                <div class="late-summary">
                    <div class="late-summary-card"><div class="value">{{ $sheet->count() }}</div><div class="label">Employees</div></div>
                    <div class="late-summary-card highlight"><div class="value text-primary">{{ $sheet->sum(fn ($r) => $r['payroll']['payable_days']) }}</div><div class="label">Total payable days</div></div>
                    <div class="late-summary-card"><div class="value text-danger">{{ $sheet->sum(fn ($r) => $r['payroll']['unpaid_days']) }}</div><div class="label">Total unpaid days</div></div>
                    <div class="late-summary-card"><div class="value">{{ $sheet->filter(fn ($r) => ($r['late']['total_days'] ?? 0) > 0)->count() }}</div><div class="label">Employees late</div></div>
                    <div class="late-summary-card"><div class="value">{{ $sheet->sum(fn ($r) => $r['late']['total_days'] ?? 0) }}</div><div class="label">Late days</div></div>
                    <div class="late-summary-card"><div class="value text-danger">{{ $sheet->sum(fn ($r) => $r['late']['very_late_days'] ?? 0) }}</div><div class="label">Very late days</div></div>
                </div>

                <div class="zoho-people-table-card">
                    <div class="card-body p-0 zoho-list-body">
                        <div class="table-responsive zoho-table-wrap">
                            <table class="table zoho-data-table sheet-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Present</th>
                                        <th>Half day</th>
                                        <th>Leave</th>
                                        <th>WFH</th>
                                        <th>Early out</th>
                                        <th>Missing<small>punch</small></th>
                                        <th>Absent</th>
                                        <th>Sun / Hol</th>
                                        <th class="sheet-group">Late days<small>{{ $allowance + 1 }}+ min</small></th>
                                        <th>Very late<small>{{ $veryLate }}+ min</small></th>
                                        <th>Late min</th>
                                        <th>Net<small>late − stayed</small></th>
                                        <th class="sheet-group">Paid leave<small>credited</small></th>
                                        <th>Payable<small>days</small></th>
                                        <th>Unpaid<small>days</small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sheet as $row)
                                        @php $p = $row['payroll']; $l = $row['late']; @endphp
                                        <tr class="sheet-row" onclick="window.location='{{ route('payroll.attendance.late', ['month' => $month, 'employee_id' => $row['employee']->id]) }}'" title="Open day-by-day view">
                                            <td>
                                                <div class="fw-bold">{{ $row['employee']->name }}</div>
                                                <div class="fs-11 text-muted">Code {{ $row['employee']->employee_code ?? '—' }} · Shift {{ substr($row['employee']->time_in ?? '09:30', 0, 5) }}</div>
                                            </td>
                                            <td>{{ $p['present_count'] }}</td>
                                            <td>{{ $p['half_day_count'] ?: '—' }}</td>
                                            <td>{{ $p['leave_count'] ?: '—' }}@if($l && $l['half_day_leave_days'])<div class="fs-11 text-muted">+{{ $l['half_day_leave_days'] }} half</div>@endif</td>
                                            <td>{{ $p['wfh_count'] ?: '—' }}</td>
                                            <td class="{{ ($l['early_out_days'] ?? 0) ? 'text-danger' : '' }}">{{ ($l['early_out_days'] ?? 0) ?: '—' }}@if($l && $l['gatepass_days'])<div class="fs-11 text-muted">{{ $l['gatepass_days'] }} gatepass</div>@endif</td>
                                            <td class="{{ $p['missing_punch_count'] ? 'text-warning fw-bold' : '' }}">{{ $p['missing_punch_count'] ?: '—' }}</td>
                                            <td class="{{ $p['absent_count'] ? 'text-danger fw-bold' : '' }}">{{ $p['absent_count'] ?: '—' }}</td>
                                            <td class="text-muted">{{ $p['weekly_off_count'] }}</td>
                                            <td class="sheet-group fw-bold">{{ ($l['total_days'] ?? 0) ?: '—' }}</td>
                                            <td class="{{ ($l['very_late_days'] ?? 0) ? 'text-danger fw-bold' : '' }}">{{ ($l['very_late_days'] ?? 0) ?: '—' }}</td>
                                            <td>{{ ($l['total_minutes'] ?? 0) ?: '—' }}</td>
                                            <td class="{{ ($l['net_minutes'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                                @if($l && $l['total_days'])
                                                    {{ $l['net_minutes'] > 0 ? $l['net_minutes'] . ' short' : abs($l['net_minutes']) . ' extra' }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="sheet-group">{{ $p['paid_leave_days'] ?: '—' }}</td>
                                            <td class="fw-bold text-primary">{{ $p['payable_days'] }}</td>
                                            <td class="{{ $p['unpaid_days'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $p['unpaid_days'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="16"><div class="attendance-empty"><p>No employees found.</p></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
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
