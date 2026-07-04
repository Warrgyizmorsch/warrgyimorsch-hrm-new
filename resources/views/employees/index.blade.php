@extends('layouts.app')

@section('content')
    <style>
        @media (max-width: 991px) {
            .nxl-container, .nxl-content, .nxl-header, .page-header {
                left: 0 !important;
                margin-left: 0 !important;
                width: 100% !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                transform: none !important;
            }
            .container-fluid {
                padding: 10px !important;
            }
            .card-header {
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                gap: 15px !important;
                padding: 20px !important;
            }
            .card-header > div:last-child {
                width: 100% !important;
                justify-content: center !important;
                flex-wrap: wrap !important;
            }
            
            /* Premium Card View for Mobile */
            .employee-card-mobile {
                background: #ffffff;
                border-radius: 16px;
                padding: 24px;
                margin-bottom: 20px;
                border: 1px solid #f1f5f9;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
                position: relative;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            /* Blue line removed as per user request */
            .emp-mobile-header {
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 20px;
            }
            .emp-mobile-photo {
                width: 64px;
                height: 64px;
                border-radius: 16px;
                object-fit: cover;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            .emp-mobile-info h6 {
                margin: 0;
                font-size: 17px;
                font-weight: 800;
                color: #1e293b;
                line-height: 1.2;
            }
            .emp-mobile-id {
                font-size: 11px;
                font-weight: 700;
                color: #1070e0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-top: 4px;
                display: block;
            }
            .emp-mobile-details {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                padding: 16px;
                background: #f8fafc;
                border-radius: 14px;
                margin-bottom: 20px;
            }
            .detail-item {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .detail-label {
                font-size: 9px;
                text-transform: uppercase;
                color: #94a3b8;
                font-weight: 800;
                letter-spacing: 0.8px;
            }
            .detail-value {
                font-size: 13px;
                color: #334155;
                font-weight: 700;
            }
            .emp-mobile-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .emp-mobile-actions .btn {
                flex: 1 1 calc(50% - 4px);
                border-radius: 10px;
                font-weight: 700;
                font-size: 11px;
                padding: 10px 5px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }
            .emp-mobile-actions .btn-full {
                flex: 1 1 100%;
            }
            .checkbox-wrapper {
                position: absolute;
                top: 20px;
                right: 20px;
            }
            .checkbox-wrapper input {
                width: 20px;
                height: 20px;
                cursor: pointer;
            }
        }
    </style>

    <div class="zoho-page-shell">
        @php
            $employeePrimaryAction = in_array(strtolower(auth()->user()->role), ['admin', 'super_admin', 'super admin'])
                ? '<a href="' . route('employees.create') . '" class="zoho-btn-primary"><i class="feather-plus"></i> Add Employee</a>'
                : '';
            $employeeExtraActions = '';
        @endphp

        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Employees',
            'viewLabel' => 'All Employees',
            'scopeLinks' => [
                ['label' => 'View All Data', 'url' => route('employees.index'), 'active' => true],
            ],
            'primaryAction' => $employeePrimaryAction,
            'showFilter' => true,
            'moreMenu' => view('employees.partials.more-options')->render(),
            'extraActions' => $employeeExtraActions,
        ])

        <div class="main-content zoho-module-content">
        <div class="zoho-people-table-card" id="employeeListPanel">

            <!-- Mobile Search Bar -->
            <div id="mobileSearchSection" class="d-none d-lg-none zoho-list-mobile-search">
                <div class="zoho-list-search">
                    <i class="feather-search"></i>
                    <input type="text" class="employee-page-search-input" value="{{ $search ?? '' }}" onkeyup="syncAndFilter(this)" placeholder="Search employees...">
                </div>
            </div>

                <div class="card-body p-0 zoho-list-body">
                    <div class="zoho-people-table-toolbar d-none d-lg-flex">
                        <div class="zoho-people-table-search">
                            <i class="feather-search"></i>
                            <input type="text" class="employee-page-search-input" value="{{ $search ?? '' }}" onkeyup="syncAndFilter(this)" placeholder="Search in list...">
                        </div>
                        <div class="zoho-list-bar mb-0 border-0 bg-transparent p-0">
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
                    </div>

                    <!-- DESKTOP TABLE VIEW -->
                    <div class="table-responsive d-none d-lg-block zoho-table-wrap">
                        <table class="table zoho-data-table" id="employeeTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th class="col-num">Sr. No. <span class="zoho-sort-icon"><i class="feather-chevron-up"></i><i class="feather-chevron-down"></i></span></th>
                                    <th>Name <span class="zoho-sort-icon"><i class="feather-chevron-up"></i><i class="feather-chevron-down"></i></span></th>
                                    <th>Role <span class="zoho-sort-icon"><i class="feather-chevron-up"></i><i class="feather-chevron-down"></i></span></th>
                                    <th>Department <span class="zoho-sort-icon"><i class="feather-chevron-up"></i><i class="feather-chevron-down"></i></span></th>
                                    <th class="col-attendance">Attendance</th>
                                    <th class="col-photo">Photo</th>
                                    <th class="col-status">Status <span class="zoho-sort-icon"><i class="feather-chevron-up"></i><i class="feather-chevron-down"></i></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $key => $emp)
                                    <tr class="fade-row" id="emp-row-{{ $emp->id }}"
                                        data-employee-id="{{ $emp->id }}" data-employee-dept="{{ $emp->department }}"
                                        data-employee-role="{{ $emp->role }}"
                                        data-employee-search="{{ strtolower($emp->name . ' ' . ($emp->employee_code ?? $emp->id)) }}">
                                        <td><input type="checkbox" class="emp-checkbox" data-id="{{ $emp->id }}"></td>
                                        <td class="col-num">{{ $employees->firstItem() + $key }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="javascript:void(0)" class="zoho-link d-flex align-items-center" role="button" data-bs-toggle="dropdown">
                                                    <span>{{ $emp->name }}</span>
                                                    <span class="ms-2 text-muted">({{ $emp->employee_code }})</span>
                                                </a>
                                                <ul class="dropdown-menu shadow-lg border-0 p-2" style="border-radius:12px;">
                                                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" onclick="viewEmployee({{ $emp->id }})"><i class="feather-eye"></i> View Details</a></li>
                                                    @if(in_array(strtolower(auth()->user()->role), ['admin', 'super_admin', 'super admin']))
                                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('employees.edit', $emp->id) }}"><i class="feather-edit-3"></i> Edit Profile</a></li>
                                                        <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" onclick="deleteEmployee({{ $emp->id }})"><i class="feather-trash-2"></i> Delete</a></li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $emp->role)) }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $emp->department)) }}</td>
                                        <td class="col-attendance"><a href="javascript:void(0)" class="zoho-link" onclick="openAttendanceModal({{ $emp->id }}, '{{ $emp->name }}')"><i class="bi bi-calendar3-event"></i></a></td>
                                        <td class="col-photo">
                                            <div class="zoho-emp-photo-wrap">
                                                @if($emp->photo)
                                                    <img src="{{ asset('storage/' . $emp->photo) }}" class="zoho-emp-avatar" alt=""
                                                        onerror="this.classList.add('d-none');this.parentElement.querySelector('.zoho-emp-initial').classList.remove('d-none');">
                                                    <div class="zoho-emp-initial d-none">{{ strtoupper(substr($emp->name, 0, 1)) }}</div>
                                                @else
                                                    <div class="zoho-emp-initial">{{ strtoupper(substr($emp->name, 0, 1)) }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="col-status">
                                            <select class="form-select account-status" data-user-id="{{ $emp->user->id ?? $emp->id }}">
                                                <option value="active"
                                                    {{ ($emp->user->account_status ?? 'active') == 'active' ? 'selected' : '' }}>
                                                    Active
                                                </option>
                                                <option value="inactive"
                                                    {{ ($emp->user->account_status ?? 'active') == 'inactive' ? 'selected' : '' }}>
                                                    Inactive
                                                </option>
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center py-5 text-muted">No employees found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- MOBILE CARD VIEW -->
                    <div class="d-lg-none px-2 pt-3" id="employeeCardsMobile">
                        @forelse($employees as $emp)
                            <div class="employee-card-mobile fade-row" data-employee-id="{{ $emp->id }}" 
                                 data-employee-name="{{ strtolower($emp->name . ' ' . ($emp->employee_code ?? $emp->id)) }}"
                                 data-employee-dept="{{ strtolower($emp->department) }}" 
                                 data-employee-role="{{ strtolower($emp->role) }}">
                                
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" class="emp-checkbox" data-id="{{ $emp->id }}">
                                </div>

                                <div class="emp-mobile-header">
                                    @if($emp->photo)
                                        <img src="{{ asset('storage/' . $emp->photo) }}" class="emp-mobile-photo">
                                    @else
                                        <div class="emp-mobile-photo d-flex align-items-center justify-content-center bg-soft-primary text-primary fw-bold fs-4">
                                            {{ substr($emp->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="emp-mobile-info">
                                        <h6>{{ $emp->name }}</h6>
                                        <span class="emp-mobile-id">ID: {{ $emp->employee_code }}</span>
                                    </div>
                                </div>

                                <div class="emp-mobile-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Role</span>
                                        <span class="detail-value text-truncate">{{ ucfirst(str_replace('_', ' ', $emp->role)) }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Department</span>
                                        <span class="detail-value text-truncate">{{ ucfirst(str_replace('_', ' ', $emp->department)) }}</span>
                                    </div>
                                </div>

                                <div class="emp-mobile-actions">
                                    <button class="btn btn-soft-primary btn-sm" onclick="viewEmployee({{ $emp->id }})">
                                        <i class="feather-eye"></i> View
                                    </button>
                                    <button class="btn btn-soft-info btn-sm" onclick="openAttendanceModal({{ $emp->id }}, '{{ $emp->name }}')">
                                        <i class="bi bi-calendar3-event"></i> Records
                                    </button>
                                    @if(in_array(strtolower(auth()->user()->role), ['admin', 'super_admin', 'super admin']))
                                        <a href="{{ route('employees.edit', $emp->id) }}" class="btn btn-soft-success btn-sm">
                                            <i class="feather-edit-3"></i> Profile
                                        </a>
                                        <button class="btn btn-soft-danger btn-sm" onclick="deleteEmployee({{ $emp->id }})">
                                            <i class="feather-trash-2"></i> Remove
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted fw-bold">No employees found.</div>
                        @endforelse
                    </div>

                    <!-- FOOTER: record count + pagination -->
                    <div class="zoho-people-table-footer">
                        <span class="zoho-record-count">Total Record Count: <strong>{{ $employees->total() }}</strong></span>
                        @if($employees->hasPages())
                            <div class="zoho-list-pagination">
                                {{ $employees->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>

                </div>
        </div>
        </div>

        <!-- FILTER DRAWER (Zoho People style) -->
        @include('layouts.partials.zoho-filter-drawer', [
            'filterContent' => view('employees.partials.filter-drawer-content', [
                'employeeFilterOptions' => $employeeFilterOptions,
                'employeeFilter' => $employeeFilter ?? '',
                'roleFilter' => $roleFilter ?? '',
                'departmentFilter' => $departmentFilter ?? '',
            ])->render(),
            'resetUrl' => route('employees.index'),
        ])

        <!-- ICONS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

        <!-- RIGHT SIDE MODAL FOR EMPLOYEE DETAILS -->
        <div class="offcanvas offcanvas-end custom-side-modal" tabindex="-1" id="employeeModal"
            aria-labelledby="employeeModalLabel">
            <div class="offcanvas-header p-3 p-md-4"
                style="background: #0f172a; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm d-md-none" data-bs-dismiss="offcanvas" style="background: rgba(255,255,255,0.15); color: white; border-radius: 10px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border: none;">
                        <i class="bi bi-chevron-left" style="font-size: 18px;"></i>
                    </button>
                    <div
                        style="background: rgba(255,255,255,0.1); width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-person-circle text-white fs-5"></i>
                    </div>
                    <div class="d-none d-sm-block">
                        <h5 class="offcanvas-title text-white fw-bold mb-0" id="employeeModalLabel" style="font-size: 16px;">Employee Profile</h5>
                        <div
                            style="font-size: 10px; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 1px; margin-top: 1px;">
                            CODE: <span id="employeeCodeDisplay" style="color: #818cf8; font-weight: 800;">-</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-sm d-flex align-items-center justify-content-center" id="editEmployeeBtn" onclick="editEmployee()"
                        style="background: #22c55e; color: #ffffff; border: none; font-weight: 800; border-radius: 10px; box-shadow: 0 4px 10px rgba(34, 197, 94, 0.3);">
                        <i class="bi bi-pencil-square"></i>
                        <span class="d-none d-md-inline ms-2">Edit</span>
                    </button>
                    <button type="button" class="btn btn-sm d-flex align-items-center justify-content-center" id="deleteEmployeeBtn" onclick="deleteEmployee()"
                        style="background: #ef4444; color: #ffffff; border: none; font-weight: 800; border-radius: 10px; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);">
                        <i class="bi bi-trash"></i>
                        <span class="d-none d-md-inline ms-2">Delete</span>
                    </button>
                    <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="offcanvas" aria-label="Close"
                        style="opacity: 0.8; width: 10px; height: 10px;"></button>
                </div>
            </div>
            <div class="offcanvas-body p-0" id="employeeDetails" style="background: #ffffff;">
                <div class="d-flex align-items-center justify-content-center h-100" style="min-height: 400px;">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                        <p class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">Retrieving Records...
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ATTENDANCE HISTORY MODAL (SEPARATE) -->
        <div class="modal fade" id="attendanceHistoryModal" tabindex="-1" aria-labelledby="attendanceHistoryLabel"
            aria-hidden="true" style="z-index: 9999 !important;">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-header p-4"
                        style="background: linear-gradient(135deg, #1070e0 0%, #0d5fc0 100%); display: flex; justify-content: space-between; align-items: center; border: none;">
                        <div class="d-flex align-items-center gap-3">
                            <div
                                style="background: rgba(255,255,255,0.2); width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-calendar-check text-white fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title text-white fw-bold mb-0" id="attendanceHistoryLabel">Attendance Portal
                                </h5>
                                <div id="attendanceEmpName"
                                    style="font-size: 11px; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 1px; margin-top: 2px;">
                                    -</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                            aria-label="Close"
                            style="background-color: rgba(255,255,255,0.1); border-radius: 50%; padding: 10px;"></button>
                    </div>
                    <div class="modal-body p-4 bg-white">
                        <!-- MONTH FILTER -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2"
                                style="letter-spacing: 0.5px;">Select Month</label>
                            <div class="input-group shadow-sm"
                                style="border-radius: 12px; overflow: hidden; border: 1.5px solid #e2e8f0;">
                                <span class="input-group-text bg-white border-0 ps-3"><i
                                        class="bi bi-filter text-primary"></i></span>
                                <input type="month" id="attendanceMonthFilter"
                                    class="form-control border-0 bg-white fw-bold px-2" value="{{ date('Y-m') }}"
                                    onchange="refreshAttendancePortal()" style="height: 50px;">
                            </div>
                        </div>

                        <div id="attendancePortalContent" style="max-height: 500px; overflow-y: auto;">
                            <!-- TABLE DATA POPULATED VIA JS -->
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light p-3">
                        <button type="button" class="btn btn-dark fw-bold px-4 py-2 rounded-pill shadow-sm w-100"
                            data-bs-dismiss="modal" style="background: #0f172a; border: none; height: 46px;">DISMISS
                            PORTAL</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ANIMATION -->
        <style>
            /* Filter Section Dropdowns - Prevent Clipping */
            #filterSection {
                overflow: visible !important;
            }
            
            #filterSection .card-body {
                overflow: visible !important;
            }
            
            #filterSection .row {
                overflow: visible !important;
            }
            
            #filterSection .col-md-3 {
                overflow: visible !important;
            }
            
            /* Ensure collapse is fully visible */
            .collapse.show {
                overflow: visible !important;
            }
            
            /* Card overflow for dropdowns */
            .card {
                overflow: visible !important;
            }
            
            /* Fixed Dropdown Search & Select UI */
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
                border-color: #1070e0 !important;
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
                color: #1070e0 !important;
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
                border-color: #1070e0;
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
                border-color: #1070e0;
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
                color: #1070e0;
            }

            .wghrm-item-check {
                opacity: 0;
                transition: opacity 0.18s ease;
            }

            .wghrm-item.selected .wghrm-item-check {
                opacity: 1;
            }

            body {
                overflow-y: scroll !important;
            }

            .fade-row {
                animation: fadeIn 0.4s ease-in-out;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .fade-out {
                opacity: 0 !important;
                transform: translateX(20px);
                transition: all 0.4s ease-out;
            }

            .is-invalid {
                border-color: #ef4444 !important;
                box-shadow: 0 0 0 0.25rem rgba(239, 68, 68, 0.1) !important;
            }

            /* RESPONSIVE STYLES */
            .custom-side-modal {
                width: 600px !important;
                border-left: none;
                box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }

            @media (max-width: 991px) {
                .custom-side-modal {
                    width: 100% !important;
                }

                .page-header {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 15px;
                }

                .page-header-right {
                    width: 100%;
                    margin-left: 0 !important;
                }

                .page-header-right-items {
                    display: flex;
                    width: 100%;
                    gap: 10px;
                }

                .page-header-right-items .btn {
                    flex: 1;
                    font-size: 13px;
                    padding: 10px;
                }

                .details-grid {
                    grid-template-columns: 1fr !important;
                }

                .nav-tabs-custom {
                    margin: 0 15px 25px 15px !important;
                    flex-wrap: nowrap !important;
                    overflow-x: auto !important;
                    padding: 4px !important;
                    gap: 4px !important;
                    scrollbar-width: none; /* Hide scrollbar for Firefox */
                }
                .nav-tabs-custom::-webkit-scrollbar {
                    display: none; /* Hide scrollbar for Chrome/Safari */
                }

                .nav-tab {
                    flex: 0 0 auto !important;
                    min-width: 100px !important;
                    font-size: 11px !important;
                    padding: 10px 15px !important;
                }
            }

            @media (max-width: 576px) {
                .nav-tab {
                    flex: 1 1 45%;
                }

                .salary-total {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 5px;
                }

                .header-actions {
                    flex-direction: column;
                    width: 100%;
                }
            }

            .table th,
            .table td {
                font-size: 13px !important;
                padding: 10px 8px !important;
                white-space: nowrap;
            }

            .offcanvas-header {
                padding: 15px !important;
            }

            #editEmployeeBtn,
            #deleteEmployeeBtn {
                padding: 5px 12px !important;
                font-size: 11px !important;
            }
            }

            table thead th {
                border-bottom: 2px solid #f0f0f0;
                color: #555;
                font-weight: 600;
                font-size: 13px;
            }

                {
                background-color: #f9f9f9;
            }

            table tbody tr:nth-child(even) {
                background-color: #f8fafc !important;
            }

            table tbody tr:nth-child(odd) {
                background-color: #fff !important;
            }


            .employee-photo {
                width: 150px;
                height: 150px;
                border-radius: 8px;
                object-fit: cover;
                margin-bottom: 20px;
            }

            .detail-label {
                font-weight: 600;
                color: #555;
                margin-top: 15px;
                margin-bottom: 5px;
            }

            .detail-value {
                color: #333;
                font-size: 14px;
            }

            /* PREMIUM EMPLOYEE DETAILS STYLES */
            .employee-details-container {
                background: #ffffff;
                position: relative;
                min-height: 100%;
            }

            .employee-premium-header {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                border-bottom-left-radius: 30px;
                border-bottom-right-radius: 30px;
                padding: 30px 25px;
                color: white;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                position: relative;
                margin-bottom: 25px;
            }

            .header-photo-wrapper img,
            .header-photo-wrapper .employee-photo-premium {
                width: 100px;
                height: 100px;
                border-radius: 24px;
                object-fit: cover;
                border: 4px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            }

            .nav-tabs-custom {
                display: flex;
                background: #f8fafc;
                padding: 5px;
                border-radius: 16px;
                margin: 0 25px 30px 25px;
                gap: 5px;
                border: 1px solid #e2e8f0;
            }

            .nav-tab {
                flex: 1;
                padding: 12px 5px;
                border: none;
                background: transparent;
                color: #64748b;
                font-weight: 800;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.8px;
                border-radius: 12px;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            .nav-tab i {
                font-size: 14px;
            }

            .nav-tab.active {
                color: #ffffff;
                background: #1070e0;
                box-shadow: 0 8px 20px rgba(56, 88, 249, 0.25);
            }

            .tab-pane {
                display: none;
                padding: 0 20px 30px 20px;
                animation: paneSlideIn 0.4s ease-out;
            }

            @keyframes paneSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .tab-pane.active {
                display: block;
            }

            .details-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .detail-card {
                background: #ffffff;
                border-radius: 14px;
                padding: 18px;
                border: 1px solid #f1f5f9;
                display: flex;
                align-items: center;
                gap: 16px;
                transition: all 0.3s ease;
            }

            .detail-card:hover {
                border-color: #1070e0;
                background: #f8fafc;
                transform: translateY(-2px);
            }

            .detail-card.full-width {
                grid-column: 1 / -1;
            }

            .detail-icon {
                width: 44px;
                height: 44px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                color: #1070e0;
                background: rgba(56, 88, 249, 0.08);
                flex-shrink: 0;
            }

            .detail-content {
                flex: 1;
                min-width: 0;
            }

            .detail-label {
                font-size: 9px;
                font-weight: 800;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-bottom: 4px;
                display: block;
            }

            .detail-value {
                font-size: 14px;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 0;
                word-break: break-all;
            }

            .salary-breakdown {
                background: #f8fafc;
                border-radius: 18px;
                padding: 20px;
                border: 1px solid #eef2f6;
            }

            .salary-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px dashed #e5e7eb;
            }

            .salary-item:last-child {
                border-bottom: none;
            }

            .salary-label {
                display: flex;
                align-items: center;
                gap: 10px;
                color: #64748b;
                font-size: 13px;
                font-weight: 500;
            }

            .salary-amount {
                font-weight: 700;
                color: #1e293b;
                font-size: 14px;
            }

            .salary-total {
                margin-top: 20px;
                padding: 18px 22px;
                background: #f1f5f9;
                border-radius: 14px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: 1px solid #e2e8f0;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
            }

            .salary-total .salary-label {
                color: #475569;
                font-size: 14px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .salary-total .salary-amount.total {
                color: #1e293b !important;
                font-size: 24px !important;
                font-weight: 900 !important;
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            }

            /* Attendance Tab Styles */
            .attendance-history-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-top: 10px;
            }

            .attendance-record-card {
                background: #ffffff;
                border-radius: 14px;
                padding: 15px;
                border: 1px solid #f1f5f9;
                display: flex;
                align-items: center;
                justify-content: space-between;
                transition: all 0.2s ease;
            }

            .attendance-record-card:hover {
                border-color: #1070e0;
                background: #f8fafc;
            }

            /* Attendance Sheet (Table) Styles */
            .att-sheet-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 8px;
            }

            .att-sheet-table th {
                text-transform: uppercase;
                font-size: 11px;
                font-weight: 800;
                color: #64748b;
                letter-spacing: 1px;
                padding: 12px 15px;
                background: #f8fafc;
            }

            .att-sheet-table td {
                padding: 15px;
                background: #fff;
                border-top: 1px solid #f1f5f9;
                border-bottom: 1px solid #f1f5f9;
                font-size: 13px;
            }

            .att-sheet-table tr td:first-child {
                border-left: 1px solid #f1f5f9;
                border-top-left-radius: 12px;
                border-bottom-left-radius: 12px;
            }

            .att-sheet-table tr td:last-child {
                border-right: 1px solid #f1f5f9;
                border-top-right-radius: 12px;
                border-bottom-right-radius: 12px;
            }

            .att-sheet-table tr:hover td {
                background: #f1f5f9;
                border-color: #e2e8f0;
            }

            /* FORCE CLEAR EVERYTHING (NO THEME BLUR) */
            body.modal-open .nxl-container,
            body.modal-open .nxl-header,
            body.modal-open .nxl-navigation,
            body.modal-open .page-header {
                filter: none !important;
                transition: none !important;
            }

            .modal-backdrop.show {
                opacity: 0.3 !important;
                /* Extremely light so you can see behind clearly */
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }

            .modal-content {
                background: #ffffff !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
                box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3) !important;
                border: 1px solid #e2e8f0 !important;
            }

            .att-sheet-table td {
                color: #0f172a !important;
                /* Deepest black-slate for max clarity */
                font-weight: 500;
            }
        </style>

        <!-- SEARCH & VIEW MODAL -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script>
            // Legacy search binding guard: current page uses .employee-page-search-input fields instead.
            const legacySearchInput = document.getElementById('searchInput');
            if (legacySearchInput) {
                legacySearchInput.addEventListener('keyup', function () {
                    syncAndFilter(this);
                });
            }

            // Select All Checkbox
            document.getElementById('selectAll').addEventListener('change', function () {
                document.querySelectorAll('.emp-checkbox').forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // View Employee Details in Right Side Panel
            function viewEmployee(empId) {
                // Store current employee ID for edit/delete buttons
                window.currentEmployeeId = empId;

                fetch(`/api/employees/${empId}`)
                    .then(res => res.json())
                    .then(emp => {
                        // Update employee code in header
                        document.getElementById('employeeCodeDisplay').textContent = emp.employee_code || '-';

                        let photoHtml = emp.photo
                            ? `<img src="/storage/${emp.photo}" class="employee-photo-premium">`
                            : `<div class="employee-photo-premium bg-light d-flex align-items-center justify-content-center text-muted">
                                                                    <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                                                                   </div>`;

                        const html = `
                                                                <div class="employee-details-container">
                                                                    <div class="employee-premium-header">
                                                                        <div class="d-flex align-items-center gap-4">
                                                                            <div class="header-photo-wrapper">
                                                                                ${emp.photo ?
                                `<img src="{{ asset('storage') }}/${emp.photo}" alt="${emp.name}" style="width:100px; height:100px; border-radius: 20px; object-fit: cover; border: 3px solid rgba(255,255,255,0.2);">` :
                                `<div class="employee-photo-premium d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.2); width:100px; height:100px; border-radius: 20px; border: 3px solid rgba(255,255,255,0.1); font-size: 42px; font-weight: 800; color: white;">
                                                                                        ${emp.name.charAt(0)}
                                                                                    </div>`
                            }
                                                                            </div>
                                                                            <div>
                                                                                <h3 class="mb-1 fw-bold text-white">${emp.name}</h3>
                                                                                <div class="d-flex align-items-center gap-2 opacity-75">
                                                                                    <i class="bi bi-briefcase small"></i>
                                                                                    <span class="small fw-bold text-uppercase" style="letter-spacing: 1px;">${emp.designation || 'EMPLOYEE'}</span>
                                                                                </div>
                                                                                <div class="d-flex align-items-center gap-2 mt-2">
                                                                                    <span class="badge bg-primary px-3 rounded-pill" style="font-size: 10px;">${emp.department || 'N/A'}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="nav-tabs-custom">
                                                                        <button class="nav-tab active" id="tabPersonal" onclick="showTab('personal')"><i class="bi bi-person"></i>PERSONAL</button>
                                                                        <button class="nav-tab" id="tabBank" onclick="showTab('bank')"><i class="bi bi-bank"></i>BANK</button>
                                                                        <button class="nav-tab" id="tabSalary" onclick="showTab('salary')"><i class="bi bi-cash-coin"></i>SALARY</button>
                                                                    </div>

                                                                    <div class="tab-content" id="modalTabContent">
                                                                        <!-- PERSONAL TAB -->
                                                                        <div id="employeeTabPersonal" class="tab-pane active">
                                                                            <div class="details-grid">
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-person-fill"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Name</label>
                                                                                        <p class="detail-value">${emp.name}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-phone"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Mobile</label>
                                                                                        <p class="detail-value">${emp.mobile_number || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card full-width">
                                                                                    <div class="detail-icon"><i class="bi bi-envelope"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Email Address</label>
                                                                                        <p class="detail-value">${emp.email || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-calendar-event"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Date of Birth</label>
                                                                                        <p class="detail-value">${emp.date_of_birth || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-gender-ambiguous"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Gender</label>
                                                                                        <p class="detail-value">${emp.gender ? emp.gender.charAt(0).toUpperCase() + emp.gender.slice(1) : 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-card-heading"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Aadhaar Number</label>
                                                                                        <p class="detail-value">${emp.aadhaar_number || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-credit-card-2-back"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">PAN Number</label>
                                                                                        <p class="detail-value">${emp.pan_number || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card full-width">
                                                                                    <div class="detail-icon"><i class="bi bi-geo-alt"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Residential Address</label>
                                                                                        <p class="detail-value">${emp.address || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>

                                                                                <!-- WORK & JOB DETAILS (Merged into Personal) -->
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-shield-lock"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Role</label>
                                                                                        <p class="detail-value">${emp.role ? emp.role.replace(/_/g, ' ').toUpperCase() : 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-calendar-check"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Joining Date</label>
                                                                                        <p class="detail-value">${emp.date_of_joining || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-clock"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Shift Hours</label>
                                                                                        <p class="detail-value">${formatTime12h(emp.time_in)} - ${formatTime12h(emp.time_out)}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-calendar-x"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Leave Balance</label>
                                                                                        <p class="detail-value text-primary">${emp.leave || '0'} Days</p>
                                                                                    </div>
                                                                                </div>
                                                                                <!-- Statutory details will follow -->

                                                                                <!-- STATUTORY DETAILS (Merged into Personal) -->
                                                                                <div class="detail-card full-width" style="border-left: 4px solid #1070e0;">
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label text-primary">Statutory Enrollment</label>
                                                                                        <div class="d-flex flex-column gap-3 mt-2">
                                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                                <span class="small fw-bold text-dark">PF Number</span>
                                                                                                <span class="status-badge ${emp.pf ? 'status-enrolled' : 'status-not-enrolled'}">${emp.pf ? emp.pf_number : 'Not Enrolled'}</span>
                                                                                            </div>
                                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                                <span class="small fw-bold text-dark">ESI Number</span>
                                                                                                <span class="status-badge ${emp.esi ? 'status-enrolled' : 'status-not-enrolled'}">${emp.esi ? emp.esi_number : 'Not Enrolled'}</span>
                                                                                            </div>
                                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                                <span class="small fw-bold text-dark">Insurance</span>
                                                                                                <span class="status-badge ${emp.insurance ? 'status-enrolled' : 'status-not-enrolled'}">${emp.insurance ? emp.insurance_provider : 'Not Enrolled'}</span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- BANK TAB -->
                                                                        <div id="employeeTabBank" class="tab-pane">
                                                                            <div class="details-grid">
                                                                                <div class="detail-card full-width">
                                                                                    <div class="detail-icon"><i class="bi bi-bank"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Bank Name</label>
                                                                                        <p class="detail-value">${emp.bank_name || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-hash"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">Account Number</label>
                                                                                        <p class="detail-value">${emp.account_number || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="detail-card">
                                                                                    <div class="detail-icon"><i class="bi bi-upc-scan"></i></div>
                                                                                    <div class="detail-content">
                                                                                        <label class="detail-label">IFSC Code</label>
                                                                                        <p class="detail-value text-primary">${emp.ifsc_code || 'N/A'}</p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- SALARY TAB -->
                                                                        <div id="employeeTabSalary" class="tab-pane">
                                                                            <div class="salary-container">
                                                                                <div class="salary-breakdown full-width">
                                                                                    <div class="salary-item">
                                                                                        <div class="salary-label"><i class="bi bi-cash"></i>Basic Salary</div>
                                                                                        <div class="salary-amount">₹ ${parseFloat(emp.basic_salary || 0).toLocaleString('en-IN')}</div>
                                                                                    </div>
                                                                                    <div class="salary-item">
                                                                                        <div class="salary-label"><i class="bi bi-house"></i>HRA</div>
                                                                                        <div class="salary-amount">₹ ${parseFloat(emp.hra || 0).toLocaleString('en-IN')}</div>
                                                                                    </div>
                                                                                    <div class="salary-item">
                                                                                        <div class="salary-label"><i class="bi bi-truck"></i>Conveyance</div>
                                                                                        <div class="salary-amount">₹ ${parseFloat(emp.conveyance_allowance || 0).toLocaleString('en-IN')}</div>
                                                                                    </div>
                                                                                    <div class="salary-item">
                                                                                        <div class="salary-label"><i class="bi bi-activity"></i>Medical</div>
                                                                                        <div class="salary-amount">₹ ${parseFloat(emp.medical_allowance || 0).toLocaleString('en-IN')}</div>
                                                                                    </div>
                                                                                    <div class="salary-item">
                                                                                        <div class="salary-label"><i class="bi bi-gift"></i>Other Allowance</div>
                                                                                        <div class="salary-amount">₹ ${parseFloat(emp.other_allowance || 0).toLocaleString('en-IN')}</div>
                                                                                    </div>
                                                                                    <div class="salary-total">
                                                                                        <div class="salary-label"><strong>Total Salary</strong></div>
                                                                                        <div class="salary-amount total">₹ ${(parseFloat(emp.basic_salary || 0) + parseFloat(emp.hra || 0) + parseFloat(emp.conveyance_allowance || 0) + parseFloat(emp.medical_allowance || 0) + parseFloat(emp.other_allowance || 0)).toLocaleString('en-IN')}</div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- ATTENDANCE TAB -->
                                                                        <div id="employeeTabAttendance" class="tab-pane">
                                                                            <div id="attendanceHistoryContent" class="attendance-history-list">
                                                                                <div class="text-center py-5 text-muted">
                                                                                    <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                                                                                    <p class="small fw-bold">Loading Attendance History...</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>      </div>
                                                                </div>
                                                            `;

                        document.getElementById('employeeDetails').innerHTML = html;
                        new bootstrap.Offcanvas(document.getElementById('employeeModal')).show();
                    })
                    .catch(err => {
                        console.error('Error loading employee:', err);
                        document.getElementById('employeeDetails').innerHTML = '<div class="p-4 text-center text-danger"><i class="bi bi-exclamation-triangle fs-1"></i><p class="mt-2">Error loading employee details</p></div>';
                    });
            }

            // Edit Employee
            function editEmployee(empId = null) {
                const id = empId || window.currentEmployeeId;
                if (!id) {
                    alert('No employee selected');
                    return;
                }
                window.location.href = `{{ url('/employees') }}/${id}/edit`;
            }

            // Delete Employee
            function deleteEmployee(empId = null) {
                const id = empId || window.currentEmployeeId;
                if (!id) {
                    alert('No employee selected');
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#1070e0',
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
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                        fetch(`{{ url('/employees') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin'
                        })
                            .then(res => {
                                if (res.status === 200 || res.status === 204 || res.ok) {
                                    // Close modal if open
                                    const modal = bootstrap.Offcanvas.getInstance(document.getElementById('employeeModal'));
                                    if (modal) modal.hide();

                                    // Remove row from table
                                    const row = document.getElementById(`emp-row-${id}`);
                                    if (row) {
                                        row.classList.add('fade-out'); // Add a fade-out effect
                                        setTimeout(() => {
                                            row.remove();
                                            // Check if table is empty
                                            const visibleRows = document.querySelectorAll("#employeeTable tbody tr:not(#noResultsRow)").length;
                                            if (visibleRows === 0) {
                                                const tbody = document.querySelector("#employeeTable tbody");
                                                const noResultsRow = document.createElement('tr');
                                                noResultsRow.id = 'noResultsRow';
                                                noResultsRow.innerHTML = '<td colspan="6" class="text-center py-4 text-muted">No employees found. <a href="{{ route('employees.create') }}">Add one</a></td>';
                                                tbody.appendChild(noResultsRow);
                                            }
                                        }, 400);
                                    }

                                    if (typeof Toast !== 'undefined') {
                                        Toast.fire({
                                            icon: 'success',
                                            title: 'Employee deleted'
                                        });
                                    }
                                } else {
                                    alert('Error deleting employee');
                                }
                            })
                            .catch(err => {
                                console.error('Delete Error:', err);
                                alert('Error deleting employee');
                            });
                    }
                });
            }

            // Tab switching function
            function showTab(tab) {
                // Hide all tabs
                document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
                // Remove active class from all buttons
                document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));

                // Show selected tab and add active class
                const targetPane = document.getElementById('employeeTab' + tab.charAt(0).toUpperCase() + tab.slice(1));
                const targetButton = document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1));

                if (targetPane) targetPane.classList.add('active');
                if (targetButton) targetButton.classList.add('active');
            }

            // Helper to format 24h to 12h (AM/PM)
            function formatTime12h(timeStr) {
                if (!timeStr || timeStr === '--:--' || timeStr === '00:00') return '--:--';
                try {
                    // Handle HH:mm or HH:mm:ss
                    let [hours, minutes] = timeStr.split(':');
                    hours = parseInt(hours);
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12; // the hour '0' should be '12'
                    return `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
                } catch (e) {
                    return timeStr;
                }
            }

            // Helper to calculate hours between two 12h time strings
            function calculateAttendanceHours(checkIn, checkOut) {
                if (!checkIn || !checkOut || checkIn === '--:--' || checkOut === '--:--') return 0;

                try {
                    function parseTime(t) {
                        let [time, ampm] = t.split(' ');
                        let [hrs, mins] = time.split(':');
                        hrs = parseInt(hrs);
                        mins = parseInt(mins);
                        if (ampm === 'PM' && hrs < 12) hrs += 12;
                        if (ampm === 'AM' && hrs === 12) hrs = 0;
                        return hrs + (mins / 60);
                    }

                    const inHrs = parseTime(checkIn);
                    const outHrs = parseTime(checkOut);

                    if (outHrs > inHrs) {
                        return outHrs - inHrs;
                    }
                    return 0;
                } catch (e) {
                    return 0;
                }
            }

            // Open Attendance Portal
            function openAttendanceModal(empId, empName) {
                window.currentAttendanceEmpId = empId;
                document.getElementById('attendanceEmpName').textContent = empName;

                // Trigger modal
                const attModal = new bootstrap.Modal(document.getElementById('attendanceHistoryModal'));
                attModal.show();

                // Load history
                refreshAttendancePortal();
            }

            // Refresh Attendance Portal history
            function refreshAttendancePortal() {
                const empId = window.currentAttendanceEmpId;
                const month = document.getElementById('attendanceMonthFilter').value;
                const container = document.getElementById('attendancePortalContent');

                container.innerHTML = `
                                                        <div class="text-center py-5 text-muted">
                                                            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                                                            <p class="small fw-bold">Loading Records...</p>
                                                        </div>
                                                    `;

                fetch(`/api/employees/${empId}/attendance?month=${month}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.length === 0) {
                            container.innerHTML = '<div class="text-center py-5 text-muted small fw-bold">No attendance records found for this month.</div>';
                            return;
                        }

                        let html = `
                                                                <table class="att-sheet-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th class="ps-4">Date</th>
                                                                            <th>Check In</th>
                                                                            <th>Check Out</th>
                                                                            <th>Duration</th>
                                                                            <th class="text-center">Status</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                            `;

                        data.forEach(record => {
                            const dateObj = new Date(record.attendance_date);
                            const day = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
                            const dateNum = dateObj.getDate();
                            const monthStr = dateObj.toLocaleDateString('en-US', { month: 'short' });
                            const fullDate = `${dateNum} ${monthStr}`;

                            const statusClass = {
                                'present': 'bg-soft-success text-success',
                                'absent': 'bg-soft-danger text-danger',
                                'leave': 'bg-soft-info text-info',
                                'half_day': 'bg-soft-warning text-warning',
                                'late': 'bg-soft-warning text-warning'
                            }[record.status] || 'bg-light';

                            html += `
                                                                    <tr>
                                                                        <td class="ps-4">
                                                                            <div class="fw-bold text-dark">${fullDate}</div>
                                                                            <div class="small text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">${day}</div>
                                                                        </td>
                                                                        <td class="fw-bold text-dark">${formatTime12h(record.check_in)}</td>
                                                                        <td class="fw-bold text-dark">${formatTime12h(record.check_out)}</td>
                                                                        <td>
                                                                            <span class="text-primary fw-bold" style="font-size: 12px;">
                                                                                <i class="bi bi-clock-history me-1"></i>
                                                                                ${(function () {
                                    let hrs = parseFloat(record.total_hours || 0);
                                    if (hrs <= 0) {
                                        const inStr = formatTime12h(record.check_in);
                                        const outStr = formatTime12h(record.check_out);
                                        hrs = calculateAttendanceHours(inStr, outStr);
                                    }
                                    return Math.max(0, hrs).toFixed(1);
                                })()} hrs
                                                                            </span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge ${statusClass} rounded-pill px-3 fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                                                                ${record.status ? record.status.replace(/_/g, ' ') : 'N/A'}
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                `;
                        });
                        html += '</tbody></table>';
                        container.innerHTML = html;
                    })
                    .catch(err => {
                        console.error('Error fetching attendance:', err);
                    });
            }

            function buildEmployeeFilterUrl() {
                const url = new URL(window.location.href);
                const employeeId = document.getElementById('filterEmployeeName')?.value || '';
                const globalSearch = (document.querySelector('.employee-page-search-input')?.value || '').trim();
                const department = document.getElementById('filterDepartment')?.value || '';
                const role = document.getElementById('filterRole')?.value || '';

                if (employeeId) {
                    url.searchParams.set('employee_id', employeeId);
                } else {
                    url.searchParams.delete('employee_id');
                }

                if (globalSearch) {
                    url.searchParams.set('search', globalSearch);
                } else {
                    url.searchParams.delete('search');
                }

                if (department) {
                    url.searchParams.set('department', department);
                } else {
                    url.searchParams.delete('department');
                }

                if (role) {
                    url.searchParams.set('role', role);
                } else {
                    url.searchParams.delete('role');
                }

                url.searchParams.set('page', '1');

                return url.toString();
            }

            // Apply Filters
            function applyFilters() {
                const drawerEl = document.getElementById('zohoFilterDrawer');
                if (drawerEl) {
                    const drawer = bootstrap.Offcanvas.getInstance(drawerEl);
                    if (drawer) drawer.hide();
                }
                loadEmployeeListAjax(buildEmployeeFilterUrl());
            }

            // Clear Filters
            function clearFilters() {
                window.location.href = "{{ route('employees.index') }}";
            }

            // Delete Selected Employees (Bulk Delete)
            function deleteSelectedEmployees() {
                const selectedCheckboxes = document.querySelectorAll('.emp-checkbox:checked');
                const employeeIds = Array.from(selectedCheckboxes).map(cb => cb.getAttribute('data-id'));

                if (employeeIds.length === 0) {
                    alert('Please select at least one employee');
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Delete ${employeeIds.length} employee(s)? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#1070e0',
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
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                        // Delete each employee
                        Promise.all(employeeIds.map(id =>
                            fetch(`/employees/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                },
                                credentials: 'same-origin'
                            }).then(res => {
                                if (res.ok) {
                                    const row = document.getElementById(`emp-row-${id}`);
                                    if (row) row.remove();
                                }
                                return res;
                            })
                        ))
                            .then(() => {
                                // Check if table is empty
                                const visibleRows = document.querySelectorAll("#employeeTable tbody tr:not(#noResultsRow)").length;
                                if (visibleRows === 0) {
                                    const tbody = document.querySelector("#employeeTable tbody");
                                    const noResultsRow = document.createElement('tr');
                                    noResultsRow.id = 'noResultsRow';
                                    noResultsRow.innerHTML = '<td colspan="6" class="text-center py-4 text-muted">No employees found. <a href="{{ route('employees.create') }}">Add one</a></td>';
                                    tbody.appendChild(noResultsRow);
                                }

                                // Uncheck select all
                                document.getElementById('selectAll').checked = false;

                                if (typeof Toast !== 'undefined') {
                                    Toast.fire({
                                        icon: 'success',
                                        title: 'Selected employees deleted'
                                    });
                                }
                            })
                            .catch(err => {
                                console.error('Delete Error:', err);
                                alert('Error deleting employees');
                            });
                    }
                });
            }
            function initializeSearchDropdown(dropdownId, inputId, defaultLabel) {
                const dropdown = document.getElementById(dropdownId);
                if (!dropdown) {
                    console.warn(`Dropdown ${dropdownId} not found`);
                    return;
                }

                const trigger = dropdown.querySelector('.wghrm-dropdown-trigger');
                const triggerText = dropdown.querySelector('.wghrm-trigger-text');
                const menu = dropdown.querySelector('.wghrm-dropdown-menu');
                const searchInput = dropdown.querySelector('.wghrm-search-input');
                const hiddenInput = document.getElementById(inputId);
                const items = dropdown.querySelectorAll('.wghrm-item');

                if (!trigger || !menu) {
                    console.warn(`Dropdown components missing for ${dropdownId}`, {trigger, menu});
                    return;
                }

                console.log(`Initializing dropdown: ${dropdownId}`);

                const closeMenu = () => {
                    menu.style.display = 'none';
                    trigger.classList.remove('open');
                    if (searchInput) {
                        searchInput.value = '';
                        filterItems('');
                    }
                };

                const openMenu = () => {
                    menu.style.display = 'block';
                    trigger.classList.add('open');
                    if (searchInput) {
                        setTimeout(() => searchInput.focus(), 50);
                    }
                };

                const filterItems = (term) => {
                    const normalizedTerm = term.trim().toLowerCase();
                    items.forEach(item => {
                        const label = (item.dataset.text || item.textContent || '').toLowerCase();
                        item.style.display = label.includes(normalizedTerm) ? 'flex' : 'none';
                    });
                };

                trigger.addEventListener('click', function (event) {
                    event.stopPropagation();
                    
                    // Check if menu is currently visible
                    const isOpen = menu.style.display === 'block';
                    console.log(`Dropdown trigger clicked for ${dropdownId}, display:`, menu.style.display, 'isOpen:', isOpen);

                    // Close all other dropdowns
                    document.querySelectorAll('.wghrm-search-dropdown .wghrm-dropdown-menu').forEach(otherMenu => {
                        if (otherMenu !== menu) {
                            otherMenu.style.display = 'none';
                        }
                    });
                    document.querySelectorAll('.wghrm-search-dropdown .wghrm-dropdown-trigger').forEach(otherTrigger => {
                        if (otherTrigger !== trigger) {
                            otherTrigger.classList.remove('open');
                        }
                    });

                    // Toggle current dropdown
                    if (!isOpen) {
                        openMenu();
                        console.log(`✓ OPENED dropdown ${dropdownId}`);
                    } else {
                        closeMenu();
                        console.log(`✗ CLOSED dropdown ${dropdownId}`);
                    }
                });

                items.forEach(item => {
                    item.addEventListener('click', function () {
                        items.forEach(option => option.classList.remove('selected'));
                        item.classList.add('selected');
                        hiddenInput.value = item.dataset.value || '';
                        triggerText.textContent = item.dataset.text || defaultLabel;
                        closeMenu();
                        console.log(`Selected item in ${dropdownId}:`, item.dataset.text);
                    });
                });

                if (searchInput) {
                    searchInput.addEventListener('click', event => event.stopPropagation());
                    searchInput.addEventListener('input', function () {
                        filterItems(searchInput.value);
                    });
                }

                document.addEventListener('click', function (event) {
                    if (!dropdown.contains(event.target)) {
                        closeMenu();
                    }
                });
            }

            // Unified Search Sync & Filter (AJAX — no full page reload)
            let employeeSearchTimer;
            let employeeListAbortController = null;
            const employeesIndexPath = @json(parse_url(route('employees.index'), PHP_URL_PATH));

            async function loadEmployeeListAjax(url, options = {}) {
                const updateHistory = options.updateHistory !== false;
                const panel = document.getElementById('employeeListPanel');
                if (!panel) return;

                if (employeeListAbortController) {
                    employeeListAbortController.abort();
                }
                employeeListAbortController = new AbortController();

                panel.classList.add('zoho-list-loading');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        signal: employeeListAbortController.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load employees');
                    }

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    const tbody = doc.querySelector('#employeeTable tbody');
                    const currentTbody = document.querySelector('#employeeTable tbody');
                    if (tbody && currentTbody) {
                        currentTbody.innerHTML = tbody.innerHTML;
                    }

                    const mobile = doc.querySelector('#employeeCardsMobile');
                    const currentMobile = document.querySelector('#employeeCardsMobile');
                    if (mobile && currentMobile) {
                        currentMobile.innerHTML = mobile.innerHTML;
                    }

                    const footer = doc.querySelector('.zoho-people-table-footer');
                    const currentFooter = document.querySelector('.zoho-people-table-footer');
                    if (footer && currentFooter) {
                        currentFooter.innerHTML = footer.innerHTML;
                    }

                    const showBtn = doc.querySelector('#showEntriesBtn');
                    const currentShowBtn = document.querySelector('#showEntriesBtn');
                    if (showBtn && currentShowBtn) {
                        currentShowBtn.textContent = showBtn.textContent.trim();
                    }

                    const searchParam = new URL(url, window.location.origin).searchParams.get('search') || '';
                    document.querySelectorAll('.employee-page-search-input').forEach(function (input) {
                        input.value = searchParam;
                    });

                    const selectAll = document.getElementById('selectAll');
                    if (selectAll) {
                        selectAll.checked = false;
                    }

                    if (updateHistory) {
                        history.pushState({ employeeList: true }, '', url);
                    }

                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Employee list load failed:', error);
                    }
                } finally {
                    panel.classList.remove('zoho-list-loading');
                }
            }

            function syncAndFilter(el) {
                const val = el.value;
                document.querySelectorAll('.employee-page-search-input').forEach(function (input) {
                    if (input !== el) {
                        input.value = val;
                    }
                });

                window.clearTimeout(employeeSearchTimer);
                employeeSearchTimer = window.setTimeout(function () {
                    loadEmployeeListAjax(buildEmployeeFilterUrl());
                }, 400);
            }

            window.addEventListener('popstate', function () {
                loadEmployeeListAjax(window.location.href, { updateHistory: false });
            });

            document.addEventListener('click', function (event) {
                const link = event.target.closest('#employeeListPanel a[href]');
                if (!link) return;

                const href = link.getAttribute('href');
                if (!href || href.startsWith('javascript') || link.target === '_blank') return;

                let url;
                try {
                    url = new URL(link.href, window.location.origin);
                } catch (e) {
                    return;
                }

                if (url.pathname !== employeesIndexPath) return;

                const inFooter = link.closest('.zoho-people-table-footer');
                const inPerPage = link.closest('.zoho-people-table-toolbar .wghrm-custom-dropdown-menu');
                if (!inFooter && !inPerPage) return;

                event.preventDefault();
                loadEmployeeListAjax(link.href);
            });

            document.addEventListener('DOMContentLoaded', function () {
                console.log('=== DOMContentLoaded fired ===');
                
                // Reset all dropdowns to initial closed state
                console.log('Resetting all dropdowns to closed state...');
                document.querySelectorAll('.wghrm-dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
                document.querySelectorAll('.wghrm-dropdown-trigger').forEach(trigger => {
                    trigger.classList.remove('open');
                });
                
                console.log('Initializing dropdowns...');
                initializeSearchDropdown('employeeFilterDropdown', 'filterEmployeeName', 'All Employees');
                initializeSearchDropdown('roleFilterDropdown', 'filterRole', 'All Roles');
                initializeSearchDropdown('departmentFilterDropdown', 'filterDepartment', 'All Departments');
                
                console.log('=== Initialization complete ===');
            });


            $(document).on('change', '.account-status', function () {
                let status = $(this).val();
                let userId = $(this).data('user-id');
                $.ajax({
                    url: "{{ route('users.update-accountStatus') }}",
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        user_id: userId,
                        status: status
                    },
                    
                    success: function(response) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Something went wrong');
                        } else {
                            alert('Something went wrong');
                        }
                    }
                });
            });

            // Filter drawer: search filter fields
            document.getElementById('zohoFilterDrawerSearch')?.addEventListener('input', function (e) {
                const term = e.target.value.toLowerCase();
                document.querySelectorAll('#zohoFilterDrawer [data-filter-field]').forEach(function (field) {
                    const label = (field.querySelector('.zoho-filter-label')?.textContent || field.textContent || '').toLowerCase();
                    field.style.display = !term || label.includes(term) ? '' : 'none';
                });
            });

            document.getElementById('zohoFilterDrawer')?.addEventListener('shown.bs.offcanvas', function () {
                if (typeof feather !== 'undefined') feather.replace();
            });
        </script>

    </div>{{-- /.zoho-page-shell --}}

@endsection
