@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
@endpush

@section('content')
@php
    $isEdit = isset($is_edit);
    $pageTitle = $isEdit ? 'Edit Attendance' : 'Add Attendance';
    $formattedDate = isset($edit_date)
        ? \Carbon\Carbon::parse($edit_date)->format('M d, Y')
        : \Carbon\Carbon::today()->format('M d, Y');
    $employeeTotal = count($employees);
    $headerActions = '
        <a href="' . route('payroll.attendance') . '" class="zoho-btn-outline">
            <i class="feather-arrow-left"></i> Back
        </a>
        <button type="submit" form="attendanceForm" class="zoho-btn-primary" id="attFormSubmitTop">
            <i class="feather-check"></i> ' . ($isEdit ? 'Update' : 'Save') . '
        </button>';
@endphp

<div class="zoho-page-shell attendance-page attendance-form-page">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Attendance Management',
        'viewLabel' => $pageTitle,
        'scopeLinks' => [
            ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Attendance List', 'url' => route('payroll.attendance'), 'active' => false],
            ['label' => $pageTitle, 'url' => 'javascript:void(0)', 'active' => true],
        ],
        'primaryAction' => $headerActions,
    ])

    <div class="main-content zoho-module-content">
        <div class="att-mark-card">
            <form action="{{ $isEdit ? route('payroll.attendance.updateByDate', $edit_date) : route('payroll.attendance.store') }}" method="POST" id="attendanceForm">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                {{-- Context bar: date + live stats --}}
                <div class="att-mark-context">
                    <div class="att-mark-context-left">
                        <div class="att-mark-date-wrap">
                            <label for="attendance_date" class="att-mark-label">
                                <i class="feather-calendar"></i> Attendance date
                            </label>
                            <input type="date"
                                   id="attendance_date"
                                   name="attendance_date"
                                   value="{{ $edit_date ?? date('Y-m-d') }}"
                                   {{ $isEdit ? 'readonly' : 'required' }}
                                   class="att-mark-date-input {{ $isEdit ? 'att-mark-date-input--readonly' : '' }}">
                            @if($isEdit)
                                <span class="att-mark-date-note"><i class="feather-lock"></i> Locked in edit mode</span>
                            @endif
                        </div>
                        @if($employeeTotal > 0)
                            <div class="att-mark-stats">
                                <span class="att-mark-stat"><span class="att-mark-stat-label">Total</span><strong id="statTotal">{{ $employeeTotal }}</strong></span>
                                <span class="att-mark-stat att-mark-stat--done"><span class="att-mark-stat-label">Marked</span><strong id="statMarked">0</strong></span>
                                <span class="att-mark-stat att-mark-stat--pending"><span class="att-mark-stat-label">Pending</span><strong id="statPending">{{ $employeeTotal }}</strong></span>
                            </div>
                        @endif
                    </div>
                    @if($employeeTotal > 0)
                        <div class="att-mark-context-right">
                            <label for="bulk_status" class="att-mark-label">Bulk update</label>
                            <div class="att-mark-bulk-row">
                                <select id="bulk_status" class="att-mark-select" onchange="applyBulkStatus()">
                                    <option value="">Apply status to selected…</option>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="half_day">Half Day</option>
                                    <option value="wfh">WFH</option>
                                    <option value="leave">Leave</option>
                                    <option value="late">Late</option>
                                    <option value="activity">Activity</option>
                                    <option value="early_leave">Early Leave</option>
                                </select>
                                <button type="button" class="att-mark-quick-btn" onclick="markAllPresent()" title="Mark all present">
                                    <i class="feather-check-circle"></i> All Present
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div id="attBulkAlert" class="att-form-inline-alert" role="alert" hidden></div>

                @if($employeeTotal === 0)
                    <div class="att-form-empty">
                        <i class="feather-users"></i>
                        <h3>No employees found</h3>
                        <p>Add employees before marking attendance.</p>
                        <a href="{{ route('employees.create') }}" class="zoho-btn-primary">
                            <i class="feather-plus"></i> Add Employee
                        </a>
                    </div>
                @else
                    {{-- Table toolbar --}}
                    <div class="att-mark-table-toolbar">
                        <div class="zoho-people-table-search att-mark-search">
                            <i class="feather-search"></i>
                            <input type="text" id="employeeSearch" placeholder="Search employee…" oninput="filterEmployees(this.value)" autocomplete="off">
                        </div>
                        <span class="att-mark-selected" id="selectedCount">0 selected</span>
                    </div>

                    <div class="att-mark-table-wrap">
                        <table class="att-mark-table" id="attendanceMarkTable">
                            <thead>
                                <tr>
                                    <th class="att-mark-col-check">
                                        <input type="checkbox" id="select_all" class="att-mark-checkbox" onclick="toggleAllCheckboxes(this)" aria-label="Select all">
                                    </th>
                                    <th class="att-mark-col-emp">Employee</th>
                                    <th class="att-mark-col-time">Check in</th>
                                    <th class="att-mark-col-time">Check out</th>
                                    <th class="att-mark-col-duration">Duration</th>
                                    <th class="att-mark-col-status">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $index => $emp)
                                    @php
                                        $initials = collect(preg_split('/\s+/', trim($emp->name)))
                                            ->filter()
                                            ->map(fn ($w) => strtoupper(substr($w, 0, 1)))
                                            ->take(2)
                                            ->join('');
                                        $statusVal = $emp->old_status ?? '';
                                    @endphp
                                    <tr class="att-mark-row" data-emp-name="{{ strtolower($emp->name) }}" data-index="{{ $index }}">
                                        <td class="att-mark-col-check">
                                            <input type="checkbox" class="att-mark-checkbox row_checkbox" data-index="{{ $index }}" onchange="updateSelectedCount()" aria-label="Select {{ $emp->name }}">
                                        </td>
                                        <td class="att-mark-col-emp">
                                            <div class="att-mark-emp">
                                                <span class="att-mark-avatar">{{ $initials ?: '?' }}</span>
                                                <span class="att-mark-emp-name">{{ $emp->name }}</span>
                                            </div>
                                            <input type="hidden" name="employees[{{ $index }}][employee_id]" value="{{ $emp->id }}">
                                        </td>
                                        <td>
                                            <div class="att-time-field">
                                                <input type="time"
                                                       id="check_in_{{ $index }}"
                                                       name="employees[{{ $index }}][check_in]"
                                                       value="{{ $emp->old_check_in ?? '' }}"
                                                       class="att-time-input"
                                                       onchange="onRowChange({{ $index }})">
                                                <button type="button" class="att-time-now" onclick="setNowTime('check_in_{{ $index }}', {{ $index }})" title="Set current time">
                                                    <i class="feather-clock"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="att-time-field">
                                                <input type="time"
                                                       id="check_out_{{ $index }}"
                                                       name="employees[{{ $index }}][check_out]"
                                                       value="{{ $emp->old_check_out ?? '' }}"
                                                       class="att-time-input"
                                                       onchange="onRowChange({{ $index }})">
                                                <button type="button" class="att-time-now" onclick="setNowTime('check_out_{{ $index }}', {{ $index }})" title="Set current time">
                                                    <i class="feather-clock"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="att-mark-col-duration">
                                            <span id="duration_{{ $index }}" class="att-mark-duration {{ (isset($emp->old_duration) && $emp->old_duration != '--') ? 'att-mark-duration--active' : '' }}">
                                                {{ $emp->old_duration ?? '--' }}
                                            </span>
                                        </td>
                                        <td class="att-mark-col-status">
                                            <select name="employees[{{ $index }}][status]"
                                                    id="status_{{ $index }}"
                                                    class="att-mark-status att-mark-status--{{ $statusVal ?: 'none' }}"
                                                    onchange="onStatusChange({{ $index }})">
                                                <option value="" {{ !$statusVal ? 'selected' : '' }}>— Select —</option>
                                                <option value="present" {{ $statusVal == 'present' ? 'selected' : '' }}>Present</option>
                                                <option value="absent" {{ $statusVal == 'absent' ? 'selected' : '' }}>Absent</option>
                                                <option value="missing_punch" {{ $statusVal == 'missing_punch' ? 'selected' : '' }}>Missing Punch</option>
                                                <option value="half_day" {{ $statusVal == 'half_day' ? 'selected' : '' }}>Half Day</option>
                                                <option value="half_day_leave" {{ $statusVal == 'half_day_leave' ? 'selected' : '' }}>Half Day Leave</option>
                                                <option value="wfh" {{ $statusVal == 'wfh' ? 'selected' : '' }}>WFH</option>
                                                <option value="leave" {{ $statusVal == 'leave' ? 'selected' : '' }}>Leave</option>
                                                <option value="unpaid_leave" {{ $statusVal == 'unpaid_leave' ? 'selected' : '' }}>Unpaid Leave</option>
                                                <option value="unauthorised" {{ $statusVal == 'unauthorised' ? 'selected' : '' }}>Unauthorised</option>
                                                <option value="late" {{ $statusVal == 'late' ? 'selected' : '' }}>Late</option>
                                                <option value="activity" {{ $statusVal == 'activity' ? 'selected' : '' }}>Activity</option>
                                                <option value="early_leave" {{ $statusVal == 'early_leave' ? 'selected' : '' }}>Early Leave</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div id="attMarkNoResults" class="att-mark-no-results" hidden>
                            <i class="feather-search"></i>
                            <p>No employees match your search.</p>
                        </div>
                    </div>

                    <div class="att-mark-footer">
                        <div class="att-mark-footer-meta">
                            @if($isEdit)
                                Editing <strong>{{ $formattedDate }}</strong> · {{ $employeeTotal }} record{{ $employeeTotal !== 1 ? 's' : '' }}
                            @else
                                Marking attendance for <strong>{{ $employeeTotal }}</strong> employee{{ $employeeTotal !== 1 ? 's' : '' }}
                            @endif
                        </div>
                        <div class="att-mark-footer-actions">
                            <a href="{{ route('payroll.attendance') }}" class="zoho-btn-outline">Cancel</a>
                            <button type="submit" class="zoho-btn-primary">
                                <i class="feather-check"></i>
                                {{ $isEdit ? 'Update Attendance' : 'Save Attendance' }}
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[id^="check_in_"]').forEach((el) => {
            calculateDuration(el.id.replace('check_in_', ''));
        });
        updateFormStats();
        updateSelectedCount();
    });

    function onRowChange(index) {
        calculateDuration(index);
        updateFormStats();
        updateRowState(index);
    }

    function onStatusChange(index) {
        const select = document.getElementById('status_' + index);
        select.className = 'att-mark-status att-mark-status--' + (select.value || 'none');
        updateFormStats();
        updateRowState(index);
    }

    function updateRowState(index) {
        const row = document.querySelector(`tr[data-index="${index}"]`);
        if (!row) return;
        const status = document.getElementById('status_' + index)?.value;
        const checkIn = document.getElementById('check_in_' + index)?.value;
        const marked = status || checkIn;
        row.classList.toggle('att-mark-row--filled', !!marked);
    }

    function updateFormStats() {
        const rows = document.querySelectorAll('#attendanceMarkTable tbody tr.att-mark-row:not([hidden])');
        let marked = 0;
        rows.forEach(row => {
            const index = row.dataset.index;
            const status = document.getElementById('status_' + index)?.value;
            const checkIn = document.getElementById('check_in_' + index)?.value;
            if (status || checkIn) marked++;
            updateRowState(index);
        });
        const total = document.querySelectorAll('#attendanceMarkTable tbody tr.att-mark-row').length;
        const markedEl = document.getElementById('statMarked');
        const pendingEl = document.getElementById('statPending');
        if (markedEl) markedEl.textContent = marked;
        if (pendingEl) pendingEl.textContent = Math.max(0, total - marked);
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll('.row_checkbox:checked').length;
        const el = document.getElementById('selectedCount');
        if (el) el.textContent = count + ' selected';
    }

    function filterEmployees(query) {
        const q = query.trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('#attendanceMarkTable tbody tr.att-mark-row').forEach(row => {
            const match = !q || (row.dataset.empName || '').includes(q);
            row.hidden = !match;
            if (match) visible++;
        });
        const noResults = document.getElementById('attMarkNoResults');
        if (noResults) noResults.hidden = visible > 0 || !q;
        updateFormStats();
    }

    function setNowTime(fieldId, index) {
        const field = document.getElementById(fieldId);
        const now = new Date();
        field.value = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

        if (fieldId.includes('check_in')) {
            const checkOutField = document.getElementById('check_out_' + index);
            if (checkOutField) {
                checkOutField.disabled = false;
                checkOutField.classList.remove('att-time-input--disabled');
            }
        }

        onRowChange(index);
    }

    function calculateDuration(index) {
        const checkInField = document.getElementById('check_in_' + index);
        const checkOutField = document.getElementById('check_out_' + index);
        const durationDisplay = document.getElementById('duration_' + index);
        if (!checkInField || !checkOutField || !durationDisplay) return;

        const checkIn = checkInField.value;
        const checkOut = checkOutField.value;

        if (checkIn && checkOut) {
            const [inHours, inMinutes] = checkIn.split(':').map(Number);
            const [outHours, outMinutes] = checkOut.split(':').map(Number);
            let inTotal = inHours * 60 + inMinutes;
            let outTotal = outHours * 60 + outMinutes;
            if (outTotal < inTotal) outTotal += 24 * 60;
            const diffMinutes = outTotal - inTotal;
            durationDisplay.textContent = Math.floor(diffMinutes / 60) + 'h ' + (diffMinutes % 60) + 'm';
            durationDisplay.classList.add('att-mark-duration--active');
        } else {
            durationDisplay.textContent = '--';
            durationDisplay.classList.remove('att-mark-duration--active');
        }
    }

    function toggleAllCheckboxes(source) {
        document.querySelectorAll('.row_checkbox').forEach(cb => {
            if (!cb.closest('tr')?.hidden) cb.checked = source.checked;
        });
        updateSelectedCount();
    }

    function showBulkAlert(message, type) {
        const alertEl = document.getElementById('attBulkAlert');
        if (!alertEl) return;
        alertEl.textContent = message;
        alertEl.className = 'att-form-inline-alert att-form-inline-alert--' + (type || 'warning');
        alertEl.hidden = false;
        clearTimeout(alertEl._hideTimer);
        alertEl._hideTimer = setTimeout(() => { alertEl.hidden = true; }, 4000);
    }

    function applyBulkStatus() {
        const selectedStatus = document.getElementById('bulk_status').value;
        if (!selectedStatus) return;

        const checkboxes = document.querySelectorAll('.row_checkbox:checked');
        if (checkboxes.length === 0) {
            showBulkAlert('Select at least one employee before applying bulk status.', 'warning');
            document.getElementById('bulk_status').value = '';
            return;
        }

        checkboxes.forEach(cb => {
            const index = cb.getAttribute('data-index');
            const statusDropdown = document.getElementById('status_' + index);
            if (statusDropdown) {
                statusDropdown.value = selectedStatus;
                onStatusChange(index);
            }
        });

        showBulkAlert('Status applied to ' + checkboxes.length + ' employee(s).', 'success');
        document.getElementById('bulk_status').value = '';
        updateFormStats();
    }

    function markAllPresent() {
        document.querySelectorAll('#attendanceMarkTable tbody tr.att-mark-row:not([hidden])').forEach(row => {
            const index = row.dataset.index;
            const statusDropdown = document.getElementById('status_' + index);
            if (statusDropdown) {
                statusDropdown.value = 'present';
                onStatusChange(index);
            }
        });
        showBulkAlert('All visible employees marked as Present.', 'success');
    }
</script>
@endpush
