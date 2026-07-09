@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
@endpush

@section('content')
    @php
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $canSyncBiometric = in_array($role, ['super_admin', 'manager']);
        $importDropdown = $isAdmin ? '
            <div class="dropdown">
                <button type="button" class="zoho-icon-btn dropdown-toggle" data-bs-toggle="dropdown" title="Import">
                    <i class="feather-upload"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end attendance-import-menu">
                    <form action="' . route('payroll.attendance.import') . '" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <label class="form-label fw-bold small text-dark mb-2">Import Excel/CSV</label>
                        <input type="file" class="form-control mb-3" name="import_file" accept=".xlsx,.xls,.csv" required>
                        <button type="submit" class="zoho-btn-primary w-100">Upload & Calculate</button>
                    </form>
                </div>
            </div>' : '';
        $addButton = $isAdmin ? '<a href="' . route('payroll.attendance.add') . '" class="zoho-btn-primary" title="Add attendance"><i class="feather-plus"></i> Add</a>' : '';
        $syncBiometricButton = $canSyncBiometric ? '
            <button type="button" class="zoho-btn-outline" id="syncBiometricBtn" onclick="syncBiometricAttendance()" title="Pull latest punches from biometric device">
                <i class="feather-refresh-cw"></i> Sync Punches
            </button>
            <button type="button" class="zoho-btn-outline" id="rebuildBiometricBtn" onclick="rebuildBiometricAttendance()" title="Recalculate attendance from stored biometric logs">
                <i class="feather-database"></i> Rebuild from Logs
            </button>' : '';
        $attendanceHeaderActions = '
            <a href="' . route('payroll.attendace.employee') . '" class="zoho-btn-outline">
                <i class="feather-users"></i> Employee Wise
            </a>
            ' . $syncBiometricButton . '
            ' . $importDropdown . '
            <button type="button" class="zoho-icon-btn" onclick="exportAttendance()" title="Export">
                <i class="feather-download"></i>
            </button>
            ' . $addButton;
    @endphp

    <div class="zoho-page-shell attendance-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Attendance Management',
            'viewLabel' => 'Attendance List',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'Attendance List', 'url' => route('payroll.attendance'), 'active' => true],
            ],
            'primaryAction' => $attendanceHeaderActions,
        ])

        <div class="main-content zoho-module-content">
            {{-- Filters --}}
            <div class="attendance-filter-panel" id="filterSection">
                <div class="attendance-filter-grid">
                    <div class="attendance-filter-field">
                        <label>Quick Range</label>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="quickRangeBtn">
                                @php
                                    $range = request('range');
                                    $label = 'All Time';
                                    if ($range == 'today') $label = 'Today';
                                    elseif ($range == 'yesterday') $label = 'Yesterday';
                                    elseif ($range == 'week') $label = 'This Week';
                                    elseif ($range == 'month') $label = 'This Month';
                                    elseif ($range == 'lastMonth') $label = 'Last Month';
                                    elseif ($range == '3months') $label = 'Last 3 Months';
                                    elseif ($range == '6months') $label = 'Last 6 Months';
                                    elseif ($range == '1year') $label = 'Last 1 Year';
                                    elseif ($range == 'custom') $label = 'Custom Date';
                                @endphp
                                {{ $label }}
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                <div class="wghrm-custom-search-box">
                                    <input type="text" class="wghrm-custom-search-input" placeholder="Search range..." onkeyup="wghrmFilterItems(this)">
                                </div>
                                <div class="wghrm-items-container">
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ !$range ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('', 'All Time')">All Time</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'today' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('today', 'Today')">Today</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'yesterday' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('yesterday', 'Yesterday')">Yesterday</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'week' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('week', 'This Week')">This Week</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'month' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('month', 'This Month')">This Month</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'lastMonth' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('lastMonth', 'Last Month')">Last Month</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == '3months' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('3months', 'Last 3 Months')">Last 3 Months</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == '6months' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('6months', 'Last 6 Months')">Last 6 Months</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == '1year' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('1year', 'Last 1 Year')">Last 1 Year</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'custom' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('custom', 'Custom Date')">Custom Date</a>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="quickRange" value="{{ request('range') }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>Employee</label>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="employeeSelectBtn">
                                @php
                                    $selectedEmpId = request('employee_id');
                                    $selectedEmp = $employees->firstWhere('id', $selectedEmpId);
                                @endphp
                                {{ $selectedEmp ? $selectedEmp->name : 'All Employees' }}
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                <div class="wghrm-custom-search-box">
                                    <input type="text" class="wghrm-custom-search-input" placeholder="Search employee..." onkeyup="wghrmFilterItems(this)">
                                </div>
                                <div class="wghrm-items-container">
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ !$selectedEmpId ? 'active' : '' }}" href="javascript:void(0);" onclick="selectEmployee('', 'All Employees')">All Employees</a>
                                    @foreach ($employees as $emp)
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ $selectedEmpId == $emp->id ? 'active' : '' }}"
                                            href="javascript:void(0);" onclick="selectEmployee('{{ $emp->id }}', '{{ addslashes($emp->name) }}')">{{ $emp->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="selectedEmployeeId" value="{{ request('employee_id') }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>Start Date</label>
                        <input type="date" id="startDate" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>End Date</label>
                        <input type="date" id="endDate" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="attendance-filter-actions">
                        <button type="button" class="zoho-btn-primary" onclick="applyFilters()">
                            <i class="feather-search"></i> Apply
                        </button>
                        <a href="{{ route('payroll.attendance') }}" class="zoho-btn-outline">Reset</a>
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
                <div id="attendanceTableContainer">
                    <div class="d-none d-lg-block">
                        <div class="zoho-people-table-toolbar">
                            <div class="zoho-people-table-search">
                                <i class="feather-search"></i>
                                <input type="text" id="tableSearch" onkeyup="searchTable()" placeholder="Search in list..." value="{{ request('search') }}">
                            </div>
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
                                            <th style="width: 130px;">Date</th>
                                            <th>Attendance History</th>
                                            <th class="text-end" style="width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($attendance as $att)
                                            @php $date = \Carbon\Carbon::parse($att->attendance_date)->format('Y-m-d'); @endphp
                                            <tr>
                                                <td><span class="attendance-date">{{ \Carbon\Carbon::parse($att->attendance_date)->format('d M Y') }}</span></td>
                                                <td>
                                                    @if(request('employee_id'))
                                                        @php
                                                            $empStatusClass = match($att->status) {
                                                                'present' => 'att-emp-status--present',
                                                                'absent', 'leave' => 'att-emp-status--absent',
                                                                'half_day' => 'att-emp-status--half_day',
                                                                'wfh' => 'att-emp-status--wfh',
                                                                'overtime' => 'att-emp-status--overtime',
                                                                default => 'att-emp-status--default',
                                                            };
                                                        @endphp
                                                        <div class="att-emp-day">
                                                            <span class="att-emp-status {{ $empStatusClass }}">{{ str_replace('_', ' ', $att->status) }}</span>
                                                            <div class="att-time-stat">
                                                                <div class="att-time-stat-label">Check In</div>
                                                                <div class="att-time-stat-value">{{ $att->check_in ? date('h:i A', strtotime($att->check_in)) : '--' }}</div>
                                                            </div>
                                                            <div class="att-time-stat">
                                                                <div class="att-time-stat-label">Check Out</div>
                                                                <div class="att-time-stat-value">{{ $att->check_out ? date('h:i A', strtotime($att->check_out)) : '--' }}</div>
                                                            </div>
                                                            <div class="att-time-stat">
                                                                <div class="att-time-stat-label">Work Hours</div>
                                                                <div class="att-time-stat-value att-time-stat-value--primary">{{ number_format($att->total_hours, 2) }} hrs</div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="att-stat-chips">
                                                            <span class="att-stat-chip att-stat-chip--present" onclick="openAttendanceDetails('{{ $date }}', 'present')">Present <strong>{{ $att->present_count }}</strong></span>
                                                            <span class="att-stat-chip att-stat-chip--overtime" onclick="openAttendanceDetails('{{ $date }}', 'overtime')">Overtime <strong>{{ $att->overtime_count }}</strong></span>
                                                            <span class="att-stat-chip att-stat-chip--half" onclick="openAttendanceDetails('{{ $date }}', 'half_day')">Half Day <strong>{{ $att->half_day_count }}</strong></span>
                                                            <span class="att-stat-chip att-stat-chip--leave" onclick="openAttendanceDetails('{{ $date }}', 'leave')">Leave <strong>{{ $att->leave_count }}</strong></span>
                                                            <span class="att-stat-chip att-stat-chip--absent" onclick="openAttendanceDetails('{{ $date }}', 'absent')">Absent <strong>{{ $att->absent_count }}</strong></span>
                                                            <span class="att-stat-chip att-stat-chip--wfh" onclick="openAttendanceDetails('{{ $date }}', 'wfh')">WFH <strong>{{ $att->wfh_count }}</strong></span>
                                                            <span class="att-stat-chip att-stat-chip--early" onclick="openAttendanceDetails('{{ $date }}', 'early_out')">Early Out <strong>{{ $att->early_count }}</strong></span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="attendance-row-actions">
                                                        <button type="button" class="zoho-icon-btn" onclick="openAttendanceDetails('{{ $date }}')" title="View">
                                                            <i class="feather-eye"></i>
                                                        </button>
                                                        @if($isAdmin)
                                                            <div class="dropdown">
                                                                <button type="button" class="zoho-icon-btn" data-bs-toggle="dropdown" title="More actions">
                                                                    <i class="feather-more-horizontal"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end zoho-more-menu">
                                                                    <li><a class="dropdown-item" href="{{ route('payroll.attendance.editByDate', $date) }}"><i class="feather-edit me-2"></i>Edit</a></li>
                                                                    <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteAttendanceByDate('{{ $date }}')"><i class="feather-trash-2 me-2"></i>Delete</a></li>
                                                                </ul>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3">
                                                    <div class="attendance-empty">
                                                        <i class="feather-calendar"></i>
                                                        <p>No attendance records found.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <!-- Mobile & Tablet Card View -->
                <div class="d-lg-none px-1 pt-3">
                    <div class="row g-3">
                        @forelse($attendance as $att)
                            @php $date = \Carbon\Carbon::parse($att->attendance_date)->format('Y-m-d'); @endphp
                            <div class="col-12 mobile-card-wrapper">
                                <div class="mobile-attendance-card p-3 shadow-sm border mb-3"
                                    style="border-radius: 15px; background: #fff;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="d-block small text-muted text-uppercase fw-bold"
                                                style="letter-spacing: 0.5px;">Date</span>
                                            <span
                                                class="fw-bold text-dark fs-6">{{ \Carbon\Carbon::parse($att->attendance_date)->format('d-M-Y') }}</span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            @if($isAdmin)
                                                <a href="{{ route('payroll.attendance.editByDate', $date) }}"
                                                    class="avatar-text avatar-sm bg-soft-primary text-primary">
                                                    <i data-feather="edit"></i>
                                                </a>
                                            @endif
                                            <a href="javascript:void(0);" onclick="openAttendanceDetails('{{ $date }}')"
                                                class="avatar-text avatar-sm bg-soft-info text-info">
                                                <i data-feather="eye"></i>
                                            </a>
                                            @if($isAdmin)
                                                <a href="javascript:void(0);" onclick="deleteAttendanceByDate('{{ $date }}')"
                                                    class="avatar-text avatar-sm bg-soft-danger text-danger">
                                                    <i data-feather="trash-2"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    @if(request('employee_id'))
                                        <div class="row g-3">
                                            <div class="col-12">
                                                @php
                                                    $statusClass = [
                                                        'present' => 'bg-soft-success text-success',
                                                        'absent' => 'bg-soft-danger text-danger',
                                                        'leave' => 'bg-soft-danger text-danger',
                                                        'half_day' => 'bg-soft-warning text-warning',
                                                        'wfh' => 'bg-soft-purple text-purple',
                                                        'overtime' => 'bg-soft-primary text-primary'
                                                    ][$att->status] ?? 'bg-soft-secondary text-secondary';
                                                @endphp
                                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background: #f8fafc;">
                                                    <span class="badge {{ $statusClass }} fw-bold text-uppercase px-2 py-1" style="font-size: 10px;">{{ str_replace('_', ' ', $att->status) }}</span>
                                                    <div class="fw-bold text-primary">{{ number_format($att->total_hours, 2) }} hrs</div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <div class="flex-fill p-2 bg-white rounded border text-center">
                                                        <div class="small text-muted fw-bold text-uppercase" style="font-size: 8px;">Check In</div>
                                                        <div class="fw-bold text-dark" style="font-size: 11px;">{{ $att->check_in ? date('h:i A', strtotime($att->check_in)) : '--:--' }}</div>
                                                    </div>
                                                    <div class="flex-fill p-2 bg-white rounded border text-center">
                                                        <div class="small text-muted fw-bold text-uppercase" style="font-size: 8px;">Check Out</div>
                                                        <div class="fw-bold text-dark" style="font-size: 11px;">{{ $att->check_out ? date('h:i A', strtotime($att->check_out)) : '--:--' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="ref-badge badge-green w-100 clickable py-2"
                                                    onclick="openAttendanceDetails('{{ $date }}', 'present')">
                                                    Present: <span class="fw-bold ms-1">{{ $att->present_count }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="ref-badge badge-blue w-100 clickable py-2"
                                                    onclick="openAttendanceDetails('{{ $date }}', 'overtime')">
                                                    Overtime: <span class="fw-bold ms-1">{{ $att->overtime_count }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="ref-badge badge-yellow w-100 clickable py-2"
                                                    onclick="openAttendanceDetails('{{ $date }}', 'half_day')">
                                                    Half Day: <span class="fw-bold ms-1">{{ $att->half_day_count }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="ref-badge badge-red w-100 clickable py-2"
                                                    onclick="openAttendanceDetails('{{ $date }}', 'leave')">
                                                    Leave: <span class="fw-bold ms-1">{{ $att->leave_count }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="ref-badge badge-purple w-100 clickable py-2"
                                                    onclick="openAttendanceDetails('{{ $date }}', 'wfh')">
                                                    WFH: <span class="fw-bold ms-1">{{ $att->wfh_count }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="ref-badge badge-purple w-100 clickable py-2"
                                                    onclick="openAttendanceDetails('{{ $date }}', 'early_out')">
                                                    Early: <span class="fw-bold ms-1">{{ $att->early_count }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem; opacity: 0.2;"></i>
                                <p class="text-muted mt-3 fw-bold">No Attendance Records Found</p>
                            </div>
                        @endforelse
                    </div>
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
@endsection

@push('modals')
    <div class="offcanvas offcanvas-end att-detail-drawer" tabindex="-1" id="attendanceDetailOffcanvas">
        <div class="att-detail-drawer-head">
            <div class="att-detail-drawer-head-main">
                <span class="att-detail-drawer-icon"><i class="feather-calendar"></i></span>
                <div>
                    <h5 class="att-detail-drawer-title">Attendance Record</h5>
                    <p class="att-detail-drawer-date">Date: <span id="offcanvasDate"></span></p>
                    <p class="att-detail-drawer-meta" id="statusIndicator">Showing all records</p>
                </div>
            </div>
            <div class="att-detail-drawer-actions">
                <button type="button" class="zoho-btn-outline att-detail-show-all" id="showAllBtn"
                    style="display:none;" onclick="resetModalFilter()">
                    <i class="feather-list"></i> Show All
                </button>
                <button type="button" class="zoho-icon-btn" data-bs-dismiss="offcanvas" title="Close">
                    <i class="feather-x"></i>
                </button>
            </div>
        </div>
        <div class="offcanvas-body att-detail-drawer-body p-0">
            <div class="d-none d-md-block">
                <div class="table-responsive zoho-table-wrap att-detail-table-wrap">
                    <table class="table zoho-data-table mb-0 att-detail-table">
                        <thead>
                            <tr>
                                <th class="col-num">Sr. No.</th>
                                <th>Employee Name</th>
                                <th class="text-center">Check In</th>
                                <th class="text-center">Check Out</th>
                                <th class="text-center">Working Hrs</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="offcanvasTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="d-md-none att-detail-mobile-list" id="offcanvasCardsBody"></div>
        </div>
    </div>
@endpush

@push('scripts')
    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        function updateDateRange(range) {
            const startInput = document.getElementById('startDate');
            const endInput = document.getElementById('endDate');
            const today = new Date();
            let start = new Date();
            let end = new Date();

            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            switch (range) {
                case 'today':
                    break;
                case 'yesterday':
                    start.setDate(today.getDate() - 1);
                    end.setDate(today.getDate() - 1);
                    break;
                case 'week':
                    start.setDate(today.getDate() - today.getDay());
                    break;
                case 'month':
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
                case 'lastMonth':
                    start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    end = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case '3months':
                    start.setMonth(today.getMonth() - 2);
                    start.setDate(1);
                    break;
                case '6months':
                    start.setMonth(today.getMonth() - 5);
                    start.setDate(1);
                    break;
                case '1year':
                    start.setFullYear(today.getFullYear() - 1);
                    start.setMonth(today.getMonth() + 1);
                    start.setDate(1);
                    break;
                case 'custom':
                    return;
            }

            if (startInput) {
                startInput.value = formatDate(start);
            }

            if (endInput) {
                endInput.value = formatDate(end);
            }
        }

        function selectQuickRange(val, label) {
            document.getElementById('quickRange').value = val;
            document.getElementById('quickRangeBtn').innerText = label;
            updateDateRange(val);
            bootstrap.Dropdown.getInstance(document.getElementById('quickRangeBtn')).hide();
        }

        let searchTimeout;
        function searchTable() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 400);
        }

        function performSearch() {
            const searchVal = document.getElementById('tableSearch').value;
            let url = new URL(window.location.href);
            if (searchVal) {
                url.searchParams.set('search', searchVal);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.set('page', 1);

            window.history.pushState(null, '', url.toString());

            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.getElementById('attendanceTableContainer');
                const currentContainer = document.getElementById('attendanceTableContainer');
                if (newContainer && currentContainer) {
                    currentContainer.innerHTML = newContainer.innerHTML;
                }
                if (window.feather) {
                    feather.replace();
                }
            })
            .catch(err => console.error('Error fetching search results:', err));
        }

        function wghrmFilterItems(input) {
            const filter = input.value.toLowerCase();
            const container = input.closest('.wghrm-custom-dropdown-menu').querySelector('.wghrm-items-container');
            const items = container.querySelectorAll('.wghrm-custom-dropdown-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(filter)) {
                    item.style.setProperty('display', 'block', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const startInput = document.getElementById('startDate');
            const endInput = document.getElementById('endDate');
            const quickRange = document.getElementById('quickRange');

            if (startInput && endInput && quickRange) {
                [startInput, endInput].forEach(el => {
                    el.addEventListener('change', () => {
                        quickRange.value = 'custom';
                        document.getElementById('quickRangeBtn').innerText = 'Custom Date';
                    });
                });
            }

            if (!quickRange.value || (quickRange.value === 'today' && !startInput.value)) {
                updateDateRange('today');
            }

            // AJAX navigation for pagination and entries dropdown inside container
            document.addEventListener('click', function(e) {
                const ajaxLink = e.target.closest('#attendanceTableContainer a');
                if (ajaxLink && (ajaxLink.closest('.pagination') || ajaxLink.closest('.wghrm-custom-dropdown-menu'))) {
                    const targetUrl = ajaxLink.getAttribute('href');
                    if (targetUrl && targetUrl !== 'javascript:void(0)' && !targetUrl.startsWith('#')) {
                        e.preventDefault();
                        
                        window.history.pushState(null, '', targetUrl);
                        
                        fetch(targetUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newContainer = doc.getElementById('attendanceTableContainer');
                            const currentContainer = document.getElementById('attendanceTableContainer');
                            if (newContainer && currentContainer) {
                                currentContainer.innerHTML = newContainer.innerHTML;
                                currentContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                            if (window.feather) {
                                feather.replace();
                            }
                        })
                        .catch(err => console.error('Error fetching dynamic content:', err));
                    }
                }
            });
        });

        function selectEmployee(val, label) {
            document.getElementById('selectedEmployeeId').value = val;
            document.getElementById('employeeSelectBtn').innerText = label;
            bootstrap.Dropdown.getInstance(document.getElementById('employeeSelectBtn')).hide();
        }

        function applyFilters() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const range = document.getElementById('quickRange').value;
            const empId = document.getElementById('selectedEmployeeId').value;
            const searchVal = document.getElementById('tableSearch').value;

            let url = new URL(window.location.href);
            if (start) url.searchParams.set('start_date', start);
            if (end) url.searchParams.set('end_date', end);
            if (range) url.searchParams.set('range', range);
            if (empId) url.searchParams.set('employee_id', empId);
            else url.searchParams.delete('employee_id');

            if (searchVal) url.searchParams.set('search', searchVal);
            else url.searchParams.delete('search');
            
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }

        let lastFetchedData = null;
        let lastDate = null;

        const OVERTIME_HOURS_THRESHOLD = 9.5;

        function extractTimeValue(value) {
            if (!value) return null;
            const raw = value.includes(' ') ? value.split(' ')[1] : value;
            return raw.substring(0, 5);
        }

        function timeToMinutes(time) {
            const [h, m] = time.split(':').map(Number);
            return (h * 60) + m;
        }

        function subtractMinutesFromTime(time, minutes) {
            let total = timeToMinutes(time) - minutes;
            if (total < 0) total += 24 * 60;
            const h = Math.floor(total / 60) % 24;
            const m = total % 60;
            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        }

        function halfDayEndTime(timeIn, timeOut) {
            const span = timeToMinutes(timeOut) - timeToMinutes(timeIn);
            const total = timeToMinutes(timeIn) + Math.floor(span / 2);
            const h = Math.floor(total / 60) % 24;
            const m = total % 60;
            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        }

        function matchesEarlyOut(item) {
            const status = (item.status || '').toLowerCase();
            if (['early_leave', 'early_out'].includes(status)) {
                return true;
            }
            if (!item.check_out || !item.employee) return false;

            const checkOutTime = extractTimeValue(item.check_out);
            const emp = item.employee;

            if (['present', 'late'].includes(status) && emp.time_out) {
                return checkOutTime <= subtractMinutesFromTime(extractTimeValue(emp.time_out), 30);
            }

            if (status === 'half_day' && emp.time_in && emp.time_out) {
                const halfEnd = halfDayEndTime(extractTimeValue(emp.time_in), extractTimeValue(emp.time_out));
                return checkOutTime <= subtractMinutesFromTime(halfEnd, 30);
            }

            return false;
        }

        function matchesOvertime(item) {
            return Number(item.total_hours) > OVERTIME_HOURS_THRESHOLD;
        }

        function resetModalFilter() {
            if (lastFetchedData) renderTable(lastFetchedData, null);
            document.getElementById('showAllBtn').style.display = 'none';
            document.getElementById('statusIndicator').innerHTML = 'Showing all records';
        }

        function openAttendanceDetails(date, filterStatus = null) {
            lastDate = date;
            //  let url = apiUrl(`/payroll/attendance/details?date=${date}`);
            let url = `/payroll/attendance/details?date=${date}`;
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    console.log('Attendance details data:', data);
                    if (data.success) {
                        lastFetchedData = data.data;
                        const dateLabel = document.getElementById('offcanvasDate');
                        if (dateLabel) {
                            try {
                                const d = new Date(date + 'T00:00:00');
                                dateLabel.innerText = d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
                            } catch (e) {
                                dateLabel.innerText = date;
                            }
                        }

                        const statusLabel = document.getElementById('statusIndicator');
                        if (statusLabel) {
                            if (filterStatus) {
                                statusLabel.innerHTML = `Filtered: <strong>${filterStatus.replace('_', ' ')}</strong>`;
                                document.getElementById('showAllBtn').style.display = 'inline-flex';
                            } else {
                                statusLabel.innerHTML = 'Showing all records';
                                document.getElementById('showAllBtn').style.display = 'none';
                            }
                        }

                        renderTable(data.data, filterStatus, data.is_activity);

                        // Robust Offcanvas opening
                        const offcanvasEl = document.getElementById('attendanceDetailOffcanvas');
                        if (offcanvasEl) {
                            try {
                                // Try finding existing instance or create new one
                                let bootstrapObj = window.bootstrap || bootstrap;
                                let offcanvas = bootstrapObj.Offcanvas.getInstance(offcanvasEl);
                                if (!offcanvas) {
                                    offcanvas = new bootstrapObj.Offcanvas(offcanvasEl);
                                }
                                offcanvas.show();
                            } catch (e) {
                                console.error('Bootstrap Offcanvas failed:', e);
                                // Fallback to jQuery if available
                                if (window.jQuery) {
                                    jQuery(offcanvasEl).offcanvas('show');
                                } else {
                                    // Extreme fallback: manual show
                                    offcanvasEl.classList.add('show');
                                    offcanvasEl.style.visibility = 'visible';
                                    offcanvasEl.style.display = 'block';
                                }
                            }
                        }
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    alert('Error loading attendance details. Please try again.');
                });
        }

        function displayClassToBadge(displayClass) {
            const map = {
                success: 'att-detail-status--present',
                danger: 'att-detail-status--absent',
                warning: 'att-detail-status--half',
                info: 'att-detail-status--info',
                secondary: 'att-detail-status--present',
            };
            return map[displayClass] || 'att-detail-status--info';
        }

        function matchesStatusFilter(item, filterStatus, isHoliday) {
            const key = (item.display_status_key || item.status || '').toLowerCase();

            switch (filterStatus) {
                case 'present':
                    return ['present', 'present_activity', 'late'].includes(key) || (isHoliday && item.status === 'absent');
                case 'half_day':
                    return key === 'half_day' || key === 'half_day_leave';
                case 'leave':
                    return ['leave', 'half_day_leave', 'unpaid_leave'].includes(key);
                case 'absent':
                    return key === 'absent' && !isHoliday;
                case 'late':
                    return key === 'late';
                case 'wfh':
                    return key === 'wfh';
                case 'missing_punch':
                    return key === 'missing_punch';
                case 'unpaid_leave':
                    return ['unpaid_leave', 'unauthorised'].includes(key);
                case 'early_out':
                    return ['early_out', 'early_leave', 'present_activity'].includes(key) || matchesEarlyOut(item);
                case 'overtime':
                    return matchesOvertime(item);
                default:
                    return true;
            }
        }

        function renderTable(rows, filterStatus, isActivityDay = false) {
            const body = document.getElementById('offcanvasTableBody');
            const cardsBody = document.getElementById('offcanvasCardsBody');
            body.innerHTML = '';
            cardsBody.innerHTML = '';

            let count = 0;
            rows.forEach((item) => {
                const isHoliday = !!item.is_holiday;
                const match = !filterStatus || matchesStatusFilter(item, filterStatus, isHoliday);

                if (match) {
                    count++;
                    const statusDisplay = item.display_status || (item.status || '').replace(/_/g, ' ');
                    const badgeClass = item.display_status_class
                        ? displayClassToBadge(item.display_status_class)
                        : getStatusBadge(item.display_status_key || item.status);

                    const adminActions = `@if($isAdmin)
                        <div class="attendance-row-actions">
                            <button type="button" class="zoho-icon-btn" onclick="editSingleAttendance(${item.id})" title="Edit">
                                <i data-feather="edit"></i>
                            </button>
                            <button type="button" class="zoho-icon-btn" onclick="deleteSingleAttendance(${item.id}, '${lastDate}')" title="Delete">
                                <i data-feather="trash-2"></i>
                            </button>
                        </div>
                    @endif`;

                    body.innerHTML += `
                        <tr>
                            <td class="text-muted fw-semibold">${count}</td>
                            <td><span class="att-detail-emp-name">${item.employee.name}</span></td>
                            <td class="text-center att-detail-time">${item.check_in ? formatTime(item.check_in) : '--'}</td>
                            <td class="text-center att-detail-time">${item.check_out ? formatTime(item.check_out) : '--'}</td>
                            <td class="text-center att-detail-hours">${formatHours(item.total_hours)}</td>
                            <td class="text-center">
                                <span class="att-detail-status ${badgeClass}">${statusDisplay}</span>
                            </td>
                            <td class="text-center">${adminActions}</td>
                        </tr>
                    `;

                    cardsBody.innerHTML += `
                        <div class="att-detail-mobile-card">
                            <div class="att-detail-mobile-card-head">
                                <div>
                                    <div class="att-detail-mobile-label">Employee</div>
                                    <div class="att-detail-emp-name">${item.employee.name}</div>
                                </div>
                                <span class="att-detail-status ${badgeClass}">${statusDisplay}</span>
                            </div>
                            <div class="att-detail-mobile-stats">
                                <div class="att-time-stat">
                                    <div class="att-time-stat-label">Check In</div>
                                    <div class="att-time-stat-value">${item.check_in ? formatTime(item.check_in) : '--'}</div>
                                </div>
                                <div class="att-time-stat">
                                    <div class="att-time-stat-label">Check Out</div>
                                    <div class="att-time-stat-value">${item.check_out ? formatTime(item.check_out) : '--'}</div>
                                </div>
                                <div class="att-time-stat">
                                    <div class="att-time-stat-label">Hours</div>
                                    <div class="att-time-stat-value att-time-stat-value--primary">${formatHours(item.total_hours)}</div>
                                </div>
                            </div>
                            @if($isAdmin)
                            <div class="att-detail-mobile-actions">
                                <button type="button" class="zoho-btn-outline" onclick="editSingleAttendance(${item.id}, '${lastDate}')">
                                    <i data-feather="edit"></i> Edit
                                </button>
                                <button type="button" class="zoho-btn-outline text-danger" onclick="deleteSingleAttendance(${item.id}, '${lastDate}')">
                                    <i data-feather="trash-2"></i> Delete
                                </button>
                            </div>
                            @endif
                        </div>
                    `;
                }
            });

            if (filterStatus) {
                document.getElementById('showAllBtn').style.display = 'inline-flex';
                document.getElementById('statusIndicator').innerHTML = `Filtered: <strong>${filterStatus.replace('_', ' ')}</strong> · ${count} record${count === 1 ? '' : 's'}`;
            } else {
                document.getElementById('showAllBtn').style.display = 'none';
                document.getElementById('statusIndicator').innerHTML = `Showing all records · ${count} employee${count === 1 ? '' : 's'}`;
            }

            if (count === 0) {
                body.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="attendance-empty">
                                <i data-feather="users"></i>
                                <p>No records found for this filter.</p>
                            </div>
                        </td>
                    </tr>`;
                cardsBody.innerHTML = `
                    <div class="attendance-empty">
                        <i data-feather="users"></i>
                        <p>No records found for this filter.</p>
                    </div>`;
            }

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        function formatHours(decimalHours) {
            if (!decimalHours) return '--';

            let hours = Math.floor(decimalHours);
            let minutes = Math.round((decimalHours - hours) * 60);

            if (minutes === 60) {
                hours += 1;
                minutes = 0;
            }

            return `${hours}h ${minutes.toString().padStart(2, '0')}m`;
        }

        function formatTime(time) {
            if (!time) return '--';
            let [h, m] = time.split(':');
            let ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return `${h}:${m} ${ampm}`;
        }

        function getStatusBadge(status) {
            switch ((status || '').toLowerCase()) {
                case 'present':
                case 'holiday':
                case 'present_activity':
                    return 'att-detail-status--present';
                case 'absent':
                case 'unauthorised':
                    return 'att-detail-status--absent';
                case 'half_day':
                case 'half_day_leave':
                    return 'att-detail-status--half';
                case 'leave':
                case 'unpaid_leave':
                case 'missing_punch':
                case 'wfh':
                case 'activity':
                case 'early_leave':
                case 'early_out':
                case 'late':
                case 'overtime':
                    return 'att-detail-status--info';
                default:
                    return 'att-detail-status--info';
            }
        }

        function deleteAttendanceByDate(date) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Delete all records for " + date + "? This cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3858f9',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete all!',
                cancelButtonText: 'No, cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-light-brand px-4 me-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/payroll/attendance/date/${date}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            if (typeof Toast !== 'undefined') {
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Attendance records deleted'
                                });
                            }
                            setTimeout(() => window.location.reload(), 1000);
                        }
                    });
                }
            });
        }

        function deleteSingleAttendance(id, date) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Delete this attendance record?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3858f9',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-light-brand px-4 me-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ url('/payroll/attendance') }}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            if (typeof Toast !== 'undefined') {
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Record deleted'
                                });
                            }
                            openAttendanceDetails(date);
                        }
                    });
                }
            });
        }

        function editSingleAttendance(id) {
            window.location.href = `{{ url('/payroll/attendance') }}/${id}/edit`;
        }

        function syncBiometricAttendance() {
            const btn = document.getElementById('syncBiometricBtn');
            if (!btn || btn.disabled) {
                return;
            }

            Swal.fire({
                title: 'Sync biometric punches?',
                text: 'This will pull the latest punches from the biometric device and update attendance.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3858f9',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, sync now',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-light-brand px-4 me-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="feather-loader"></i> Syncing...';

                fetch('{{ route('sync.attendance') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Biometric sync failed');
                        }

                        return data;
                    })
                    .then((data) => {
                        if (typeof Toast !== 'undefined') {
                            Toast.fire({
                                icon: 'success',
                                title: data.message || 'Attendance synced successfully',
                            });
                        }

                        setTimeout(() => window.location.reload(), 1200);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Sync failed',
                            text: error.message || 'Unable to import punches from biometric device.',
                        });
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    });
            });
        }

        function rebuildBiometricAttendance() {
            const btn = document.getElementById('rebuildBiometricBtn');
            if (!btn || btn.disabled) {
                return;
            }

            Swal.fire({
                title: 'Rebuild from stored logs?',
                text: 'This recalculates attendance from punches already saved in the database. It does not contact the biometric device.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3858f9',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, rebuild',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-light-brand px-4 me-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="feather-loader"></i> Rebuilding...';

                fetch('{{ route('sync.attendance.rebuild') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Rebuild failed');
                        }

                        return data;
                    })
                    .then((data) => {
                        if (typeof Toast !== 'undefined') {
                            Toast.fire({
                                icon: 'success',
                                title: data.message || 'Attendance rebuilt successfully',
                            });
                        }

                        setTimeout(() => window.location.reload(), 1200);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Rebuild failed',
                            text: error.message || 'Unable to rebuild attendance from logs.',
                        });
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    });
            });
        }

         function exportAttendance() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const employeeId = document.getElementById('selectedEmployeeId')?.value || '';
            const range = document.getElementById('quickRange')?.value || '';

            fetch('{{ route("payroll.attendance.export") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    start_date: start,
                    end_date: end,
                    employee_id: employeeId,
                    range: range
                })
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'attendance.xlsx'; // change filename if needed
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Export failed:', error);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        .wghrm-custom-select-btn {
            background-color: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            border-radius: 10px;
            color: #475569;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            height: 44px;
            font-size: 14px;
            text-align: left;
            transition: all 0.2s;
        }

        .wghrm-custom-select-btn:focus,
        .wghrm-custom-select-btn:active {
            border-color: #3858f9;
            box-shadow: 0 0 0 3px rgba(56, 88, 249, 0.1);
            outline: none;
        }

        .wghrm-custom-select-btn::after {
            border-top: .3em solid;
            border-right: .3em solid transparent;
            border-bottom: 0;
            border-left: .3em solid transparent;
            margin-left: .255em;
            content: "";
        }

        .wghrm-custom-dropdown-menu {
            border-radius: 12px !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08) !important;
            padding: 8px !important;
            margin-top: 8px !important;
            z-index: 99 !important;
            background: #fff !important;
            border: 1px solid #e2e8f0 !important;
            width: 100%;
            min-width: 250px;
        }

        .wghrm-custom-search-box {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            padding: 4px;
            margin-bottom: 8px;
        }

        .wghrm-custom-search-input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            outline: none;
            background: #f8fafc;
            font-weight: 500;
        }

        .wghrm-custom-search-input:focus {
            border-color: #3858f9;
            background: #fff;
        }

        .wghrm-items-container {
            max-height: 250px;
            overflow-y: auto;
        }

        .wghrm-custom-dropdown-item {
            border-radius: 10px !important;
            padding: 10px 15px !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            color: #475569 !important;
            margin-bottom: 3px !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            white-space: nowrap !important;
        }

        .wghrm-custom-dropdown-item:hover,
        .wghrm-custom-dropdown-item.active {
            background: #f1f5f9 !important;
            color: #3858f9 !important;
        }

        .wghrm-custom-search-box {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            padding-bottom: 8px;
            margin-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .wghrm-custom-search-input {
            width: 100%;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            outline: none;
        }

        .wghrm-custom-search-input:focus {
            border-color: #3858f9;
            box-shadow: 0 0 0 2px rgba(56, 88, 249, 0.1);
        }

        .breadcrumb-item+.breadcrumb-item::before {
            content: ">";
            color: #94a3b8;
        }

        .hover-row:hover {
            background-color: #fbfcfe;
        }

        .ref-badge {
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            letter-spacing: 0.5px;
            min-width: 90px;
            display: inline-block;
            text-align: center;
            text-transform: uppercase;
        }

        .clickable {
            cursor: pointer;
            transition: transform 0.1s;
        }

        .clickable:hover {
            transform: scale(1.05);
        }

        .badge-green {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .badge-blue {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
        }

        .badge-yellow {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fef3c7;
        }

        .badge-red {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .status-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .status-badge-success {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .status-badge-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .status-badge-warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fef3c7;
        }

        .status-badge-info {
            background: #f0f9ff;
            color: #0284c7;
            border: 1px solid #e0f2fe;
        }

        .btn-soft-primary {
            background-color: rgba(56, 88, 249, 0.1);
            color: #3858f9;
            border: none;
            transition: all 0.2s;
        }

        .btn-soft-primary:hover {
            background-color: #3858f9;
            color: #fff;
        }

        .btn-soft-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: none;
            transition: all 0.2s;
        }

        .btn-soft-danger:hover {
            background-color: #ef4444;
            color: #fff;
        }

        .mobile-attendance-card {
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9 !important;
        }

        .mobile-attendance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            border-color: #e2e8f0 !important;
        }

        @media (max-width: 768px) {
            .offcanvas {
                width: 100% !important;
            }

            .container-fluid {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }

            .card-header {
                padding: 15px !important;
            }
        }

        .action-btn-outline {
            background: transparent !important;
            border: 0 !important;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            color: #64748b;
            box-shadow: none !important;
        }

        .action-btn-outline:hover {
            background: #f1f5f9 !important;
            color: #3858f9;
            border: 0 !important;
        }

        .badge-purple {
            background: #f3e8ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
        }

        .attendance-pagination .pagination {
            margin-bottom: 0;
            justify-content: center;
            gap: 0.35rem;
        }

        .attendance-pagination .page-link {
            min-width: 38px;
            height: 38px;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-weight: 600;
            box-shadow: none;
        }

        .attendance-pagination .page-item.active .page-link {
            background: #3858f9;
            border-color: #3858f9;
            color: #fff;
        }

        .attendance-pagination .page-item.disabled .page-link {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .attendance-pagination .page-link svg {
            width: 14px;
            height: 14px;
        }

        /* Premium Calendar/Date Input Styling */
        input[type="date"],
        input[type="month"] {
            border: 1px solid #e2e8f0 !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #334155 !important;
            font-weight: 600 !important;
            cursor: pointer;
        }

        input[type="date"]:hover,
        input[type="month"]:hover {
            border-color: #cbd5e1 !important;
            background-color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        input[type="date"]:focus,
        input[type="month"]:focus {
            border-color: #3858f9 !important;
            box-shadow: 0 0 0 4px rgba(56, 88, 249, 0.12) !important;
            background-color: #ffffff !important;
            outline: none !important;
        }

        /* Customizing the native calendar picker icon */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="month"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.6;
            transition: all 0.2s;
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover,
        input[type="month"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        /* Custom Scrollbar (Slider) */
        .wghrm-items-container::-webkit-scrollbar,
        .wghrm-custom-dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .wghrm-items-container::-webkit-scrollbar-track,
        .wghrm-custom-dropdown-menu::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
        }

        .wghrm-items-container::-webkit-scrollbar-thumb,
        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .wghrm-items-container::-webkit-scrollbar-thumb:hover,
        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
@endpush
