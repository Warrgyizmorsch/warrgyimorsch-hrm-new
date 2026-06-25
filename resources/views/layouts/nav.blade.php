<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="/" class="b-brand">
                <!-- Logo: uses Laravel asset() helper for correct path -->
                <img src="{{ asset('assets/images/warr-logo.webp') }}" alt="Main Logo" class="logo logo-lg"
                    style="max-height: 48px; width: auto;" />
                <img src="{{ asset('assets/images/logo-blue.png') }}" alt="Abbreviated Logo" class="logo logo-sm"
                    style="max-height: 40px; width: auto;" />
            </a>
        </div>
        <div class="navbar-content">
                @php

                    $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));

                    $isAdmin = in_array($role, [
                        'super_admin',
                        'manager',
                        'hr_executive',
                        'hr_intern',
                        'business_operation_head'
                    ]);

                    $isTeamLeader = in_array($role, [
                        'team_leader'
                    ]);

                @endphp
                    <ul class="nxl-navbar">
                <li class="nxl-item nxl-caption">
                    <label>Navigation</label>
                </li>
                <li class="nxl-item">
                    <a href="{{ route('dashboard') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
                </li>
                @if($isAdmin)
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-radio"></i></span>
                            <span class="nxl-mtext">HR Module</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>

                        <ul class="nxl-submenu">
                            <li class="nxl-item nxl-hasmenu">
                                <a href="javascript:void(0);" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-user"></i></span>
                                    <span class="nxl-mtext">Employees</span>
                                    <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                                </a>
                                <ul class="nxl-submenu">
                                    <li class="nxl-item">
                                        <a href="{{ route('employees.create') }}" class="nxl-link">
                                            <span class="nxl-micon"><i class="feather-plus-circle"></i></span>
                                            <span class="nxl-mtext">Add</span>
                                        </a>
                                    </li>
                                    <li class="nxl-item">
                                        <a href="{{ route('employees.index') }}" class="nxl-link">
                                            <span class="nxl-micon"><i class="feather-list"></i></span>
                                            <span class="nxl-mtext">View List</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nxl-item nxl-hasmenu">
                                <a href="javascript:void(0);" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                    <span class="nxl-mtext">Payroll Module</span>
                                    <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                                </a>
                                <ul class="nxl-submenu">
                                    <li class="nxl-item">
                                        <a href="{{ route('payroll.index') }}" class="nxl-link">
                                            <span class="nxl-micon"><i class="feather-circle"></i></span>
                                            <span class="nxl-mtext">Admin View</span>
                                        </a>
                                    </li>
                                    <li class="nxl-item">
                                        <a href="{{ route('payroll.attendance') }}" class="nxl-link">
                                            <span class="nxl-micon"><i class="feather-circle"></i></span>
                                            <span class="nxl-mtext">Attendance List</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>

                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-database"></i></span>
                            <span class="nxl-mtext">Master Module</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item">
                                <a href="{{ route('master.departments') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-circle"></i></span>
                                    <span class="nxl-mtext">Departments</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('master.designations') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-circle"></i></span>
                                    <span class="nxl-mtext">Designations</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('master.roles') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-circle"></i></span>
                                    <span class="nxl-mtext">Roles</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-calendar"></i></span>
                            <span class="nxl-mtext">Leave Module</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item">
                                <a href="{{ route('holidays.index') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-list"></i></span>
                                    <span class="nxl-mtext">Holiday List</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('leave.allotment') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-plus-circle"></i></span>
                                    <span class="nxl-mtext">Leave Allotment</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('leave.history') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                    <span class="nxl-mtext">Leave Applications</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                @if($isTeamLeader)
                    <li class="nxl-item">
                        <a href="{{ route('payroll.attendance') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-check-circle"></i></span>
                            <span class="nxl-mtext">Attendance List</span>
                        </a>
                    </li>

                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-calendar"></i></span>
                            <span class="nxl-mtext">Leave Module</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item">
                                <a href="{{ route('holidays.index') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-list"></i></span>
                                    <span class="nxl-mtext">Holiday List</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('leave.allotment') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-plus-circle"></i></span>
                                    <span class="nxl-mtext">Leave Allotment</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('leave.history') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                    <span class="nxl-mtext">Leave Applications</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                @if(!$isAdmin && !$isTeamLeader)
                    <!-- Attendance History -->
                    <li class="nxl-item">
                            <a href="{{ route('attendance-history') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-calendar"></i></span>
                                <span class="nxl-mtext">Attendance History</span>
                            </a>
                    </li>

                    <!-- Leave History -->
                    <li class="nxl-item">
                            <a href="{{ route('profile.leave-history') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                <span class="nxl-mtext">Leave History</span>
                            </a>
                    </li>

                    <!-- Leave Balance -->
                    <li class="nxl-item">
                            <a href="{{ route('profile.leave-balance') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-layers"></i></span>
                                <span class="nxl-mtext">Leave Balance</span>
                            </a>
                    </li>
                @endif

                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="fas fa-tasks"></i></span>
                        <span class="nxl-mtext">Project Module</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        @if ($isAdmin || $isTeamLeader)
                            <li class="nxl-item">
                                <a href="{{ route('projects.index') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-list"></i></span>
                                    <span class="nxl-mtext">Projects</span>
                                </a>
                            </li>
                        @endif
                        <li class="nxl-item">
                             <a href="{{ route('daily-tasks.index') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-check-square"></i></span>
                                <span class="nxl-mtext">Daily Tasks</span>
                            </a>
                        </li>
                    </ul>
                </li>
            
                @if ($isAdmin || $isTeamLeader)
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-briefcase"></i></span>
                            <span class="nxl-mtext">Job Module</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item">
                                <a href="{{ route('vacancy.show') }}" class="nxl-link">
                                        <span class="nxl-micon"><i class="feather-user-plus"></i></span>
                                        <span class="nxl-mtext">Job Vacancy</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('requirement.show') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="fas fa-clipboard-list"></i></span>
                                    <span class="nxl-mtext">Job Requirement</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-star"></i></span>
                        <span class="nxl-mtext">Employee Review</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item">
                            <a href="{{ route('employee.review') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-user"></i></span>
                                <span class="nxl-mtext">Personal Review</span>
                            </a>
                        </li>
                        <li class="nxl-item">
                            <a href="{{ route('technical.review') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-code"></i></span>
                                <span class="nxl-mtext">Technical Review</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nxl-item">
                    <a href="{{ route('payroll.empindex') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-file-text"></i></span>
                        <span class="nxl-mtext">Employee Payroll</span>
                    </a>
                </li>

                @if ($isAdmin || $isTeamLeader)
                    <a href="{{ route('broadcasts.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-radio"></i></span>
                        <span class="nxl-mtext">Broadcast</span>
                    </a>
                @endif

                <li class="nxl-item">
                    <a href="{{ route('employees.employeeDays') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="fa-solid fa-cake-candles"></i></span>
                        <span class="nxl-mtext">Celebrations</span>
                    </a>
                </li>

                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="fas fa-laptop-house"></i></span>
                        <span class="nxl-mtext">Asset Management</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        
                            <li class="nxl-item">
                                <a href="{{ route('assets.index') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="fas fa-clipboard-list"></i></span>
                                    <span class="nxl-mtext">Manage Assets</span>
                                </a>
                            </li>
                       
                        <li class="nxl-item">
                            <a href="{{ route('assets.employee') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="fas fa-user-tag"></i></span>
                                <span class="nxl-mtext">My Assets</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nxl-item">
                    <a href="{{ route('tickets.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="bi bi-headset"></i></i></span>
                        <span class="nxl-mtext">Help & Support</span>
                    </a>
                </li>

            </ul>

            <div class="card text-center">
                <div class="card-body">
                    <i class="feather-sunrise fs-4 text-dark"></i>
                    <h6 class="mt-4 text-dark fw-bolder">{{ auth()->user()->name}} </h6>
                    <p class="fs-11 my-3 text-dark"> {{auth()->user()->email}}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <a href="{{ route('logout') }}" class="btn btn-primary w-100"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            <i class="feather-log-out"></i>
                            <span>&nbsp;Logout</span>
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>