@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
@endpush

@section('content')
    @php
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive']);
        $canSyncBiometric = in_array($role, ['super_admin', 'manager']);
        $query = request()->query();

        $importDropdown = $isAdmin ? '
            <div class="dropdown">
                <button type="button" class="zoho-icon-btn dropdown-toggle" data-bs-toggle="dropdown" title="Import">
                    <i class="feather-upload"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end attendance-import-menu">
                    <form action="' . route('payroll.attendance.import') . '" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <label class="form-label fw-bold small text-dark mb-2">Import Excel/CSV</label>
                        <select class="form-select mb-2" name="machine">
                            <option value="codes">Employee codes (manual sheet)</option>
                            ' . collect(\App\Models\BiometricEnrollment::machines())->map(fn ($label, $key) => '<option value="' . e($key) . '">' . e($label) . ' — raw device export</option>')->implode('') . '
                        </select>
                        <input type="file" class="form-control mb-3" name="import_file" accept=".xlsx,.xls,.csv" required>
                        <button type="submit" class="zoho-btn-primary w-100">Upload & Calculate</button>
                    </form>
                </div>
            </div>' : '';
        $exportDropdown = $isAdmin ? '
            <div class="dropdown">
                <button type="button" class="zoho-btn-outline dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="feather-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="' . e(route('payroll.attendance.logExport', $query)) . '">This list (Excel)</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="exportMonthlySheet()">Date-by-employee sheet (Excel)</a></li>
                    <li><a class="dropdown-item" href="' . route('payroll.attendance.late') . '">Monthly attendance report</a></li>
                </ul>
            </div>' : '';
        $syncBiometricButton = $canSyncBiometric ? '
            <button type="button" class="zoho-btn-outline" id="syncBiometricBtn" onclick="syncBiometricAttendance()" title="Pull latest punches from biometric device">
                <i class="feather-refresh-cw"></i> Sync Punches
            </button>
            <button type="button" class="zoho-btn-outline" id="rebuildBiometricBtn" onclick="rebuildBiometricAttendance()" title="Recalculate attendance from stored biometric logs">
                <i class="feather-database"></i> Rebuild from Logs
            </button>' : '';
        $addButton = $isAdmin ? '<a href="' . route('payroll.attendance.add') . '" class="zoho-btn-primary" title="Add attendance"><i class="feather-plus"></i> Add</a>' : '';

        $statusGroups = \App\Services\AttendanceLogService::STATUS_GROUPS;
        $currentStatus = request('status');
        $selectedEmployee = $employees->firstWhere('id', (int) request('employee_id'));
        $isSingleDay = $startDate->equalTo($endDate);

        $filters = [
            ['name' => 'range', 'label' => 'Quick Range', 'current' => request('range', 'month'), 'options' => [
                'today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'This Week', 'month' => 'This Month',
                'lastMonth' => 'Last Month', '3months' => 'Last 3 Months', '6months' => 'Last 6 Months',
                '1year' => 'Last 1 Year', 'all' => 'All Time', 'custom' => 'Custom Date',
            ]],
            ['name' => 'employee_id', 'label' => 'Employee', 'current' => request('employee_id', ''), 'options' => ['' => 'All Employees'] + $employees->pluck('name', 'id')->all()],
        ];

        if ($departments->isNotEmpty()) {
            $filters[] = ['name' => 'department_id', 'label' => 'Department', 'current' => request('department_id', ''), 'options' => ['' => 'All Departments'] + $departments->pluck('name', 'id')->all()];
        }

        $filters[] = ['name' => 'status', 'label' => 'Status', 'current' => $currentStatus ?? '', 'options' => ['' => 'All Statuses'] + collect($statusGroups)->map(fn ($g) => $g['label'])->all() + ['overtime' => 'Overtime']];

        $chipClasses = [
            'present' => 'present', 'half_day' => 'half', 'leave' => 'leave', 'absent' => 'absent',
            'wfh' => 'wfh', 'early_out' => 'early', 'missing_punch' => 'absent', 'off' => 'overtime',
        ];
        $badgeClasses = ['success' => 'present', 'danger' => 'absent', 'warning' => 'half'];
        $formatDays = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.') ?: '0';
    @endphp

    <div class="zoho-page-shell attendance-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'New Attendance History',
            'viewLabel' => $startDate->format('d M Y') . ($isSingleDay ? '' : ' – ' . $endDate->format('d M Y')),
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'New Attendance History', 'url' => route('attendance.log'), 'active' => true],
            ],
            'primaryAction' => $syncBiometricButton . $importDropdown . $exportDropdown . $addButton,
        ])

        <div class="main-content zoho-module-content">
            <form method="GET" action="{{ route('attendance.log') }}" class="attendance-filter-panel" id="attFilterForm">
                <div class="att-log-filters">
                    @foreach ($filters as $filter)
                        <div class="attendance-filter-field">
                            <label>{{ $filter['label'] }}</label>
                            <div class="dropdown">
                                <button class="wghrm-custom-select-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                    {{ $filter['options'][$filter['current']] ?? reset($filter['options']) }}
                                </button>
                                <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                    @if (count($filter['options']) > 8)
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                    @endif
                                    <div class="wghrm-items-container">
                                        @foreach ($filter['options'] as $value => $label)
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ (string) $filter['current'] === (string) $value ? 'active' : '' }}"
                                                href="javascript:void(0);" data-filter="{{ $filter['name'] }}" data-value="{{ $value }}">{{ $label }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="{{ $filter['name'] }}" id="filter_{{ $filter['name'] }}" value="{{ $filter['current'] }}">
                        </div>
                    @endforeach
                    <div class="attendance-filter-field">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" name="start_date" class="form-control" value="{{ $startDate->toDateString() }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate" name="end_date" class="form-control" value="{{ $endDate->toDateString() }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label for="attSearch">Search</label>
                        <input type="text" id="attSearch" name="search" class="form-control" placeholder="Name, code, date..." value="{{ request('search') }}">
                    </div>
                    <div class="attendance-filter-actions">
                        <button type="submit" class="zoho-btn-primary"><i class="feather-search"></i> Apply</button>
                        <a href="{{ route('attendance.log') }}" class="zoho-btn-outline">Reset</a>
                    </div>
                </div>
            </form>

            @if ($message = Session::get('success'))
                <div class="attendance-alert" role="alert">
                    <i class="feather-check-circle"></i>
                    <span>{{ $message }}</span>
                    <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="zoho-people-table-card">
                <div class="att-log-summary">
                    <div class="att-stat-chips">
                        @foreach ($statusGroups as $key => $group)
                            <a href="{{ request()->fullUrlWithQuery(['status' => $currentStatus === $key ? null : $key, 'page' => null]) }}"
                                class="att-stat-chip att-stat-chip--{{ $chipClasses[$key] }} {{ $currentStatus === $key ? 'att-log-chip-active' : '' }}">{{ $group['label'] }} <strong>{{ $counts[$key] }}</strong></a>
                        @endforeach
                        <a href="{{ request()->fullUrlWithQuery(['status' => $currentStatus === 'overtime' ? null : 'overtime', 'page' => null]) }}"
                            class="att-stat-chip att-stat-chip--overtime {{ $currentStatus === 'overtime' ? 'att-log-chip-active' : '' }}">Overtime <strong>{{ $overtimeCount }}</strong></a>
                    </div>
                    @if ($selectedEmployee)
                        <span class="att-log-payable" title="Paid leave from the leave balance is added on top of this in payroll">Attendance payable <strong>{{ $formatDays($payableDays) }}</strong> days</span>
                    @endif
                </div>

                <div class="zoho-people-table-toolbar">
                    <div class="att-log-bulk">
                        @if ($isAdmin && $isSingleDay)
                            <a href="{{ route('payroll.attendance.editByDate', $startDate->toDateString()) }}?from=log" class="zoho-btn-outline"><i class="feather-calendar"></i> Edit everyone on {{ $startDate->format('d M') }}</a>
                        @endif
                        @if ($isAdmin && $selectedEmployee)
                            <a href="{{ route('payroll.attendance.employee.editByName', $selectedEmployee->id) }}?start_date={{ $startDate->toDateString() }}&end_date={{ $endDate->toDateString() }}&from=log" class="zoho-btn-outline"><i class="feather-user"></i> Edit all days of {{ $selectedEmployee->name }}</a>
                        @endif
                        <span class="text-muted small">{{ number_format($attendance->total()) }} records</span>
                    </div>
                    <div class="zoho-list-bar mb-0 border-0 bg-transparent p-0">
                        <span class="text-muted small fw-bold text-uppercase">Show</span>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" style="width: 80px; height: 38px; padding: 0 12px;">{{ $perPage }}</button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 10px;">
                                @foreach ([20, 50, 100] as $size)
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $perPage === $size ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => null]) }}">{{ $size }}</a>
                                @endforeach
                            </div>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase">entries</span>
                    </div>
                </div>

                <div class="table-responsive zoho-table-wrap">
                    <table class="table zoho-data-table mb-0 att-log-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Worked</th>
                                <th>Status</th>
                                <th class="text-center">Payable</th>
                                @if ($isAdmin)
                                    <th class="text-end">Edit</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attendance as $row)
                                <tr>
                                    <td class="text-nowrap">
                                        <span class="attendance-date">{{ $row['date_label'] }}</span>
                                        <span class="att-log-sub">{{ $row['day_label'] }}</span>
                                    </td>
                                    <td>
                                        <a class="att-log-name" href="{{ request()->fullUrlWithQuery(['employee_id' => $row['employee_id'], 'page' => null]) }}" title="Show only this employee">{{ $row['employee_name'] }}</a>
                                        <span class="att-log-sub">{{ $row['department'] ?: '—' }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ $row['punch_in'] }}</td>
                                    <td class="text-nowrap">{{ $row['punch_out'] }}</td>
                                    <td class="text-nowrap">
                                        <span class="att-detail-hours">{{ $row['total_hours'] }}</span>
                                        @if ($row['activity_credit'])
                                            <span class="att-log-sub" title="Saturday activity hour counted toward the day">+1h activity</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="att-detail-status att-detail-status--{{ $badgeClasses[$row['statusClass']] ?? 'info' }}">{{ $row['status'] }}</span>
                                        @if ($row['is_manual'])
                                            <span class="att-log-sub" title="Corrected by hand; device syncs won't overwrite it">Edited</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $formatDays($row['payable']) }}</td>
                                    @if ($isAdmin)
                                        <td class="text-end text-nowrap">
                                            <div class="attendance-row-actions justify-content-end">
                                                <button type="button" class="zoho-icon-btn" title="Edit this day"
                                                    data-employee="{{ $row['employee_id'] }}" data-name="{{ $row['employee_name'] }}"
                                                    data-date="{{ $row['date_key'] }}" data-date-label="{{ $row['date_label'] }}"
                                                    data-in="{{ substr((string) $row['raw_check_in'], 0, 5) }}" data-out="{{ substr((string) $row['raw_check_out'], 0, 5) }}"
                                                    data-status="{{ $row['status_key'] }}" data-stored-status="{{ $row['stored_status'] ?? '' }}"
                                                    onclick="openDayEditor(this)"><i class="feather-edit"></i></button>
                                                <a class="zoho-icon-btn" href="{{ route('payroll.attendance.editByDate', $row['date_key']) }}?from=log" title="Edit everyone on this date"><i class="feather-calendar"></i></a>
                                                <a class="zoho-icon-btn" href="{{ route('payroll.attendance.employee.editByName', $row['employee_id']) }}?start_date={{ $startDate->toDateString() }}&end_date={{ $endDate->toDateString() }}&from=log" title="Edit all days of this employee"><i class="feather-user"></i></a>
                                                @if ($row['attendance_id'])
                                                    <button type="button" class="zoho-icon-btn" title="Delete record" onclick="deleteAttendanceRow({{ $row['attendance_id'] }})"><i class="feather-trash-2"></i></button>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdmin ? 8 : 7 }}">
                                        <div class="attendance-empty">
                                            <i class="feather-calendar"></i>
                                            <p>No attendance records match these filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($attendance->hasPages())
                    <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                        {{ $attendance->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('modals')
    @if ($isAdmin)
        <div class="modal fade" id="dayEditModal" tabindex="-1" aria-labelledby="dayEditTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" id="dayEditForm">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="dayEditTitle">Edit attendance</h5>
                            <small class="text-muted" id="dayEditDate"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="employee_id" id="dayEditEmployee">
                        <input type="hidden" name="attendance_date" id="dayEditDay">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="dayEditIn">Check In</label>
                                <input type="time" class="form-control" name="check_in" id="dayEditIn">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="dayEditOut">Check Out</label>
                                <input type="time" class="form-control" name="check_out" id="dayEditOut">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="dayEditStatus">Status</label>
                                <select class="form-select" name="status" id="dayEditStatus" required>
                                    @foreach (['present', 'absent', 'missing_punch', 'half_day', 'half_day_leave', 'wfh', 'leave', 'unpaid_leave', 'unauthorised', 'late', 'activity', 'early_leave'] as $option)
                                        <option value="{{ $option }}">{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">A day with {{ rtrim(rtrim(number_format(\App\Services\AttendanceStatusService::FULL_DAY_HOURS, 1), '0'), '.') }}+ hours always shows as Present, and {{ rtrim(rtrim(number_format(\App\Services\AttendanceStatusService::HALF_DAY_MIN_HOURS, 1), '0'), '.') }}+ hours is never Absent. On Saturdays the activity hour counts too.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="zoho-btn-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="zoho-btn-primary" id="dayEditSave">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endpush

@push('scripts')
    <script>
        function wghrmFilterItems(input) {
            const filter = input.value.toLowerCase();
            input.closest('.wghrm-custom-dropdown-menu').querySelectorAll('.wghrm-custom-dropdown-item').forEach(item => {
                item.style.setProperty('display', item.textContent.toLowerCase().includes(filter) ? 'block' : 'none', 'important');
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('attFilterForm');
            const startInput = document.getElementById('startDate');
            const endInput = document.getElementById('endDate');
            const rangeInput = document.getElementById('filter_range');

            form.querySelectorAll('[data-filter]').forEach(item => {
                item.addEventListener('click', function () {
                    const field = this.closest('.attendance-filter-field');
                    field.querySelector('input[type="hidden"]').value = this.dataset.value;
                    const button = field.querySelector('.dropdown-toggle');
                    button.textContent = this.textContent;
                    bootstrap.Dropdown.getOrCreateInstance(button).hide();
                    field.querySelectorAll('[data-filter]').forEach(other => other.classList.toggle('active', other === this));

                    // A named range replaces the dates; the server works them out.
                    if (this.dataset.filter === 'range' && this.dataset.value !== 'custom') {
                        startInput.value = '';
                        endInput.value = '';
                    }
                });
            });

            [startInput, endInput].forEach(input => input.addEventListener('change', () => {
                rangeInput.value = 'custom';
                rangeInput.closest('.attendance-filter-field').querySelector('.dropdown-toggle').textContent = 'Custom Date';
            }));

            // Keep the URL short: drop empty filters.
            form.addEventListener('submit', () => {
                form.querySelectorAll('input[name]').forEach(input => {
                    if (!input.value) input.disabled = true;
                });
            });

            document.getElementById('dayEditForm')?.addEventListener('submit', saveDayEdit);
        });

        function openDayEditor(button) {
            const data = button.dataset;
            document.getElementById('dayEditTitle').textContent = data.name;
            document.getElementById('dayEditDate').textContent = data.dateLabel;
            document.getElementById('dayEditEmployee').value = data.employee;
            document.getElementById('dayEditDay').value = data.date;
            document.getElementById('dayEditIn').value = data.in;
            document.getElementById('dayEditOut').value = data.out;
            // Open on the status the list shows; display-only ones (Early Out, Sunday...) aren't options.
            const statusSelect = document.getElementById('dayEditStatus');
            statusSelect.value = data.status;
            if (!statusSelect.value) statusSelect.value = data.storedStatus || 'absent';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('dayEditModal')).show();
        }

        function saveDayEdit(event) {
            event.preventDefault();
            const save = document.getElementById('dayEditSave');
            save.disabled = true;

            fetch('{{ route('payroll.attendance.saveDay') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: new FormData(event.target),
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Could not save attendance');
                    }
                    window.location.reload();
                })
                .catch(error => {
                    save.disabled = false;
                    Swal.fire({ icon: 'error', title: 'Not saved', text: error.message });
                });
        }

        function deleteAttendanceRow(id) {
            Swal.fire({
                title: 'Delete this record?',
                text: 'The day will show as Absent until it is marked again.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: { confirmButton: 'btn btn-primary px-4', cancelButton: 'btn btn-light-brand px-4 me-3' },
                buttonsStyling: false,
            }).then(result => {
                if (!result.isConfirmed) return;

                fetch(`{{ url('/payroll/attendance') }}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                }).then(res => res.json()).then(data => {
                    if (data.success) window.location.reload();
                });
            });
        }

        function exportMonthlySheet() {
            fetch('{{ route('payroll.attendance.export') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    start_date: document.getElementById('startDate').value,
                    end_date: document.getElementById('endDate').value,
                    employee_id: document.getElementById('filter_employee_id').value,
                    range: document.getElementById('filter_range').value,
                }),
            })
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'attendance.xlsx';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    window.URL.revokeObjectURL(url);
                });
        }
    </script>

    @include('payroll.partials.attendance-sync-script')
    @include('payroll.partials.attendance-filter-styles')

    <style>
        .att-log-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .att-log-summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px 0;
        }

        .att-log-summary .att-stat-chip { text-decoration: none; }

        .att-log-chip-active { outline: 2px solid currentColor; outline-offset: 1px; }

        .att-log-payable { font-size: 13px; color: #475569; }

        .att-log-bulk {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .att-log-table td { vertical-align: middle; }

        .att-log-name { font-weight: 600; color: #0f172a; text-decoration: none; }

        .att-log-name:hover { color: #3858f9; }

        .att-log-sub {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .att-log-table .attendance-row-actions { display: flex; gap: 4px; }
    </style>
@endpush
