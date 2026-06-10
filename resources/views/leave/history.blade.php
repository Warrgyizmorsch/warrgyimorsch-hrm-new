@extends('layouts.app')

@section('content')
    <!-- Feather Icons CDN for redundancy -->
    <script src="https://unpkg.com/feather-icons"></script>
    @php
        $selectedCategoryLabel = match (request('category')) {
            'Gatepass Leave' => 'Early Leave',
            'wfh' => 'WFH',
            default => request('category'),
        };

        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);
    @endphp

    <div class="container-fluid px-0" style="background: #f8fafc; min-height: 100vh; font-family: 'Inter', sans-serif;">
        <!-- Top Header -->
        <div class="px-4 py-3 bg-white border-bottom shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #334155;">Leave History</h5>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="#"
                                        class="text-decoration-none text-muted small">Home</a></li>
                                <li class="breadcrumb-item active small fw-bold" style="color: #3858f9;"
                                    aria-current="page">History</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="d-flex align-items-center pe-2 gap-2">
                    <a href="{{ route('leave.export', request()->all()) }}"
                        class="btn btn-light-brand text-muted fw-bold small d-flex align-items-center px-4 shadow-sm"
                        style="border-radius: 10px; height: 42px; border: 1.5px solid #e2e8f0; background: #fff; text-decoration: none;">
                        <i data-feather="download" style="width: 16px; height: 16px;" class="me-2"></i> Export
                    </a>
                    <button class="btn btn-primary fw-bold small d-flex align-items-center px-4 shadow-sm"
                        data-bs-toggle="offcanvas" data-bs-target="#applyLeaveModal"
                        style="border-radius: 10px; height: 42px; background: #3858f9; border: none;">
                        <i data-feather="plus" style="width: 16px; height: 16px;" class="me-2"></i> Apply For Leave
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="px-4 mb-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 12px;">
                <form action="{{ route('leave.history') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase"
                            style="letter-spacing: 0.5px;">Search Employee</label>
                        <div class="wghrm-search-dropdown" id="filterEmployeeDropdown">
                            <div class="wghrm-dropdown-trigger" style="height: 44px; border-radius: 8px; background: #f8fafc !important; border: none !important;">
                                <span class="wghrm-trigger-text fw-bold text-dark">{{ request('search') ?: 'Search Employee...' }}</span>
                                <i data-feather="chevron-down" style="width: 16px; height: 16px;"></i>
                            </div>
                            <div class="wghrm-dropdown-menu">
                                <div class="wghrm-search-container">
                                    <i data-feather="search" class="wghrm-search-icon"></i>
                                    <input type="text" class="wghrm-search-input" placeholder="Type name...">
                                </div>
                                <div class="wghrm-items-list">
                                    <div class="wghrm-item {{ !request('search') ? 'selected' : '' }}" data-value="" data-text="All Employees">
                                        <span class="wghrm-item-text">All Employees</span>
                                        <i data-feather="check" class="wghrm-item-check" style="width: 14px; height: 14px;"></i>
                                    </div>
                                    @foreach($employees as $emp)
                                        <div class="wghrm-item {{ request('search') == $emp->name ? 'selected' : '' }}" data-value="{{ $emp->name }}" data-text="{{ $emp->name }}">
                                            <span class="wghrm-item-text">{{ $emp->name }}</span>
                                            <i data-feather="check" class="wghrm-item-check" style="width: 14px; height: 14px;"></i>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase"
                            style="letter-spacing: 0.5px;">Category</label>
                        <div class="wghrm-search-dropdown" id="filterCategoryDropdown">
                            <div class="wghrm-dropdown-trigger" style="height: 44px; border-radius: 8px; background: #f8fafc !important; border: none !important;">
                                <span class="wghrm-trigger-text fw-bold text-dark">{{ $selectedCategoryLabel ?: 'All Categories' }}</span>
                                <i data-feather="chevron-down" style="width: 16px; height: 16px;"></i>
                            </div>
                            <div class="wghrm-dropdown-menu">
                                <div class="wghrm-items-list">
                                    <div class="wghrm-item {{ !request('category') ? 'selected' : '' }}" data-value="" data-text="All Categories">
                                        <span class="wghrm-item-text">All Categories</span>
                                        <i data-feather="check" class="wghrm-item-check" style="width: 14px; height: 14px;"></i>
                                    </div>
                                    @foreach([
                                        'Paid Leave' => 'Paid Leave',
                                        'Sick Leave' => 'Sick Leave',
                                        'Casual Leave' => 'Casual Leave',
                                        'Gatepass Leave' => 'Early Leave',
                                        'wfh' => 'WFH',
                                    ] as $catValue => $catLabel)
                                        <div class="wghrm-item {{ request('category') == $catValue ? 'selected' : '' }}" data-value="{{ $catValue }}" data-text="{{ $catLabel }}">
                                            <span class="wghrm-item-text">{{ $catLabel }}</span>
                                            <i data-feather="check" class="wghrm-item-check" style="width: 14px; height: 14px;"></i>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase"
                            style="letter-spacing: 0.5px;">Status</label>
                        <div class="wghrm-search-dropdown" id="filterStatusDropdown">
                            <div class="wghrm-dropdown-trigger" style="height: 44px; border-radius: 8px; background: #f8fafc !important; border: none !important;">
                                <span class="wghrm-trigger-text fw-bold text-dark text-capitalize">{{ request('status') ?: 'All Status' }}</span>
                                <i data-feather="chevron-down" style="width: 16px; height: 16px;"></i>
                            </div>
                            <div class="wghrm-dropdown-menu">
                                <div class="wghrm-items-list">
                                    <div class="wghrm-item {{ !request('status') ? 'selected' : '' }}" data-value="" data-text="All Status">
                                        <span class="wghrm-item-text">All Status</span>
                                        <i data-feather="check" class="wghrm-item-check" style="width: 14px; height: 14px;"></i>
                                    </div>
                                    @foreach(['pending', 'approved', 'on_hold', 'rejected', 'unauthorised'] as $stat)
                                        <div class="wghrm-item {{ request('status') == $stat ? 'selected' : '' }}" data-value="{{ $stat }}" data-text="{{ ucfirst(str_replace('_', ' ', $stat)) }}">
                                            <span class="wghrm-item-text text-capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                            <i data-feather="check" class="wghrm-item-check" style="width: 14px; height: 14px;"></i>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        </div>
                    </div>
                    <div class="col-md-1.5" style="flex: 1;">
                        <label class="form-label small fw-bold text-muted text-uppercase"
                            style="letter-spacing: 0.5px;">From</label>
                        <input type="date" name="from_date" class="form-control border-0 bg-light px-2"
                            value="{{ request('from_date') }}" style="border-radius: 8px; height: 44px;">
                    </div>
                    <div class="col-md-1.5" style="flex: 1;">
                        <label class="form-label small fw-bold text-muted text-uppercase"
                            style="letter-spacing: 0.5px;">Upto</label>
                        <input type="date" name="to_date" class="form-control border-0 bg-light px-2"
                            value="{{ request('to_date') }}" style="border-radius: 8px; height: 44px;">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary fw-bold flex-grow-1"
                            style="border-radius: 8px; height: 44px; background: #3858f9; border: none;">SEARCH</button>
                        <a href="{{ route('leave.history') }}"
                            class="btn btn-soft-danger fw-bold d-flex align-items-center justify-content-center"
                            style="border-radius: 8px; height: 44px; width: 80px; font-size: 13px;">RESET</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="px-4 pb-5">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-0">
                    <!-- SHOW ENTRIES -->
                    <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2">
                        <span class="text-muted small fw-bold text-uppercase">Show</span>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" id="showEntriesBtn"
                                style="width: 80px; height: 44px; padding: 0 15px;">
                                {{ $perPage ?? 20 }}
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 12px;">
                                <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 20) == 20 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}">20</a>
                                <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 50) == 50 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}">50</a>
                                <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 100) == 100 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}">100</a>
                            </div>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase">entries</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="small text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                    <th class="ps-4">Sr.No.</th>
                                    <th>Employee</th>
                                    <th class="text-center">Status</th>
                                    <th>Leave Type</th>
                                    <th>Category</th>
                                    <th>Duration</th>
                                    <th>Total Days</th>
                                    <!-- <th>Leave Reason</th> -->
                                    <th class="text-center pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaves as $key => $leave)
                                    <tr class="border-bottom">
                                        <td class="ps-4 fw-semibold text-muted">{{ $leaves->firstItem() + $key }}</td>
                                        <td>
                                            <a href="javascript:void(0);" class="fw-bold text-primary text-decoration-none"
                                                onclick="openViewModal({{ $leave->id }})">
                                                {{ $leave->employee->name }}
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusClass = [
                                                    'pending' => 'bg-soft-dark text-dark',
                                                    'approved' => 'bg-soft-primary text-primary',
                                                    'on_hold' => 'bg-soft-warning text-warning',
                                                    'rejected' => 'bg-soft-danger text-danger',
                                                    'unauthorised' => 'bg-soft-danger text-danger',
                                                ][$leave->status] ?? 'bg-soft-secondary text-secondary';
                                            @endphp
                                            <span class="badge {{ $statusClass }} px-3 rounded-pill fw-bold text-capitalize"
                                                style="font-size: 11px; min-width: 80px;">
                                                {{ str_replace('_', ' ', $leave->status) }}
                                            </span>
                                        </td>
                                        <td><span class="fw-semibold text-dark">{{ $leave->leave_type == 'Gatepass Leave' ? 'Early leave' : $leave->leave_type }}</span></td>
                                        <td>
                                            @php
                                                $catRaw = strtolower(trim($leave->leave_category));
                                                $catClass = 'bg-soft-primary text-primary';
                                                
                                                if (str_contains($catRaw, 'half')) {
                                                    $catDisplay = 'HALF DAY';
                                                    $catClass = 'bg-soft-warning text-warning';
                                                    if (str_contains($catRaw, 'first'))
                                                        $catDisplay .= ' (FIRST)';
                                                    else if (str_contains($catRaw, 'second'))
                                                        $catDisplay .= ' (SECOND)';
                                                } elseif ($catRaw === 'full' || $catRaw === 'full day') {
                                                    $catDisplay = 'FULL DAY';
                                                    $catClass = 'bg-soft-primary text-primary';
                                                } elseif (str_contains($catRaw, 'gatepass')) {
                                                    $catDisplay = 'Early Leave';
                                                    $catClass = 'bg-soft-info text-info';
                                                } elseif (str_contains($catRaw, 'wfh')) {
                                                    $catDisplay = 'WFH';
                                                    $catClass = 'bg-soft-success text-success';
                                                } else {
                                                    $catDisplay = strtoupper($catRaw);
                                                }
                                            @endphp
                                            <span class="badge {{ $catClass }} px-3 rounded-pill fw-bold"
                                                style="font-size: 11px;">{{ $catDisplay }}</span>
                                        </td>
                                        <td class="small text-muted">
                                            <div class="fw-bold text-dark">
                                                {{ $leave->start_date->format('d-M-Y') }}
                                            </div>
                                            @if(str_contains(strtolower($leave->leave_category), 'gatepass') && $leave->start_time)
                                                <span class="badge bg-soft-info text-info p-1 px-2 mt-1" style="font-size: 9px;">
                                                    <i data-feather="clock" style="width: 10px; height: 10px;"></i>
                                                    {{ Carbon\Carbon::parse($leave->start_time)->format('h:i A') }} -
                                                    {{ $leave->end_time ? Carbon\Carbon::parse($leave->end_time)->format('h:i A') : 'N/A' }}
                                                </span>
                                            @elseif($leave->end_date && $leave->end_date->gt($leave->start_date))
                                                <span style="font-size: 10px;">Upto {{ $leave->end_date->format('d-M-Y') }}</span>
                                            @endif
                                        </td>
                                        <!-- <td class="small text-dark fw-semibold">{{ $leave->reason }}</td> -->
                                        <td class="small text-dark fw-semibold">
                                            @php
                                                $totalDays = (float) ($leave->total_days ?? 0);
                                            @endphp
                                            @if(str_contains(strtolower($leave->leave_category), 'gatepass'))
                                                1 Hour
                                            @elseif(str_contains(strtolower($leave->leave_type ?? ''), 'half'))
                                                0.5 Day
                                            @else
                                                {{ rtrim(rtrim(number_format($totalDays, 2, '.', ''), '0'), '.') }} Day{{ $totalDays == 1 ? '' : 's' }}
                                            @endif
                                        </td>
                                        <td class="text-center pe-4">
                                            <div class="hstack gap-2 justify-content-center">
                                                <button class="btn btn-icon btn-soft-info"
                                                    onclick="openViewModal({{ $leave->id }})" title="View Details">
                                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                                </button>
                                                @if($isAdmin)
                                                    <button class="btn btn-icon btn-soft-primary"
                                                        onclick="openActionModal({{ $leave->id }})" title="Take Action">
                                                        <i data-feather="edit-3" style="width: 14px; height: 14px;"></i>
                                                    </button>
                                                    <button class="btn btn-icon btn-soft-danger"
                                                        onclick="deleteApplication({{ $leave->id }})" title="Delete">
                                                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">No leave applications found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($leaves->hasPages())
                    <div class="card-footer bg-white border-top px-4 py-3 leave-history-pagination">
                        <div class="d-flex justify-content-between align-items-center gap-4 flex-wrap">
                            <span class="text-muted small fw-bold">
                                Showing {{ $leaves->firstItem() }} to {{ $leaves->lastItem() }} of {{ $leaves->total() }} results
                            </span>
                            <div class="{{ $leaves->lastPage() <= 1 ? 'd-none' : '' }}">
                                {{ $leaves->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- APPLY LEAVE MODAL -->
    <div class="offcanvas offcanvas-end custom-side-modal" tabindex="-1" id="applyLeaveModal" style="width: 650px;">
        <div class="offcanvas-header bg-white border-bottom p-4">
            <h5 class="offcanvas-title fw-bold" id="applyLeaveModalLabel">Apply For Leave</h5>
            <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4 bg-light bg-opacity-10">
            <form id="applyLeaveForm">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Employee <span
                                class="text-danger">*</span></label>
                        <div class="wghrm-search-dropdown" id="applyEmployeeDropdown">
                            <div class="wghrm-dropdown-trigger" style="height: 48px; border-radius: 10px; background: white !important; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;">
                                <span class="wghrm-trigger-text fw-bold text-dark">Select Employee</span>
                                <i data-feather="chevron-down" style="width: 16px; height: 16px;"></i>
                            </div>
                            <div class="wghrm-dropdown-menu">
                                <div class="wghrm-search-container">
                                    <i data-feather="search" class="wghrm-search-icon"></i>
                                    <input type="text" class="wghrm-search-input" placeholder="Search employee...">
                                </div>
                                <div class="wghrm-items-list">
                                    @foreach($employees as $emp)
                                        <div class="wghrm-item" data-value="{{ $emp->id }}" data-text="{{ $emp->name }}">
                                            <span class="wghrm-item-text">{{ $emp->name }}</span>
                                            <i data-feather="check" class="wghrm-item-check" style="width: 14px; height: 14px;"></i>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="employee_id" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Today's Date</label>
                        <input type="text" class="form-control border-0 bg-light shadow-sm" value="{{ date('d-m-Y') }}"
                            style="height: 48px; border-radius: 10px;" readonly>
                    </div>

                    <div class="col-md-6" id="leaveTypeWrapper">
                        <label class="form-label small fw-bold text-muted text-uppercase"
                            style="letter-spacing: 0.5px;">
                            Leave Category <span class="text-danger">*</span>
                        </label>

                        <div class="wghrm-search-dropdown" id="leaveCategoryDropdown">
                            <!-- Trigger -->
                            <div class="wghrm-dropdown-trigger"
                                style="height: 48px; border-radius: 10px; background: #f8fafc !important; border: none !important;">
                                
                                <span class="wghrm-trigger-text fw-bold text-dark">
                                    Select Type...
                                </span>

                                <i data-feather="chevron-down"
                                    style="width: 16px; height: 16px;"></i>
                            </div>

                            <!-- Dropdown Menu -->
                            <div class="wghrm-dropdown-menu">
                                <div class="wghrm-items-list">

                                    <div class="wghrm-item" data-value="Paid Leave" data-text="Paid Leave">
                                        <span class="wghrm-item-text">Paid Leave</span>
                                        <i data-feather="check" class="wghrm-item-check"
                                            style="width: 14px; height: 14px;"></i>
                                    </div>

                                    <div class="wghrm-item" data-value="Sick Leave" data-text="Sick Leave">
                                        <span class="wghrm-item-text">Sick Leave</span>
                                        <i data-feather="check" class="wghrm-item-check"
                                            style="width: 14px; height: 14px;"></i>
                                    </div>

                                    <div class="wghrm-item" data-value="Gatepass Leave" data-text="Early Leave">
                                        <span class="wghrm-item-text">Early Leave</span>
                                        <i data-feather="check" class="wghrm-item-check"
                                            style="width: 14px; height: 14px;"></i>
                                    </div>

                                    <div class="wghrm-item" data-value="Casual Leave" data-text="Casual Leave">
                                        <span class="wghrm-item-text">Casual Leave</span>
                                        <i data-feather="check" class="wghrm-item-check"
                                            style="width: 14px; height: 14px;"></i>
                                    </div>

                                    <div class="wghrm-item" data-value="wfh" data-text="WFH">
                                        <span class="wghrm-item-text">WFH</span>
                                        <i data-feather="check" class="wghrm-item-check"
                                            style="width: 14px; height: 14px;"></i>
                                    </div>

                                </div>
                            </div>

                            <!-- Hidden Input -->
                            <input type="hidden" name="leave_category" id="leaveCategory" required>
                        </div>
                    </div>

                    <div class="col-md-6" id="leaveCategoryWrapper">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">Leave Type <span class="text-danger">*</span></label>
                        <div class="category-grid">
                            <input type="radio" name="leave_type" value="Full Day" id="catFull" checked onchange="toggleCategoryFields()">
                            <label for="catFull" class="category-tile">
                                <!-- <i data-feather="sun"></i> -->
                                <span>Full Day</span>
                            </label>

                            <input type="radio" name="leave_type" value="Half Day" id="catHalf" onchange="toggleCategoryFields()">
                            <label for="catHalf" class="category-tile">
                                <!-- <i data-feather="clock"></i> -->
                                <span>Half Day</span>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Start Date <span
                                class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="date" name="start_date" id="startDate" class="form-control border-0 bg-white shadow-sm"
                                style="height: 48px; border-radius: 10px; padding-right: 40px;" required onchange="calculateDays()">
                        </div>
                    </div>

                    <div class="col-md-4" id="endDateWrapper">
                        <label class="form-label small fw-bold text-muted text-uppercase">End Date</label>
                        <div class="position-relative">
                            <input type="date" name="end_date" id="endDate" class="form-control border-0 bg-white shadow-sm"
                                style="height: 48px; border-radius: 10px; padding-right: 40px;" onchange="calculateDays()">
                        </div>
                    </div>

                    <div class="col-md-4" id="halfDayOptionWrapper" style="display: none;">
                        <label class="form-label small fw-bold text-muted text-uppercase">Which Half? <span
                                class="text-danger">*</span></label>
                        <select name="half_day_type" class="form-select border-0 bg-white shadow-sm"
                            style="height: 48px; border-radius: 10px;">
                            <option value="First Half">First Half</option>
                            <option value="Second Half">Second Half</option>
                        </select>
                    </div>

                    <div class="col-md-4" id="startTimeWrapper" style="display: none;">
                        <label class="form-label small fw-bold text-muted text-uppercase">Start Time <span
                                class="text-danger">*</span></label>
                        <input type="time" name="start_time" id="startTime" class="form-control border-0 bg-white shadow-sm"
                            style="height: 48px; border-radius: 10px;" onchange="calculateDays()">
                    </div>

                    <div class="col-md-4" id="endTimeWrapper" style="display: none;">
                        <!-- <label class="form-label small fw-bold text-muted text-uppercase">End Time (Auto)</label> -->
                        <input type="hidden" name="end_time" id="endTime"
                            class="form-control border-0 bg-light shadow-sm text-muted fw-bold"
                            style="height: 48px; border-radius: 10px;" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Total Days/Duration</label>
                        <input type="text" name="total_days" id="totalDays"
                            class="form-control border-0 bg-light shadow-sm fw-bold text-primary" value="1"
                            style="height: 48px; border-radius: 10px;" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted text-uppercase">Reason <span
                                class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control border-0 bg-white shadow-sm"
                            placeholder="Enter short reason" style="height: 48px; border-radius: 10px;" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted text-uppercase">Message (Optional)</label>
                        <textarea name="message" class="form-control border-0 bg-white shadow-sm"
                            placeholder="Enter detailed message" style="height: 100px; border-radius: 10px;"></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm"
                            style="height: 50px; border-radius: 12px; background: #16a34a; border: none;">APPLY NOW</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- LEAVE ACTION MODAL -->
    <div class="offcanvas offcanvas-end custom-side-modal" tabindex="-1" id="leaveActionModal" style="width: 600px;">
        <div class="offcanvas-header bg-white border-bottom p-4">
            <h5 class="offcanvas-title fw-bold" id="leaveActionModalLabel">Leave Action Page</h5>
            <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4 bg-light bg-opacity-10">
            <div id="actionModalContent">
                <div class="mb-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex">
                        <div class="bg-soft-primary p-2 rounded-3 me-3">
                            <i data-feather="file-text" class="text-primary"></i>
                        </div>
                        <div>
                            <span class="small fw-bold text-muted text-uppercase d-block">Application ID</span>
                            <span id="displayAppCode" class="fw-bold text-dark fs-5">-</span>
                        </div>
                    </div>
                    <div class="me-3">
                        <span class="small fw-bold text-muted text-uppercase d-block mb-2">Balance</span>
                        <span id="displayBalanceBadge" class="badge rounded-pill px-2 py-1 bg-soft-primary text-primary fw-bold" style="font-size: 16px;">
                            -
                        </span>
                    </div>
                </div>

                <form id="actionForm">
                    @csrf
                    <input type="hidden" name="leave_id" id="actionLeaveId">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Set Status</label>
                        <select name="status" class="form-select border-0 bg-white shadow-sm"
                            style="height: 55px; border-radius: 12px; font-weight: 600;">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="on_hold">On Hold</option>
                            <option value="rejected">Rejected</option>
                            <option value="unpaid">Unpaid Leave</option>
                            <option value="unauthorised">Unauthorised</option>
                        </select>
                    </div>

                    <div class="mt-5">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm"
                            style="height: 55px; border-radius: 12px; background: #37a760ff; border: none;">Update
                            Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RESTORED TABBED VIEW PORTFOLIO MODAL -->
    <div class="offcanvas offcanvas-end custom-side-modal" tabindex="-1" id="viewLeaveModal" style="width: 800px;">
        <div class="offcanvas-header bg-white border-bottom p-4">
            <div class="d-flex align-items-center w-100">
                <div class="avatar-text bg-soft-primary text-primary fw-bold me-3 shadow-sm"
                    style="width: 50px; height: 50px; font-size: 20px;">
                    <span id="viewAvatarLetter">E</span>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-0 text-dark" id="viewEmployeeName">Employee Name</h5>
                    <span class="text-muted small fw-bold text-uppercase d-block mt-1"
                        style="letter-spacing: 0.5px;">Employee Portfolio</span>
                </div>
                <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
        </div>
        <div class="offcanvas-body p-0 bg-light bg-opacity-25">
            <!-- Tabs -->
            <ul class="nav nav-tabs nav-tabs-custom px-4 bg-white border-bottom shadow-sm" id="viewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-uppercase py-3" id="details-tab" data-bs-toggle="tab"
                        data-bs-target="#detailsContent" type="button" role="tab"
                        style="font-size: 11px; letter-spacing: 0.5px;">Current Application</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-uppercase py-3 text-primary" id="history-tab" data-bs-toggle="tab"
                        data-bs-target="#historyContent" type="button" role="tab"
                        style="font-size: 11px; letter-spacing: 0.5px;">Full History & Future</button>
                </li>
            </ul>

            <div class="tab-content" id="viewTabsContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active p-4" id="detailsContent" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-white p-3 rounded-4 shadow-sm border h-100">
                                <label class="small fw-bold text-muted text-uppercase d-block mb-1">Leave Info</label>
                                <div class="fw-bold text-dark" id="viewLeaveType">-</div>
                                <div class="hstack gap-2 mt-1">
                                    <span id="viewCategoryBadge"
                                        class="badge bg-soft-primary text-primary border border-primary border-opacity-10">CAT</span>
                                    <span id="viewStatusBadge" class="badge rounded-pill fw-bold text-uppercase"
                                        style="font-size: 10px;">STATUS</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-white p-3 rounded-4 shadow-sm border h-100">
                                <label class="small fw-bold text-muted text-uppercase d-block mb-1">Duration & Days</label>
                                <div id="viewTotalDays" class="fw-bold text-primary fs-5">-</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-white p-3 rounded-4 shadow-sm border">
                                <label class="small fw-bold text-muted text-uppercase d-block mb-1">Timeframe</label>
                                <div class="row align-items-center">
                                    <div class="col-5 border-end">
                                        <div class="small text-muted">From Date</div>
                                        <div id="viewStartDateText" class="fw-bold text-dark">-</div>
                                        <div id="viewStartTimeText" class="small text-primary fw-semibold">N/A</div>
                                    </div>
                                    <div class="col-2 text-center text-muted">
                                        <i data-feather="arrow-right" style="width: 16px;"></i>
                                    </div>
                                    <div class="col-5">
                                        <div class="small text-muted">To / End</div>
                                        <div id="viewEndDateText" class="fw-bold text-dark">-</div>
                                        <div id="viewEndTimeText" class="small text-primary fw-semibold">N/A</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-white p-3 rounded-4 shadow-sm border">
                                <label class="small fw-bold text-muted text-uppercase d-block mb-1">Reason</label>
                                <div id="viewReason" class="fw-bold text-dark fs-6">-</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-white p-3 rounded-4 shadow-sm border" style="min-height: 150px;">
                                <label class="small fw-bold text-muted text-uppercase d-block mb-1">Employee Message</label>
                                <div id="viewMessage" class="small text-dark mt-2"
                                    style="white-space: pre-line; line-height: 1.6;">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane fade p-4" id="historyContent" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">Attendance Record</h6>
                        <span id="historyCountBadge"
                            class="badge bg-soft-primary text-primary rounded-pill px-3 fw-bold border border-primary border-opacity-10">0
                            Total</span>
                    </div>
                    <div class="bg-white rounded-4 shadow-sm border overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr class="small text-muted fw-bold text-uppercase" style="font-size: 10px;">
                                        <th class="ps-3 py-3">Date Range</th>
                                        <th>Details</th>
                                        <th class="text-center">Status</th>
                                        <th class="pe-3 text-end">Days</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const holidays = @json($holidays ?? []);

        function isNonWorkingDate(dateString) {
            const date = new Date(dateString + 'T00:00:00');
            return date.getDay() === 0 || holidays.includes(dateString);
        }

        function calculateWorkingDayCount(startDate, endDate) {
            let current = new Date(startDate + 'T00:00:00');
            const end = new Date(endDate + 'T00:00:00');
            let count = 0;

            while (current <= end) {
                const currentDate =
                    current.getFullYear() + '-' +
                    String(current.getMonth() + 1).padStart(2, '0') + '-' +
                    String(current.getDate()).padStart(2, '0');

                if (!isNonWorkingDate(currentDate)) {
                    count++;
                }

                current.setDate(current.getDate() + 1);
            }

            return count;
        }

        function switchTab(tabId, el) {
            document.querySelectorAll('.toggle-opt').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            document.querySelectorAll('.content-pane').forEach(p => p.classList.add('d-none'));
            document.getElementById('tab-' + tabId).classList.remove('d-none');
        }

        function toggleCategoryFields() {
            const leaveType  = document.querySelector('input[name="leave_type"]:checked').value;
            const leaveCategory = document.getElementById('leaveCategory').value;

            const endDateWrapper = document.getElementById('endDateWrapper');
            const startTimeWrapper = document.getElementById('startTimeWrapper');
            const endTimeWrapper = document.getElementById('endTimeWrapper');
            const halfDayOptionWrapper = document.getElementById('halfDayOptionWrapper');
            const leaveCategoryWrapper = document.getElementById('leaveCategoryWrapper');

            halfDayOptionWrapper.style.display = 'none';
            endTimeWrapper.style.display = 'none';

            // EARLY LEAVE
            if (leaveCategory === 'Gatepass Leave') {

                endDateWrapper.style.display = 'none';
                startTimeWrapper.style.display = 'block';
                leaveCategoryWrapper.style.visibility = 'hidden';

                document.getElementById('endDate').required = false;
                document.getElementById('startTime').required = true;

                document.querySelectorAll('input[name="leave_type"]').forEach(radio => {
                    radio.required = false;
                });

            } 
            // ALL OTHER TYPES INCLUDING WFH
            else {

                startTimeWrapper.style.display = 'none';
                endDateWrapper.style.display = 'block';
                leaveCategoryWrapper.style.visibility = 'visible';

                document.getElementById('startTime').required = false;

                // HALF DAY
                if (leaveType  === 'Half Day') {
                    endDateWrapper.style.display = 'none';
                    halfDayOptionWrapper.style.display = 'block';
                }

                document.getElementById('endDate').required = false;
            }

            calculateDays();
        }

        function calculateDays() {

            const leaveType  = document.querySelector('input[name="leave_type"]:checked').value;
            const leaveCategory = document.getElementById('leaveCategory').value;

            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;

            const totalDaysInput = document.getElementById('totalDays');

            // EARLY LEAVE
            if (leaveCategory === 'Gatepass Leave') {

                const startTimeInput = document.getElementById('startTime');
                const endTimeInput = document.getElementById('endTime');

                if (startTimeInput.value) {

                    const [hours, minutes] = startTimeInput.value.split(':');

                    let dateObj = new Date();

                    dateObj.setHours(parseInt(hours) + 1, parseInt(minutes));

                    endTimeInput.value = dateObj.toTimeString().substring(0, 5);
                }

                totalDaysInput.value = '1 Hour (Early Leave)';
                return;
            }

            // HALF DAY
            if (leaveType === 'Half Day') {
                totalDaysInput.value = '0.5 Days';
                return;
            }

            // FULL DAY
            if (start && end) {
            totalDaysInput.value = calculateWorkingDayCount(start, end) + ' Days';

            } else if (start) {
                totalDaysInput.value = isNonWorkingDate(start) ? '0 Days' : '1 Day';

            } else {

                totalDaysInput.value = '0 Days';
            }
        }

            // Auto-select employee if only one is available
        document.addEventListener('DOMContentLoaded', function() {
            const empSelect = document.getElementById('employee_id');
            if (empSelect && empSelect.options.length === 2) {
                empSelect.selectedIndex = 1;
            }
        });

        document.getElementById('applyLeaveForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            // Clean total_days - extract numeric value only
            let totalDaysRaw = String(data['total_days'] || '0');
            data['total_days'] = parseFloat(totalDaysRaw.replace(/[^0-9.]/g, '')) || 0;

            if (data['leave_type'] === 'Half Day' && data['half_day_type']) {
                data['leave_type'] = `Half Day (${data['half_day_type']})`;
            }

            fetch('{{ route("leave.apply") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
                .then(res => {
                    if (!res.ok) {
                        return res.json().then(err => { throw err; });
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('Leave applied successfully! Status: Pending', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    }
                })
                .catch(err => {
                    console.error('Leave Application Error:', err);
                    let msg = 'Something went wrong! Please check the form and try again.';
                    if (err && err.errors) {
                        msg = Object.values(err.errors).flat().join(', ');
                    } else if (err && err.message) {
                        msg = err.message;
                    } else if (typeof err === 'string') {
                        msg = err;
                    }
                    showToast(msg, 'error');
                });
        });

        function openActionModal(id) {
            fetch(`/api/leave/details/${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('actionLeaveId').value = data.id;
                    document.getElementById('displayAppCode').textContent = `LA-${String(data.id).padStart(4, '0')}`;
                    updateBalanceBadge(data.balance);
                    document.querySelector('#actionForm select[name="status"]').value = data.status;
                    const modal = new bootstrap.Offcanvas(document.getElementById('leaveActionModal'));
                    modal.show();
                });
        }

        function updateBalanceBadge(balance) {
            const badge = document.getElementById('displayBalanceBadge');
            if (!badge) {
                return;
            }

            const numericBalance = Number(balance ?? 0);
            badge.textContent = Number.isInteger(numericBalance) ? numericBalance : numericBalance.toFixed(1);
            badge.className = `badge px-2 py-1 ${numericBalance < 0 ? 'bg-soft-danger text-danger' : 'bg-soft-primary text-primary'} fw-bold`;
            badge.style.fontSize = '20px';
        }

        function openViewModal(id) {
            fetch(`/api/leave/details/${id}`)
                .then(res => res.json())
                .then(data => {
                    // Reset to details tab
                    const initialTab = new bootstrap.Tab(document.getElementById('details-tab'));
                    initialTab.show();

                    document.getElementById('viewEmployeeName').textContent = data.employee.name;
                    document.getElementById('viewAvatarLetter').textContent = data.employee.name.charAt(0);
                    document.getElementById('viewLeaveType').textContent = data.leave_type;

                    const catRaw = data.leave_category.toLowerCase();
                    let catDisp = data.leave_category.toUpperCase();
                    let catClass = 'bg-soft-primary text-primary';

                    if (catRaw.includes('half')) {
                        catDisp = catDisp.replace('HALF', 'HALF DAY').replace('HALF DAY DAY', 'HALF DAY');
                        catClass = 'bg-soft-warning text-warning';
                    } else if (catRaw === 'full' || catRaw.trim() === 'full') {
                        catDisp = 'FULL DAY';
                    } else if (catRaw.includes('gatepass')) {
                        catClass = 'bg-soft-info text-info';
                    } else if (catRaw.includes('wfh')) {
                        catDisp = 'WFH';
                        catClass = 'bg-soft-success text-success';
                    }

                    const catBadge = document.getElementById('viewCategoryBadge');
                    catBadge.textContent = catDisp;
                    catBadge.className = `badge ${catClass} border border-opacity-10`;

                    const statusBadge = document.getElementById('viewStatusBadge');
                    const status = data.status.toLowerCase();
                    statusBadge.textContent = status.toUpperCase();

                    // Reset classes
                    statusBadge.className = 'badge rounded-pill fw-bold text-uppercase';
                    const statusClass = {
                        'pending': 'bg-soft-dark text-dark',
                        'approved': 'bg-soft-primary text-primary',
                        'on_hold': 'bg-soft-warning text-warning',
                        'rejected': 'bg-soft-danger text-danger',
                        'unauthorised': 'bg-soft-danger text-danger'
                    }[status] || 'bg-light';
                    statusBadge.classList.add(...statusClass.split(' '));

                    const cat = data.leave_category.toLowerCase();
                    const isGatepass = cat.includes('gatepass');
                    const isHalfDay = cat.includes('half');
                    const isWFH = cat.includes('wfh');

                    // Duration & Days
                    if (isGatepass) {
                        document.getElementById('viewTotalDays').textContent = '1 Hour (Gatepass Slot)';
                    } else if (isHalfDay) {
                        document.getElementById('viewTotalDays').textContent = '0.5 Days (Half Day)';
                    } else if (isWFH) {
                        document.getElementById('viewTotalDays').textContent = `${data.total_days} Days (WFH)`;
                    } else {
                        document.getElementById('viewTotalDays').textContent = `${data.total_days} Days`;
                    }

                    // Timeframe section
                    if (isGatepass) {
                        // Gatepass: Show date + time slot
                        document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                        document.getElementById('viewStartTimeText').textContent = data.start_time ? data.start_time.substring(0, 5) : 'N/A';
                        document.getElementById('viewEndDateText').textContent = 'Same Day';
                        document.getElementById('viewEndTimeText').textContent = data.end_time ? data.end_time.substring(0, 5) : 'N/A';
                    } else if (isHalfDay) {
                        // Half Day: Show date + which half
                        document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

                        // Extract "First Half" or "Second Half" more robustly
                        let whichHalf = 'Half Day';
                        if (cat.includes('first')) whichHalf = 'First Half';
                        else if (cat.includes('second')) whichHalf = 'Second Half';

                        document.getElementById('viewStartTimeText').textContent = whichHalf;

                        // End part for Half Day
                        document.getElementById('viewEndDateText').textContent = 'Same Day';
                        document.getElementById('viewEndTimeText').textContent = '0.5 Day';
                    } else if (isWFH) {
                        // WFH: Show date range
                        document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                        document.getElementById('viewStartTimeText').textContent = 'WFH Mode';
                        document.getElementById('viewEndDateText').textContent = data.end_date ? new Date(data.end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
                        document.getElementById('viewEndTimeText').textContent = 'Return Date';
                    } else {
                        // Full Day: Show date range
                        document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                        document.getElementById('viewStartTimeText').textContent = 'Full Day';
                        document.getElementById('viewEndDateText').textContent = data.end_date ? new Date(data.end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
                        document.getElementById('viewEndTimeText').textContent = 'Return Date';
                    }

                    document.getElementById('viewReason').textContent = data.reason;
                    document.getElementById('viewMessage').textContent = data.message || 'No extra message.';

                    fetchHistory(data.employee_id);

                    const modal = new bootstrap.Offcanvas(document.getElementById('viewLeaveModal'));
                    modal.show();
                });
        }

        function fetchHistory(empId) {
            const tbody = document.getElementById('historyTableBody');
            const badge = document.getElementById('historyCountBadge');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted small">Loading records...</td></tr>';

            fetch(`/api/leave/employee/${empId}`)
                .then(res => res.json())
                .then(data => {
                    tbody.innerHTML = '';
                    badge.textContent = `${data.length} Total`;

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted small">No history available for this employee.</td></tr>';
                        return;
                    }

                    const today = new Date();
                    data.forEach(item => {
                        const sDate = new Date(item.start_date);
                        const isFuture = sDate > today;
                        const dateFormatted = sDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });

                        const statusClass = {
                            'pending': 'bg-soft-dark text-dark',
                            'approved': 'bg-soft-primary text-primary',
                            'on_hold': 'bg-soft-warning text-warning',
                            'rejected': 'bg-soft-danger text-danger',
                            'unauthorised': 'bg-soft-danger text-danger'
                        }[item.status] || 'bg-light';

                        const catRaw = item.leave_category.toLowerCase();
                        let catClass = 'bg-soft-primary text-primary';
                        let catDisp = item.leave_category.toUpperCase();
                        
                        if (catRaw.includes('half')) {
                            catDisp = catDisp.replace('HALF', 'HALF DAY').replace('HALF DAY DAY', 'HALF DAY');
                            catClass = 'bg-soft-warning text-warning';
                        } else if (catRaw === 'full' || catRaw === 'full day') {
                            catDisp = 'FULL DAY';
                        } else if (catRaw.includes('gatepass')) {
                            catClass = 'bg-soft-info text-info';
                        } else if (catRaw.includes('wfh')) {
                            catDisp = 'WFH';
                            catClass = 'bg-soft-success text-success';
                        }

                        tbody.innerHTML += `
                                                    <tr class="${isFuture ? 'bg-soft-primary bg-opacity-10' : ''}">
                                                        <td class="ps-3 py-3">
                                                            <div class="fw-bold text-dark small">${dateFormatted}</div>
                                                            ${isFuture ? '<div class="text-primary fw-bold" style="font-size: 8px;">UPCOMING</div>' : ''}
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold text-dark small">${item.leave_type}</div>
                                                            <span class="badge ${catClass} p-1 px-2 mt-1" style="font-size: 8px; font-weight: 800;">${catDisp}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge ${statusClass} px-2 rounded-pill fw-bold" style="font-size: 10px;">${item.status.toUpperCase()}</span>
                                                        </td>
                                                        <td class="pe-3 text-end fw-bold text-primary small">${item.leave_category.toLowerCase().includes('gatepass') ? '1 Hr' : item.total_days}</td>
                                                    </tr>
                                                `;
                    });
                });
        }

        document.getElementById('actionForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            const selectedStatus = data['status'];

            fetch('{{ route("leave.updateAction") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const statusLabel = selectedStatus.charAt(0).toUpperCase() + selectedStatus.slice(1).replace('_', ' ');
                        showToast('Leave status updated to: ' + statusLabel, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    }
                });
        });

        function deleteApplication(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
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
                    fetch(`/leave/application/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (typeof Toast !== 'undefined') {
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Application deleted'
                                });
                            }
                            setTimeout(() => window.location.reload(), 1000);
                        });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
            toggleCategoryFields();
        });

        function showToast(message, type) {
            const toast = document.getElementById('customToast');
            const toastMsg = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');
            toastMsg.textContent = message;

            toast.className = 'custom-toast';
            if (type === 'success') {
                toast.classList.add('toast-success');
                toastIcon.innerHTML = '✓';
            } else {
                toast.classList.add('toast-error');
                toastIcon.innerHTML = '✗';
            }

            toast.classList.add('toast-show');
            setTimeout(() => {
                toast.classList.remove('toast-show');
            }, 2000);
        }
    </script>

    <!-- Toast Notification -->
    <div id="customToast" class="custom-toast">
        <span id="toastIcon" class="toast-icon"></span>
        <span id="toastMessage"></span>
    </div>

    <style>
        .bg-soft-primary {
            background-color: rgba(56, 88, 249, 0.08);
        }

        .bg-soft-danger {
            background-color: rgba(239, 68, 68, 0.08);
        }

        .bg-soft-warning {
            background-color: rgba(245, 158, 11, 0.08);
        }

        .bg-soft-dark {
            background-color: rgba(51, 65, 85, 0.08);
        }

        .bg-soft-info {
            background-color: rgba(6, 182, 212, 0.08);
        }

        .avatar-text {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .avatar-xs {
            width: 32px;
            height: 32px;
            font-size: 11px;
        }

        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .btn-soft-primary {
            color: #3858f9 !important;
            background: rgba(56, 88, 249, 0.1) !important;
        }

        .btn-soft-primary:hover {
            background: #3858f9 !important;
            color: #fff !important;
        }

        .btn-soft-info {
            color: #06b6d4 !important;
            background: rgba(6, 182, 212, 0.1) !important;
        }

        .btn-soft-info:hover {
            background: #06b6d4 !important;
            color: #fff !important;
        }

        .btn-soft-danger {
            color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.1) !important;
        }

        .btn-soft-danger:hover {
            background: #ef4444 !important;
            color: #fff !important;
        }

        .custom-side-modal {
            border-left: 2px solid #3858f9;
            box-shadow: -20px 0 50px rgba(0, 0, 0, 0.15);
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .nav-tabs-custom .nav-link.active {
            color: #3858f9;
            border-bottom-color: #3858f9;
            background: transparent;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .custom-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 14px 24px;
            border-radius: 12px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            transform: translateX(120%);
            transition: transform 0.4s ease;
            font-family: 'Inter', sans-serif;
        }

        .custom-toast.toast-show {
            transform: translateX(0);
        }

        .custom-toast.toast-success {
            background: linear-gradient(135deg, #16a34a, #22c55e);
        }

        .custom-toast.toast-error {
            background: linear-gradient(135deg, #dc2626, #ef4444);
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            /* margin-top: 5px; */
        }
        .category-grid input[type="radio"] {
            display: none;
        }
        .category-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            /* padding: 15px 10px; */
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 48px;
        }
        .category-tile i {
            width: 20px;
            height: 20px;
            margin-bottom: 8px;
            color: #64748b;
            transition: all 0.3s ease;
        }
        .category-tile span {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
        }
        .category-grid input[type="radio"]:checked + .category-tile {
            border-color: #3858f9;
            background: rgba(56, 88, 249, 0.04);
        }
        .category-grid input[type="radio"]:checked + .category-tile i {
            color: #3858f9;
        }
        .category-grid input[type="radio"]:checked + .category-tile span {
            color: #3858f9;
        }
        .category-tile:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .leave-history-pagination .pagination {
            margin-bottom: 0;
            gap: 0.35rem;
            justify-content: flex-end;
        }

        .leave-history-pagination .page-link {
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

        .leave-history-pagination .page-item.active .page-link {
            background: #3858f9;
            border-color: #3858f9;
            color: #fff;
        }

        .leave-history-pagination .page-item.disabled .page-link {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .leave-history-pagination .page-link svg {
            width: 14px;
            height: 14px;
        }

        .leave-history-pagination nav .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
            justify-content: flex-end !important;
        }

        .leave-history-pagination nav .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between > div:first-child {
            display: none !important;
        }

            .wghrm-custom-select-btn {
                background-color: #fff !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 12px !important;
                color: #1e293b !important;
                padding: 10px 16px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                width: 100% !important;
                height: 48px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                transition: all 0.2s !important;
                text-align: left !important;
            }
            .wghrm-custom-select-btn:focus {
                border-color: #3858f9 !important;
                box-shadow: 0 0 0 4px rgba(56, 88, 249, 0.1) !important;
                outline: none !important;
            }
            .wghrm-custom-dropdown-menu {
                border-radius: 12px !important;
                box-shadow: 0 15px 40px rgba(0,0,0,0.12) !important;
                padding: 8px !important;
                border: 1px solid #e2e8f0 !important;
                width: 100% !important;
                min-width: 200px !important;
                z-index: 9999 !important;
            }
            .wghrm-custom-search-box {
                padding: 4px;
                margin-bottom: 8px;
                border-bottom: 1px solid #f1f5f9;
            }
            .wghrm-custom-search-input {
                width: 100% !important;
                padding: 8px 12px !important;
                border-radius: 8px !important;
                border: 1px solid #e2e8f0 !important;
                font-size: 13px !important;
                outline: none !important;
                background: #f8fafc !important;
            }
            .wghrm-custom-dropdown-item {
                border-radius: 8px !important;
                padding: 10px 15px !important;
                font-weight: 600 !important;
                font-size: 13px !important;
                color: #475569 !important;
                cursor: pointer !important;
                display: block !important;
                text-decoration: none !important;
            }
            .wghrm-custom-dropdown-item:hover, .wghrm-custom-dropdown-item.active {
                background: #f1f5f9 !important;
                color: #3858f9 !important;
            }

            .wghrm-search-dropdown {
                position: relative;
                width: 100%;
                overflow: visible;
            }

            .wghrm-dropdown-trigger {
                width: 100%;
                border: 1px solid #e2e8f0;
                background: #fff;
                border-radius: 12px;
                padding: 10px 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
                position: relative;
                z-index: 1;
            }

            .wghrm-dropdown-trigger.open {
                border-color: #3858f9;
                box-shadow: 0 0 0 4px rgba(56, 88, 249, 0.08);
            }

            .wghrm-trigger-text {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .wghrm-dropdown-menu {
                position: absolute;
                top: calc(100% + 8px);
                left: 0;
                right: 0;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
                z-index: 9999 !important;
                padding: 10px;
                display: none;
                min-width: 100%;
                width: auto;
                pointer-events: auto !important;
                visibility: visible !important;
            }

            .wghrm-dropdown-menu.show {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }

            .wghrm-search-container {
                position: relative;
                margin-bottom: 10px;
                display: block !important;
                visibility: visible !important;
            }

            .wghrm-search-icon {
                position: absolute;
                top: 50%;
                left: 12px;
                transform: translateY(-50%);
                width: 14px;
                height: 14px;
                color: #94a3b8;
            }

            .wghrm-search-dropdown .wghrm-search-input {
                width: 100%;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
                padding: 10px 12px 10px 34px;
                font-size: 13px;
                font-weight: 600;
                color: #334155;
                outline: none;
            }

            .wghrm-search-dropdown .wghrm-search-input:focus {
                border-color: #3858f9;
                box-shadow: 0 0 0 3px rgba(56, 88, 249, 0.08);
            }

            .wghrm-items-list {
                max-height: 220px;
                overflow-y: auto;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            .wghrm-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                border-radius: 10px;
                padding: 10px 12px;
                color: #475569;
                cursor: pointer;
                transition: all 0.18s ease;
            }

            .wghrm-item:hover,
            .wghrm-item.selected {
                background: #f1f5f9;
                color: #3858f9;
            }

            .wghrm-item-check {
                opacity: 0;
                transition: opacity 0.18s ease;
            }

            .wghrm-item.selected .wghrm-item-check {
                opacity: 1;
            }
    </style>
@endsection
