@php
    $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
    $isAdminNav = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
    $pageTitle = trim($__env->yieldContent('page_title')) ?: match (true) {
        request()->routeIs('dashboard') => 'Home',
        request()->routeIs('employees.*') => 'Employees',
        request()->routeIs('payroll.*') => 'Payroll',
        request()->routeIs('daily-tasks.*') => 'Daily Tasks',
        request()->routeIs('leave.*', 'holidays.*') => 'Leave',
        request()->routeIs('projects.*') => 'Projects',
        request()->routeIs('tickets.*') => 'Support',
        request()->routeIs('profile.*') => 'Profile',
        default => 'Home',
    };
    $currentRoute = request()->route()?->getName() ?? '';
@endphp

<header class="nxl-header">
    <div class="zoho-header-stack">
        {{-- Zoho-style primary top bar --}}
        <div class="zoho-topbar">
            <div class="d-flex align-items-center">
                <button type="button" class="zoho-mobile-toggle" id="mobile-collapse" aria-label="Toggle menu">
                    <i class="feather-menu"></i>
                </button>
                <span class="zoho-topbar-badge">HRM</span>
                <ul class="zoho-topbar-nav">
                    <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Home</a></li>
                    @if($isAdminNav)
                        <li><a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">Employees</a></li>
                        <li><a href="{{ route('payroll.index') }}" class="{{ request()->routeIs('payroll.index', 'payroll.attendance*') ? 'active' : '' }}">Payroll</a></li>
                    @endif
                    <li><a href="{{ route('daily-tasks.index') }}" class="{{ request()->routeIs('daily-tasks.*') ? 'active' : '' }}">Tasks</a></li>
                    <li><a href="{{ route('leave.history') }}" class="{{ request()->routeIs('leave.*', 'holidays.*') ? 'active' : '' }}">Leave</a></li>
                    @if($isAdminNav)
                        <li><a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">Projects</a></li>
                    @endif
                    <li><a href="{{ route('tickets.index') }}" class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}">Support</a></li>
                </ul>
            </div>

            <div class="zoho-topbar-actions">
                <button type="button" class="zoho-topbar-icon" data-bs-toggle="modal" data-bs-target="#ticketModal" title="Raise Ticket">
                    <i class="feather-plus"></i>
                </button>

                @php
                    $notifications = collect();
                    $roleUpper = strtoupper(auth()->user()->role);

                    $today = now()->format('m-d');
                    $celebrations = \App\Models\Employee::where(function ($q) use ($today) {
                            $q->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$today])
                              ->orWhereRaw("DATE_FORMAT(date_of_joining, '%m-%d') = ?", [$today]);
                        })
                        ->get()
                        ->map(function ($emp) use ($today) {
                            $isBirthday = \Carbon\Carbon::parse($emp->date_of_birth)->format('m-d') == $today;
                            $type = $isBirthday ? 'Birthday' : 'Work Anniversary';
                            $years = (int) \Carbon\Carbon::parse($emp->date_of_joining)->diffInYears(now());

                            return (object) [
                                'id' => 'celebration-' . $emp->id,
                                'type' => 'celebration',
                                'event_type' => $type,
                                'employee' => $emp,
                                'years' => $years,
                                'message' => $isBirthday
                                    ? "Today is {$emp->name}'s Birthday! 🎂"
                                    : "Today is {$emp->name}'s {$years}" . ($years == 1 ? 'st' : ($years == 2 ? 'nd' : ($years == 3 ? 'rd' : 'th'))) . " Work Anniversary! 🏆",
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        });

                    if ($roleUpper == 'ADMIN' || $roleUpper == 'SUPER_ADMIN' || $roleUpper == 'SUPER ADMIN') {
                        $leaveNotifications = \App\Models\LeaveApplication::with('employee')->latest()->get();
                        $payrollNotifications = \App\Models\Payroll::with('employee')
                            ->whereNotNull('remarks')
                            ->where('remarks', '!=', '')
                            ->latest()
                            ->get();

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
                    $headerEmployee = auth()->user()->employee_id ? \App\Models\Employee::find(auth()->user()->employee_id) : null;
                    $firstName = explode(' ', auth()->user()->name ?? 'User')[0];
                    $userInitials = collect(explode(' ', trim(auth()->user()->name ?? 'User')))
                        ->filter()
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->take(2)
                        ->join('');
                @endphp

                <button type="button"
                        class="zoho-topbar-icon zoho-topbar-notify"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#zohoNotifyOffcanvas"
                        aria-controls="zohoNotifyOffcanvas"
                        title="Notifications">
                    <i class="feather-bell"></i>
                    @if(count($notifications) > 0)
                        <span class="zoho-notify-badge">{{ count($notifications) > 9 ? '9+' : count($notifications) }}</span>
                    @endif
                </button>

                <button type="button"
                        class="zoho-topbar-profile"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#zohoProfileOffcanvas"
                        aria-controls="zohoProfileOffcanvas"
                        title="Account">
                    @if($headerEmployee && $headerEmployee->photo)
                        <img src="{{ asset('storage/' . $headerEmployee->photo) }}" alt="" class="zoho-topbar-profile-avatar">
                    @else
                        <span class="zoho-topbar-profile-avatar zoho-topbar-profile-avatar--initial">{{ $userInitials ?: 'U' }}</span>
                    @endif
                    <span class="zoho-topbar-profile-meta d-none d-xl-flex">
                        <span class="zoho-topbar-profile-name">{{ $firstName }}</span>
                        <span class="zoho-topbar-profile-role">{{ ucwords(str_replace('_', ' ', auth()->user()->role ?? 'Employee')) }}</span>
                    </span>
                    <i class="feather-chevron-down zoho-topbar-profile-chevron d-none d-md-inline"></i>
                </button>
            </div>
        </div>

        {{-- Sub-header: dashboard only — title + refresh (greeting lives in page content) --}}
        @if(request()->routeIs('dashboard'))
        <div class="zoho-page-header">
            <div class="zoho-page-header-left">
                <h1 class="zoho-page-title">{{ $pageTitle }}</h1>
                <span class="zoho-page-date">{{ now()->format('l, d M Y') }}</span>
            </div>
            <div class="zoho-page-header-right">
                <button type="button" class="zoho-header-btn" onclick="window.location.reload()" title="Refresh dashboard">
                    <i class="feather-refresh-cw"></i>
                    <span class="zoho-header-btn-label">Refresh</span>
                </button>
            </div>
        </div>
        @endif
    </div>

    {{-- Legacy header wrapper kept for theme JS compatibility --}}
    <div class="header-wrapper d-none">
        <div class="header-left d-flex align-items-center gap-4">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse-legacy">
                <div class="hamburger hamburger--arrowturn"><div class="hamburger-box"><div class="hamburger-inner"></div></div></div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-arrow-right"></i></a>
            </div>
        </div>
    </div>
</header>

<div class="modal fade" id="ticketModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('/tickets/store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Raise Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
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
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="5" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Notifications drawer (Zoho People style) --}}
<x-ui.drawer
    id="zohoNotifyOffcanvas"
    title="Notifications"
    group="header"
    class="zoho-header-offcanvas zoho-notify-offcanvas border-0"
    body-class="zoho-notify-offcanvas-body p-0"
    footer-class="zoho-offcanvas-foot">
    @forelse($notifications as $item)
        @php
            $isCelebration = isset($item->type) && $item->type == 'celebration';
            $isPayroll = !$isCelebration && isset($item->remarks);
            $emp = (in_array($roleUpper, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']) || $isCelebration) ? $item->employee : auth()->user()->employee;
            $photo = ($emp && $emp->photo) ? asset('storage/' . $emp->photo) : null;
            $notifyType = $isCelebration ? 'celebration' : ($isPayroll ? 'payroll' : 'leave');
            $notifyIcon = $isCelebration ? 'gift' : ($isPayroll ? 'message-circle' : 'calendar');
            $notifyUrl = $isCelebration ? route('employees.employeeDays') : ($isPayroll ? route('payroll.index') : route('leave.history'));
        @endphp
        <a href="{{ $notifyUrl }}" class="zoho-notify-item zoho-notify-item--{{ $notifyType }}">
            <span class="zoho-notify-item-icon"><i class="feather-{{ $notifyIcon }}"></i></span>
            <span class="zoho-notify-item-body">
                @if($isCelebration)
                    <span class="zoho-notify-item-title">{{ $item->message }}</span>
                    <span class="zoho-notify-item-meta">Happening today</span>
                @elseif(in_array($roleUpper, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']))
                    @if($isPayroll)
                        <span class="zoho-notify-item-title">{{ $item->employee->name ?? 'Someone' }} commented on payroll</span>
                        <span class="zoho-notify-item-desc">"{{ Str::limit($item->remarks, 120) }}"</span>
                        <span class="zoho-notify-item-meta">{{ $item->updated_at->format('d M, h:i A') }}</span>
                    @else
                        <span class="zoho-notify-item-title">{{ $emp->name ?? 'Someone' }} applied for leave</span>
                        <span class="zoho-notify-item-desc">{{ $item->leave_type }} leave request</span>
                        <span class="zoho-notify-item-meta">{{ $item->created_at->format('d M, h:i A') }}</span>
                    @endif
                @else
                    <span class="zoho-notify-item-title">Leave {{ strtoupper($item->status) }}</span>
                    <span class="zoho-notify-item-desc">Your {{ $item->leave_type }} application was {{ $item->status }}.</span>
                    <span class="zoho-notify-item-meta">{{ $item->updated_at->format('d M, h:i A') }}</span>
                @endif
            </span>
            @if($photo)
                <img src="{{ $photo }}" alt="" class="zoho-notify-item-avatar">
            @else
                <span class="zoho-notify-item-avatar zoho-notify-item-avatar--initial">{{ strtoupper(substr($emp->name ?? '?', 0, 1)) }}</span>
            @endif
        </a>
    @empty
        <div class="zoho-offcanvas-empty">
            <div class="zoho-offcanvas-empty-art">
                <i class="feather-bell"></i>
            </div>
            <p class="zoho-offcanvas-empty-title">No Notification found</p>
            <p class="zoho-offcanvas-empty-desc">You're all caught up. New alerts will appear here.</p>
        </div>
    @endforelse

    @if(count($notifications) > 0)
        <x-slot name="footer">
            <a href="{{ route('notifications.index') }}" class="zoho-offcanvas-foot-link">View all notifications</a>
        </x-slot>
    @endif
</x-ui.drawer>

{{-- Profile drawer (Zoho People style) --}}
<x-ui.drawer
    id="zohoProfileOffcanvas"
    group="header"
    class="zoho-header-offcanvas zoho-profile-offcanvas border-0"
    body-class="zoho-profile-offcanvas-body p-0">
    <x-slot name="header">
        <div class="zoho-profile-offcanvas-hero">
            <button type="button" class="zoho-offcanvas-close zoho-offcanvas-close--light" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="feather-x"></i>
            </button>
            <div class="zoho-profile-hero-user">
                @if($headerEmployee && $headerEmployee->photo)
                    <img src="{{ asset('storage/' . $headerEmployee->photo) }}" alt="" class="zoho-profile-hero-avatar">
                @else
                    <span class="zoho-profile-hero-avatar zoho-profile-hero-avatar--initial">{{ $userInitials ?: 'U' }}</span>
                @endif
                <div class="zoho-profile-hero-info">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->email }}</span>
                    <span class="zoho-profile-hero-meta">User ID : {{ auth()->user()->id }}</span>
                    @if($headerEmployee && $headerEmployee->employee_code)
                        <span class="zoho-profile-hero-meta">Employee ID : {{ $headerEmployee->employee_code }}</span>
                    @endif
                    <span class="zoho-profile-hero-meta">Role : {{ ucwords(str_replace('_', ' ', auth()->user()->role ?? 'Employee')) }}</span>
                </div>
            </div>
            <div class="zoho-profile-hero-actions">
                <a href="{{ route('profile.show') }}" class="zoho-profile-hero-action">
                    <i class="feather-user"></i> My Account
                </a>
                <form method="POST" action="{{ route('logout') }}" class="zoho-profile-hero-logout-form">
                    @csrf
                    <button type="submit" class="zoho-profile-hero-action zoho-profile-hero-action--signout">
                        <i class="feather-power"></i> Sign Out
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <nav class="zoho-profile-nav">
        <a href="{{ route('profile.show') }}" class="zoho-profile-nav-link">
            <i class="feather-user"></i><span>Profile Details</span><i class="feather-chevron-right"></i>
        </a>
        <a href="{{ route('profile.leave-balance') }}" class="zoho-profile-nav-link">
            <i class="feather-box"></i><span>Leave Balance</span><i class="feather-chevron-right"></i>
        </a>
        <a href="{{ route('profile.leave-history') }}" class="zoho-profile-nav-link">
            <i class="feather-list"></i><span>Leave History</span><i class="feather-chevron-right"></i>
        </a>
        <a href="{{ route('attendance-history') }}" class="zoho-profile-nav-link">
            <i class="feather-calendar"></i><span>Attendance History</span><i class="feather-chevron-right"></i>
        </a>
        <a href="{{ route('tickets.index') }}" class="zoho-profile-nav-link">
            <i class="feather-life-buoy"></i><span>Help &amp; Support</span><i class="feather-chevron-right"></i>
        </a>
    </nav>
</x-ui.drawer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nav = document.querySelector('.nxl-navigation');
        const toggleBtn = document.getElementById('mobile-collapse');
        const closeBtn = document.getElementById('zohoSidebarClose');
        const MOBILE_BP = 1024;

        let overlay = document.querySelector('.zoho-sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'zoho-sidebar-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlay);
        }

        function isMobile() {
            return window.innerWidth <= MOBILE_BP;
        }

        function closeSidebar() {
            if (!nav) return;
            nav.classList.remove('mob-navigation-active');
            overlay.classList.remove('active');
            document.body.classList.remove('zoho-sidebar-open');
        }

        function openSidebar() {
            if (!nav) return;
            nav.classList.add('mob-navigation-active');
            overlay.classList.add('active');
            document.body.classList.add('zoho-sidebar-open');
        }

        function toggleSidebar() {
            if (!nav) return;
            nav.classList.contains('mob-navigation-active') ? closeSidebar() : openSidebar();
        }

        toggleBtn?.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });

        closeBtn?.addEventListener('click', function (e) {
            e.preventDefault();
            closeSidebar();
        });

        overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });

        nav?.querySelectorAll('.nxl-link[href]').forEach(function (link) {
            const href = link.getAttribute('href');
            if (!href || href.indexOf('javascript') === 0) return;
            link.addEventListener('click', function () {
                if (isMobile()) closeSidebar();
            });
        });

        window.addEventListener('resize', function () {
            if (!isMobile()) closeSidebar();
        });

        // Legacy theme hook (hidden fallback button)
        const legacyBtn = document.getElementById('mobile-collapse-legacy');
        if (legacyBtn && toggleBtn) {
            legacyBtn.addEventListener('click', toggleSidebar);
        }
    });
</script>

<style>
    html.modal-open, body.modal-open,
    body.modal-open .nxl-container, body.modal-open .nxl-header,
    body.modal-open .nxl-navigation, body.modal-open .main-content {
        filter: none !important;
        backdrop-filter: none !important;
    }
    .modal-backdrop { background-color: rgba(0, 0, 0, 0.5) !important; }
</style>
