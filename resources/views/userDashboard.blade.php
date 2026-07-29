@extends('layouts.app')
@section('content')
    <!-- [ page-header ] start -->
    <div class="page-header hrm-resp-page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Dashboard</h5>
            </div>
            <ul class="breadcrumb hrm-resp-breadcrumb">
                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                <li class="breadcrumb-item">Dashboard</li>
            </ul>
        </div>
    </div>
    <style>
        /* Premium Dropdown & Select UI (As per screenshot) */
        .hrm-resp-main-content .form-select,
        .hrm-resp-main-content .btn-light.dropdown-toggle,
        .hrm-resp-main-content .dropdown-toggle {
            border-radius: 12px !important;
            border: 1.5px solid #e2e8f0 !important;
            background-color: #fff !important;
            padding: 8px 16px !important;
            font-weight: 500 !important;
            color: #64748b !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        .hrm-resp-main-content .form-select:focus,
        .hrm-resp-main-content .btn-light.dropdown-toggle:focus,
        .hrm-resp-main-content .dropdown-toggle:focus,
        .hrm-resp-main-content .dropdown-toggle[aria-expanded="true"] {
            border-color: #3858f9 !important;
            box-shadow: 0 0 0 4px rgba(56, 88, 249, 0.1) !important;
            color: #3858f9 !important;
        }

        .hrm-resp-main-content .dropdown-menu {
            border-radius: 16px !important;
            border: 1px solid #f1f5f9 !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1) !important;
            padding: 8px !important;
            margin-top: 8px !important;
            z-index: 1050 !important;
        }

        .hrm-resp-main-content .dropdown-item {
            border-radius: 10px !important;
            padding: 10px 15px !important;
            font-weight: 600 !important;
            color: #475569 !important;
            margin-bottom: 2px !important;
            transition: all 0.2s ease !important;
        }

        .hrm-resp-main-content .dropdown-item:hover,
        .hrm-resp-main-content .dropdown-item.active {
            background-color: #eff6ff !important;
            color: #3858f9 !important;
        }

        .hrm-resp-main-content .dropdown-divider {
            margin: 8px -8px !important;
            border-color: #f1f5f9 !important;
        }

        /* Responsive Dashboard Utilities */
        .hrm-resp-main-content {
            overflow: visible !important;
        }

        @media (max-width: 767.98px) {
            .hrm-resp-page-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
                padding: 15px !important;
            }

            .hrm-resp-main-content {
                padding-top: 1rem !important;
                padding-left: 15px !important;
                padding-right: 15px !important;
            }

            .hrm-resp-card-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
                padding: 15px !important;
            }

            .hrm-resp-card-header-action {
                width: 100% !important;
            }

            .hrm-resp-card-header-action .d-flex {
                width: 100% !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
            }

            .hrm-resp-card-header-action select,
            .hrm-resp-card-header-action .dropdown,
            .hrm-resp-card-header-action .btn {
                flex: 1 !important;
                min-width: 120px !important;
            }

            .hrm-resp-dropdown-menu {
                left: 0 !important;
                right: auto !important;
                transform: none !important;
                min-width: 220px !important;
                width: 100% !important;
            }

            .avatar-lg {
                width: 40px !important;
                height: 40px !important;
                font-size: 16px !important;
            }

            .fs-4 {
                font-size: 1.1rem !important;
            }

            .gap-4 {
                gap: 0.75rem !important;
            }

            .row {
                margin-left: -5px !important;
                margin-right: -5px !important;
            }

            .col-xxl-3,
            .col-md-6,
            .col-md-4,
            .col-xxl-4,
            .col-xxl-8 {
                padding-left: 5px !important;
                padding-right: 5px !important;
            }
        }

        @media (max-width: 575.98px) {

            .hrm-resp-card-header-action select,
            .hrm-resp-card-header-action .dropdown,
            .hrm-resp-card-header-action .btn {
                width: 100% !important;
                flex: none !important;
            }

            .hrm-resp-breadcrumb {
                display: none !important;
            }
        }

        /* Premium scrollbar for tables */
        .hrm-resp-table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .hrm-resp-table-responsive::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        /* --- MAIN BANNER CONTAINER --- */
        .celebration-banner {
            position: relative;
            display: flex;
            align-items: center;
            padding: 18px 24px;
            border-radius: 14px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #eef2f6;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.05);
            overflow: visible; /* Allows animations to escape the container boundaries */
            animation: premiumEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .celebration-banner::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            border-radius: 14px 0 0 14px;
        }

        .celebration-icon {
            font-size: 26px;
            margin-right: 18px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            flex-shrink: 0;
            z-index: 2;
        }

        .celebration-content {
            z-index: 2;
        }

        .celebration-content h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .celebration-content p {
            margin: 4px 0 0;
            font-size: 13.5px;
            color: #4b5563;
        }

        /* --- THEMES --- */
        .celebration-banner.birthday {
            background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%);
        }
        .celebration-banner.birthday::before {
            background: linear-gradient(to bottom, #6366f1, #ec4899);
        }
        .celebration-banner.birthday .celebration-icon {
            background: #eeebff;
            color: #6366f1;
        }
        .celebration-banner.birthday h3 {
            color: #4338ca;
        }

        .celebration-banner.anniversary {
            background: linear-gradient(135deg, #ffffff 0%, #fefbeb 100%);
        }
        .celebration-banner.anniversary::before {
            background: linear-gradient(to bottom, #f59e0b, #d97706);
        }
        .celebration-banner.anniversary .celebration-icon {
            background: #fef3c7;
            color: #b45309;
        }
        .celebration-banner.anniversary h3 {
            color: #b45309;
        }


        /* --- FLOATING BALLOON & CONFETTI ENGINE --- */
        .animation-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none; /* Allows clicks to pass through safely */
            z-index: 1;
        }

        /* Balloons Base Setup */
        .balloon {
            position: fixed; /* Fixed to viewport so they fly up across the entire web page */
            bottom: -60px;
            font-size: 45px;
            opacity: 0;
            animation: flyUpwards 7s linear infinite;
        }

        /* Staggered positioning, sizes, and delays for natural flight tracking */
        .b1 { left: 15vw; animation-delay: 0s; font-size: 50px; }
        .b2 { left: 40vw; animation-delay: 1.5s; font-size: 38px; }
        .b3 { left: 65vw; animation-delay: 0.8s; font-size: 45px; }
        .b4 { left: 85vw; animation-delay: 2.3s; font-size: 55px; }

        /* Anniversary element custom overrides */
        .a1 { left: 20vw; animation-delay: 0s; font-size: 30px; }
        .a2 { left: 45vw; animation-delay: 1.2s; font-size: 25px; }
        .a3 { left: 70vw; animation-delay: 0.5s; font-size: 35px; }
        .a4 { left: 90vw; animation-delay: 2s; font-size: 28px; }

        /* Confetti Falling Pieces */
        .confetti-piece {
            position: absolute;
            top: -20px;
            width: 10px;
            height: 10px;
            background: #6366f1;
            opacity: 0;
            border-radius: 2px;
            animation: confettiFall 4s linear infinite;
        }

        /* Give confetti different variations, placement, and speed */
        .cp1 { left: 20%; background: #ff6b6b; animation-delay: 0s; animation-duration: 3.5s; }
        .cp2 { left: 45%; background: #4dadf7; width: 8px; height: 12px; animation-delay: 1.2s; animation-duration: 4.2s; }
        .cp3 { left: 60%; background: #37b24d; animation-delay: 0.4s; animation-duration: 3.8s; }
        .cp4 { left: 75%; background: #fcc419; width: 11px; height: 7px; animation-delay: 1.8s; animation-duration: 4.5s; }
        .cp5 { left: 90%; background: #f06595; animation-delay: 0.9s; animation-duration: 3.9s; }


        /* --- ANIMATION KEYFRAMES --- */

        /* Base container entrance slide */
        @keyframes premiumEntrance {
            0% { opacity: 0; transform: translateY(15px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Balloon float upwards with slight swaying side-to-side path */
        @keyframes flyUpwards {
            0% {
                bottom: -60px;
                transform: translateX(0) rotate(0deg);
                opacity: 0;
            }
            5% {
                opacity: 0.9;
            }
            50% {
                transform: translateX(30px) rotate(10deg);
            }
            75% {
                transform: translateX(-20px) rotate(-10deg);
            }
            100% {
                bottom: 110vh;
                transform: translateX(10px) rotate(5deg);
                opacity: 0;
            }
        }

        /* Confetti tumbling effect down the card element */
        @keyframes confettiFall {
            0% {
                top: -10px;
                transform: translateX(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                top: 150px;
                transform: translateX(40px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Responsive safety to clean up view on extra small phones */
        @media (max-width: 576px) {
            .balloon {
                font-size: 32px; /* Smaller balloons on mobile devices */
            }
            .b4, .a4 {
                display: none; /* Remove right-most elements to prevent horizontal scrolling layout bugs */
            }
        }
    </style>
    @include('partials.birthday-wish-card', ['employee' => $employee, 'celebration' => $celebration])

    @if($celebration['isAnniversaryToday'])
        <div class="celebration-banner anniversary">
            <div class="animation-container">
                <span class="balloon a1">✨</span>
                <span class="balloon a2">⭐</span>
                <span class="balloon a3">🎉</span>
                <span class="balloon a4">✨</span>
                <span class="confetti-piece cp1"></span>
                <span class="confetti-piece cp2"></span>
                <span class="confetti-piece cp3"></span>
                <span class="confetti-piece cp4"></span>
                <span class="confetti-piece cp5"></span>
            </div>

            <div class="celebration-icon">🏆</div>
            <div class="celebration-content">
                <h3>Happy {{ $celebration['years'] }} Year Work Anniversary, {{ $employee->name }}! 🎊</h3>
                <p>Thank you for your incredible dedication, hard work, and loyalty.</p>
            </div>
        </div>
    @endif
    <!-- [ page-header ] end -->
    <!-- [ Main Content ] start -->
    <div class="main-content pt-md-4 pt-2 hrm-resp-main-content">
        @include('partials.urgent-announcements-card', ['announcements' => $announcements])

        <div class="row">
            <!-- [Available Leave Balance] start -->
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-gray-200">
                                    <i class="feather-check-circle text-success"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-dark">{{ number_format(max($availableLeaveBalance, 0),1) }}</div>
                                    <h3 class="fs-13 fw-semibold text-truncate-1-line">Available Leave Balance</h3>
                                </div>
                            </div>
                            <div class="dropdown d-none">
                                <a href="javascript:void(0);" class="avatar-text avatar-sm" data-bs-toggle="dropdown"
                                    data-bs-offset="0, 10">
                                    <i class="feather-more-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="dropdown-header text-uppercase fs-10 fw-800 text-muted">Select History</div>
                                    @for ($i = 0; $i < 6; $i++)
                                        @php $m = \Carbon\Carbon::now()->startOfMonth()->subMonths($i); @endphp
                                        <a href="{{ route('dashboard', ['month' => $m->format('Y-m')]) }}"
                                            class="dropdown-item {{ $selectedMonth == $m->format('Y-m') ? 'active' : '' }}">
                                            <i class="feather-calendar me-2"></i>
                                            <span>{{ $m->format('M Y') }} Overview</span>
                                        </a>
                                    @endfor
                                    <div class="dropdown-divider"></div>
                                    <a href="javascript:void(0);" class="dropdown-item"
                                        onclick="showMonthlySummary('{{ $selectedMonth }}')">
                                        <i class="feather-file-text me-2"></i>
                                        <span>Full Breakdown Details</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <a href="javascript:void(0);"
                                    class="fs-12 fw-medium text-muted text-truncate-1-line">Leaves remaining</a>
                                <div class="w-100 text-end">
                                    <span class="fs-12 text-dark">{{ number_format($availableLeaveBalance, 1) }}</span>
                                    <span class="fs-11 text-muted">days</span>
                                </div>
                            </div>
                            <div class="progress mt-2 ht-3">
                                <div class="progress-bar bg-success" role="progressbar"
                                    style="width: {{ $totalAllottedLeave > 0 ? max(0, min(100, ($availableLeaveBalance / $totalAllottedLeave) * 100)) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [Available Leave Balance] end -->
            <!-- [Total Utilized Leave] start -->
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-gray-200">
                                    <i class="feather-activity text-primary"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-dark">{{ number_format($totalUtilizedLeave, 1) }}</div>
                                    <h3 class="fs-13 fw-semibold text-truncate-1-line">Total Utilized Leave</h3>
                                </div>
                            </div>
                            <div class="dropdown d-none">
                                <a href="javascript:void(0);" class="avatar-text avatar-sm" data-bs-toggle="dropdown"
                                    data-bs-offset="0, 10">
                                    <i class="feather-more-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="dropdown-header text-uppercase fs-10 fw-800 text-muted">Select History</div>
                                    @for ($i = 0; $i < 6; $i++)
                                        @php $m = \Carbon\Carbon::now()->startOfMonth()->subMonths($i); @endphp
                                        <a href="{{ route('dashboard', ['month' => $m->format('Y-m')]) }}"
                                            class="dropdown-item {{ $selectedMonth == $m->format('Y-m') ? 'active' : '' }}">
                                            <i class="feather-calendar me-2"></i>
                                            <span>{{ $m->format('M Y') }} Overview</span>
                                        </a>
                                    @endfor
                                    <div class="dropdown-divider"></div>
                                    <a href="javascript:void(0);" class="dropdown-item"
                                        onclick="showMonthlySummary('{{ $selectedMonth }}')">
                                        <i class="feather-file-text me-2"></i>
                                        <span>Full Breakdown Details</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <a href="javascript:void(0);"
                                    class="fs-12 fw-medium text-muted text-truncate-1-line">Leaves used till now</a>
                                <div class="w-100 text-end">
                                    <span class="fs-12 text-dark">{{ number_format($totalUtilizedLeave, 1) }}</span>
                                    <span class="fs-11 text-muted">days</span>
                                </div>
                            </div>
                            <div class="progress mt-2 ht-3">
                                <div class="progress-bar bg-primary" role="progressbar"
                                    style="width: {{ $totalAllottedLeave > 0 ? max(0, min(100, ($totalUtilizedLeave / $totalAllottedLeave) * 100)) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [Total Utilized Leave] end -->
            <!-- [This Month's Utilized Leave] start -->
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-gray-200">
                                    <i class="feather-trending-up text-warning"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-dark">{{ number_format($currentMonthUtilizedLeave, 1) }}</div>
                                    <h3 class="fs-13 fw-semibold text-truncate-1-line">This Month's Utilized Leave</h3>
                                </div>
                            </div>
                            <div class="dropdown d-none">
                                <a href="javascript:void(0);" class="avatar-text avatar-sm" data-bs-toggle="dropdown"
                                    data-bs-offset="0, 10">
                                    <i class="feather-more-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="dropdown-header text-uppercase fs-10 fw-800 text-muted">Select History</div>
                                    @for ($i = 0; $i < 6; $i++)
                                        @php $m = \Carbon\Carbon::now()->startOfMonth()->subMonths($i); @endphp
                                        <a href="{{ route('dashboard', ['month' => $m->format('Y-m')]) }}"
                                            class="dropdown-item {{ $selectedMonth == $m->format('Y-m') ? 'active' : '' }}">
                                            <i class="feather-calendar me-2"></i>
                                            <span>{{ $m->format('M Y') }} Overview</span>
                                        </a>
                                    @endfor
                                    <div class="dropdown-divider"></div>
                                    <a href="javascript:void(0);" class="dropdown-item"
                                        onclick="showMonthlySummary('{{ $selectedMonth }}')">
                                        <i class="feather-file-text me-2"></i>
                                        <span>Full Breakdown Details</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <a href="javascript:void(0);"
                                    class="fs-12 fw-medium text-muted text-truncate-1-line">{{ now()->format('F Y') }} used</a>
                                <div class="w-100 text-end">
                                    <i class="feather-clock text-warning me-1"></i>
                                    <span class="fs-12 text-dark">{{ number_format($currentMonthUtilizedLeave, 1) }}</span>
                                    <span class="fs-11 text-muted">days</span>
                                </div>
                            </div>
                            <div class="progress mt-2 ht-3">
                                <div class="progress-bar bg-warning" role="progressbar"
                                    style="width: {{ $currentMonthAllottedLeave > 0 ? max(0, min(100, ($currentMonthUtilizedLeave / $currentMonthAllottedLeave) * 100)) : 0 }}%"></div>
                            </div>
                            <!-- <div class="d-flex align-items-center justify-content-between mt-2">
                                <span class="fs-11 text-muted">0 days</span>
                                <span class="fs-11 text-muted">{{ number_format($currentMonthAllottedLeave, 1) }} days allotted</span>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- [This Month's Utilized Leave] end -->
            <!-- [Total Journey] start -->
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-gray-200">
                                    <i class="feather-briefcase text-info"></i>
                                </div>
                                <div>
                                    <div class="fs-5 fw-bold text-dark">{{ $totalJourney }}</div>
                                    <h3 class="fs-13 fw-semibold text-truncate-1-line">Total Journey</h3>
                                </div>
                            </div>
                            <div class="dropdown d-none">
                                <a href="javascript:void(0);" class="avatar-text avatar-sm" data-bs-toggle="dropdown"
                                    data-bs-offset="0, 10">
                                    <i class="feather-more-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="dropdown-header text-uppercase fs-10 fw-800 text-muted">Quick Access</div>
                                    <a href="{{ route('dashboard') }}" class="dropdown-item">
                                        <i class="feather-refresh-cw me-2"></i>
                                        <span>Show All-Time History</span>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    @for ($i = 0; $i < 6; $i++)
                                        @php $m = \Carbon\Carbon::now()->startOfMonth()->subMonths($i); @endphp
                                        <a href="{{ route('dashboard', ['month' => $m->format('Y-m')]) }}"
                                            class="dropdown-item {{ $selectedMonth == $m->format('Y-m') ? 'active' : '' }}">
                                            <i class="feather-calendar me-2"></i>
                                            <span>{{ $m->format('M Y') }} Overview</span>
                                        </a>
                                    @endfor
                                    <div class="dropdown-divider"></div>
                                    <a href="javascript:void(0);" class="dropdown-item"
                                        onclick="showFullYearBreakdown('{{ date('Y') }}')">
                                        <i class="feather-file-text me-2"></i>
                                        <span>Full Yearly Breakdown</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <a href="javascript:void(0);" class="fs-12 fw-medium text-muted text-truncate-1-line">
                                    Joined date</a>
                                <div class="w-100 text-end">
                                    <span class="fs-12 text-dark">
                                        {{ $employee && $employee->date_of_joining ? \Carbon\Carbon::parse($employee->date_of_joining)->format('d M Y') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                            <div class="progress mt-2 ht-3">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [Total Journey] end -->

            <div class="row">

                <!-- [Today Leave Records] start -->
                <div class="col-md-4">
                    <div class="card stretch stretch-full">
                        <div class="card-header hrm-resp-card-header">
                            <h5 class="card-title">Leave Report</h5>
                            <form method="GET">
                                <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-light dropdown-toggle"
                                        data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                        @php
                                            $label = 'Current Month';

                                            if (request('leave_from') && request('leave_to')) {
                                                $label = \Carbon\Carbon::parse(request('leave_from'))->format('d M Y')
                                                    . ' → ' .
                                                    \Carbon\Carbon::parse(request('leave_to'))->format('d M Y');
                                            } elseif (request('leave_filter') == 'week') {
                                                $label = 'Last Week';
                                            } elseif (request('leave_filter') == 'month') {
                                                $label = 'Last Month';
                                            } elseif (request('leave_filter') == '3month') {
                                                $label = 'Last 3 Months';
                                            } elseif (request('leave_filter') == '6month') {
                                                $label = 'Last 6 Months';
                                            } elseif (request('leave_filter') == 'year') {
                                                $label = 'Last 1 Year';
                                            }
                                        @endphp

                                        {{ $label }}
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-end p-2"
                                        style="min-width: 220px; position: absolute !important;">

                                        <!-- Normal Filters -->
                                        <div id="normalFiltersLeave">
                                            <button type="submit" name="leave_filter" value="week"
                                                class="dropdown-item" onclick="clearLeaveCustomDates()">Last Week</button>
                                            <button type="submit" name="leave_filter" value="month"
                                                class="dropdown-item" onclick="clearLeaveCustomDates()">Last Month</button>
                                            <button type="submit" name="leave_filter" value="3month"
                                                class="dropdown-item" onclick="clearLeaveCustomDates()">Last 3 Months</button>
                                            <button type="submit" name="leave_filter" value="6month"
                                                class="dropdown-item" onclick="clearLeaveCustomDates()">Last 6 Months</button>
                                            <button type="submit" name="leave_filter" value="year"
                                                class="dropdown-item" onclick="clearLeaveCustomDates()">Last 1 Year</button>


                                            <div class="dropdown-divider"></div>

                                            <a href="javascript:void(0);" class="dropdown-item text-primary fw-bold"
                                                onclick="event.stopPropagation(); showLeaveCustomFilter()">
                                                Custom Range →
                                            </a>
                                        </div>

                                        <!-- Custom Form -->
                                        <div id="customFilterBoxLeave" style="display:none;"
                                            onclick="event.stopPropagation();">
                                            <label class="form-label small mb-1">From</label>
                                            <input type="date" name="leave_from" class="form-control form-control-sm mb-2"
                                                value="{{ request('leave_from') }}">

                                            <label class="form-label small mb-1">To</label>
                                            <input type="date" name="leave_to" class="form-control form-control-sm mb-2"
                                                value="{{ request('leave_to') }}">

                                            <button type="submit" class="btn btn-sm btn-primary w-100 mb-2">
                                                Apply
                                            </button>

                                            <a href="javascript:void(0);" class="btn btn-sm btn-light w-100"
                                                onclick="hideLeaveCustomFilter()">← Back</a>
                                        </div>

                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body custom-card-action p-0">
                            <div class="table-responsive hrm-resp-table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        @forelse($leaveReport as $emp)
                                            <div class="p-3 border border-dashed rounded-3 mt-4 leave-slide-item mx-auto"
                                                style="max-width: 95%;">
                                                <div class="d-flex justify-content-between align-items-center">

                                                    <!-- Left side (employee info) -->
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="avatar-text avatar-md bg-soft-primary text-primary">
                                                            {{ substr($emp->name, 0, 1) }}
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $emp->name }}</div>
                                                            <div class="fs-11 text-muted">{{ $emp->designation }}</div>
                                                        </div>
                                                    </div>

                                                    <!-- Right side (leave count) -->
                                                    <div>
                                                        <span class="badge bg-soft-danger text-danger">
                                                            {{ $emp->leave_count }} Days
                                                        </span>
                                                    </div>

                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-4 text-muted">
                                                No leave data found.
                                            </div>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-none">
                            <div class="row g-4">
                                <div class="col-lg-3">
                                    <div class="p-3 border border-dashed rounded">
                                        <div class="fs-12 text-muted mb-1">Pending</div>
                                        <h6 class="fw-bold text-dark">₹{{ number_format($totalPendingAmount, 0) }}</h6>
                                        <div class="progress mt-2 ht-3">
                                            <div class="progress-bar bg-primary" role="progressbar"
                                                style="width: {{ $totalNetSalary > 0 ? ($totalPendingAmount / $totalNetSalary) * 100 : 0 }}%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="p-3 border border-dashed rounded">
                                        <div class="fs-12 text-muted mb-1">Paid</div>
                                        <h6 class="fw-bold text-dark">₹{{ number_format($totalPaidAmount, 0) }}</h6>
                                        <div class="progress mt-2 ht-3">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                style="width: {{ $totalNetSalary > 0 ? ($totalPaidAmount / $totalNetSalary) * 100 : 0 }}%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="p-3 border border-dashed rounded">
                                        <div class="fs-12 text-muted mb-1">Rejected</div>
                                        <h6 class="fw-bold text-dark">₹{{ number_format($totalRejectedAmount, 0) }}</h6>
                                        <div class="progress mt-2 ht-3">
                                            <div class="progress-bar bg-danger" role="progressbar"
                                                style="width: {{ $totalNetSalary > 0 ? ($totalRejectedAmount / $totalNetSalary) * 100 : 0 }}%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="p-3 border border-dashed rounded">
                                        <div class="fs-12 text-muted mb-1">Total Salary</div>
                                        <h6 class="fw-bold text-dark">₹{{ number_format($totalNetSalary, 0) }}</h6>
                                        <div class="progress mt-2 ht-3">
                                            <div class="progress-bar bg-dark" role="progressbar" style="width: 100%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- [Today Leave Records] end -->

                <!-- [Late Arrivals] start -->
                <div class="col-md-4">
                    <div class="card stretch stretch-full">
                        <div class="card-header hrm-resp-card-header">
                            <h5 class="card-title">Late Arrivals</h5>
                            <div class="card-header-action hrm-resp-card-header-action">
                                <div class="d-flex flex-wrap gap-2" id="lateFilterContainerUnique">
                                    <!-- Time Filter -->
                                    <div class="dropdown">

                                        <!-- Trigger Button -->
                                        <button class="btn btn-light btn-sm dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" style="width: 120px; height: 32px;">
                                            {{ request('late_range', 'Today') }}
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px;">

                                            <!-- Normal Filters -->
                                            <div id="normalFiltersLate">
                                                <button type="button" class="dropdown-item"
                                                    onclick="applyLateRange('today')">Today</button>
                                                <button type="button" class="dropdown-item"
                                                    onclick="applyLateRange('yesterday')">Yesterday</button>
                                                <button type="button" class="dropdown-item"
                                                    onclick="applyLateRange('week')">Last Week</button>
                                                <button type="button" class="dropdown-item"
                                                    onclick="applyLateRange('month')">Current Month</button>
                                                <button type="button" class="dropdown-item"
                                                    onclick="applyLateRange('last_month')">Last Month</button>
                                                <button type="button" class="dropdown-item"
                                                    onclick="applyLateRange('3months')">3 Months</button>
                                                <button type="button" class="dropdown-item"
                                                    onclick="applyLateRange('year')">1 Year</button>

                                                <div class="dropdown-divider"></div>

                                                <a href="javascript:void(0);" class="dropdown-item text-primary fw-bold"
                                                    onclick="event.stopPropagation(); showLateCustomFilter()">
                                                    Custom Range →
                                                </a>
                                            </div>

                                            <!-- Custom Form -->
                                            <div id="customFilterBoxLate" style="display:none;"
                                                onclick="event.stopPropagation();">
                                                <label class="form-label small mb-1">From</label>
                                                <input type="date" id="late_from" class="form-control form-control-sm mb-2"
                                                    value="{{ request('late_custom_start') }}">

                                                <label class="form-label small mb-1">To</label>
                                                <input type="date" id="late_to" class="form-control form-control-sm mb-2"
                                                    value="{{ request('late_custom_end') }}">

                                                <button type="button" class="btn btn-sm btn-primary w-100 mb-2"
                                                    onclick="applyLateCustomFilter()">
                                                    Apply
                                                </button>

                                                <a href="javascript:void(0);" class="btn btn-sm btn-light w-100"
                                                    onclick="hideLateCustomFilter()">← Back</a>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            @forelse($todayLateEmployees as $lateEmp)
                                <div class="p-3 border border-dashed rounded-3 mb-3 late-slide-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <div
                                            class="wd-50 ht-50 bg-soft-warning text-warning d-flex align-items-center justify-content-center rounded-2">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">
                                                {{ $lateEmp['employee']->name ?? 'N/A' }}
                                            </div>
                                            <div class="fs-11 text-muted">
                                                Late by {{ $lateEmp['late_duration'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    No late arrivals found.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <!-- [Late Arrivals] end -->

                <!--! BEGIN: [Upcoming Schedule] !-->

                <div class="col-md-4">
                    <div class="card stretch stretch-full">
                        <div class="card-header hrm-resp-card-header">
                            <h5 class="card-title">Upcoming Holidays</h5>
                            @php
                                // Use test date or real date
                                $today = isset($today)
                                    ? \Carbon\Carbon::parse($today)
                                    : \Carbon\Carbon::today();

                                // Get NEXT upcoming holiday (strictly future)
                                $nextHoliday = collect($upcomingHolidays)
                                    ->filter(fn($h) => \Carbon\Carbon::parse($h->date)->gt($today))
                                    ->sortBy('date')
                                    ->first();

                                if ($nextHoliday) {
                                    $hDate = \Carbon\Carbon::parse($nextHoliday->date);

                                    // Get proper difference (months + days)
                                    $diff = $today->diff($hDate);

                                    $months = $diff->m;
                                    $days = $diff->d;

                                    if ($months > 0) {
                                        $remainingText = $months . ' month' . ($months > 1 ? 's ' : ' ')
                                            . $days . ' day' . ($days > 1 ? 's' : '') . ' left';
                                    } else {
                                        if ($days == 1) {
                                            $remainingText = 'Tomorrow';
                                        } else {
                                            $remainingText = $days . ' days left';
                                        }
                                    }

                                    $badgeClass = 'badge bg-soft-success text-success'; // GREEN
                                } else {
                                    $remainingText = 'No upcoming holidays';
                                    $badgeClass = 'badge bg-soft-danger text-danger';
                                }
                            @endphp
                            <span class="{{ $badgeClass }}">{{ $remainingText }}</span>
                            <div class="card-header-action hrm-resp-card-header-action">
                                <div class="card-header-btn">
                                    <div data-bs-toggle="tooltip" title="Delete text-primary">
                                        <a href="javascript:void(0);" class="avatar-text avatar-xs bg-danger"
                                            data-bs-toggle="remove"> </a>
                                    </div>
                                    <div data-bs-toggle="tooltip" title="Refresh">
                                        <a href="javascript:void(0);" class="avatar-text avatar-xs bg-warning"
                                            data-bs-toggle="refresh"> </a>
                                    </div>
                                    <div data-bs-toggle="tooltip" title="Maximize/Minimize">
                                        <a href="javascript:void(0);" class="avatar-text avatar-xs bg-success"
                                            data-bs-toggle="expand"> </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @forelse($upcomingHolidays as $index => $holiday)
                                @php $hDate = \Carbon\Carbon::parse($holiday->date); @endphp
                                <div class="p-3 border border-dashed rounded-3 mb-3 holiday-slide-item {{ $index >= 4 ? 'd-none' : '' }}"
                                    data-index="{{ $index }}">
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <div
                                                class="wd-50 ht-50 bg-soft-primary text-primary lh-1 d-flex align-items-center justify-content-center flex-column rounded-2 schedule-date">
                                                <span class="fs-18 fw-bold mb-1 d-block">{{ $hDate->format('d') }}</span>
                                                <span
                                                    class="fs-10 fw-semibold text-uppercase d-block">{{ $hDate->format('M') }}</span>
                                            </div>
                                            <div class="text-dark">
                                                <a href="javascript:void(0);"
                                                    class="fw-bold mb-2 text-truncate-1-line">{{ $holiday->title }}</a>
                                                <span class="fs-11 fw-normal text-muted text-truncate-1-line">Holiday
                                                    ({{ $hDate->format('Y') }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">No upcoming holidays.</div>
                            @endforelse

                            @if(count($upcomingHolidays) > 4)
                                <div class="d-flex align-items-center justify-content-center gap-4 mt-2">
                                    <a href="javascript:void(0);" id="prev-holiday"
                                        class="avatar-text avatar-md bg-soft-primary text-primary opacity-50 border-0 disabled shadow-sm">
                                        <i class="feather-chevron-left fs-20"></i>
                                    </a>
                                    <a href="javascript:void(0);" id="next-holiday"
                                        class="avatar-text avatar-md bg-soft-primary text-primary border-0 shadow-sm">
                                        <i class="feather-chevron-right fs-20"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('holidays.index') }}"
                            class="card-footer fs-11 fw-bold text-uppercase text-center py-4">View Full Holiday Calendar</a>
                    </div>
                </div>
                <!--! END: [Upcoming Schedule] !-->
            </div>
            <div class="row">
                <!-- [Latest leave report] start -->
                <!-- <div class="col-xxl-8">
                            <div class="card stretch stretch-full">
                                <div class="card-header hrm-resp-card-header">
                                    <h5 class="card-title">Latest Leave Report</h5>

                                    <form method="GET" class="d-flex gap-2">
                                        Employee Dropdown
                                        <select name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()" style="height: 32px !important; padding: 0 0 0 10px !important">
                                            <option value="">All Employees</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}"
                                                    {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                                    {{ $emp->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        Keep filter value


                                    </form>
                                    <form method="GET">
                                        <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                                @php
                                                $label = 'Current Month';

                                                if (request('leave_from') && request('leave_to')) {
                                                    $label = \Carbon\Carbon::parse(request('leave_from'))->format('d M Y')
                                                            . ' → ' .
                                                            \Carbon\Carbon::parse(request('leave_to'))->format('d M Y');
                                                } elseif (request('leave_filter') == 'week') {
                                                    $label = 'Last Week';
                                                } elseif (request('leave_filter') == 'month') {
                                                    $label = 'Last Month';
                                                } elseif (request('leave_filter') == '3month') {
                                                    $label = 'Last 3 Months';
                                                } elseif (request('leave_filter') == '6month') {
                                                    $label = 'Last 6 Months';
                                                } elseif (request('leave_filter') == 'year') {
                                                    $label = 'Last 1 Year';
                                                }
                                            @endphp

                                            {{ $label }}
                                            </button>

                                            <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px; position: absolute !important;">

                                                Normal Filters
                                                <div id="normalFiltersLeave">
                                                    <button type="submit" name="leave_filter" value="week" class="dropdown-item">Last Week</button>
                                                    <button type="submit" name="leave_filter" value="month" class="dropdown-item">Last Month</button>
                                                    <button type="submit" name="leave_filter" value="3month" class="dropdown-item">Last 3 Months</button>
                                                    <button type="submit" name="leave_filter" value="6month" class="dropdown-item">Last 6 Months</button>
                                                    <button type="submit" name="leave_filter" value="year" class="dropdown-item">Last 1 Year</button>


                                                    <div class="dropdown-divider"></div>

                                                    <a href="javascript:void(0);"
                                                    class="dropdown-item text-primary fw-bold"
                                                    onclick="event.stopPropagation(); showLeaveCustomFilter()">
                                                    Custom Range →
                                                    </a>
                                                </div>

                                                Custom Form
                                                <div id="customFilterBoxLeave" style="display:none;" onclick="event.stopPropagation();">
                                                        <label class="form-label small mb-1">From</label>
                                                        <input type="date" name="leave_from" class="form-control form-control-sm mb-2"
                                                            value="{{ request('leave_from') }}">

                                                        <label class="form-label small mb-1">To</label>
                                                        <input type="date" name="leave_to" class="form-control form-control-sm mb-2"
                                                            value="{{ request('leave_to') }}">

                                                        <button type="submit" class="btn btn-sm btn-primary w-100 mb-2">
                                                            Apply
                                                        </button>

                                                        <a href="javascript:void(0);" class="btn btn-sm btn-light w-100"
                                                        onclick="hideLeaveCustomFilter()">← Back</a>
                                                </div>

                                            </div>
                                        </div>
                                    </form>
                                    <div class="card-header-action hrm-resp-card-header-action">
                                        <div class="card-header-btn">
                                            <div data-bs-toggle="tooltip" title="Delete">
                                                <a href="javascript:void(0);" class="avatar-text avatar-xs bg-danger"
                                                    data-bs-toggle="remove"> </a>
                                            </div>
                                            <div data-bs-toggle="tooltip" title="Refresh">
                                                <a href="javascript:void(0);" class="avatar-text avatar-xs bg-warning"
                                                    data-bs-toggle="refresh"> </a>
                                            </div>
                                            <div data-bs-toggle="tooltip" title="Maximize/Minimize">
                                                <a href="javascript:void(0);" class="avatar-text avatar-xs bg-success"
                                                    data-bs-toggle="expand"> </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body custom-card-action p-0">
                                    <div class="table-responsive hrm-resp-table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Month</th>
                                                    <th>Leave Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($leaveReport as $emp)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="avatar-text avatar-md bg-soft-primary text-primary">
                                                                    {{ substr($emp->name, 0, 1) }}
                                                                </div>
                                                                <div>
                                                                    <span class="d-block">{{ $emp->name }}</span>
                                                                    <span class="fs-12 text-muted">{{ $emp->designation }}</span>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <td>
                                                            <span class="badge bg-gray-200 text-dark">
                                                                {{ request('filter') ?? 'Last Month' }}
                                                            </span>
                                                        </td>

                                                        <td>
                                                            <span class="badge bg-soft-danger text-danger">
                                                                {{ $emp->leave_count }} Days
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-4">
                                                            No leave data found.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer d-flex flex-column align-items-center gap-3">
                                    <div class="fs-12 text-muted">
                                        @if($recentPayrolls->total() > 0)
                                            Showing {{ $recentPayrolls->firstItem() }} to {{ $recentPayrolls->lastItem() }} of {{ $recentPayrolls->total() }} entries
                                        @endif
                                    </div>
                                    @if($recentPayrolls->hasPages())
                                        <ul class="list-unstyled d-flex align-items-center gap-2 mb-0 pagination-common-style">
                                            <li>
                                                <a href="{{ $recentPayrolls->previousPageUrl() }}" class="{{ $recentPayrolls->onFirstPage() ? 'opacity-50 pointer-events-none' : '' }}">
                                                    <i class="feather-chevron-left"></i>
                                                </a>
                                            </li>
                                            @foreach ($recentPayrolls->getUrlRange(max(1, $recentPayrolls->currentPage() - 1), min($recentPayrolls->lastPage(), $recentPayrolls->currentPage() + 1)) as $page => $url)
                                                <li>
                                                    <a href="{{ $url }}" class="{{ ($page == $recentPayrolls->currentPage()) ? 'active' : '' }}">{{ $page }}</a>
                                                </li>
                                            @endforeach
                                            <li>
                                                <a href="{{ $recentPayrolls->nextPageUrl() }}" class="{{ !$recentPayrolls->hasMorePages() ? 'opacity-50 pointer-events-none' : '' }}">
                                                    <i class="feather-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div> -->
                <!-- [Latest leave report] end -->

                <!--! BEGIN: [Quick Notes] !-->
                <div class="col-xxl-4 saas-animate-in" style="animation-delay:0.55s">
                    @include('partials.quick-notes-card')
                </div>
                <!--! END: [Quick Notes] !-->
                <!--! BEGIN: [Attendance Analytics - disabled] !-->
                <!-- <div class="col-xxl-4">
                            <div class="card stretch stretch-full">
                                <div class="card-header border-bottom-0 pb-0">
                                    <h5 class="card-title">Attendance Analytics</h5>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('payroll.attendance') }}" class="dropdown-item">
                                                <i class="feather-external-link me-2"></i>
                                                <span>Full Attendance List</span>
                                            </a>
                                        </div>
                                    <div class="dropdown">
                                        <button type="button" class="avatar-text avatar-sm border-0 bg-transparent" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                            <i class="feather-more-vertical"></i>
                                        </button>

                                        <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px; position: absolute !important;">

                                            Normal Filters
                                            <div id="normalFilters">
                                                <a href="?filter=today" class="dropdown-item">Today</a>
                                                <a href="?filter=yesterday" class="dropdown-item">Yesterday</a>
                                                <a href="?filter=week" class="dropdown-item">Last Week</a>
                                                <a href="?filter=month" class="dropdown-item">Last Month</a>

                                                <div class="dropdown-divider"></div>

                                                <a href="javascript:void(0);" class="dropdown-item text-primary fw-bold" onclick="event.stopPropagation(); showCustomFilter()">
                                                    Custom Range →
                                                </a>
                                            </div>

                                            Custom Form (hidden initially)
                                            <div id="customFilterBox" style="display:none;" onclick="event.stopPropagation();">
                                                <form method="GET">
                                                    <label class="form-label small mb-1">From</label>
                                                    <input type="date" name="from" class="form-control form-control-sm mb-2"
                                                        value="{{ request('from') }}">

                                                    <label class="form-label small mb-1">To</label>
                                                    <input type="date" name="to" class="form-control form-control-sm mb-2"
                                                        value="{{ request('to') }}">

                                                    <button type="submit" class="btn btn-sm btn-primary w-100 mb-2">
                                                        Apply
                                                    </button>

                                                    <a href="javascript:void(0);" class="btn btn-sm btn-light w-100"
                                                    onclick="hideCustomFilter()">← Back</a>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-3 text-center">
                                    <div class="py-4 position-relative">
                                        <div class="fs-1 fw-black text-primary mb-1 mt-2">
                                            @if(request()->has('from') || request()->has('filter'))
                                                {{ $rangeAttendanceRate }}%
                                            @else
                                                {{ $attendanceRate }}%
                                            @endif
                                        </div>
                                        <div class="text-muted fw-bold small text-uppercase">Average Attendance Rate</div>
                                    </div>

                                    <div class="p-3 bg-soft-primary rounded-3 text-start mb-4">

                                        @php
                                            $isFiltered = request()->has('from') || request()->has('filter');
                                        @endphp

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-dark">Staff Present</span>
                                            <span class="small fw-black text-primary">
                                                {{ $isFiltered ? $rangePresent : $present }}/{{ $totalEmployees }}
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-dark">Work from home</span>
                                            <span class="small fw-black text-primary">
                                                {{ $isFiltered ? $rangeWFH : $wfh }}/{{ $totalEmployees }}
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-dark">Late</span>
                                            <span class="small fw-black text-primary">
                                                {{ $isFiltered ? $rangeLate : $late }}/{{ $totalEmployees }}
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-dark">Half Day</span>
                                            <span class="small fw-black text-primary">
                                                {{ $isFiltered ? $rangeHalfday : $half_day }}/{{ $totalEmployees }}
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-dark">Leave</span>
                                            <span class="small fw-black text-primary">
                                                {{ $isFiltered ? $rangeLeave : $leave }}/{{ $totalEmployees }}
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-dark">Early out</span>
                                            <span class="small fw-black text-primary">
                                                {{ $isFiltered ? $rangeEarly : $early }}/{{ $totalEmployees }}
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-dark">Absent</span>
                                            <span class="small fw-black text-primary">
                                                {{ $isFiltered ? $rangeAbsent : $absent }}/{{ $totalEmployees }}
                                            </span>
                                        </div>

                                    </div>
                                </div>

                                    <div class="row g-2 text-start">
                                        <div class="col-6 border-end">
                                            <div class="fs-5 fw-bold text-dark">{{ $totalEmployees }}</div>
                                            <div class="fs-11 text-muted text-uppercase fw-bold">Total Staff</div>
                                        </div>
                                        <div class="col-6 ps-3">
                                            <div class="fs-5 fw-bold text-success"></div>
                                            <div class="fs-11 text-muted text-uppercase fw-bold">Checked-in</div>
                                        </div>
                                    </div>
                                    <div class="card-footer border-top p-3 bg-light bg-opacity-10 text-center">
                                        <a href="{{ route('payroll.attendance.add') }}" class="fs-12 fw-bold text-primary text-uppercase">
                                            <i class="feather-plus-circle me-1"></i> Add Daily Records
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div> -->
                <!--! END: [Attendance Analytics] !-->
            </div><!-- row end -->
            <div class="row pt-4">

            </div><!-- second row end -->
            @if($canViewPayrollAnalytics)
            <!-- Monthly Summary Modal [ENHANCED] -->
            <div class="modal fade" id="summaryModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                        <div class="modal-header bg-soft-primary border-0 p-4">
                            <div>
                                <h5 class="modal-title fw-bold mb-0 text-primary">Financial Breakdown History</h5>
                                <p class="text-muted small mb-0 mt-1">Detailed payroll analytics for the last 6 months</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="table-responsive hrm-resp-table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-uppercase fs-10 fw-800 text-muted">
                                        <tr>
                                            <th class="ps-4">Month</th>
                                            <th>Basic / Earnings</th>
                                            <th>Deductions</th>
                                            <th>Net Payable</th>
                                            <th class="pe-4 text-end">Trends</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalHistoryTable">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4 bg-light bg-opacity-50">
                            <div class="w-100 d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    <i class="feather-info me-1"></i> Data shown is aggregated for all employees.
                                </div>
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close Summary</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Holiday Modal Deleted -->

    <!-- [ Main Content ] end -->
@endsection

@push('scripts')
    <script shadow>
        $(document).ready(function () {
            // Initializing the chart with dynamic data from controller
            var options = {
                chart: {
                    height: 380,
                    width: "100%",
                    type: "bar",
                    toolbar: { show: false },
                    fontFamily: 'Inter, sans-serif'
                },
                stroke: {
                    width: [0, 0, 0],
                    show: false
                },
                plotOptions: {
                    bar: {
                        columnWidth: "45%",
                        borderRadius: 4,
                        dataLabels: { position: 'top' }
                    }
                },
                colors: ["#e2e8f0", "#10b981", "#f59e0b"],
                // series: [
                //     {
                //         name: "Total Payroll (Expected)",
                //         type: "bar",
                //         data: {!! json_encode($chartTotal) !!}
                //     },
                //     {
                //         name: "Completed (Paid)",
                //         type: "bar",
                //         data: {!! json_encode($chartPaid) !!}
                //     },
                //     {
                //         name: "Pending (Unpaid)",
                //         type: "bar",
                //         data: {!! json_encode($chartPending) !!}
                //     }
                // ],
                // fill: {
                //     opacity: [1, 1, 1],
                //     type: ['solid', 'solid', 'solid']
                // },
                // markers: { size: 0 },
                // xaxis: {
                //     categories: {!! json_encode($chartMonths) !!},
                //     axisBorder: { show: false },
                //     axisTicks: { show: false },
                //     labels: {
                //         style: {
                //             fontSize: "10px",
                //             colors: "#64748b",
                //             fontWeight: 600
                //         }
                //     }
                // },
                yaxis: {
                    labels: {
                        formatter: function (e) {
                            return "₹" + e.toLocaleString()
                        },
                        style: { color: "#64748b", fontWeight: 600 }
                    }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } }
                },
                dataLabels: { enabled: false },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (e) {
                            return "₹" + e.toLocaleString()
                        }
                    },
                    theme: 'dark'
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: "12px",
                    fontFamily: "Inter",
                    fontWeight: 600,
                    markers: { radius: 12 }
                }
            };

            // Re-render chart to ensure dynamic data is applied over theme defaults
            setTimeout(function () {
                @if($canViewPayrollAnalytics)
                const chartContainer = document.querySelector("#payment-records-chart");
                var chart;
                if (chartContainer) {
                    chartContainer.innerHTML = '';
                    chart = new ApexCharts(chartContainer, options);
                    chart.render();
                }
                // Modal Drill-down Logic
                var summaryModal = new bootstrap.Modal(document.getElementById('summaryModal'));

                function showMonthlySummary(month) {
                    fetch(`/dashboard/summary?month=${month}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                let html = '';
                                data.history.forEach(item => {
                                    html += `
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark">${item.month}</div>
                                                <div class="fs-10 text-muted text-uppercase fw-bold">Financial Record</div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark">₹${item.earnings.toLocaleString()}</div>
                                                <div class="fs-10 text-success text-uppercase fw-bold">Total Earnings</div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-danger">-₹${item.deductions.toLocaleString()}</div>
                                                <div class="fs-10 text-muted text-uppercase fw-bold">Total Deducted</div>
                                            </td>
                                            <td>
                                                <div class="fw-black text-primary">₹${item.net.toLocaleString()}</div>
                                                <div class="fs-10 text-muted text-uppercase fw-bold">Net Distributed</div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <span class="badge bg-soft-primary text-primary fs-10 text-uppercase fw-bold">Analyzed</span>
                                            </td>
                                        </tr>
                                    `;
                                });
                                document.getElementById('modalHistoryTable').innerHTML = html;
                                summaryModal.show();
                            }
                        });
                }
                // Chart Update Logic
                function updateChartRange(range) {
                    fetch(`/dashboard/chart?range=${range}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                chart.updateSeries(data.series);
                                chart.updateOptions({
                                    xaxis: { categories: data.labels }
                                });
                            }
                        });
                }
                @endif

                // Holiday Slider Logic
                let currentHolidayPage = 0;
                const holidaysPerPage = 4;
                const totalHolidays = {{ count($upcomingHolidays) }};
                const holidayItems = document.querySelectorAll('.holiday-slide-item');
                const prevHolidayBtn = document.getElementById('prev-holiday');
                const nextHolidayBtn = document.getElementById('next-holiday');

                function updateHolidayView() {
                    holidayItems.forEach((item, index) => {
                        const start = currentHolidayPage * holidaysPerPage;
                        const end = start + holidaysPerPage;
                        if (index >= start && index < end) {
                            item.classList.remove('d-none');
                        } else {
                            item.classList.add('d-none');
                        }
                    });

                    // Update Button States
                    if (currentHolidayPage === 0) {
                        prevHolidayBtn.classList.add('disabled');
                        prevHolidayBtn.style.opacity = '0.5';
                    } else {
                        prevHolidayBtn.classList.remove('disabled');
                        prevHolidayBtn.style.opacity = '1';
                    }

                    if ((currentHolidayPage + 1) * holidaysPerPage >= totalHolidays) {
                        nextHolidayBtn.classList.add('disabled');
                        nextHolidayBtn.style.opacity = '0.5';
                    } else {
                        nextHolidayBtn.classList.remove('disabled');
                        nextHolidayBtn.style.opacity = '1';
                    }
                }

                if (nextHolidayBtn) {
                    nextHolidayBtn.addEventListener('click', function () {
                        if ((currentHolidayPage + 1) * holidaysPerPage < totalHolidays) {
                            currentHolidayPage++;
                            updateHolidayView();
                        }
                    });
                }

                if (prevHolidayBtn) {
                    prevHolidayBtn.addEventListener('click', function () {
                        if (currentHolidayPage > 0) {
                            currentHolidayPage--;
                            updateHolidayView();
                        }
                    });
                }
            }, 500);
        });

        function showCustomFilter() {
            document.getElementById('normalFilters').style.display = 'none';
            document.getElementById('customFilterBox').style.display = 'block';
        }

        function hideCustomFilter() {
            document.getElementById('normalFilters').style.display = 'block';
            document.getElementById('customFilterBox').style.display = 'none';
        }

        function showLeaveCustomFilter() {
            const box = event.target.closest('.dropdown-menu');
            box.querySelector('#normalFiltersLeave').style.display = 'none';
            box.querySelector('#customFilterBoxLeave').style.display = 'block';
        }

        function hideLeaveCustomFilter() {
            const box = event.target.closest('.dropdown-menu');
            box.querySelector('#customFilterBoxLeave').style.display = 'none';
            box.querySelector('#normalFiltersLeave').style.display = 'block';
        }

        const lateEmployeeFilter = document.getElementById('lateEmployeeFilter');
        const lateTimeFilter = document.getElementById('lateTimeFilter');

        lateEmployeeFilter?.addEventListener('change', applyLateFilters);
        lateTimeFilter?.addEventListener('change', applyLateFilters);

        function applyLateFilters() {
            let employee = lateEmployeeFilter?.value || '';
            let range = lateTimeFilter?.value || new URL(window.location.href).searchParams.get('late_range') || 'today';

            let url = new URL(window.location.href);

            if (employee) {
                url.searchParams.set('late_employee', employee);
            } else {
                url.searchParams.delete('late_employee');
            }

            url.searchParams.set('late_range', range);

            window.location.href = url.toString();
        }

        // Today Leave Slider
        initSlider({
            itemsSelector: '.leave-slide-item',
            prevBtnId: 'prev-leave',
            nextBtnId: 'next-leave',
            totalItems: {{ count($todayLeaveEmployees) }}
        });

        // Late Arrivals Slider
        initSlider({
            itemsSelector: '.late-slide-item',
            prevBtnId: 'prev-late',
            nextBtnId: 'next-late',
            totalItems: {{ count($todayLateEmployees) }}
        });

        function initSlider({
            itemsSelector,
            prevBtnId,
            nextBtnId,
            totalItems,
            itemsPerPage = 4
        }) {
            let currentPage = 0;
            const items = document.querySelectorAll(itemsSelector);
            const prevBtn = document.getElementById(prevBtnId);
            const nextBtn = document.getElementById(nextBtnId);

            function updateView() {
                items.forEach((item, index) => {
                    const start = currentPage * itemsPerPage;
                    const end = start + itemsPerPage;

                    if (index >= start && index < end) {
                        item.classList.remove('d-none');
                    } else {
                        item.classList.add('d-none');
                    }
                });

                // Prev Button
                if (prevBtn) {
                    if (currentPage === 0) {
                        prevBtn.classList.add('disabled');
                        prevBtn.style.opacity = '0.5';
                    } else {
                        prevBtn.classList.remove('disabled');
                        prevBtn.style.opacity = '1';
                    }
                }

                // Next Button
                if (nextBtn) {
                    if ((currentPage + 1) * itemsPerPage >= totalItems) {
                        nextBtn.classList.add('disabled');
                        nextBtn.style.opacity = '0.5';
                    } else {
                        nextBtn.classList.remove('disabled');
                        nextBtn.style.opacity = '1';
                    }
                }
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    if ((currentPage + 1) * itemsPerPage < totalItems) {
                        currentPage++;
                        updateView();
                    }
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    if (currentPage > 0) {
                        currentPage--;
                        updateView();
                    }
                });
            }

            updateView();
        }


        document.getElementById('lateEmployeeFilter')?.addEventListener('change', function () {
            applyLateRange(new URL(window.location.href).searchParams.get('late_range') || 'today');
        });
        function applyLateRange(range) {
            let url = new URL(window.location.href);

            url.searchParams.set('late_range', range);

            const emp = document.getElementById('lateEmployeeFilter')?.value;
            if (emp) url.searchParams.set('late_employee', emp);

            window.location.href = url.toString();
        }

        function applyLateCustomFilter() {
            let url = new URL(window.location.href);

            url.searchParams.set('late_range', 'custom');
            url.searchParams.set('late_custom_start', document.getElementById('late_from').value);
            url.searchParams.set('late_custom_end', document.getElementById('late_to').value);

            const emp = document.getElementById('lateEmployeeFilter')?.value;
            if (emp) url.searchParams.set('late_employee', emp);

            window.location.href = url.toString();
        }

        function showLateCustomFilter() {
            document.getElementById('normalFiltersLate').style.display = 'none';
            document.getElementById('customFilterBoxLate').style.display = 'block';
        }

        function hideLateCustomFilter() {
            document.getElementById('normalFiltersLate').style.display = 'block';
            document.getElementById('customFilterBoxLate').style.display = 'none';
        }

        function filterLateEmployee(employeeId) {
            const url = new URL(window.location.href);

            if (employeeId) {
                url.searchParams.set('late_employee', employeeId);
            } else {
                url.searchParams.delete('late_employee');
            }

            // KEEP EXISTING FILTERS (very important)
            const lateRange = "{{ request('late_range') }}";
            const start = "{{ request('late_custom_start') }}";
            const end = "{{ request('late_custom_end') }}";

            if (lateRange) url.searchParams.set('late_range', lateRange);
            if (start) url.searchParams.set('late_custom_start', start);
            if (end) url.searchParams.set('late_custom_end', end);

            window.location.href = url.toString();
        }

        function clearLeaveCustomDates() {
            document.querySelector('input[name="leave_from"]').value = '';
            document.querySelector('input[name="leave_to"]').value = '';
        }
    </script>
    @include('partials.urgent-announcements-scripts')
@endpush
