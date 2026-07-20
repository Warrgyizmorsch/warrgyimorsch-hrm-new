@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/payroll-history.css') }}?v={{ filemtime(public_path('assets/css/payroll-history.css')) ?: time() }}">
@endpush

@section('content')
    @php
        $payrollHeaderActions = '
            <button type="button" class="zoho-btn-outline" id="shareReportBtn" title="Share salary report">
                <i class="feather-share-2"></i> Share Report
            </button>
            <button type="button" class="zoho-icon-btn" data-bs-toggle="collapse" data-bs-target="#filterSection" title="Filter">
                <i class="feather-filter"></i>
            </button>
            <div class="position-relative" id="exportWrapper">
                <button type="button" class="zoho-icon-btn" id="exportBtn" title="Export">
                    <i class="feather-download"></i>
                </button>
                <div id="exportMenu" class="d-none position-absolute end-0 mt-2 bg-white border rounded shadow payroll-export-menu" style="z-index: 9999;">
                    <button type="button" onclick="exportPayroll(\'pdf\')" class="dropdown-item">PDF</button>
                    <button type="button" onclick="exportPayroll(\'excel\')" class="dropdown-item">Excel</button>
                </div>
            </div>';
    @endphp

    <div class="zoho-page-shell payroll-history-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Payroll History',
            'viewLabel' => 'All Records',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'History', 'url' => route('payroll.index'), 'active' => true],
            ],
            'primaryAction' => $payrollHeaderActions,
        ])

        <div class="main-content zoho-module-content">
            {{-- Share salary report --}}
            <div id="salaryFormSection" class="payroll-share-panel" style="display: none;">
                <h4><i class="feather-mail me-2"></i>Share Salary Report</h4>
                <form action="{{ route('payroll.sendDateRange') }}" method="POST">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted mb-2">Employee</label>
                            <div class="dropdown">
                                <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" id="salaryEmployeeBtn">
                                    Select employee
                                </button>
                                <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                    <div class="wghrm-custom-search-box">
                                        <input type="text" class="wghrm-custom-search-input" placeholder="Search employee..."
                                            onkeyup="wghrmFilterItems(this)" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                    </div>
                                    @foreach(\App\Models\Employee::active()->orderBy('name')->get() as $emp)
                                        <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);"
                                            onclick="document.getElementById('salaryEmployeeId').value='{{ $emp->id }}'; document.getElementById('salaryEmployeeBtn').innerText='{{ addslashes($emp->name) }}'; bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle')).hide();">
                                            {{ $emp->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" id="salaryEmployeeId" name="employee_id" value="">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-2">From Date</label>
                            <input type="date" name="from_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-2">To Date</label>
                            <input type="date" name="to_date" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="zoho-btn-primary w-100"><i class="feather-send"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- List filters --}}
            <div class="collapse payroll-filter-panel" id="filterSection">
                <div class="payroll-filter-grid">
                    <div>
                        <label class="form-label small fw-bold text-muted mb-2">Employee</label>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="employeeFilterBtn">
                                @php $selectedEmp = \App\Models\Employee::find(request('employee_id')); @endphp
                                {{ $selectedEmp ? $selectedEmp->name : 'All Employees' }}
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                <div class="wghrm-custom-search-box">
                                    <input type="text" class="wghrm-custom-search-input" placeholder="Search employee..."
                                        onkeyup="wghrmFilterItems(this)" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                </div>
                                <a class="dropdown-item wghrm-custom-dropdown-item {{ !request('employee_id') ? 'active' : '' }}"
                                    href="javascript:void(0);"
                                    onclick="document.getElementById('employeeFilter').value=''; document.getElementById('employeeFilterBtn').innerText='All Employees';">All Employees</a>
                                @foreach(\App\Models\Employee::active()->orderBy('name')->get() as $emp)
                                    <a class="dropdown-item wghrm-custom-dropdown-item {{ request('employee_id') == $emp->id ? 'active' : '' }}"
                                        href="javascript:void(0);"
                                        onclick="document.getElementById('employeeFilter').value='{{ $emp->id }}'; document.getElementById('employeeFilterBtn').innerText='{{ addslashes($emp->name) }}';">
                                        {{ $emp->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" id="employeeFilter" value="{{ request('employee_id') }}">
                    </div>
                    <div>
                        <label class="form-label small fw-bold text-muted mb-2">Month</label>
                        <input type="month" id="monthFilter" class="form-control" value="{{ request('month') }}">
                    </div>
                    <div class="payroll-calc-actions">
                        <button type="button" class="zoho-btn-primary" onclick="applyFilters()">
                            <i class="feather-search"></i> Apply
                        </button>
                        <button type="button" class="zoho-icon-btn" onclick="resetFilters()" title="Reset">
                            <i class="feather-refresh-cw"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Calculation parameters --}}
            <div class="payroll-calc-panel">
                <div class="payroll-calc-panel-head">
                    <div>
                        <h3><i class="feather-calculator me-1"></i> Setup Calculation Parameters</h3>
                        <p>Select employee and month to calculate or update payroll</p>
                    </div>
                </div>
                <div class="payroll-calc-panel-body">
                    <div class="payroll-calc-grid">
                        <div class="payroll-calc-field">
                            <label>Employee</label>
                            <div class="dropdown">
                                <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" id="employeeSelectBtn">
                                    Select employee
                                </button>
                                <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                    <div class="wghrm-custom-search-box">
                                        <input type="text" class="wghrm-custom-search-input" placeholder="Search employee..."
                                            onkeyup="wghrmFilterItems(this)" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                    </div>
                                    @foreach(\App\Models\Employee::active()->get() as $emp)
                                        <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);"
                                            onclick="document.getElementById('employeeSelect').value='{{ $emp->id }}'; document.getElementById('employeeSelectBtn').innerText='{{ addslashes($emp->name) }}'; bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle')).hide();">
                                            {{ $emp->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" id="employeeSelect" value="">
                        </div>
                        <div class="payroll-calc-field">
                            <label>Month</label>
                            <input type="month" id="monthSelect" class="form-control"
                                value="{{ date('Y-m', strtotime('-1 month')) }}">
                        </div>
                        <div class="payroll-calc-actions">
                            <button type="button" class="zoho-btn-primary" onclick="calculatePayroll()">
                                <i class="feather-zap"></i> Calculate
                            </button>
                            <a href="{{ route('payroll.index') }}" class="zoho-btn-outline">Reset</a>
                        </div>
                    </div>
                </div>

                <div class="payroll-calc-result-wrap">
                    <div id="noCalculation" style="display: none;"></div>
                    <input type="hidden" id="payrollIdForUpdate" value="">
                    <input type="hidden" id="isEditMode" value="false">
                    <div id="calculationResult" style="display: none;">
                    <div class="row g-3">
                        <!-- Earnings -->
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light-subtle">
                                <h6 class="fw-bold text-primary text-uppercase mb-3">
                                    Earnings
                                </h6>

                                <div class="row g-3">
                                    <div class="col-lg col-md-6">
                                        <label class="form-label small text-muted">Payable Days</label>
                                        <input type="number" step="0.01" id="inputPayableDays"
                                            class="form-control" title="Can exceed month days when adding overtime/extra payable days manually">
                                    </div>

                                    <div class="col-lg col-md-6">
                                        <label class="form-label small text-muted">Basic Salary</label>
                                        <input type="number" id="inputBasic"
                                            class="form-control">
                                    </div>

                                    <div class="col-lg col-md-6">
                                        <label class="form-label small text-muted">HRA</label>
                                        <input type="number" id="inputHRA"
                                            class="form-control">
                                    </div>

                                    <div class="col-lg col-md-6">
                                        <label class="form-label small text-muted">Conveyance</label>
                                        <input type="number" id="inputConveyance"
                                            class="form-control">
                                    </div>

                                    <div class="col-lg col-md-6">
                                        <label class="form-label small text-muted">Medical</label>
                                        <input type="number" id="inputMedical"
                                            class="form-control">
                                    </div>
                                </div>

                                <div id="overtime_box" class="mt-3" style="display:none;"></div>
                                <div id="payrollBreakdownBox" class="small text-muted mt-2" style="display:none;"></div>
                            </div>
                        </div>

                        <!-- Deductions -->
                        <div class="col-12">
                            <div class="border rounded-3 p-3 pt-0 bg-light-subtle">
                                <h6 class="fw-bold text-danger text-uppercase mb-3">
                                    Deductions
                                </h6>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">PF</label>
                                        <input type="number" id="inputPF"
                                            class="form-control">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">ESI</label>
                                        <input type="number" id="inputESI"
                                            class="form-control">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Other</label>
                                        <input type="number" id="inputOther"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="small text-muted mb-1">Override</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="checkbox" id="overrideCheck" class="form-check-input">
                                            <input type="number" id="overrideAmount"
                                                class="form-control"
                                                placeholder="Amount" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="border rounded-3 p-3 h-100 text-center">
                                        <div class="small text-muted">Gross Salary</div>
                                        <div class="fw-bold fs-4 text-primary"
                                            id="tableGrossSalary">
                                            ₹ 0.00
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="border rounded-3 p-3 h-100 text-center">
                                        <div class="small text-muted">Total Deductions</div>
                                        <div class="fw-bold fs-4 text-danger"
                                            id="tableTotalDeductions">
                                            ₹ 0.00
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-center" style="background-color: #f3f4f6; width: 98%; margin: 0 10px 0 12px;">
                            <div class="col-10">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">

                                        <!-- Left Side -->
                                        <div class="col-md-4 ms-4">
                                            <div class="h-100 d-flex flex-column justify-content-center rounded-4 p-4"
                                                style="background: linear-gradient(135deg, #3858f9 0%, #1e3a8a 100%); min-height: 80px;">
                                                <div class="text-white opacity-75 mb-2">Take Home Pay</div>
                                                <div class="fw-bold text-white" id="tableNetSalary" style="font-size: 2rem;">
                                                    ₹ 0.00
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Side -->
                                        <div class="col-md-7" style="min-height: 80px;">

                                            <!-- Summary Row -->
                                            <div class="row mb-2 d-flex justify-content-between">

                                                <div class="summary-box ms-3">
                                                    <div class="summary-label">Payable</div>
                                                    <div class="summary-value text-primary" id="resultPayableDays">
                                                        0
                                                    </div>
                                                </div>

                                                <div class="summary-box">
                                                    <div class="summary-label">Unpaid</div>
                                                    <div class="summary-value text-danger" id="resultUnpaidDays">
                                                        0
                                                    </div>
                                                </div>

                                                <div class="summary-box me-3">
                                                    <div class="summary-label">Loss</div>
                                                    <div class="summary-value text-danger" id="resultSalaryLoss">
                                                        ₹ 0.00
                                                    </div>
                                                </div>

                                            </div>

                                            <!-- Submit Button -->
                                            <button class="btn w-100 py-3 fw-bold text-white"
                                                style="background:#3858f9;border:none;border-radius:12px;"
                                                onclick="savePayroll(this)">
                                                <i class="bi bi-check2-circle me-2"></i>
                                                SUBMIT PAYROLL
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payroll list table --}}
            <div class="zoho-people-table-card">
                <div class="zoho-people-table-toolbar d-none d-lg-flex">
                    <div class="zoho-people-table-search">
                        <i class="feather-search"></i>
                        <input type="text" id="tableSearch" onkeyup="searchTable(event)" placeholder="Search in list..."
                            value="{{ request('search') }}">
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
                                    <th class="col-num">Sr. No.</th>
                                    <th>Employee Name</th>
                                    <th class="text-center">Month</th>
                                    <th class="text-center">Payable Days</th>
                                    <th class="text-end">Gross Salary</th>
                                    <th class="text-end">Deductions</th>
                                    <th class="text-end">Net Salary</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payrolls as $index => $payroll)
                                    @php
                                        $statusKey = strtolower($payroll->status ?? 'pending');
                                        $statusBadgeClass = match ($statusKey) {
                                            'paid' => 'payroll-status-badge--paid',
                                            'rejected' => 'payroll-status-badge--rejected',
                                            default => 'payroll-status-badge--pending',
                                        };
                                        $roleSlug = auth()->user()->role;
                                        $roleId = DB::table('roles_master')->where('slug', $roleSlug)->value('id');
                                        $isAdmin = in_array($roleId, [1, 2, 3, 4]);
                                    @endphp
                                    <tr>
                                        <td class="text-muted fw-semibold">{{ ($payrolls->currentPage() - 1) * $payrolls->perPage() + $loop->iteration }}</td>
                                        <td><span class="payroll-emp-name">{{ $payroll->employee->name }}</span></td>
                                        <td class="text-center"><span class="text-muted small fw-semibold">{{ $payroll->month }}</span></td>
                                        <td class="text-center fw-semibold">{{ $payroll->payable_days }}</td>
                                        <td class="text-end payroll-amount">***</td>
                                        <td class="text-end payroll-amount payroll-amount--deduction">***</td>
                                        <td class="text-end payroll-amount payroll-amount--net">***</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <span class="payroll-status-badge {{ $statusBadgeClass }} dropdown-toggle"
                                                    data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                    {{ $payroll->status }}
                                                </span>
                                                <ul class="dropdown-menu dropdown-menu-end zoho-more-menu">
                                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="updateStatus({{ $payroll->id }}, 'pending')">Pending</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="updateStatus({{ $payroll->id }}, 'paid')">Paid</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="updateStatus({{ $payroll->id }}, 'rejected')">Rejected</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="payroll-row-actions">
                                                <button type="button" class="zoho-icon-btn" onclick="viewPayroll({{ $payroll->id }})" title="View">
                                                    <i class="feather-eye"></i>
                                                </button>
                                                <div class="dropdown">
                                                    <button type="button" class="zoho-icon-btn" data-bs-toggle="dropdown" title="More actions">
                                                        <i class="feather-more-horizontal"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end zoho-more-menu">
                                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="editPayroll({{ $payroll->id }})"><i class="feather-edit me-2"></i>Edit</a></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="downloadSlip({{ $payroll->id }}, 'pdf')"><i class="feather-download me-2"></i>Download PDF</a></li>
                                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deletePayroll({{ $payroll->id }})"><i class="feather-trash-2 me-2"></i>Delete</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item comment-btn {{ (!$payroll->is_read && $isAdmin) ? 'blink' : '' }}"
                                                                href="javascript:void(0);"
                                                                data-id="{{ $payroll->id }}"
                                                                data-remark="{{ $payroll->remarks ?? '' }}"
                                                                data-role="{{ auth()->user()->role }}">
                                                                <i class="feather-message-square me-2"></i>Comment
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">
                                            <div class="payroll-empty-state">
                                                <i class="feather-file-text"></i>
                                                <p>No payroll records found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($payrolls->hasPages())
                        <div class="card-footer bg-white border-0 py-3 payroll-pagination">
                            {{ $payrolls->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="modal fade" id="commentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content" style="border-radius: 12px;">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Comment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <textarea id="remarksField" class="form-control" rows="4" placeholder="Write comment..."></textarea>
                            <input type="hidden" id="userRole">
                            <input type="hidden" id="payrollId">
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button id="saveBtn" class="btn btn-primary" onclick="saveComment()">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <!-- Payroll Calculation Offcanvas -->
    <div class="offcanvas offcanvas-end border-0 shadow-lg" tabindex="-1" id="payrollCalculationOffcanvas"
        style="width: 650px !important; background: #f8fafc;">
        <div class="offcanvas-header bg-white border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-text avatar-md bg-soft-primary text-primary rounded-3">
                    <i class="feather-calculator"></i>
                </div>
                <div>
                    <h5 class="offcanvas-title fw-bold text-dark">Enterprise Payroll Engine</h5>
                    <p class="text-muted small mb-0">Generate and validate monthly employee payslips</p>
                </div>
            </div>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('payroll._calculation_form')
        </div>
    </div>

    <!-- Statement Modal -->
    <div class="modal fade" id="payrollDetailModal" tabindex="-1" aria-labelledby="payrollModalLabel" aria-hidden="true"
        data-bs-backdrop="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; background: #fff; overflow: hidden;">
                <div class="modal-header border-0 px-4 py-3" style="background: #3858f9; color: #ffffff;">
                    <h5 class="modal-title fw-bold" id="payrollModalLabel"><i class="bi bi-file-earmark-text-fill me-2"></i>
                        Payroll Statement</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-white" id="payrollModalBody"
                    style="min-height: 400px; max-height: 80vh; overflow-y: auto;">
                    <div class="d-flex justify-content-center align-items-center" style="height: 400px;">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary fw-bold px-4 shadow-none" data-bs-dismiss="modal"
                        style="border-radius: 8px;">CLOSE</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>

        document.querySelectorAll('.comment-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const remark = this.dataset.remark; // already JSON-safe string
                const role = this.dataset.role;

                openCommentModal(id, remark, role);
            });
        });

        function openCommentModal(id, remarks, role) {
            role = (role || '').toLowerCase();
            let modalEl = document.getElementById('commentModal');
            let modal = new bootstrap.Modal(modalEl);
            modal.show();

            // Set hidden fields
            document.getElementById('payrollId').value = id;
            document.getElementById('userRole').value = role;

            let field = document.getElementById('remarksField');
            let title = modalEl.querySelector('.modal-title');
            let saveBtn = document.getElementById('saveBtn');

            field.value = remarks || '';

            if (role === 'employee') {
                // ✅ Employee: can edit
                field.removeAttribute('readonly');
                field.style.display = 'block';

                title.innerText = 'Add Comment';
            } else {
                // ✅ Admin/HR: view only
                field.setAttribute('readonly', true);
                field.style.display = 'block'; // keep visible
                title.innerText = 'Remarks';
                saveBtn.style.display = 'none';

                document.querySelector(`[data-id="${id}"]`)?.classList.remove('blink');

                markAsRead(id);
            }
        }

        function markAsRead(id) {
            fetch(`/payroll/${id}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }).catch(err => console.error(err));
        }

        function saveComment() {
            let role = document.getElementById('userRole').value;
            if (role.toLowerCase() !== 'employee') return;

            let id = document.getElementById('payrollId').value;
            let remarks = document.getElementById('remarksField').value;

            let formData = new FormData();
            formData.append('remarks', remarks);

            fetch(`/payroll/${id}/remarks`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('commentModal')).hide();
                        location.reload();
                    } else {
                        alert("Error: " + (data.error || "Unknown error"));
                    }
                })
                .catch(err => console.error("Fetch Error:", err));
        }

        document.addEventListener('DOMContentLoaded', function () {

            const btn = document.getElementById('exportBtn');
            const menu = document.getElementById('exportMenu');

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.classList.toggle('d-none');
            });

            menu.addEventListener('click', function (e) {
                e.stopPropagation();
            });

            document.addEventListener('click', function () {
                menu.classList.add('d-none');
            });

        });

        document.getElementById("shareReportBtn").addEventListener("click", function () {
            let section = document.getElementById("salaryFormSection");

            if (section.style.display === "none") {
                section.style.display = "block";
            } else {
                section.style.display = "none";
            }
        });

        function applyFilters() {
            const month = document.getElementById('monthFilter').value;
            const empId = document.getElementById('employeeFilter').value;
            const searchVal = document.getElementById('tableSearch').value;
            let url = `{{ route("payroll.index") }}?month=${month}&employee_id=${empId}`;
            if (searchVal) {
                url += `&search=${encodeURIComponent(searchVal)}`;
            }
            window.location.href = url;
        }

        let searchTimeout;
        function searchTable(event) {
            if (event && event.key === 'Enter') {
                clearTimeout(searchTimeout);
                performSearch();
                return;
            }
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 800);
        }

        function performSearch() {
            const searchVal = document.getElementById('tableSearch').value;
            const url = new URL(window.location.href);
            if (searchVal) {
                url.searchParams.set('search', searchVal);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }

        // Custom Dropdown Search Logic
        function wghrmFilterItems(input) {
            const filter = input.value.toLowerCase();
            const items = input.closest('.wghrm-custom-dropdown-menu').querySelectorAll('.wghrm-custom-dropdown-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(filter)) {
                    item.style.setProperty('display', 'block', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        }

        function resetFilters() {
            window.location.href = '{{ route("payroll.index") }}';
        }

        function viewPayroll(id) {
            const modalEl = document.getElementById('payrollDetailModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            document.getElementById('payrollModalBody').innerHTML = `
                                                        <div class="d-flex justify-content-center align-items-center" style="height: 400px;">
                                                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                                                        </div>`;

            modal.show();

            fetch(`/payroll/${id}`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('payrollModalBody').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('payrollModalBody').innerHTML = '<div class="p-5 text-center text-danger">Error loading payroll data.</div>';
                });
        }

        function editPayroll(id) {
            // Fetch payroll data
            fetch(`/payroll/${id}/edit`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const payroll = data.data;
                        
                        // Set the payroll ID for update
                        document.getElementById('payrollIdForUpdate').value = id;
                        
                        // Pre-fill form fields
                        document.getElementById('employeeSelect').value = payroll.employee_id;
                        document.getElementById('employeeSelectBtn').innerText = payroll.employee_name;
                        document.getElementById('monthSelect').value = payroll.month;
                        document.getElementById('inputPayableDays').value = payroll.payable_days || '';
                        document.getElementById('inputBasic').value = payroll.basic_salary || '';
                        document.getElementById('inputHRA').value = payroll.hra || '';
                        document.getElementById('inputConveyance').value = payroll.conveyance_allowance || '';
                        document.getElementById('inputMedical').value = payroll.medical_allowance || '';
                        document.getElementById('inputPF').value = payroll.pf_deduction || '';
                        document.getElementById('inputESI').value = payroll.esi_deduction || '';
                        document.getElementById('inputOther').value = payroll.other_deduction || '';
                        
                        // Set currentPayrollData for calculations
                        window.currentPayrollData = payroll;
                        
                        // Set the isEditMode flag
                        document.getElementById('isEditMode').value = 'true';
                        
                        // Show calculation result
                        document.getElementById('calculationResult').style.display = 'block';
                        document.getElementById('noCalculation').style.display = 'none';
                        
                        // Recalculate to show totals
                        recalculate();
                        
                        // Open offcanvas
                        const offcanvasEl = document.getElementById('payrollCalculationOffcanvas');
                        const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
                        offcanvas.show();
                    } else {
                        alert('Error loading payroll data: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error loading payroll data');
                });
        }

        function downloadSlip(id, format = 'csv') {
            window.location.href = `{{ route('payroll.export') }}?id=${id}&format=${format}`;
        }

        function deletePayroll(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this payroll record!",
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
                    fetch(`/payroll/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            if (typeof Toast !== 'undefined') {
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Payroll record deleted'
                                });
                            }
                            setTimeout(() => location.reload(), 1000);
                        }
                    });
                }
            });
        }

        function updateStatus(id, newStatus) {
            fetch(`/payroll/${id}/status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    // Instantly update color
                    location.reload();
                }
            });
        }

        function exportPayroll(format = 'csv') {
            const month = document.getElementById('monthFilter').value;
            const empId = document.getElementById('employeeFilter').value;
            window.location.href = `{{ route('payroll.export') }}?month=${month}&employee_id=${empId}&format=${format}`;
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        .summary-box{
            background:#ffffff;
            border-radius:12px;
            text-align:center;
            display:flex;
            flex-direction:column;
            justify-content:center;
            width: 30%;
            padding: 4px 0 4px 0;
        }

        .summary-label{
            font-size:13px;
            color:#6b7280;
            margin-bottom: 4px;
        }

        .summary-value{
            font-size:22px;
            font-weight:700;
        }

        .wghrm-custom-select-btn {
            background-color: #fff;
            border: 0;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            border-radius: 8px;
            color: #475569;
            padding: 0 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            height: 40px !important;
            font-size: 14px;
            text-align: left;
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
            border-radius: 16px !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12) !important;
            padding: 10px !important;
            margin-top: 10px !important;
            z-index: 1060 !important;
            background: #fff !important;
            max-height: 350px !important;
            overflow-y: auto !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
            min-width: 250px !important;
            width: 100%;
        }

        /* Custom Scrollbar (Slider) */
        .wghrm-custom-dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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

        .action-btn-outline {
            background: transparent !important;
            border: none !important;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            color: #64748b;
        }

        .table-responsive {
            overflow: visible !important;
        }

        .dropdown-menu {
            z-index: 99999999 !important;
        }

        .action-btn-outline:hover {
            background: #f1f5f9 !important;
            color: #3858f9;
        }

        .form-select-sm {
            font-size: 11px;
            letter-spacing: 0.3px;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .payroll-pagination .pagination {
            margin-bottom: 0;
            justify-content: center;
            gap: 0.35rem;
        }

        .payroll-pagination .page-link {
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

        .payroll-pagination .page-item.active .page-link {
            background: #3858f9;
            border-color: #3858f9;
            color: #fff;
        }

        .payroll-pagination .page-item.disabled .page-link {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .payroll-pagination .page-link svg {
            width: 14px !important;
            height: 14px !important;
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
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%233858f9' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover,
        input[type="month"]::-webkit-calendar-picker-indicator:hover {
            background-color: rgba(56, 88, 249, 0.08);
        }

        .blink {
            animation: blink-animation 1s infinite;
        }

        @keyframes blink-animation {
            50% {
                opacity: 0.3;
            }
        }
    </style>

<script>
    if (typeof isManualDays === 'undefined') {
        var isManualDays = false;
    }
    if (typeof currentPayrollData === 'undefined') {
        var currentPayrollData = null;
    }
    if (typeof currentPayrollTotalDays === 'undefined') {
        var currentPayrollTotalDays = 0;
    }
    if (typeof isManualPF === 'undefined') {
        var isManualPF = false;
    }
    if (typeof isManualESI === 'undefined') {
        var isManualESI = false;
    }

    // Use a unique scoped setup if possible, or just re-ensure listeners
    function initPayrollLogic() {
        document.addEventListener('input', recalculate);
        const daysInput = document.getElementById('inputPayableDays');
        if(daysInput) {
            daysInput.addEventListener('input', function () {
                isManualDays = true;
            });
        }

        const pfInput = document.getElementById('inputPF');
        if(pfInput) {
            pfInput.addEventListener('input', function () {
                isManualPF = true;
            });
        }

        const esiInput = document.getElementById('inputESI');
        if(esiInput) {
            esiInput.addEventListener('input', function () {
                isManualESI = true;
            });
        }

        const overrideCheck = document.getElementById('overrideCheck');
        if(overrideCheck) {
            overrideCheck.addEventListener('change', function () {
                document.getElementById('overrideAmount').disabled = !this.checked;
            });
        }
    }

    initPayrollLogic();

   function recalculate(event) {
        const sourceId = event?.target?.id || '';
        if (sourceId === 'inputPayableDays') {
            isManualDays = true;
        }
        let basic = parseFloat(document.getElementById('inputBasic')?.value) || 0;
        let hra = parseFloat(document.getElementById('inputHRA')?.value) || 0;
        let conv = parseFloat(document.getElementById('inputConveyance')?.value) || 0;
        let med = parseFloat(document.getElementById('inputMedical')?.value) || 0;
        let otherAllowance = parseFloat(currentPayrollData?.other_allowance) || 0;

        let fullSalary = basic + hra + conv + med + otherAllowance;
        let backendPerDaySalary = Number(currentPayrollData?.perdaysalary || 0);
        let totalDays = Number(currentPayrollTotalDays || currentPayrollData?.total_days || 0);
        if (!totalDays && backendPerDaySalary > 0 && fullSalary > 0) {
            totalDays = Math.round(fullSalary / backendPerDaySalary);
        }
        totalDays = totalDays || 30;

        let rawPayableDays = parseFloat(document.getElementById('inputPayableDays')?.value) || 0;
        // Auto-calculated days stay capped at month length; manual HR override can exceed for OT/extra days.
        let payableDays = isManualDays ? rawPayableDays : Math.min(rawPayableDays, totalDays);
        let extraDays = Math.max(0, payableDays - totalDays);
        let baseGross = (fullSalary / totalDays) * payableDays;
        // Overtime pay is informational only — shown for review, never added to gross/net salary.
        let overtimePay = Number(currentPayrollData?.overtime_pay || 0);
        let gross = baseGross;

        if (document.getElementById('overrideCheck')?.checked) {
            let override = parseFloat(document.getElementById('overrideAmount').value);
            if (!isNaN(override) && override > 0) gross = override;
        }

        if(document.getElementById('tableGrossSalary')) document.getElementById('tableGrossSalary').innerText = '₹ ' + gross.toFixed(2);

        let earnedBasic = totalDays > 0 ? (basic / totalDays) * payableDays : 0;
        let autoPF = currentPayrollData?.pf_enabled ? earnedBasic * 0.12 : 0;
        let autoESI = (currentPayrollData?.esi_enabled && gross <= 21000) ? gross * 0.0075 : 0;

        if (sourceId !== 'inputPF' && document.getElementById('inputPF')) {
            document.getElementById('inputPF').value = autoPF.toFixed(2);
        }

        if (sourceId !== 'inputESI' && document.getElementById('inputESI')) {
            document.getElementById('inputESI').value = autoESI.toFixed(2);
        }

        let pf = parseFloat(document.getElementById('inputPF')?.value) || 0;
        let esi = parseFloat(document.getElementById('inputESI')?.value) || 0;
        let other = parseFloat(document.getElementById('inputOther')?.value) || 0;

        let totalDeduction = pf + esi + other;
        let net = gross - totalDeduction;

        if(document.getElementById('tableTotalDeductions')) document.getElementById('tableTotalDeductions').innerText = '₹ ' + totalDeduction.toFixed(2);
        if(document.getElementById('tableNetSalary')) document.getElementById('tableNetSalary').innerText = '₹ ' + net.toFixed(2);

        if(document.getElementById('resultPayableDays')) document.getElementById('resultPayableDays').innerText = rawPayableDays;
        let unpaidDays = Math.max(0, totalDays - payableDays);
        if(document.getElementById('resultUnpaidDays')) document.getElementById('resultUnpaidDays').innerText = unpaidDays.toFixed(2);

        let salaryLoss = payableDays >= totalDays ? 0 : Math.max(0, fullSalary - baseGross);
        if(document.getElementById('resultSalaryLoss')) document.getElementById('resultSalaryLoss').innerText = '₹ ' + salaryLoss.toFixed(2);

        const breakdownBox = document.getElementById('payrollBreakdownBox');
        if (breakdownBox && currentPayrollData) {
            const attDays = Number(currentPayrollData.attendance_payable_days ?? 0);
            const paidLeave = Number(currentPayrollData.paid_leave_days ?? 0);
            const otHours = Number(currentPayrollData.overtime_hours ?? 0);
            breakdownBox.style.display = 'block';
            const extraLabel = extraDays > 0 ? ` (+${extraDays} extra)` : '';
            breakdownBox.innerHTML = `Base: ${payableDays}/${totalDays} days${extraLabel} → ₹ ${baseGross.toFixed(2)}`
                + (!isManualDays && otHours > 0 ? ` · OT ${otHours}h (ref. only, not added): ₹ ${overtimePay.toFixed(2)}` : '')
                + (attDays && !isManualDays ? ` · Attendance ${attDays} + Leave ${paidLeave}` : '');
        }
    }

    function calculatePayroll() {
        isManualDays = false;
        isManualPF = false;
        isManualESI = false;
        const month = document.getElementById('monthSelect').value;
        const employeeId = document.getElementById('employeeSelect').value;

        if (!month || !employeeId) {
            alert('Please select person and month');
            return;
        }

        const noCalc = document.getElementById('noCalculation');
        noCalc.innerHTML = `<div class="py-5 text-center"><div class="spinner-border text-primary"></div></div>`;

        fetch('{{ url("/payroll/calculate") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ month, employee_id: employeeId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentPayrollData = data.payroll;
                currentPayrollTotalDays = Number(data.payroll.total_days || 0);
                displayPayrollData(data.payroll);
                noCalc.style.display = 'none';
                document.getElementById('calculationResult').style.display = 'block';
            } else alert(data.message);
        });
    }

    function displayPayrollData(p) {
        const formattedMonth = new Date(p.month + '-01').toLocaleString('en-IN', { month: 'short', year: 'numeric' });
        if(document.getElementById('resultMonth')) document.getElementById('resultMonth').textContent = formattedMonth;

        document.getElementById('inputPayableDays').value = p.payable_days;
        document.getElementById('inputBasic').value = p.basic_salary;
        document.getElementById('inputHRA').value = p.hra;
        document.getElementById('inputConveyance').value = p.conveyance_allowance;
        document.getElementById('inputMedical').value = p.medical_allowance;
        isManualPF = false;
        isManualESI = false;
        document.getElementById('inputPF').value = p.pf_deduction;
        document.getElementById('inputESI').value = p.esi_deduction;
        document.getElementById('inputOther').value = p.other_deduction || 0;
        currentPayrollTotalDays = Number(p.total_days || currentPayrollTotalDays || 0);
        const overtimeBox = document.getElementById('overtime_box');

        if (overtimeBox) {
            const otHours = Number(p.overtime_hours || 0);
            const otDays = Number(p.overtime_days || 0);

            overtimeBox.style.display = 'block';

            if (otHours > 0) {
                overtimeBox.innerHTML = `
                    <div class="alert alert-info mb-0">
                        <strong>${p.emp_name}</strong> worked
                        <strong>${otHours}</strong> extra hrs
                        (<strong>${otDays.toFixed(2)}</strong> extra shift-days) this month.
                        Reference value (1.5× basic rate): <strong>₹ ${Number(p.overtime_pay || 0).toFixed(2)}</strong>
                        — shown for review only, not added to payroll.
                    </div>
                `;
            } else {
                overtimeBox.innerHTML = `
                    <div class="alert alert-secondary mb-3">
                        No overtime this month
                    </div>
                `;
            }
        }

        recalculate();
    }

    function savePayroll(btn) {
        if (!currentPayrollData) return;
        btn.disabled = true;
        
        const payrollId = document.getElementById('payrollIdForUpdate').value;
        const isEditMode = document.getElementById('isEditMode').value === 'true';
        
        const payloadData = {
            ...currentPayrollData,
            basic_salary: document.getElementById('inputBasic').value,
            hra: document.getElementById('inputHRA').value,
            conveyance_allowance: document.getElementById('inputConveyance').value,
            medical_allowance: document.getElementById('inputMedical').value,
            payable_days: document.getElementById('inputPayableDays').value,
            pf_deduction: document.getElementById('inputPF').value,
            esi_deduction: document.getElementById('inputESI').value,
            other_deduction: document.getElementById('inputOther').value,
            deductions: (
                (parseFloat(document.getElementById('inputPF').value) || 0) +
                (parseFloat(document.getElementById('inputESI').value) || 0) +
                (parseFloat(document.getElementById('inputOther').value) || 0)
            ).toFixed(2),
            net_salary: document.getElementById('tableNetSalary').innerText.replace(/[₹,]/g,''),
            gross_salary: document.getElementById('tableGrossSalary').innerText.replace(/[₹,]/g,'')
        };
        
        const url = isEditMode ? `{{ url("/payroll") }}/${payrollId}` : '{{ url("/payroll/store") }}';
        const method = isEditMode ? 'PUT' : 'POST';
        
        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payloadData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof Toast !== 'undefined') {
                    Toast.fire({
                        icon: 'success',
                        title: isEditMode ? 'Payroll updated successfully!' : 'Payroll saved successfully!'
                    });
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert(isEditMode ? 'Payroll updated successfully!' : 'Payroll saved successfully!');
                    location.reload();
                }
            } else {
                if (typeof Toast !== 'undefined') {
                    Toast.fire({
                        icon: 'error',
                        title: data.message || 'Error saving payroll'
                    });
                } else {
                    alert(data.message || 'Error saving payroll');
                }
                btn.disabled = false;
            }
        });
    }

    // Reset form when offcanvas is hidden
    function resetPayrollForm() {
        document.getElementById('payrollIdForUpdate').value = '';
        document.getElementById('isEditMode').value = 'false';
        document.getElementById('employeeSelect').value = '';
        document.getElementById('employeeSelectBtn').innerText = 'Select';
        document.getElementById('monthSelect').value = new Date().toISOString().substring(0, 7);
        document.getElementById('inputPayableDays').value = '';
        document.getElementById('inputBasic').value = '';
        document.getElementById('inputHRA').value = '';
        document.getElementById('inputConveyance').value = '';
        document.getElementById('inputMedical').value = '';
        document.getElementById('inputPF').value = '';
        document.getElementById('inputESI').value = '';
        document.getElementById('inputOther').value = '';
        document.getElementById('overrideCheck').checked = false;
        document.getElementById('overrideAmount').disabled = true;
        document.getElementById('overrideAmount').value = '';
        
        currentPayrollData = null;
        isManualDays = false;
        isManualPF = false;
        isManualESI = false;
        currentPayrollTotalDays = 0;
        
        document.getElementById('calculationResult').style.display = 'none';
        document.getElementById('noCalculation').style.display = 'block';
    }

    // Add event listener to reset form when offcanvas is hidden
    document.addEventListener('DOMContentLoaded', function() {
        const offcanvasEl = document.getElementById('payrollCalculationOffcanvas');
        if (offcanvasEl) {
            offcanvasEl.addEventListener('hidden.bs.offcanvas', function() {
                resetPayrollForm();
            });
        }
    });
</script>
@endpush