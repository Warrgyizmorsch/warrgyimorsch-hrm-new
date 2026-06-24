<header class="nxl-header shadow-sm">
    <div class="header-wrapper">
        <!--! [Start] Header Left !-->
        <div class="header-left d-flex align-items-center gap-4">
            <!--! [Start] nxl-head-mobile-toggler !-->
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </div>
            </a>
            <!--! [Start] nxl-head-mobile-toggler !-->
            <!--! [Start] nxl-navigation-toggle !-->
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button">
                    <i class="feather-align-left"></i>
                </a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display: none">
                    <i class="feather-arrow-right"></i>
                </a>
            </div>
            <!--! [End] nxl-navigation-toggle !-->
        </div>
        <!--! [End] Header Left !-->
        <!--! [Start] Header Right !-->
        <div class="header-right ms-auto">
            <div class="d-flex align-items-center">
                <button
                    class="btn btn-primary rounded-3"
                    data-bs-toggle="modal"
                    data-bs-target="#ticketModal">

                    Raise Ticket
                </button>
                <div class="nxl-h-item dark-light-theme">
                    <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button">
                        <i class="feather-moon"></i>
                    </a>
                    <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none">
                        <i class="feather-sun"></i>
                    </a>
                </div>
                <div class="dropdown nxl-h-item">
                    @php
                        $notifications = collect();
                        $role = strtoupper(auth()->user()->role);
                        
                        // --- Celebrations Notification Logic (NEW) ---
                        $today = now()->format('m-d');
                        $celebrations = \App\Models\Employee::where(function($q) use ($today) {
                                $q->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$today])
                                  ->orWhereRaw("DATE_FORMAT(date_of_joining, '%m-%d') = ?", [$today]);
                            })
                            ->get()
                            ->map(function($emp) use ($today) {
                                $isBirthday = \Carbon\Carbon::parse($emp->date_of_birth)->format('m-d') == $today;
                                $type = $isBirthday ? 'Birthday' : 'Work Anniversary';
                                $years = \Carbon\Carbon::parse($emp->date_of_joining)->diffInYears(now());
                                
                                return (object)[
                                    'id' => 'celebration-' . $emp->id,
                                    'type' => 'celebration',
                                    'event_type' => $type,
                                    'employee' => $emp,
                                    'years' => $years,
                                    'message' => $isBirthday 
                                        ? "Today is {$emp->name}'s Birthday! 🎂" 
                                        : "Today is {$emp->name}'s {$years}" . ($years==1?'st':($years==2?'nd':($years==3?'rd':'th'))) . " Work Anniversary! 🏆",
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            });

                        if ($role == 'ADMIN' || $role == 'SUPER_ADMIN' || $role == 'SUPER ADMIN') {

                            // 1. Leave Notifications
                            $leaveNotifications = \App\Models\LeaveApplication::with('employee')
                                ->latest()
                                ->get();

                            // 2. Payroll Comment Notifications
                            $payrollNotifications = \App\Models\Payroll::with('employee')
                                ->whereNotNull('remarks')
                                ->where('remarks', '!=', '')
                                ->latest()
                                ->get();

                            // Merge all
                            $notifications = $celebrations
                            ->concat($leaveNotifications)
                            ->concat($payrollNotifications)
                            ->sortByDesc(function ($item) {
                                return isset($item->type) && $item->type == 'celebration'
                                    ? now()
                                    : (isset($item->remarks) ? $item->updated_at : $item->created_at);
                            })->take(5);

                        } else {
                            $myLeaves = \App\Models\LeaveApplication::where('employee_id', auth()->user()->employee_id)
                                ->whereIn('status', ['approved', 'rejected', 'Approved', 'Rejected'])
                                ->where('updated_at', '>=', now()->subDays(3))
                                ->latest()
                                ->get();
                                
                            $notifications = $celebrations
                                ->concat($myLeaves)
                                ->sortByDesc('updated_at')
                                ->take(5);
                        }
                    @endphp
                    <a class="nxl-head-link me-3" data-bs-toggle="dropdown" href="#" role="button"
                        data-bs-auto-close="outside">
                        <i class="feather-bell"></i>
                        @if(count($notifications) > 0)
                            <span class="badge bg-danger nxl-h-badge">{{ count($notifications) }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-notifications-menu">
                        <div class="d-flex justify-content-between align-items-center notifications-head">
                            <h6 class="fw-bold mb-0">Notifications</h6>
                            <a href="javascript:void(0);" class="fs-11 text-success text-uppercase border-bottom border-success">
                                <span>Make as Read</span>
                            </a>
                        </div>
                        <div class="notifications-list" style="max-height: 400px; overflow-y: auto;">
                            @forelse($notifications as $item)
                                <div class="notifications-item">
                                    @php
                                        $isCelebration = isset($item->type) && $item->type == 'celebration';
                                        $isPayroll = !$isCelebration && isset($item->remarks);
                                        $emp = (in_array($role, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']) || $isCelebration) ? $item->employee : auth()->user()->employee;
                                        $photo = ($emp && $emp->photo) ? asset('storage/' . $emp->photo) : null;
                                    @endphp
                                    @if($photo)
                                        <img src="{{ $photo }}" alt="" class="rounded me-3 border" style="width: 35px; height: 35px; object-fit: cover;" />
                                    @else
                                        <div class="avatar-text avatar-md bg-soft-primary text-primary rounded me-3 border d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px; font-size: 12px;">
                                            {{ substr($emp->name ?? '?', 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="notifications-desc">
                                        @if($isCelebration)
                                            <a href="{{ route('employees.employeeDays') }}" class="font-body text-truncate-2-line">
                                                <span class="fw-bold text-dark">{{ $item->message }}</span>
                                            </a>
                                        @else
                                            <a href="{{ $isPayroll ? route('payroll.index') : route('leave.history') }}" class="font-body text-truncate-2-line">
                                                @if(in_array($role, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']))
                                                    @if($isPayroll)
                                                        <span class="fw-semibold text-dark">{{ $item->employee->name ?? 'Someone' }}</span>
                                                        commented: <span class="text-muted">"{{ $item->remarks }}"</span>
                                                    @else
                                                        <span class="fw-semibold text-dark">{{ $emp->name ?? 'Someone' }}</span>
                                                        applied for {{ $item->leave_type }} leave.
                                                    @endif
                                                @else
                                                    Your leave application for {{ $item->leave_type }} has been
                                                    <span class="fw-bold {{ $item->status == 'approved' ? 'text-success' : 'text-danger' }}">
                                                        {{ strtoupper($item->status) }}
                                                    </span>.
                                                @endif
                                            </a>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="notifications-date text-muted border-bottom border-bottom-dashed" style="font-size: 10px;">
                                                @if($isCelebration)
                                                    <i class="feather-gift fs-10 me-1 text-danger"></i> Happening Today!
                                                @elseif(in_array($role, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']))
                                                    @if($isPayroll)
                                                        <i class="feather-message-square fs-10 me-1"></i> Commented: {{ $item->updated_at->format('d M, h:i A') }}
                                                    @else
                                                        <i class="feather-clock fs-10 me-1"></i> Applied: {{ $item->created_at->format('d M, h:i A') }}
                                                    @endif
                                                @else
                                                    <i class="feather-check-circle fs-10 me-1 text-success"></i> Reply: {{ $item->updated_at->format('d M, h:i A') }}
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center float-end gap-2">
                                                <a href="{{ $isCelebration ? route('employees.employeeDays') : route('leave.history') }}" class="text-primary" data-bs-toggle="tooltip" title="View Detail">
                                                    <i class="feather-eye fs-12"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center">
                                    <i class="feather-bell-off fs-24 text-muted mb-2"></i>
                                    <p class="text-muted small mb-0">No new notifications</p>
                                </div>
                            @endforelse
                        </div>
                        <div class="text-center notifications-footer">
                            <a href="{{ route('notifications.index') }}" class="fs-13 fw-semibold text-dark">All Notifications</a>
                        </div>
                    </div>
                </div>
                <div class="dropdown nxl-h-item">
                    <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" data-bs-auto-close="outside">
                        @php
                            $employee = auth()->user()->employee_id ? \App\Models\Employee::find(auth()->user()->employee_id) : null;
                        @endphp
                        @if($employee && $employee->photo)
                            <img src="{{ asset('storage/' . $employee->photo) }}" alt="user-image"
                                class="img-fluid user-avtar me-0" style="width: 35px; height: 35px; object-fit: cover;" />
                        @else
                            <div class="avatar-text avatar-md bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                style="width: 35px; height: 35px; font-size: 14px;">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-user-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex align-items-center">
                                @if($employee && $employee->photo)
                                    <img src="{{ asset('storage/' . $employee->photo) }}" alt="user-image"
                                        class="img-fluid user-avtar"
                                        style="width: 45px; height: 45px; object-fit: cover;" />
                                @else
                                    <div class="avatar-text avatar-md bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-2"
                                        style="width: 45px; height: 45px; font-size: 18px;">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <h6 class="text-dark mb-0 fw-bold">{{ auth()->user()->name }}</h6>
                                    <div class="d-flex flex-column gap-1 mt-1">
                                        <div>
                                            <span class="badge bg-soft-success text-success px-2 rounded-pill" style="font-size: 9px; letter-spacing: 0.5px;">{{ strtoupper(auth()->user()->role ?? 'USER') }}</span>
                                        </div>
                                        <span class="fs-12 fw-medium text-muted" style="font-size: 10px;">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('profile.show') }}" class="dropdown-item">
                            <i class="feather-user"></i>
                            <span>Profile Details</span>
                        </a>
                        <a href="{{ route('profile.leave-balance') }}" class="dropdown-item">
                            <i class="feather-box"></i>
                            <span>Leave Balance</span>
                        </a>
                        <a href="{{ route('profile.leave-history') }}" class="dropdown-item">
                            <i class="feather-list"></i>
                            <span>Leave History</span>
                        </a>
                        <a href="{{ route('attendance-history') }}" class="dropdown-item">
                            <i class="feather-calendar"></i>
                            <span>Attendance History</span>
                        </a>

                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}" class="dropdown-item"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                <i class="feather-log-out"></i>
                                <span>Logout</span>
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--! [End] Header Right !-->
    </div>
</header>

<div class="modal fade" id="ticketModal">

    <div class="modal-dialog">
        <div class="modal-content">

            <form action="{{ url('/tickets/store') }}" method="POST">

                @csrf

                <div class="modal-header">
                    <h5>Raise Ticket</h5>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Type</label>

                        <select name="type" class="form-control">

                            <option value="">Select Type</option>

                            <option value="HR">HR</option>
                            <option value="Payroll">Payroll</option>
                            <option value="Attendance">Attendance</option>
                            <option value="Leave">Leave</option>
                            <option value="IT Support">IT Support</option>
                            <option value="Other">Other</option>

                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Description</label>

                        <textarea name="description" rows="5" class="form-control"></textarea>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="submit"
                        class="btn btn-primary">
                        Submit
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

<!-- <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAttendanceHistory"
    aria-labelledby="offcanvasAttendanceHistoryLabel" style="width: 800px;">
    <div class="offcanvas-header border-bottom py-3">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex flex-column">
                <h5 id="offcanvasAttendanceHistoryLabel" class="offcanvas-title fw-bold text-primary mb-0">Record for
                    {{ auth()->user()->name }}
                </h5>
                <small class="text-muted">Attendance History</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="month" id="attendanceMonthFilter" class="form-control form-control-sm border-0 bg-light"
                    value="{{ date('Y-m') }}" onchange="refreshAttendanceWithMonth()">
                <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>
    <div class="offcanvas-body p-0">
        <div id="attendance-history-content" class="h-100 overflow-auto">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div> -->

<script>
    window.currentEmployeeId = null;

    // window.handleAttendanceClick = function () {
    //     const eid = {{ auth()->user()->employee_id ?? 'null' }};
    //     if (eid) {
    //         window.openAttendanceHistory(eid);
    //     } else {
    //         alert('No employee record linked to your account. Please contact admin.');
    //     }
    // };

    // window.openAttendanceHistory = function (employeeId, month = null) {
    //     window.currentEmployeeId = employeeId;
    //     const offcanvasElement = document.getElementById('offcanvasAttendanceHistory');
    //     if (!offcanvasElement) {
    //         alert('Internal Error: Attendance panel not found.');
    //         return;
    //     }

    //     // Force show using both Bootstrap and manual fallback
    //     try {
    //         const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement) || new bootstrap.Offcanvas(offcanvasElement);
    //         offcanvas.show();
    //     } catch (e) {
    //         offcanvasElement.classList.add('show');
    //         offcanvasElement.style.display = 'block';
    //         offcanvasElement.style.visibility = 'visible';
    //     }

    //     const contentDiv = document.getElementById('attendance-history-content');
    //     const monthInput = document.getElementById('attendanceMonthFilter');
    //     const targetMonth = month || (monthInput ? monthInput.value : '{{ date('Y-m') }}');

    //     contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

    //     fetch(`/api/employees/${employeeId}/attendance?month=${targetMonth}`)
    //         .then(response => response.json())
    //         .then(data => {
    //             if (data.success) {
    //                 const label = document.getElementById('offcanvasAttendanceHistoryLabel');
    //                 if (label && data.employee_name) label.innerText = `Record for ${data.employee_name}`;

    //                 if (data.history && data.history.length > 0) {
    //                     let html = '<div class="table-responsive"><table class="table align-middle table-hover mb-0" style="font-size: 13px;"><thead class="bg-light"><tr class="text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; height: 50px;"><th class="ps-3">SR. NO.</th><th>DATE</th><th>CHECK IN</th><th>CHECK OUT</th><th class="text-nowrap">WORKING HRS</th><th class="text-center">STATUS</th></tr></thead><tbody>';
    //                     data.history.forEach((item, index) => {
    //                         let icon = '<i class="feather-check-circle text-success me-2"></i>';
    //                         if (item.status.includes('Absent')) icon = '<i class="feather-x-circle text-danger me-2"></i>';
    //                         if (item.status.includes('Leave')) icon = '<i class="feather-info text-info me-2"></i>';
    //                         if (item.status.includes('Half Day')) icon = '<i class="feather-clock text-warning me-2"></i>';
    //                         if (item.status.includes('Sunday')) icon = '<i class="feather-calendar text-muted me-2"></i>';
    //                         html += `<tr style="height: 60px;"><td class="ps-3 fw-bold text-muted">${index + 1}</td><td class="fw-bold text-dark text-nowrap">${item.date}</td><td>${item.punch_in}</td><td>${item.punch_out}</td><td class="fw-bold text-primary">${item.total_hours}</td><td class="text-center"><span class="badge ${item.statusClass} rounded-pill px-3 py-1 fw-bold text-uppercase d-inline-flex align-items-center" style="font-size: 10px;">${icon} ${item.status}</span></td></tr>`;
    //                     });
    //                     html += '</tbody></table></div>';
    //                     contentDiv.innerHTML = html;
    //                 } else {
    //                     contentDiv.innerHTML = '<div class="text-center py-5 px-4"><i class="feather-info fs-1 text-muted mb-3 d-block"></i><h5>No Records Found</h5><p class="text-muted">We couldn\'t find any attendance records for your account.</p></div>';
    //                 }
    //             } else {
    //                 contentDiv.innerHTML = `<div class="alert alert-warning m-3">${data.message || 'Failed to load data.'}</div>`;
    //             }
    //         })
    //         .catch(err => {
    //             contentDiv.innerHTML = '<div class="alert alert-danger m-3">Connection error. Please try again.</div>';
    //         });
    // };

    // window.refreshAttendanceWithMonth = function () {
    //     if (window.currentEmployeeId) {
    //         const monthInput = document.getElementById('attendanceMonthFilter');
    //         window.openAttendanceHistory(window.currentEmployeeId, monthInput.value);
    //     }
    // };

    // Password visibility toggle
    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (e) {
            if (e.target.closest('.p-show-pass')) {
                const btn = e.target.closest('.p-show-pass');
                const input = btn.parentElement.querySelector('input');
                const icon = btn.querySelector('i');
                if (input && icon) {
                    const isPass = input.type === 'password';
                    input.type = isPass ? 'text' : 'password';
                    icon.className = isPass ? 'feather-eye' : 'feather-eye-off';
                    e.preventDefault();
                }
            }
        });
    });
</script>

<style>
    /* Absolute Clarity - No Blur Anywhere */
    html.modal-open,
    body.modal-open,
    body.modal-open .nxl-container,
    body.modal-open .nxl-header,
    body.modal-open .nxl-navigation,
    body.modal-open .page-header,
    body.modal-open #main-wrapper,
    body.modal-open .main-content {
        filter: none !important;
        -webkit-filter: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .modal-backdrop {
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        background-color: rgba(0, 0, 0, 0.5) !important;
    }

    .modal.fade .modal-dialog {
        transform: none !important;
    }

    .modal-content {
        filter: none !important;
        -webkit-filter: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2) !important;
    }
</style>