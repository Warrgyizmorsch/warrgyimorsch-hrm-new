@extends('layouts.app')

@section('content')
    @php
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);
    @endphp
    <div class="container-fluid px-0" style="background: #f8fafc; min-height: 100vh; font-family: 'Inter', sans-serif;">
        <!-- Main Content Card -->
        <div class="px-2 px-md-4 pt-3 pt-md-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; background: white;">
                <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
                    style="border-radius: 12px 12px 0 0;">
                    <div class="d-flex flex-column">
                        <h5 class="fw-bold mb-0" style="color: #334155;">Attendance Management</h5>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="#"
                                        class="text-decoration-none text-muted small">Home</a></li>
                                <li class="breadcrumb-item active small fw-bold" style="color: #3858f9;"
                                    aria-current="page">Attendance List</li>
                            </ol>
                        </nav>
                    </div>
                    <div
                        class="d-flex flex-column flex-md-row align-items-center gap-2 ms-md-auto w-100 w-md-auto justify-content-end">
                        <!-- Premium Search Bar -->
                        <div class="flex-grow-1 flex-md-grow-0" style="min-width: 250px; max-width: 320px;">
                            <div class="search-group d-flex align-items-center px-3"
                                style="height: 40px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; transition: all 0.3s ease;">
                                <i data-feather="search" class="text-muted me-2" style="width: 16px; height: 16px;"></i>
                                <input type="text" id="tableSearch" onkeyup="searchTable()" placeholder="Search records..."
                                    style="border: none; background: transparent; outline: none; width: 100%; font-size: 13px; font-weight: 500; color: #334155;">
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('payroll.attendace.employee') }}"
                                    class="btn btn-soft-primary btn-sm fw-bold d-flex align-items-center px-3"
                                    style="height: 40px; border-radius: 10px; border: 1px solid rgba(56, 88, 249, 0.2) !important; white-space: nowrap;">
                                    <span class="d-none d-sm-inline">EMPLOYEE WISE</span>
                                    <i data-feather="users" class="ms-sm-2"></i>
                                </a>

                                @if($isAdmin)
                                    <div class="dropdown">
                                        <button
                                            class="btn btn-soft-secondary btn-sm fw-bold d-flex align-items-center px-3 dropdown-toggle"
                                            type="button" data-bs-toggle="dropdown"
                                            style="height: 40px; border-radius: 10px; border: 1px solid rgba(100, 116, 139, 0.2) !important;">
                                            <span class="d-none d-sm-inline">IMPORT</span>
                                            <i class="fas fa-upload ms-2"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-4 shadow-lg border-0"
                                            style="width: 320px; border-radius: 15px;">
                                            <form action="{{ route('payroll.attendance.import') }}" method="POST"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-dark mb-2">Import Excel/CSV
                                                        File</label>
                                                    <input type="file" class="form-control" name="import_file"
                                                        accept=".xlsx, .xls, .csv" required style="border-radius: 8px;">
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100 fw-bold py-2"
                                                    style="border-radius: 8px;">
                                                    UPLOAD & CALCULATE
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif

                                <div class="d-flex gap-1">
                                    <a href="javascript:void(0);" class="btn btn-icon btn-soft-info"
                                        onclick="exportAttendance()"
                                        style="height: 40px; width: 40px; border-radius: 10px; border: 1px solid rgba(13, 202, 240, 0.1) !important;">
                                        <i data-feather="download"></i>
                                    </a>
                                    @if($isAdmin)
                                        <a href="{{ route('payroll.attendance.add') }}" class="btn btn-icon btn-primary shadow-sm"
                                            style="height: 40px; width: 40px; border-radius: 10px;">
                                            <i data-feather="plus"></i>
                                        </a>
                                    @endif
                                </div>
                        </div>
                    </div>
                </div>

                <!-- Always Visible Filter Section -->
                <div id="filterSection">
                    <div class="card-body border-bottom bg-light bg-opacity-10 p-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-2">Quick Range</label>
                                <div class="dropdown">
                                    <button class="wghrm-custom-select-btn fw-bold dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" id="quickRangeBtn">
                                        @php
                                            $range = request('range');
                                            $label = 'All Time';
                                            if ($range == 'today')
                                                $label = 'Today';
                                            elseif ($range == 'yesterday')
                                                $label = 'Yesterday';
                                            elseif ($range == 'week')
                                                $label = 'This Week';
                                            elseif ($range == 'month')
                                                $label = 'This Month';
                                            elseif ($range == 'lastMonth')
                                                $label = 'Last Month';
                                            elseif ($range == '3months')
                                                $label = 'Last 3 Months';
                                            elseif ($range == '6months')
                                                $label = 'Last 6 Months';
                                            elseif ($range == '1year')
                                                $label = 'Last 1 Year';
                                            elseif ($range == 'custom')
                                                $label = 'Custom Date';
                                        @endphp
                                        {{ $label }}
                                    </button>
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input"
                                                placeholder="Search range..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ !$range ? 'active' : '' }}"
                                                href="javascript:void(0);" onclick="selectQuickRange('', 'All Time')">All
                                                Time</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'today' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('today', 'Today')">Today</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'yesterday' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('yesterday', 'Yesterday')">Yesterday</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'week' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('week', 'This Week')">This Week</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'month' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('month', 'This Month')">This Month</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'lastMonth' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('lastMonth', 'Last Month')">Last Month</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == '3months' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('3months', 'Last 3 Months')">Last 3 Months</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == '6months' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('6months', 'Last 6 Months')">Last 6 Months</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == '1year' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('1year', 'Last 1 Year')">Last 1 Year</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $range == 'custom' ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectQuickRange('custom', 'Custom Date')">Custom Date</a>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="quickRange" value="{{ request('range') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-2">Employee</label>
                                <div class="dropdown">
                                    <button class="wghrm-custom-select-btn fw-bold dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" id="employeeSelectBtn">
                                        @php
                                            $selectedEmpId = request('employee_id');
                                            $selectedEmp = $employees->firstWhere('id', $selectedEmpId);
                                        @endphp
                                        {{ $selectedEmp ? $selectedEmp->name : 'All Employees' }}
                                    </button>
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input"
                                                placeholder="Search employee..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ !$selectedEmpId ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="selectEmployee('', 'All Employees')">All Employees</a>
                                            @foreach ($employees as $emp)
                                                <a class="dropdown-item wghrm-custom-dropdown-item {{ $selectedEmpId == $emp->id ? 'active' : '' }}"
                                                    href="javascript:void(0);"
                                                    onclick="selectEmployee('{{ $emp->id }}', '{{ $emp->name }}')">
                                                    {{ $emp->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="selectedEmployeeId" value="{{ request('employee_id') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-2">Start Date</label>
                                <input type="date" id="startDate"
                                    class="form-control border-0 bg-white py-2 px-3 shadow-sm fw-bold"
                                    value="{{ request('start_date') }}" style="border-radius: 10px; height: 44px;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-2">End Date</label>
                                <input type="date" id="endDate"
                                    class="form-control border-0 bg-white py-2 px-3 shadow-sm fw-bold"
                                    value="{{ request('end_date') }}" style="border-radius: 10px; height: 44px;">
                            </div>
                            <div class="col-md-3 d-flex justify-content-around">
                                <button type="button"
                                    class="btn btn-primary w-50 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm"
                                    onclick="applyFilters()"
                                    style="background: #3858f9; border: none; height: 44px; border-radius: 10px;">
                                    <i data-feather="search"></i> APPLY
                                </button>
                                <a href="{{ route('payroll.attendance') }}"
                                    class="btn btn-soft-danger fw-bold d-flex align-items-center justify-content-center"
                                    style="border-radius: 8px; height: 44px; width: 80px; font-size: 13px;">
                                RESET</a>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($message = Session::get('success'))
                    <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center py-3" role="alert"
                        style="border-radius: 12px; background: #ecfdf5; color: #065f46;">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div class="fw-bold">{{ $message }}</div>
                        <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Desktop Table View -->
                <div class="d-none d-lg-block">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                                <tr>
                                    <th class="ps-4 py-3 text-muted small fw-bold text-uppercase"
                                        style="letter-spacing: 0.5px; width: 150px;">DATE</th>
                                    <th class="py-3 text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                        ATTENDANCE HISTORY</th>
                                    <th class="pe-4 py-3 text-muted small fw-bold text-uppercase text-end"
                                        style="width: 150px; letter-spacing: 0.5px;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendance as $att)
                                    <tr class="border-bottom hover-row">
                                        <td class="ps-4 py-4 text-dark fw-bold">
                                            {{ \Carbon\Carbon::parse($att->attendance_date)->format('d-m-Y') }}
                                        </td>
                                        <td>
                                            @php $date = \Carbon\Carbon::parse($att->attendance_date)->format('Y-m-d'); @endphp
                                            @if(request('employee_id'))
                                                <div class="d-flex align-items-center gap-3">
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
                                                    <span class="badge {{ $statusClass }} fw-bold text-uppercase px-3 py-2" style="font-size: 11px; border-radius: 6px;">{{ str_replace('_', ' ', $att->status) }}</span>
                                                    
                                                    <div class="d-flex align-items-center gap-4">
                                                        <div class="text-center">
                                                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Check In</div>
                                                            <div class="fw-bold text-dark" style="font-size: 13px;">{{ $att->check_in ? date('h:i A', strtotime($att->check_in)) : '--:--' }}</div>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Check Out</div>
                                                            <div class="fw-bold text-dark" style="font-size: 13px;">{{ $att->check_out ? date('h:i A', strtotime($att->check_out)) : '--:--' }}</div>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 9px;">Work Hours</div>
                                                            <div class="fw-bold text-primary" style="font-size: 13px;">{{ number_format($att->total_hours, 2) }} hrs</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="ref-badge badge-green clickable" title="View Present"
                                                        onclick="openAttendanceDetails('{{ $date }}', 'present')">
                                                        Present: <span class="fw-bold ms-1">{{ $att->present_count }}</span>
                                                    </div>
                                                    <div class="ref-badge badge-blue clickable" title="View Overtime"
                                                        onclick="openAttendanceDetails('{{ $date }}', 'overtime')">
                                                        Overtime: <span class="fw-bold ms-1">{{ $att->overtime_count }}</span>
                                                    </div>
                                                    <div class="ref-badge badge-yellow clickable" title="View Half Day"
                                                        onclick="openAttendanceDetails('{{ $date }}', 'half_day')">
                                                        Half Day: <span class="fw-bold ms-1">{{ $att->half_day_count }}</span>
                                                    </div>
                                                    <div class="ref-badge badge-red clickable" title="View Leave"
                                                        onclick="openAttendanceDetails('{{ $date }}', 'leave')">
                                                        Leave: <span class="fw-bold ms-1">{{ $att->leave_count }}</span>
                                                    </div>
                                                    <div class="ref-badge badge-red clickable" title="View Absent"
                                                        onclick="openAttendanceDetails('{{ $date }}', 'absent')">
                                                        Absent: <span class="fw-bold ms-1">{{ $att->absent_count }}</span>
                                                    </div>
                                                    <div class="ref-badge badge-purple clickable" title="View Present"
                                                        onclick="openAttendanceDetails('{{ $date }}', 'wfh')">
                                                        WFH: <span class="fw-bold ms-1">{{ $att->wfh_count }}</span>
                                                    </div>
                                                    <div class="ref-badge badge-purple clickable" title="View Present"
                                                        onclick="openAttendanceDetails('{{ $date }}', 'early_out')">
                                                        Early Out: <span class="fw-bold ms-1">{{ $att->early_count }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                @if($isAdmin)
                                                    <a href="{{ route('payroll.attendance.editByDate', \Carbon\Carbon::parse($att->attendance_date)->format('Y-m-d')) }} "
                                                        class="btn btn-icon btn-soft-primary rounded-circle" title="Edit"
                                                        style="width: 34px; height: 34px; border: 1px solid rgba(56, 88, 249, 0.1) !important;">
                                                        <i data-feather="edit" style="width: 14px; height: 14px;"></i>
                                                    </a>
                                                @endif
                                                <a href="javascript:void(0);"
                                                    class="btn btn-icon btn-soft-primary rounded-circle"
                                                    onclick="openAttendanceDetails('{{ $date }}')" title="View"
                                                    style="width: 34px; height: 34px; border: 1px solid rgba(56, 88, 249, 0.1) !important;">
                                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                                </a>
                                                @if($isAdmin)
                                                    <a href="javascript:void(0);"
                                                        class="btn btn-icon btn-soft-danger rounded-circle"
                                                        onclick="deleteAttendanceByDate('{{ $date }}')" title="Delete"
                                                        style="width: 34px; height: 34px; border: 1px solid rgba(239, 68, 68, 0.1) !important;">
                                                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5">
                                            <div class="py-5">
                                                <i class="bi bi-calendar-x text-muted"
                                                    style="font-size: 3rem; opacity: 0.2;"></i>
                                                <p class="text-muted mt-3 fw-bold">No Attendance Records Found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
@endsection

@push('modals')
    <div class="offcanvas offcanvas-end" tabindex="-1" id="attendanceDetailOffcanvas" style="width: 900px;">
        <div class="offcanvas-header border-bottom px-4 py-3 bg-white shadow-sm">
            <div class="d-flex flex-column">
                <h5 class="offcanvas-title fw-bold" style="color: #334155;">Record for <span id="offcanvasDate"
                        class="text-primary"></span></h5>
                <div id="statusIndicator" class="small fw-bold text-muted mt-1">Showing All Records</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-link text-decoration-none fw-bold small p-0 me-3" id="showAllBtn"
                    style="display:none;" onclick="resetModalFilter()">Show All</button>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
            </div>
        </div>
        <div class="offcanvas-body p-0">
            <!-- Desktop View -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 small fw-bold text-muted text-uppercase" style="width: 80px;">SR. NO.
                                </th>
                                <th class="py-3 small fw-bold text-muted text-uppercase">EMPLOYEE NAME</th>
                                <th class="py-3 small fw-bold text-muted text-uppercase text-center">CHECK IN</th>
                                <th class="py-3 small fw-bold text-muted text-uppercase text-center">CHECK OUT</th>
                                <th class="py-3 small fw-bold text-muted text-uppercase text-center">Working Hrs</th>
                                <th class="py-3 small fw-bold text-muted text-uppercase text-center">STATUS</th>
                                <th class="pe-4 py-3 small fw-bold text-muted text-uppercase text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="offcanvasTableBody"></tbody>
                    </table>
                </div>
            </div>
            <!-- Mobile View -->
            <div class="d-md-none p-3" id="offcanvasCardsBody">
                <!-- Cards will be injected here via JS -->
            </div>
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

        function searchTable() {
            const filter = document.getElementById('tableSearch').value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr.hover-row');
            const cards = document.querySelectorAll('.mobile-card-wrapper');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });

            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(filter) ? '' : 'none';
            });
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

            let url = new URL(window.location.href);
            if (start) url.searchParams.set('start_date', start);
            if (end) url.searchParams.set('end_date', end);
            if (range) url.searchParams.set('range', range);
            if (empId) url.searchParams.set('employee_id', empId);
            else url.searchParams.delete('employee_id');
            
            window.location.href = url.toString();
        }

        let lastFetchedData = null;
        let lastDate = null;

        function resetModalFilter() {
            if (lastFetchedData) renderTable(lastFetchedData, null);
            document.getElementById('showAllBtn').style.display = 'none';
            document.getElementById('statusIndicator').innerText = 'Showing All Records';
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
                        if (dateLabel) dateLabel.innerText = date;

                        const statusLabel = document.getElementById('statusIndicator');
                        if (statusLabel) {
                            if (filterStatus) {
                                statusLabel.innerText = `Showing ${filterStatus.charAt(0).toUpperCase() + filterStatus.slice(1).replace('_', ' ')} Records`;
                                document.getElementById('showAllBtn').style.display = 'inline-block';
                            } else {
                                statusLabel.innerText = 'Showing All Records';
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

        function renderTable(rows, filterStatus, isActivityDay = false) {
            const body = document.getElementById('offcanvasTableBody');
            const cardsBody = document.getElementById('offcanvasCardsBody');
            body.innerHTML = '';
            cardsBody.innerHTML = '';

            let count = 0;
            rows.forEach((item, index) => {
                let match = !filterStatus;
                const isHoliday = !!item.is_holiday;
                if (filterStatus === 'present' && (item.status === 'present' || (isHoliday && item.status === 'absent'))) match = true;
                if (filterStatus === 'half_day' && item.status === 'half_day') match = true;
                if (filterStatus === 'leave' && item.status === 'leave') match = true;
                if (filterStatus === 'absent' && item.status === 'absent' && !isHoliday) match = true;
                if (filterStatus === 'late' && item.status === 'late') match = true;
                if (filterStatus === 'overtime' && item.total_hours > 9.30) match = true;
                if (filterStatus === 'wfh' && item.status === 'wfh') match = true;
                if (filterStatus === 'early_out' && item.check_out) {
                    const checkOutTime = item.check_out.includes(' ') ? item.check_out.split(' ')[1] : item.check_out;
                    if (checkOutTime >= '15:00' && checkOutTime < '17:30') {
                        match = true;
                    }
                }

                if (match) {
                    count++;
                    let statusDisplay = item.status;
                    let badgeClass = getStatusBadge(item.status);

                    let isEarly = false;
                    let isHalfDayPunch = false;

                    if (item.check_out) {
                        const checkOutTime = item.check_out.includes(' ') ? item.check_out.split(' ')[1] : item.check_out;
                        if (checkOutTime < '15:00') {
                            isHalfDayPunch = true;
                        } else if (checkOutTime < '17:30') {
                            isEarly = true;
                        }
                    }

                    if (isHoliday && item.status === 'absent') {
                        statusDisplay = 'Holiday';
                        badgeClass = 'status-badge-success';
                    } else if (isActivityDay && (isEarly || item.status === 'early_out' || item.status === 'early_leave' || (item.status === 'half_day' && !isHalfDayPunch))) {
                        statusDisplay = 'Present Activity';
                        badgeClass = 'status-badge-info';
                    } else if (isEarly) {
                        statusDisplay = 'Early Out';
                        badgeClass = 'status-badge-info';
                    } else if (isHalfDayPunch || item.status === 'half_day') {
                        statusDisplay = 'Half Day';
                        badgeClass = 'status-badge-warning';
                    } else {
                        statusDisplay = item.status.charAt(0).toUpperCase() + item.status.slice(1).replace('_', ' ');
                        if (statusDisplay === 'Early out' || statusDisplay === 'Early leave') statusDisplay = 'Early Out';
                        badgeClass = getStatusBadge(item.status);
                    }

                    // Desktop Row
                    body.innerHTML += `
                                <tr class="border-bottom">
                                    <td class="ps-4 py-3 text-muted fw-bold">${index + 1}</td>
                                    <td class="fw-bold text-dark">${item.employee.name}</td>
                                    <td class="text-center">${item.check_in ? formatTime(item.check_in) : '--'}</td>
                                    <td class="text-center">${item.check_out ? formatTime(item.check_out) : '--'}</td>
                                   <td class="text-center">${formatHours(item.total_hours)}</td>
                                    <td class="text-center">
                                        <span class="status-badge ${badgeClass}">${statusDisplay}</span>
                                    </td>
                                    <td class="pe-4 text-center d-flex justify-content-center">
                                        @if($isAdmin)
                                            <button class="btn btn-sm text-primary shadow-none" onclick="editSingleAttendance(${item.id})">
                                                <i data-feather="edit" style="width:14px; height:14px;"></i>
                                            </button>
                                            <button class="btn btn-sm text-danger shadow-none" onclick="deleteSingleAttendance(${item.id}, '${lastDate}')">
                                                <i data-feather="trash-2" style="width:14px; height:14px;"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            `;

                    // Mobile Card
                    cardsBody.innerHTML += `
                            <div class="mobile-attendance-card p-3 shadow-sm border mb-3" style="border-radius: 12px; background: #fff;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="d-block small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px;">Employee</span>
                                        <span class="fw-bold text-dark">${item.employee.name}</span>
                                    </div>
                                    <span class="status-badge ${badgeClass}">${statusDisplay}</span>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-4 text-center">
                                        <span class="d-block small text-muted">In</span>
                                        <span class="fw-bold small text-dark">${item.check_in ? formatTime(item.check_in) : '--'}</span>
                                    </div>
                                    <div class="col-4 text-center border-start border-end">
                                        <span class="d-block small text-muted">Out</span>
                                        <span class="fw-bold small text-dark">${item.check_out ? formatTime(item.check_out) : '--'}</span>
                                    </div>
                                    <div class="col-4 text-center">
                                        <span class="d-block small text-muted">Hrs</span>
                                        <span class="fw-bold small text-dark">${formatHours(item.total_hours)}</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    @if($isAdmin)
                                        <button class="btn btn-sm btn-soft-primary px-3 rounded-pill" onclick="editSingleAttendance(${item.id}, '${lastDate}')">
                                            <i class="feather-edit me-1"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-soft-danger px-3 rounded-pill" onclick="deleteSingleAttendance(${item.id}, '${lastDate}')">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        `;
                }
            });

            if (filterStatus) {
                document.getElementById('showAllBtn').style.display = 'block';
                document.getElementById('statusIndicator').innerHTML = `Showing: <span class="text-primary text-uppercase">${filterStatus}</span> (${count} found)`;
            } else {
                document.getElementById('showAllBtn').style.display = 'none';
                document.getElementById('statusIndicator').innerText = 'Showing All Records';
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
            switch (status.toLowerCase()) {
                case 'present': return 'status-badge-success';
                case 'holiday': return 'status-badge-success';
                case 'absent': return 'status-badge-danger';
                case 'half_day': return 'status-badge-warning';
                case 'activity': return 'status-badge-info';
                case 'early_leave': return 'status-badge-info';
                case 'early_out': return 'status-badge-info';
                default: return 'status-badge-info';
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

         function exportAttendance() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const employeeId = document.getElementById('selectedEmployeeId')?.value || '';

            fetch('{{ route("payroll.attendance.export") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    start_date: start,
                    end_date: end,
                    employee_id: employeeId
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
