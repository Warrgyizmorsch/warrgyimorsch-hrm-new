@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
@endpush

@section('content')
    @php
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);
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
        $employeeHeaderActions = '
            <a href="' . route('payroll.attendance') . '" class="zoho-btn-outline">
                <i class="feather-calendar"></i> Date Wise
            </a>
            ' . $importDropdown . '
            <button type="button" class="zoho-icon-btn" onclick="exportAttendance()" title="Export">
                <i class="feather-download"></i>
            </button>
            ' . $addButton;
    @endphp

    <div class="zoho-page-shell attendance-page attendance-employee-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Attendance Management',
            'viewLabel' => 'Employee Wise',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'Employee Wise', 'url' => route('payroll.attendace.employee'), 'active' => true],
            ],
            'primaryAction' => $employeeHeaderActions,
        ])

        <div class="main-content zoho-module-content">
            <div class="attendance-filter-panel" id="filterSection">
                <div class="attendance-filter-grid attendance-filter-grid--employee">
                    <div class="attendance-filter-field">
                        <label>Employee</label>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="employeeSelectBtn">
                                @php
                                    $selectedEmpId = request('employee_id');
                                    $selectedEmpName = 'All Employees';
                                    foreach ($employees as $emp) {
                                        if ($emp->id == $selectedEmpId) {
                                            $selectedEmpName = $emp->name;
                                            break;
                                        }
                                    }
                                @endphp
                                {{ $selectedEmpName }}
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                <div class="wghrm-custom-search-box">
                                    <input type="text" class="wghrm-custom-search-input" placeholder="Search employee..." onkeyup="wghrmFilterItems(this)">
                                </div>
                                <div class="wghrm-items-container">
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ !$selectedEmpId ? 'active' : '' }}"
                                        href="javascript:void(0);" onclick="selectEmployee('', 'All Employees')">All Employees</a>
                                    @foreach ($employees as $employee)
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ $selectedEmpId == $employee->id ? 'active' : '' }}"
                                            href="javascript:void(0);"
                                            onclick="selectEmployee('{{ $employee->id }}', '{{ addslashes($employee->name) }}')">{{ $employee->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="employee_id" value="{{ request('employee_id') }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>Quick Range</label>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="quickRangeBtn">
                                @php
                                    $activeRange = request('range');
                                    if (!$activeRange && !request()->filled('start_date') && !request()->filled('end_date')) {
                                        $activeRange = 'lastMonth';
                                    }
                                    $label = 'All Time';
                                    if ($activeRange == 'today') $label = 'Today';
                                    elseif ($activeRange == 'yesterday') $label = 'Yesterday';
                                    elseif ($activeRange == 'week') $label = 'This Week';
                                    elseif ($activeRange == 'month') $label = 'This Month';
                                    elseif ($activeRange == 'lastMonth') $label = 'Last Month';
                                    elseif ($activeRange == '3months') $label = 'Last 3 Months';
                                    elseif ($activeRange == '6months') $label = 'Last 6 Months';
                                    elseif ($activeRange == '1year') $label = 'Last 1 Year';
                                    elseif ($activeRange == 'custom') $label = 'Custom Date';
                                @endphp
                                {{ $label }}
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                <div class="wghrm-custom-search-box">
                                    <input type="text" class="wghrm-custom-search-input" placeholder="Search range..." onkeyup="wghrmFilterItems(this)">
                                </div>
                                <div class="wghrm-items-container">
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange === 'all' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('all', 'All Time')">All Time</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == 'today' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('today', 'Today')">Today</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == 'yesterday' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('yesterday', 'Yesterday')">Yesterday</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == 'week' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('week', 'This Week')">This Week</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == 'month' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('month', 'This Month')">This Month</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == 'lastMonth' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('lastMonth', 'Last Month')">Last Month</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == '3months' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('3months', 'Last 3 Months')">Last 3 Months</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == '6months' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('6months', 'Last 6 Months')">Last 6 Months</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == '1year' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('1year', 'Last 1 Year')">Last 1 Year</a>
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ $activeRange == 'custom' ? 'active' : '' }}" href="javascript:void(0);" onclick="selectQuickRange('custom', 'Custom Date')">Custom Date</a>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="quickRange" value="{{ $activeRange ?? '' }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>Start Date</label>
                        <input type="date" id="startDate" class="form-control" value="{{ request('start_date', $listStartDate->toDateString()) }}">
                    </div>
                    <div class="attendance-filter-field">
                        <label>End Date</label>
                        <input type="date" id="endDate" class="form-control" value="{{ request('end_date', $listEndDate->toDateString()) }}">
                    </div>
                    <div class="attendance-filter-actions">
                        <button type="button" class="zoho-btn-primary" onclick="applyFilters()">
                            <i class="feather-search"></i> Apply
                        </button>
                        <a href="{{ route('payroll.attendace.employee') }}" class="zoho-btn-outline">Reset</a>
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
                                <input type="text" id="tableSearch" onkeyup="searchTable(event)" placeholder="Search in list..." value="{{ request('search') }}">
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
                                            <th style="width: 200px;">Employee Name</th>
                                            <th>Attendance History</th>
                                            <th class="text-end" style="width: 100px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($attendance as $att)
                                            @php
                                                $employeeId = $att->employee_id;
                                                $employeeName = $att->employee_name;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="att-emp-list-name">{{ $employeeName }}</span>
                                                </td>
                                                <td>
                                                    <div class="att-stat-chips">
                                                        <span class="att-stat-chip att-stat-chip--present" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'present')">Present <strong>{{ $att->present_count }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--overtime" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'overtime')">Overtime <strong>{{ $att->overtime_count }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--half" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'half_day')">Half Day <strong>{{ $att->half_day_count }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--leave" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'leave')">Leave <strong>{{ $att->leave_count }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--absent" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'absent')">Absent <strong>{{ $att->absent_count }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--wfh" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'wfh')">WFH <strong>{{ $att->wfh_count }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--early" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'early_out')">Early Out <strong>{{ $att->early_count }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--missing" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'missing_punch')">Missing Punch <strong>{{ $att->missing_punch_count ?? 0 }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--weekly" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'weekly_off')">Weekly Off <strong>{{ $att->weekly_off_count ?? 0 }}</strong></span>
                                                        <span class="att-stat-chip att-stat-chip--payable" title="Capped at days in filter. Full month: Present + Half×0.5 + Missing Punch + Paid Leave.">Payable <strong>{{ $att->payable_days ?? 0 }}</strong></span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="attendance-row-actions">
                                                        <button type="button" class="zoho-icon-btn" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}')" title="View">
                                                            <i class="feather-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3">
                                                    <div class="attendance-empty">
                                                        <i class="feather-users"></i>
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

                    <div class="d-lg-none att-emp-mobile-list">
                        @forelse($attendance as $att)
                            @php
                                $employeeId = $att->employee_id;
                                $employeeName = $att->employee_name;
                            @endphp
                            <div class="att-emp-mobile-card">
                                <div class="att-emp-mobile-card-head">
                                    <div>
                                        <div class="att-detail-mobile-label">Employee</div>
                                        <div class="att-emp-list-name">{{ $employeeName }}</div>
                                    </div>
                                    <button type="button" class="zoho-icon-btn" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}')" title="View">
                                        <i class="feather-eye"></i>
                                    </button>
                                </div>
                                <div class="att-stat-chips">
                                    <span class="att-stat-chip att-stat-chip--present" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'present')">Present <strong>{{ $att->present_count }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--overtime" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'overtime')">Overtime <strong>{{ $att->overtime_count }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--half" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'half_day')">Half Day <strong>{{ $att->half_day_count }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--leave" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'leave')">Leave <strong>{{ $att->leave_count }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--absent" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'absent')">Absent <strong>{{ $att->absent_count }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--wfh" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'wfh')">WFH <strong>{{ $att->wfh_count }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--early" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'early_out')">Early Out <strong>{{ $att->early_count }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--missing" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'missing_punch')">Missing Punch <strong>{{ $att->missing_punch_count ?? 0 }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--weekly" onclick="openAttendanceDetails('{{ $employeeId }}', '{{ addslashes($employeeName) }}', 'weekly_off')">Weekly Off <strong>{{ $att->weekly_off_count ?? 0 }}</strong></span>
                                    <span class="att-stat-chip att-stat-chip--payable" title="Capped at days in filter. Full month: Present + Half×0.5 + Missing Punch + Paid Leave.">Payable <strong>{{ $att->payable_days ?? 0 }}</strong></span>
                                </div>
                            </div>
                        @empty
                            <div class="attendance-empty">
                                <i class="feather-users"></i>
                                <p>No attendance records found.</p>
                            </div>
                        @endforelse
                    </div>

                    @if($attendance->hasPages())
                        <div class="attendance-pagination">
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
                <span class="att-detail-drawer-icon"><i class="feather-user"></i></span>
                <div>
                    <h5 class="att-detail-drawer-title">Employee Attendance</h5>
                    <p class="att-detail-drawer-date">Employee: <span id="offcanvasDate"></span></p>
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
                                <th>Date</th>
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
    <script>
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
        const isTeamLeader = {{ $isTeamLeader ? 'true' : 'false' }};

        function updateDateRange(range) {
            const startInput = document.getElementById('startDate');
            const endInput = document.getElementById('endDate');
            const today = new Date();
            let start = new Date();
            let end = new Date();

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
                case 'all':
                    startInput.value = '';
                    endInput.value = '';
                    return;
            }

            startInput.value = start.getFullYear() + '-' +
                String(start.getMonth() + 1).padStart(2, '0') + '-' +
                String(start.getDate()).padStart(2, '0');

            endInput.value = end.getFullYear() + '-' +
                String(end.getMonth() + 1).padStart(2, '0') + '-' +
                String(end.getDate()).padStart(2, '0');
        }

        function selectEmployee(id, name) {
            document.getElementById('employee_id').value = id;
            document.getElementById('employeeSelectBtn').innerText = name;
            bootstrap.Dropdown.getInstance(document.getElementById('employeeSelectBtn')).hide();
        }

        function selectQuickRange(val, label) {
            document.getElementById('quickRange').value = val;
            document.getElementById('quickRangeBtn').innerText = label;
            updateDateRange(val);
            bootstrap.Dropdown.getInstance(document.getElementById('quickRangeBtn')).hide();
        }

        let searchTimeout;
        function searchTable(event) {
            if (event && event.key === 'Enter') {
                clearTimeout(searchTimeout);
                performSearch();
                return;
            }
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
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
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
            })
            .catch(err => console.error('Error fetching search results:', err));
        }

        function wghrmFilterItems(input) {
            const filter = input.value.toLowerCase();
            const container = input.closest('.wghrm-custom-dropdown-menu').querySelector('.wghrm-items-container');
            const items = container.querySelectorAll('.wghrm-custom-dropdown-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.setProperty('display', text.includes(filter) ? 'block' : 'none', 'important');
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const startInput = document.getElementById('startDate');
            const endInput = document.getElementById('endDate');
            const quickRange = document.getElementById('quickRange');

            if (startInput && endInput && quickRange) {
                if (quickRange.value && quickRange.value !== 'all' && quickRange.value !== 'custom') {
                    updateDateRange(quickRange.value);
                }

                [startInput, endInput].forEach(el => {
                    el.addEventListener('change', () => {
                        quickRange.value = 'custom';
                        document.getElementById('quickRangeBtn').innerText = 'Custom Date';
                    });
                });
            }

            document.addEventListener('click', function(e) {
                const ajaxLink = e.target.closest('#attendanceTableContainer a');
                if (ajaxLink && (ajaxLink.closest('.pagination') || ajaxLink.closest('.dropdown-menu'))) {
                    const targetUrl = ajaxLink.getAttribute('href');
                    if (targetUrl && targetUrl !== 'javascript:void(0)' && !targetUrl.startsWith('#')) {
                        e.preventDefault();
                        window.history.pushState(null, '', targetUrl);
                        fetch(targetUrl, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
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
                        })
                        .catch(err => console.error('Error fetching dynamic content:', err));
                    }
                }
            });
        });

        function applyFilters() {
            const employeeId = document.getElementById('employee_id').value;
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const range = document.getElementById('quickRange').value;
            const searchVal = document.getElementById('tableSearch').value;

            let url = new URL(window.location.href);

            if (employeeId) url.searchParams.set('employee_id', employeeId);
            else url.searchParams.delete('employee_id');
            if (start) url.searchParams.set('start_date', start);
            else url.searchParams.delete('start_date');
            if (end) url.searchParams.set('end_date', end);
            else url.searchParams.delete('end_date');
            if (range) url.searchParams.set('range', range);
            else url.searchParams.delete('range');

            if (searchVal) url.searchParams.set('search', searchVal);
            else url.searchParams.delete('search');

            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }

        let lastFetchedData = null;

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
            document.getElementById('statusIndicator').innerText = 'Showing all records';
        }

        function openAttendanceDetails(employeeId, employeeName = '', filterStatus = null) {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const range = document.getElementById('quickRange').value;

            let url = `/payroll/attendance/employee-wise-details?employee_id=${employeeId}`;
            if (start) url += `&start_date=${start}`;
            if (end) url += `&end_date=${end}`;
            if (range) url += `&range=${encodeURIComponent(range)}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        lastFetchedData = data.data;
                        const dateLabel = document.getElementById('offcanvasDate');
                        if (dateLabel) dateLabel.innerText = data.employee_name || employeeName;

                        const statusLabel = document.getElementById('statusIndicator');
                        if (statusLabel) {
                            const rangeText = data.start_date && data.end_date
                                ? `${data.start_date} to ${data.end_date}`
                                : 'all records';
                            if (filterStatus) {
                                statusLabel.innerText = `Showing ${filterStatus.charAt(0).toUpperCase() + filterStatus.slice(1).replace('_', ' ')} (${rangeText})`;
                                document.getElementById('showAllBtn').style.display = 'inline-flex';
                            } else {
                                statusLabel.innerText = `Showing all days (${rangeText})`;
                                document.getElementById('showAllBtn').style.display = 'none';
                            }
                        }

                        renderTable(data.data, filterStatus);

                        const offcanvasEl = document.getElementById('attendanceDetailOffcanvas');
                        if (offcanvasEl) {
                            bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).show();
                        }
                    }
                })
                .catch(err => {
                    console.error('Error fetching details:', err);
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

        function matchesStatusFilter(item, filterStatus) {
            const key = (item.display_status_key || item.status || '').toLowerCase();

            switch (filterStatus) {
                case 'present':
                    return ['present', 'present_activity', 'late'].includes(key);
                case 'half_day':
                    return key === 'half_day' || key === 'half_day_leave';
                case 'leave':
                    return ['leave', 'half_day_leave', 'unpaid_leave'].includes(key);
                case 'absent':
                    return key === 'absent';
                case 'late':
                    return key === 'late';
                case 'wfh':
                    return key === 'wfh';
                case 'missing_punch':
                    return key === 'missing_punch';
                case 'unpaid_leave':
                    return ['unpaid_leave', 'unauthorised'].includes(key);
                case 'early_out':
                    return ['early_out', 'early_leave'].includes(key) || matchesEarlyOut(item);
                case 'overtime':
                    return matchesOvertime(item);
                case 'weekly_off':
                    return ['sunday', 'holiday'].includes(key);
                default:
                    return true;
            }
        }

        function renderTable(rows, filterStatus) {
            const body = document.getElementById('offcanvasTableBody');
            const cardsBody = document.getElementById('offcanvasCardsBody');
            body.innerHTML = '';
            cardsBody.innerHTML = '';

            let count = 0;
            rows.forEach((item) => {
                const match = !filterStatus || matchesStatusFilter(item, filterStatus);

                if (match) {
                    count++;
                    const dateDisplay = item.date || formatDate(item.attendance_date);
                    const statusDisplay = item.display_status || (item.status || '').replace(/_/g, ' ');
                    const badgeClass = item.display_status_class
                        ? displayClassToBadge(item.display_status_class)
                        : getStatusBadge(item.display_status_key || item.status);
                    const hoursDisplay = item.total_hours_label || formatHours(item.total_hours);
                    const activityBadge = item.is_activity
                        ? '<span class="badge bg-soft-primary text-primary ms-1">Activity</span>'
                        : '';

                    const adminActions = (isAdmin && item.id) ? `
                        <div class="attendance-row-actions">
                            <button type="button" class="zoho-icon-btn" onclick="editSingleAttendance(${item.id}, '${item.employee_id}')" title="Edit">
                                <i class="feather-edit"></i>
                            </button>
                            <button type="button" class="zoho-icon-btn" onclick="deleteSingleAttendance(${item.id})" title="Delete">
                                <i class="feather-trash-2"></i>
                            </button>
                        </div>` : '';

                    body.innerHTML += `
                        <tr>
                            <td class="text-muted fw-semibold">${count}</td>
                            <td class="fw-bold">${dateDisplay}</td>
                            <td class="text-center att-detail-time">${item.check_in ? formatTime(item.check_in) : '--'}</td>
                            <td class="text-center att-detail-time">${item.check_out ? formatTime(item.check_out) : '--'}</td>
                            <td class="text-center att-detail-hours">${hoursDisplay}</td>
                            <td class="text-center">
                                <span class="att-detail-status ${badgeClass}">${statusDisplay}</span>${activityBadge}
                            </td>
                            <td class="text-center">${adminActions}</td>
                        </tr>`;

                    cardsBody.innerHTML += `
                        <div class="att-detail-mobile-card">
                            <div class="att-detail-mobile-card-head">
                                <div>
                                    <div class="att-detail-mobile-label">Date</div>
                                    <div class="fw-bold text-dark">${dateDisplay}${activityBadge}</div>
                                </div>
                                <span class="att-detail-status ${badgeClass}">${statusDisplay}</span>
                            </div>
                            <div class="att-detail-mobile-stats">
                                <div class="text-center">
                                    <div class="att-detail-mobile-label">Check In</div>
                                    <div class="att-detail-time">${item.check_in ? formatTime(item.check_in) : '--'}</div>
                                </div>
                                <div class="text-center">
                                    <div class="att-detail-mobile-label">Check Out</div>
                                    <div class="att-detail-time">${item.check_out ? formatTime(item.check_out) : '--'}</div>
                                </div>
                                <div class="text-center">
                                    <div class="att-detail-mobile-label">Hours</div>
                                    <div class="att-detail-hours">${hoursDisplay}</div>
                                </div>
                            </div>
                            ${(isAdmin && item.id) ? `
                            <div class="att-detail-mobile-actions">
                                <button type="button" class="zoho-btn-outline" onclick="editSingleAttendance(${item.id}, '${item.employee_id}')">
                                    <i class="feather-edit"></i> Edit
                                </button>
                                <button type="button" class="zoho-btn-outline" onclick="deleteSingleAttendance(${item.id})">
                                    <i class="feather-trash-2"></i> Delete
                                </button>
                            </div>` : ''}
                        </div>`;
                }
            });

            if (filterStatus) {
                document.getElementById('showAllBtn').style.display = 'inline-flex';
                document.getElementById('statusIndicator').innerHTML = `Showing: <strong>${filterStatus.replace('_', ' ')}</strong> (${count} found)`;
            } else {
                document.getElementById('showAllBtn').style.display = 'none';
                document.getElementById('statusIndicator').innerText = `Showing all days (${rows.length} total)`;
            }
        }

        function formatDate(date) {
            if (!date) return '--';
            return new Date(date).toLocaleDateString('en-GB');
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
            const raw = time.includes(' ') ? time.split(' ')[1] : time;
            let [h, m] = raw.split(':');
            h = parseInt(h, 10);
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return `${h}:${m} ${ampm}`;
        }

        function getStatusBadge(status) {
            switch ((status || '').toLowerCase()) {
                case 'present':
                case 'holiday':
                case 'present_activity':
                case 'sunday':
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
                case 'activity':
                case 'early_leave':
                case 'early_out':
                case 'late':
                case 'wfh':
                    return 'att-detail-status--info';
                default:
                    return 'att-detail-status--info';
            }
        }

        function deleteSingleAttendance(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete this attendance record?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1070e0',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
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
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    }).then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                if (typeof Toast !== 'undefined') {
                                    Toast.fire({ icon: 'success', title: 'Record deleted' });
                                }
                                const employeeName = document.getElementById('offcanvasDate').innerText;
                                const employeeId = lastFetchedData[0]?.employee_id;
                                if (employeeId) {
                                    openAttendanceDetails(employeeId, employeeName);
                                }
                            }
                        });
                }
            });
        }

        function editSingleAttendance(id, employeeId) {
            window.location.href = `{{ url('/payroll/attendace/employee') }}/${employeeId}/edit?attendance_id=${id}`;
        }

        function exportAttendance() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const employeeId = document.getElementById('employee_id').value;
            const range = document.getElementById('quickRange').value;

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
                a.download = 'attendance.xlsx';
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => console.error('Export failed:', error));
        }
    </script>
@endpush
