<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('dashboard') }}" class="zoho-sidebar-brand b-brand">
                <img src="{{ asset('assets/images/warr-logo-white.webp') }}" alt="Warrgyizmorsch" style="max-height: 32px; width: auto;" />
               
            </a>
            <button type="button" class="zoho-sidebar-close" id="zohoSidebarClose" aria-label="Close menu">
                <i class="feather-x"></i>
            </button>
        </div>
        <div class="navbar-content">
            <div class="zoho-sidebar-search">
                <div class="zoho-sidebar-search-wrap">
                    <i class="feather-search"></i>
                    <input type="text" id="zohoSidebarSearch" placeholder="Search modules..." autocomplete="off">
                </div>
            </div>
                @php
                    $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));

                    $isAdmin = in_array($role, [
                        'super_admin',
                        'manager',
                        'hr_executive',
                        'hr_intern',
                        'business_operation_head'
                    ]);

                    $isTeamLeader = in_array($role, ['team_leader']);

                    $navActive = fn (...$patterns) => request()->routeIs($patterns);
                    $navOpen = fn (...$patterns) => $navActive(...$patterns) ? 'zoho-menu-open' : '';
                    $navLinkActive = fn (...$patterns) => $navActive(...$patterns) ? 'active' : '';
                    $navItemActive = fn (...$patterns) => $navActive(...$patterns) ? 'active' : '';
                @endphp

            <div class="zoho-sidebar-scroll">
                    <ul class="nxl-navbar" id="zohoSidebarNav">
                <li class="nxl-item nxl-caption">
                    <label>Navigation</label>
                </li>
                <li class="nxl-item {{ $navItemActive('dashboard') }}">
                    <a href="{{ route('dashboard') }}" class="nxl-link {{ $navLinkActive('dashboard') }}">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
                </li>


                @if($isAdmin)
                    <li class="nxl-item nxl-hasmenu {{ $navOpen('employees.*', 'payroll.index', 'payroll.attendance', 'payroll.attendance.add') }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-radio"></i></span>
                            <span class="nxl-mtext">HR Module</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>

                        <ul class="nxl-submenu">
                            <li class="nxl-item nxl-hasmenu {{ $navOpen('employees.*') }}">
                                <a href="javascript:void(0);" class="nxl-link">
                                    <span class="nxl-micon"><i class="feather-user"></i></span>
                                    <span class="nxl-mtext">Employees</span>
                                    <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                                </a>
                                <ul class="nxl-submenu">
                                    <li class="nxl-item">
                                        <a href="{{ route('employees.create') }}" class="nxl-link {{ $navLinkActive('employees.create') }}">
                                            <span class="nxl-micon"><i class="feather-plus-circle"></i></span>
                                            <span class="nxl-mtext">Add</span>
                                        </a>
                                    </li>
                                    <li class="nxl-item">
                                        <a href="{{ route('employees.index') }}" class="nxl-link {{ $navLinkActive('employees.index', 'employees.show', 'employees.edit') }}">
                                            <span class="nxl-micon"><i class="feather-list"></i></span>
                                            <span class="nxl-mtext">View List</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nxl-item nxl-hasmenu {{ $navOpen('payroll.index', 'payroll.attendance', 'payroll.attendance.add') }}">
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

                    <li class="nxl-item nxl-hasmenu {{ $navOpen('master.*') }}">
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

                    <li class="nxl-item nxl-hasmenu {{ $navOpen('holidays.*', 'leave.*') }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-calendar"></i></span>
                            <span class="nxl-mtext">Leave Module</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item">
                                <a href="{{ route('holidays.index') }}" class="nxl-link {{ $navLinkActive('holidays.*') }}">
                                    <span class="nxl-micon"><i class="feather-list"></i></span>
                                    <span class="nxl-mtext">Holiday List</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('leave.allotment') }}" class="nxl-link {{ $navLinkActive('leave.allotment') }}">
                                    <span class="nxl-micon"><i class="feather-plus-circle"></i></span>
                                    <span class="nxl-mtext">Leave Allotment</span>
                                </a>
                            </li>
                            <li class="nxl-item">
                                <a href="{{ route('leave.history') }}" class="nxl-link {{ $navLinkActive('leave.history') }}">
                                    <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                    <span class="nxl-mtext">Leave Applications</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                @if($isTeamLeader)
                    <li class="nxl-item {{ $navItemActive('payroll.attendance', 'payroll.attendance.add') }}">
                        <a href="{{ route('payroll.attendance') }}" class="nxl-link {{ $navLinkActive('payroll.attendance', 'payroll.attendance.add') }}">
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
                    <li class="nxl-item {{ $navItemActive('attendance-history') }}">
                            <a href="{{ route('attendance-history') }}" class="nxl-link {{ $navLinkActive('attendance-history') }}">
                                <span class="nxl-micon"><i class="feather-calendar"></i></span>
                                <span class="nxl-mtext">Attendance History</span>
                            </a>
                    </li>

                    <li class="nxl-item {{ $navItemActive('profile.leave-history') }}">
                            <a href="{{ route('profile.leave-history') }}" class="nxl-link {{ $navLinkActive('profile.leave-history') }}">
                                <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                <span class="nxl-mtext">Leave History</span>
                            </a>
                    </li>

                    <li class="nxl-item {{ $navItemActive('profile.leave-balance') }}">
                            <a href="{{ route('profile.leave-balance') }}" class="nxl-link {{ $navLinkActive('profile.leave-balance') }}">
                                <span class="nxl-micon"><i class="feather-layers"></i></span>
                                <span class="nxl-mtext">Leave Balance</span>
                            </a>
                    </li>
                @endif

                <li class="nxl-item nxl-hasmenu {{ $navOpen('projects.*', 'daily-tasks.*') }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="fas fa-tasks"></i></span>
                        <span class="nxl-mtext">Project Module</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        @if ($isAdmin || $isTeamLeader)
                            <li class="nxl-item">
                                <a href="{{ route('projects.index') }}" class="nxl-link {{ $navLinkActive('projects.*') }}">
                                    <span class="nxl-micon"><i class="feather-list"></i></span>
                                    <span class="nxl-mtext">Projects</span>
                                </a>
                            </li>
                        @endif
                        <li class="nxl-item">
                             <a href="{{ route('daily-tasks.index') }}" class="nxl-link {{ $navLinkActive('daily-tasks.*') }}">
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
                            @if($isAdmin)
                                <li class="nxl-item">
                                    <a href="{{ route('vacancy.show') }}" class="nxl-link">
                                            <span class="nxl-micon"><i class="feather-user-plus"></i></span>
                                            <span class="nxl-mtext">Job Vacancy</span>
                                    </a>
                                </li>
                            @endif
                            <li class="nxl-item">
                                <a href="{{ route('requirement.show') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="fas fa-clipboard-list"></i></span>
                                    <span class="nxl-mtext">Job Requirement</span>
                                </a>
                            </li>
                            @if($isAdmin)
                                <li class="nxl-item">
                                    <a href="{{ route('candidates.index') }}" class="nxl-link">
                                        <span class="nxl-micon"><i class="feather-users"></i></span>
                                        <span class="nxl-mtext">Candidates</span>
                                    </a>
                                </li>
                            @endif
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
                
                <li class="nxl-item {{ $navItemActive('payroll.empindex', 'payroll.employee') }}">
                    <a href="{{ route('payroll.empindex') }}" class="nxl-link {{ $navLinkActive('payroll.empindex', 'payroll.employee') }}">
                        <span class="nxl-micon"><i class="feather-file-text"></i></span>
                        <span class="nxl-mtext">Employee Payroll</span>
                    </a>
                </li>

                @if ($isAdmin || $isTeamLeader)
                    <li class="nxl-item {{ $navItemActive('broadcasts.*') }}">
                        <a href="{{ route('broadcasts.index') }}" class="nxl-link {{ $navLinkActive('broadcasts.*') }}">
                            <span class="nxl-micon"><i class="feather-radio"></i></span>
                            <span class="nxl-mtext">Broadcast</span>
                        </a>
                    </li>
                @endif

                @if (in_array($role, ['super_admin', 'manager']))
                    <li class="nxl-item {{ $navItemActive('suggestions.*') }}">
                        <a href="{{ route('suggestions.index') }}" class="nxl-link {{ $navLinkActive('suggestions.*') }}">
                            <span class="nxl-micon"><i class="feather-message-square"></i></span>
                            <span class="nxl-mtext">Suggestion Box</span>
                        </a>
                    </li>
                @endif

                <li class="nxl-item {{ $navItemActive('employees.employeeDays') }}">
                    <a href="{{ route('employees.employeeDays') }}" class="nxl-link {{ $navLinkActive('employees.employeeDays') }}">
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
                        @if($isAdmin || $isTeamLeader)
                            <li class="nxl-item">
                                <a href="{{ route('assets.index') }}" class="nxl-link">
                                    <span class="nxl-micon"><i class="fas fa-clipboard-list"></i></span>
                                    <span class="nxl-mtext">{{ $isAdmin ? 'Manage Assets' : 'Team Assets' }}</span>
                                </a>
                            </li>
                        @endif
                        <li class="nxl-item">
                            <a href="{{ route('assets.employee') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="fas fa-user-tag"></i></span>
                                <span class="nxl-mtext">My Assets</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nxl-item {{ $navItemActive('tickets.*') }}">
                    <a href="{{ route('tickets.index') }}" class="nxl-link {{ $navLinkActive('tickets.*') }}">
                        <span class="nxl-micon"><i class="bi bi-headset"></i></span>
                        <span class="nxl-mtext">Help & Support</span>
                    </a>
                </li>
                
                <li class="nxl-item {{ $navItemActive('login-activity.index') }}">
                    <a href="{{ route('login-activity.index') }}" class="nxl-link {{ $navLinkActive('login-activity.index') }}">
                        <span class="nxl-micon"><i class="feather-log-in"></i></span>
                        <span class="nxl-mtext">Login Activity</span>
                    </a>
                </li>

            </ul>
            <div id="zohoSidebarNoResults" class="zoho-sidebar-no-results d-none">No modules found</div>
            </div>

            <div class="zoho-sidebar-user">
            <div class="card text-center border-0">
                <div class="card-body py-3">
                    <div class="zoho-sidebar-user-name">{{ auth()->user()->name }}</div>
                    <p class="zoho-sidebar-user-email">{{ auth()->user()->email }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 zoho-logout-btn" onclick="event.preventDefault(); this.closest('form').submit();">
                            <i class="feather-log-out me-1"></i> LOGOUT
                        </button>
                    </form>
                </div>
            </div>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('zohoSidebarSearch');
    const nav = document.getElementById('zohoSidebarNav');
    const noResults = document.getElementById('zohoSidebarNoResults');
    if (!searchInput || !nav) return;

    const topItems = () => Array.from(nav.querySelectorAll(':scope > .nxl-item:not(.nxl-caption)'));

    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        let visibleCount = 0;

        topItems().forEach(function (item) {
            const text = item.textContent.replace(/\s+/g, ' ').trim().toLowerCase();
            const show = query === '' || text.includes(query);
            item.classList.toggle('zoho-nav-hidden', !show);
            if (show) visibleCount++;
        });

        if (noResults) {
            noResults.classList.toggle('d-none', query === '' || visibleCount > 0);
        }
    });

    document.querySelectorAll('.nxl-item.nxl-hasmenu > .nxl-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (this.getAttribute('href') === 'javascript:void(0);') {
                e.preventDefault();
                const parent = this.closest('.nxl-hasmenu');
                if (parent) {
                    parent.classList.toggle('zoho-menu-open');
                }
            }
        });
    });
});
</script>