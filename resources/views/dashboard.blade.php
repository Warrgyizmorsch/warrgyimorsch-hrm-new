@extends('layouts.app')

@section('page_title', 'Home')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $payrollPaidPct = $totalNetSalary > 0 ? round(($totalPaidAmount / $totalNetSalary) * 100) : 0;
        $payrollPendingPct = $totalNetSalary > 0 ? round(($totalPendingAmount / $totalNetSalary) * 100) : 0;
        $payrollRejectedPct = $totalNetSalary > 0 ? round(($totalRejectedAmount / $totalNetSalary) * 100) : 0;
        $displayAttendanceRate = (request()->has('from') || request()->has('filter')) ? $rangeAttendanceRate : $attendanceRate;
        $attendanceMixTotal = $present + $wfh + $late + $half_day + $leave + $early + $absent;
    @endphp

    <style>
        /* ── Zoho Portal Dashboard (extends zoho-portal.css) ── */
        :root {
            --saas-navy: #1e2235;
            --saas-blue: #2284d0;
            --saas-blue-light: #42a5f5;
            --saas-surface: #ffffff;
            --saas-bg: #eef1f4;
            --saas-border: #dce1e6;
            --saas-text: #313949;
            --saas-muted: #6b7280;
            --saas-radius: 6px;
            --saas-shadow: 0 1px 2px rgba(30, 34, 53, 0.06);
            --saas-shadow-hover: 0 2px 8px rgba(30, 34, 53, 0.1);
        }

        .saas-dashboard-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
            padding: 1.75rem 2rem;
            margin-bottom: 1.5rem;
            border-radius: var(--saas-radius);
            background: linear-gradient(135deg, var(--saas-navy) 0%, #152a52 50%, var(--saas-blue) 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .saas-dashboard-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 90% 10%, rgba(96, 165, 250, 0.2) 0%, transparent 45%),
                radial-gradient(circle at 10% 90%, rgba(59, 130, 246, 0.15) 0%, transparent 40%);
            pointer-events: none;
        }

        .saas-hero-content { position: relative; z-index: 1; }

        .saas-hero-eyebrow {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            opacity: 0.7;
            margin: 0 0 0.35rem;
        }

        .saas-hero-title {
            font-size: clamp(1.35rem, 2.5vw, 1.75rem);
            font-weight: 800;
            margin: 0 0 0.35rem;
            letter-spacing: -0.02em;
        }

        .saas-hero-sub {
            font-size: 0.875rem;
            opacity: 0.78;
            margin: 0;
        }

        .saas-hero-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            position: relative;
            z-index: 1;
            align-self: center;
        }

        .saas-hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 0.78rem;
            font-weight: 600;
            backdrop-filter: blur(6px);
        }

        .saas-hero-pill i { font-size: 13px; opacity: 0.85; }

        .saas-animate-in {
            opacity: 0;
            animation: saasFadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes saasFadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .saas-stat-card {
            background: var(--saas-surface);
            border: 1px solid var(--saas-border);
            border-radius: var(--saas-radius);
            padding: 1.35rem 1.4rem;
            box-shadow: var(--saas-shadow);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .saas-stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: var(--saas-radius) var(--saas-radius) 0 0;
        }

        .saas-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--saas-shadow-hover);
        }

        .saas-stat-paid::after { background: linear-gradient(90deg, #10b981, #34d399); }
        .saas-stat-pending::after { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .saas-stat-rejected::after { background: linear-gradient(90deg, #ef4444, #f87171); }
        .saas-stat-staff::after { background: linear-gradient(90deg, var(--saas-blue), var(--saas-blue-light)); }

        .saas-stat-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .saas-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .saas-stat-paid .saas-stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .saas-stat-pending .saas-stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .saas-stat-rejected .saas-stat-icon { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .saas-stat-staff .saas-stat-icon { background: rgba(29, 78, 216, 0.1); color: var(--saas-blue); }

        .saas-stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--saas-text);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .saas-stat-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--saas-muted);
            margin-top: 0.15rem;
        }

        .saas-stat-footer {
            padding-top: 0.85rem;
            border-top: 1px solid #f1f5f9;
        }

        .saas-progress-track {
            height: 6px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .saas-progress-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1);
            width: 0;
        }

        .saas-progress-fill.is-animated { width: var(--fill-width); }

        .saas-chart-card {
            background: var(--saas-surface);
            border: 1px solid var(--saas-border);
            border-radius: var(--saas-radius);
            box-shadow: var(--saas-shadow);
            overflow: hidden;
            height: 100%;
        }

        .saas-chart-card .card-header,
        .saas-panel-card .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.15rem 1.35rem;
        }

        .saas-chart-card .card-title,
        .saas-panel-card .card-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--saas-text);
            margin: 0;
        }

        .saas-chart-card .card-body { padding: 1rem 1.35rem 1.35rem; }

        .saas-chart-wrap {
            min-height: 300px;
            position: relative;
        }

        .saas-chart-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 260px;
            text-align: center;
            color: var(--saas-muted);
            padding: 1.5rem;
        }

        .saas-chart-empty-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #94a3b8;
            margin-bottom: 0.75rem;
        }

        .saas-chart-empty p {
            margin: 0;
            font-size: 0.8125rem;
            max-width: 220px;
            line-height: 1.5;
        }

        .saas-range-pills {
            display: flex;
            gap: 0.35rem;
            background: #f8fafc;
            padding: 0.25rem;
            border-radius: 10px;
        }

        .saas-range-pills button {
            border: none;
            background: transparent;
            padding: 0.3rem 0.65rem;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--saas-muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .saas-range-pills button.active,
        .saas-range-pills button:hover {
            background: #fff;
            color: var(--saas-blue);
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .saas-panel-card {
            background: var(--saas-surface);
            border: 1px solid var(--saas-border);
            border-radius: var(--saas-radius);
            box-shadow: var(--saas-shadow);
            height: 100%;
            transition: box-shadow 0.25s ease;
        }

        .saas-panel-card:hover { box-shadow: var(--saas-shadow-hover); }

        .saas-list-item {
            padding: 0.85rem 1rem;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            margin-bottom: 0.65rem;
            background: #fafbfc;
            transition: background 0.2s, border-color 0.2s, transform 0.2s;
        }

        .saas-list-item:hover {
            background: #fff;
            border-color: var(--saas-border);
            transform: translateX(3px);
        }

        .saas-list-item:last-child { margin-bottom: 0; }

        .saas-avatar {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .saas-attendance-ring {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 0.5rem;
        }

        #attendance-rate-ring {
            position: absolute;
            inset: 0;
        }

        .saas-attendance-rate {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .saas-attendance-rate .rate-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--saas-blue);
            line-height: 1;
        }

        .saas-attendance-rate .rate-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--saas-muted);
            letter-spacing: 0.04em;
            margin-top: 0.2rem;
        }

        .saas-metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.55rem 0;
            border-bottom: 1px solid #f8fafc;
        }

        .saas-metric-row:last-child { border-bottom: none; }

        .saas-metric-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
            flex-shrink: 0;
        }

        .saas-announcement-card {
            display: none;
        }

        @media (max-width: 767.98px) {
            .saas-dashboard-hero { padding: 1.25rem 1.35rem; }
            .saas-hero-pills { width: 100%; }
        }

        /* Unique Premium Dropdown UI */
        .wghrm-custom-select-btn {
            border-radius: 12px !important;
            border: 1px solid #e2e8f0 !important;
            background-color: #fff !important;
            padding: 8px 16px !important;
            font-weight: 500 !important;
            color: #64748b !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            text-align: left !important;
            min-width: 120px;
        }

        .wghrm-custom-select-btn:focus,
        .wghrm-custom-select-btn[aria-expanded="true"] {
            border-color: #3858f9 !important;
            box-shadow: 0 0 0 3px rgba(56, 88, 249, 0.1) !important;
            color: #3858f9 !important;
        }

        .wghrm-custom-dropdown-menu {
            border-radius: 16px !important;
            border: 1px solid #f1f5f9 !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08) !important;
            padding: 8px !important;
            margin-top: 8px !important;
            z-index: 99 !important;
            /* Reduced to avoid header overlap */
            background: #fff !important;
            max-height: 350px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        /* Custom Scrollbar (Slider) */
        .wghrm-custom-dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
            margin: 8px 0;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .wghrm-custom-search-input {
            border-radius: 10px !important;
            border: 1px solid #e2e8f0 !important;
            padding: 8px 12px !important;
            font-size: 13px !important;
            width: 100% !important;
            outline: none !important;
            background-color: #f8fafc !important;
            margin-bottom: 8px !important;
            transition: border-color 0.2s ease;
        }

        .wghrm-custom-search-input:focus {
            border-color: #3858f9 !important;
            background-color: #fff !important;
        }

        .wghrm-custom-dropdown-item {
            border-radius: 8px !important;
            padding: 10px 15px !important;
            font-weight: 500 !important;
            color: #475569 !important;
            margin-bottom: 2px !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            display: block !important;
            text-decoration: none !important;
        }

        .wghrm-custom-dropdown-item:hover,
        .wghrm-custom-dropdown-item.active {
            background-color: #eff6ff !important;
            color: #3858f9 !important;
        }

        .wghrm-custom-dropdown-divider {
            margin: 8px 0 !important;
            border-top: 1px solid #f1f5f9 !important;
        }

        .late-scroll-container{
            max-height: 485px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }

        .leave-report-scroll-container{
            height: 402px;
            max-height: 485px;
            overflow-y: auto !important;
            overflow-x: auto !important;
            display: block;
        }

        .leave-report-scroll-container table{
            margin-bottom: 0;
        }

        .leave-report-scroll-container::-webkit-scrollbar{
            width: 0px;
            height: 6px;
        }

        .leave-report-scroll-container::-webkit-scrollbar-track{
            background: #f1f5f9;
            border-radius: 10px;
        }

        .leave-report-scroll-container::-webkit-scrollbar-thumb{
            background: #cbd5e1;
            border-radius: 10px;
        }

        .leave-report-scroll-container::-webkit-scrollbar-thumb:hover{
            background: #94a3b8;
        }

        /* Custom Scrollbar */
        .late-scroll-container::-webkit-scrollbar{
            width: 0px;
        }

        .late-scroll-container::-webkit-scrollbar-track{
            background: #f1f5f9;
            border-radius: 10px;
        }

        .late-scroll-container::-webkit-scrollbar-thumb{
            background: #cbd5e1;
            border-radius: 10px;
        }

        .late-scroll-container::-webkit-scrollbar-thumb:hover{
            background: #94a3b8;
        }

        @media (max-width: 575.98px) {
            .wghrm-resp-dropdown-menu {
                position: absolute !important;
                left: auto !important;
                right: 0 !important;
                max-width: calc(100vw - 30px) !important;
                overflow-x: hidden !important;
            }
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

        .saas-leave-report-header {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            padding: 1.15rem 1.35rem;
            border-bottom: 1px solid #f1f5f9;
            background: transparent;
        }

        .saas-leave-report-header .card-title {
            margin: 0;
            font-size: 0.9375rem;
            font-weight: 700;
        }

        .saas-leave-report-filters {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
            width: 100%;
        }

        .saas-leave-report-filters form {
            min-width: 0;
            width: 100%;
        }

        .saas-leave-report-filters .dropdown {
            width: 100%;
        }

        .saas-leave-report-filters .wghrm-custom-select-btn {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (min-width: 576px) {
            .saas-leave-report-filters {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (min-width: 992px) {
            .saas-leave-report-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }

            .saas-leave-report-filters {
                width: auto;
                flex: 0 1 auto;
                grid-template-columns: auto auto;
                gap: 0.5rem;
            }

            .saas-leave-report-filters form {
                width: auto;
            }

            .saas-leave-report-filters .dropdown {
                width: auto;
            }

            .saas-leave-report-filters .wghrm-custom-select-btn {
                width: auto !important;
                min-width: 150px !important;
                max-width: 220px;
            }
        }

        @media (max-width: 575.98px) {
            .saas-leave-report-filters .wghrm-custom-dropdown-menu {
                position: absolute !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }

        .leave-report-loading,
        .saas-ajax-loading {
            opacity: 0.45;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        /* Premium scrollbar for tables */
        .hrm-resp-table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .hrm-resp-table-responsive::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

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

    <!-- [ Main Content ] start -->
    <div class="main-content pt-md-2 pt-2 hrm-resp-main-content">
        {{-- Zoho-style quick summary row --}}
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="zoho-widget">
                    <div class="zoho-widget-body padded d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <div class="fs-15 fw-bold text-dark">{{ $greeting }}, {{ auth()->user()->name ?? 'User' }}</div>
                            <div class="zoho-widget-sub mt-1">HRM overview for {{ $selectedMonthLabel }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="zoho-header-btn"><i class="feather-users me-1"></i>{{ $totalEmployees }} Staff</span>
                            <span class="zoho-header-btn"><i class="feather-activity me-1"></i>{{ $attendanceRate }}% Today</span>
                            <span class="zoho-header-btn"><i class="feather-calendar me-1"></i>{{ $selectedMonthLabel }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.urgent-announcements-card', ['announcements' => $announcements])

        <div class="row g-3 mb-4">
            @if($canViewPayrollAnalytics)
            <div class="col-xxl-3 col-md-6 saas-animate-in" style="animation-delay:0.05s">
                <div class="zoho-metric zoho-metric--success position-relative">
                    <span class="zoho-metric-label zoho-metric-label--success">Paid in {{ $selectedMonthLabel }}</span>
                    <div class="zoho-metric-value saas-count-up" data-target="{{ round($totalPaidAmount) }}" data-prefix="₹">₹{{ number_format($totalPaidAmount, 0) }}</div>
                    <div class="zoho-metric-meta">{{ $totalEmpPaid }} employees paid · {{ $payrollPaidPct }}%</div>
                    <div class="dropdown position-absolute" style="top:0.75rem;right:0.75rem;">
                        <a href="javascript:void(0);" class="text-muted" data-bs-toggle="dropdown"><i class="feather-more-horizontal"></i></a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @for ($i = 0; $i < 6; $i++)
                                @php $m = \Carbon\Carbon::now()->startOfMonth()->subMonths($i); @endphp
                                <a href="{{ route('dashboard', ['month' => $m->format('Y-m')]) }}" class="dropdown-item {{ $selectedMonth == $m->format('Y-m') ? 'active' : '' }}">{{ $m->format('M Y') }}</a>
                            @endfor
                            <div class="dropdown-divider"></div>
                            <a href="javascript:void(0);" class="dropdown-item" onclick="showMonthlySummary('{{ $selectedMonth }}')">Full Breakdown</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-md-6 saas-animate-in" style="animation-delay:0.1s">
                <div class="zoho-metric zoho-metric--warning">
                    <span class="zoho-metric-label zoho-metric-label--warning">Pending in {{ $selectedMonthLabel }}</span>
                    <div class="zoho-metric-value saas-count-up" data-target="{{ round($totalPendingAmount) }}" data-prefix="₹">₹{{ number_format($totalPendingAmount, 0) }}</div>
                    <div class="zoho-metric-meta">{{ $totalEmpPending }} awaiting payment · {{ $payrollPendingPct }}%</div>
                </div>
            </div>
            <div class="col-xxl-3 col-md-6 saas-animate-in" style="animation-delay:0.15s">
                <div class="zoho-metric zoho-metric--danger">
                    <span class="zoho-metric-label zoho-metric-label--danger">Rejected in {{ $selectedMonthLabel }}</span>
                    <div class="zoho-metric-value saas-count-up" data-target="{{ round($totalRejectedAmount) }}" data-prefix="₹">₹{{ number_format($totalRejectedAmount, 0) }}</div>
                    <div class="zoho-metric-meta">Payment failed · {{ $payrollRejectedPct }}%</div>
                </div>
            </div>
            @endif
            <div class="col-xxl-3 col-md-6 saas-animate-in" style="animation-delay:0.2s">
                <div class="zoho-metric zoho-metric--info position-relative">
                    <span class="zoho-metric-label">Total Active Staff</span>
                    <div class="zoho-metric-value saas-count-up" data-target="{{ $totalEmployees }}" data-prefix="" data-skip-animate="{{ $totalEmployees == 0 ? '1' : '0' }}">{{ $totalEmployees }}</div>
                    <div class="zoho-metric-meta">Today's attendance rate · {{ $attendanceRate }}%</div>
                    <div class="dropdown position-absolute" style="top:0.75rem;right:0.75rem;">
                        <a href="javascript:void(0);" class="text-muted" data-bs-toggle="dropdown"><i class="feather-more-horizontal"></i></a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @for ($i = 0; $i < 6; $i++)
                                @php $m = \Carbon\Carbon::now()->startOfMonth()->subMonths($i); @endphp
                                <a href="{{ route('dashboard', ['month' => $m->format('Y-m')]) }}" class="dropdown-item">{{ $m->format('M Y') }}</a>
                            @endfor
                            @if($canViewPayrollAnalytics)
                            <div class="dropdown-divider"></div>
                            <a href="javascript:void(0);" class="dropdown-item" onclick="showFullYearBreakdown('{{ date('Y') }}')">Yearly Breakdown</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Analytics Charts Row --}}
        <div class="row g-3 mb-4">
            @if($canViewPayrollAnalytics)
            <div class="col-xxl-8 col-lg-7 saas-animate-in" style="animation-delay:0.25s">
                <div class="saas-chart-card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="card-title">Payroll Analytics</h5>
                            <p class="text-muted fs-12 mb-0">Net salary distribution over time</p>
                        </div>
                        <div class="saas-range-pills" id="payrollRangePills">
                            <button type="button" data-range="6" class="active">6M</button>
                            <button type="button" data-range="12">12M</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="saas-chart-wrap">
                            <div id="saas-payroll-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <div class="{{ $canViewPayrollAnalytics ? 'col-xxl-4 col-lg-5' : 'col-12' }} saas-animate-in" style="animation-delay:0.3s">
                <div class="saas-chart-card">
                    <div class="card-header">
                        <h5 class="card-title">Today's Attendance Mix</h5>
                        <p class="text-muted fs-12 mb-0">Live workforce breakdown</p>
                    </div>
                    <div class="card-body">
                        @if($attendanceMixTotal > 0)
                            <div class="saas-chart-wrap">
                                <div id="attendance-donut-chart"></div>
                            </div>
                        @else
                            <div class="saas-chart-empty">
                                <div class="saas-chart-empty-icon"><i class="feather-users"></i></div>
                                <strong class="fs-14 text-dark mb-1">No attendance recorded today</strong>
                                <p>Attendance will appear here once employees check in or records are added.</p>
                            </div>
                        @endif
                        <div class="row g-2 mt-1 text-center">
                            <div class="col-4"><div class="fs-14 fw-bold text-success">{{ $present }}</div><div class="fs-11 text-muted">Present</div></div>
                            <div class="col-4"><div class="fs-14 fw-bold text-warning">{{ $late }}</div><div class="fs-11 text-muted">Late</div></div>
                            <div class="col-4"><div class="fs-14 fw-bold text-danger">{{ $absent }}</div><div class="fs-11 text-muted">Absent</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <div class="row g-3">

                <!-- [Today Leave Records] start -->
                <div class="col-md-4 saas-animate-in" style="animation-delay:0.35s">
                    <div class="saas-panel-card">
                        <div class="card-header hrm-resp-card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Today Leave</h5>
                                <p class="text-muted fs-12 mb-0">{{ count($todayLeaveEmployees) }} employees away</p>
                            </div>
                            <span class="badge bg-soft-danger text-danger">{{ count($todayLeaveEmployees) }}</span>
                        </div>
                        <div class="card-body">
                            <div class="late-scroll-container">
                                @forelse($todayLeaveEmployees as $todayLeave)
                                    <div class="saas-list-item">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="saas-avatar bg-soft-danger text-danger">
                                                {{ strtoupper(substr($todayLeave->employee_name ?? 'N', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold fs-13">{{ $todayLeave->employee_name ?? 'N/A' }}</div>
                                                <div class="fs-11 text-muted">{{ $todayLeave->leave_type }} Today</div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-4 text-muted">
                                        No employees on leave today.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <!-- [Today Leave Records] end -->

                <!-- [Late Arrivals] start -->
                <div class="col-md-4 saas-animate-in" style="animation-delay:0.4s">
                    <div class="saas-panel-card">
                        <div class="card-header hrm-resp-card-header">
                            <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                                <div>
                                    <h5 class="card-title">Late Arrivals</h5>
                                    <p class="text-muted fs-12 mb-0">Filtered by period</p>
                                </div>
                                <span class="badge bg-soft-warning text-warning" id="lateArrivalsCount">{{ count($todayLateEmployees) }}</span>
                            </div>
                            <div class="card-header-action hrm-resp-card-header-action mt-2">
                                <div class="d-flex flex-wrap gap-2 w-100" id="lateFilterContainerUnique">
                                    <div class="dropdown flex-fill" id="lateEmployeeDropdown" style="min-width: 140px;">
                                        <button class="btn wghrm-custom-select-btn dropdown-toggle w-100" type="button"
                                            id="lateEmployeeFilterBtn"
                                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            @php
                                                $lateSelectedEmp = $employees->firstWhere('id', request('late_employee'));
                                            @endphp
                                            <span id="lateEmployeeFilterLabel">{{ $lateSelectedEmp ? $lateSelectedEmp->name : 'All Employees' }}</span>
                                        </button>
                                        <div class="dropdown-menu wghrm-custom-dropdown-menu wghrm-resp-dropdown-menu">
                                            <div class="wghrm-custom-search-box">
                                                <input type="text" class="wghrm-custom-search-input"
                                                    placeholder="Search employee..." onkeyup="wghrmFilterItems(this)"
                                                    onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                            </div>
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ !request('late_employee') ? 'active' : '' }}"
                                                href="javascript:void(0);" onclick="applyLateEmployeeFilter('')">
                                                All Employees
                                            </a>
                                            @foreach($employees as $emp)
                                                <a class="dropdown-item wghrm-custom-dropdown-item {{ request('late_employee') == $emp->id ? 'active' : '' }}"
                                                    href="javascript:void(0);" onclick="applyLateEmployeeFilter('{{ $emp->id }}')">
                                                    {{ $emp->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="dropdown flex-fill" id="lateRangeDropdown" style="min-width: 140px;">
                                        <button class="btn wghrm-custom-select-btn dropdown-toggle w-100" type="button"
                                            id="lateRangeFilterBtn"
                                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            @php
                                                $lateRangeLabel = match (request('late_range', 'today')) {
                                                    'today' => 'Today',
                                                    'yesterday' => 'Yesterday',
                                                    'week' => 'Last Week',
                                                    'month' => 'Current Month',
                                                    'last_month' => 'Last Month',
                                                    '3months' => '3 Months',
                                                    'year' => '1 Year',
                                                    'custom' => (request('late_custom_start') && request('late_custom_end'))
                                                        ? \Carbon\Carbon::parse(request('late_custom_start'))->format('d M Y') . ' → ' . \Carbon\Carbon::parse(request('late_custom_end'))->format('d M Y')
                                                        : 'Custom Range',
                                                    default => 'Today',
                                                };
                                            @endphp
                                            <span id="lateRangeFilterLabel">{{ $lateRangeLabel }}</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end wghrm-custom-dropdown-menu wghrm-resp-dropdown-menu">
                                            <div class="wghrm-custom-search-box">
                                                <input type="text" class="wghrm-custom-search-input"
                                                    placeholder="Search range..." onkeyup="wghrmFilterItems(this)"
                                                    onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                            </div>
                                            <div id="normalFiltersLate">
                                                <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                    onclick="applyLateRangeFilter('today')">Today</button>
                                                <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                    onclick="applyLateRangeFilter('yesterday')">Yesterday</button>
                                                <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                    onclick="applyLateRangeFilter('week')">Last Week</button>
                                                <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                    onclick="applyLateRangeFilter('month')">Current Month</button>
                                                <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                    onclick="applyLateRangeFilter('last_month')">Last Month</button>
                                                <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                    onclick="applyLateRangeFilter('3months')">3 Months</button>
                                                <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                    onclick="applyLateRangeFilter('year')">1 Year</button>
                                                <div class="wghrm-custom-dropdown-divider"></div>
                                                <a href="javascript:void(0);"
                                                    class="dropdown-item wghrm-custom-dropdown-item text-primary fw-bold"
                                                    onclick="event.stopPropagation(); showLateCustomFilter()">
                                                    Custom Range →
                                                </a>
                                            </div>
                                            <div id="customFilterBoxLate" style="display:none;"
                                                onclick="event.stopPropagation();">
                                                <label class="form-label small mb-1">From</label>
                                                <input type="date" id="late_from" class="form-control form-control-sm mb-2"
                                                    value="{{ request('late_custom_start') }}">
                                                <label class="form-label small mb-1">To</label>
                                                <input type="date" id="late_to" class="form-control form-control-sm mb-2"
                                                    value="{{ request('late_custom_end') }}">
                                                <button type="button" class="btn btn-sm btn-primary w-100 mb-2"
                                                    onclick="applyLateCustomRangeFilter()">
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

                        <div class="card-body" id="lateArrivalsListWrap">
                            <div class="late-scroll-container" id="lateArrivalsListBody">
                                @include('dashboard.partials.late-arrivals-list', ['todayLateEmployees' => $todayLateEmployees])
                            </div>
                        </div>
                    </div>
                </div>
                <!-- [Late Arrivals] end -->

                <!--! BEGIN: [Upcoming Schedule] !-->

                <div class="col-md-4 saas-animate-in" style="animation-delay:0.45s">
                    <div class="saas-panel-card">
                        <div class="card-header hrm-resp-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="card-title">Upcoming Holidays</h5>
                                <p class="text-muted fs-12 mb-0">Company calendar</p>
                            </div>
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
                        </div>
                        <div class="card-body">
                            @forelse($upcomingHolidays as $index => $holiday)
                                @php $hDate = \Carbon\Carbon::parse($holiday->date); @endphp
                                <div class="saas-list-item holiday-slide-item {{ $index >= 4 ? 'd-none' : '' }}" data-index="{{ $index }}">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="saas-avatar bg-soft-primary text-primary flex-column lh-1" style="height:46px;">
                                            <span class="fs-16 fw-bold">{{ $hDate->format('d') }}</span>
                                            <span class="fs-10 text-uppercase">{{ $hDate->format('M') }}</span>
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-13">{{ $holiday->title }}</div>
                                            <div class="fs-11 text-muted">Holiday · {{ $hDate->format('Y') }}</div>
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
            <div class="row g-3 mt-1">
                <!-- [Latest leave report] start -->
                <div class="col-xxl-8 saas-animate-in" style="animation-delay:0.5s">
                    <div class="saas-panel-card">
                        <div class="saas-leave-report-header">
                            <h5 class="card-title">Latest Leave Report</h5>

                            <div class="saas-leave-report-filters">
                                <div class="dropdown" id="leaveEmployeeDropdown">
                                    <button class="btn wghrm-custom-select-btn dropdown-toggle" type="button"
                                        id="leaveEmployeeFilterBtn"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                        @php
                                            $leaveEmp = $employees->firstWhere('id', request('employee_id'));
                                        @endphp
                                        <span id="leaveEmployeeFilterLabel">{{ $leaveEmp ? $leaveEmp->name : 'All Employees' }}</span>
                                    </button>
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu wghrm-resp-dropdown-menu">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input"
                                                placeholder="Search employee..." onkeyup="wghrmFilterItems(this)"
                                                onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                        </div>
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ !request('employee_id') ? 'active' : '' }}"
                                            href="javascript:void(0);" onclick="applyLeaveEmployeeFilter('')">
                                            All Employees
                                        </a>
                                        @foreach($employees as $emp)
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ request('employee_id') == $emp->id ? 'active' : '' }}"
                                                href="javascript:void(0);" onclick="applyLeaveEmployeeFilter('{{ $emp->id }}')">
                                                {{ $emp->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="dropdown" id="leaveRangeDropdown">
                                    <button class="btn wghrm-custom-select-btn dropdown-toggle" type="button"
                                        id="leaveRangeFilterBtn"
                                        data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="outside" aria-expanded="false">
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
                                        <span id="leaveRangeFilterLabel">{{ $label }}</span>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-end wghrm-custom-dropdown-menu wghrm-resp-dropdown-menu">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input"
                                                placeholder="Search range..." onkeyup="wghrmFilterItems(this)"
                                                onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                        </div>

                                        <div id="normalFiltersLeave">
                                            <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                onclick="applyLeaveRangeFilter('week')">Last Week</button>
                                            <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                onclick="applyLeaveRangeFilter('month')">Last Month</button>
                                            <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                onclick="applyLeaveRangeFilter('3month')">Last 3 Months</button>
                                            <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                onclick="applyLeaveRangeFilter('6month')">Last 6 Months</button>
                                            <button type="button" class="dropdown-item wghrm-custom-dropdown-item"
                                                onclick="applyLeaveRangeFilter('year')">Last 1 Year</button>

                                            <div class="wghrm-custom-dropdown-divider"></div>

                                            <a href="javascript:void(0);"
                                                class="dropdown-item wghrm-custom-dropdown-item text-primary fw-bold"
                                                onclick="event.stopPropagation(); showLeaveCustomFilter()">
                                                Custom Range →
                                            </a>
                                        </div>

                                        <div id="customFilterBoxLeave" style="display:none;"
                                            onclick="event.stopPropagation();">
                                            <label class="form-label small mb-1">From</label>
                                            <input type="date" id="leave_from" class="form-control form-control-sm mb-2"
                                                value="{{ request('leave_from') }}">

                                            <label class="form-label small mb-1">To</label>
                                            <input type="date" id="leave_to" class="form-control form-control-sm mb-2"
                                                value="{{ request('leave_to') }}">

                                            <button type="button" class="btn btn-sm btn-primary w-100 mb-2"
                                                onclick="applyLeaveCustomRangeFilter()">
                                                Apply
                                            </button>

                                            <a href="javascript:void(0);" class="btn btn-sm btn-light w-100"
                                                onclick="hideLeaveCustomFilter()">← Back</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body custom-card-action p-0" id="leaveReportTableWrap">
                            <div class="table-responsive hrm-resp-table-responsive leave-report-scroll-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Leave Count</th>
                                        </tr>
                                    </thead>
                                    <tbody id="leaveReportTableBody">
                                        @include('dashboard.partials.leave-report-rows', ['leaveReport' => $leaveReport])
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <!-- [Latest leave report] end -->

                <!--! BEGIN: [Attendance Analytics] !-->
                <div class="col-xxl-4 saas-animate-in" style="animation-delay:0.55s">
                    <div class="saas-panel-card">
                        <div class="card-header border-bottom-0 pb-0 d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title">Attendance Analytics</h5>
                                <p class="text-muted fs-12 mb-0">Filtered workforce metrics</p>
                            </div>
                            <!-- <div class="dropdown-menu dropdown-menu-end">
                                                <a href="{{ route('payroll.attendance') }}" class="dropdown-item">
                                                    <i class="feather-external-link me-2"></i>
                                                    <span>Full Attendance List</span>
                                                </a>
                                            </div> -->
                            <div class="dropdown">
                                <button type="button" class="avatar-text avatar-sm border-0 bg-transparent"
                                    data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                    <i class="feather-more-vertical"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-end p-2"
                                    style="min-width: 220px; position: absolute !important;">

                                    <!-- Normal Filters -->
                                    <div id="normalFilters">
                                        <a href="?filter=today" class="dropdown-item">Today</a>
                                        <a href="?filter=yesterday" class="dropdown-item">Yesterday</a>
                                        <a href="?filter=week" class="dropdown-item">Last Week</a>
                                        <a href="?filter=month" class="dropdown-item">Last Month</a>

                                        <div class="dropdown-divider"></div>

                                        <a href="javascript:void(0);" class="dropdown-item text-primary fw-bold"
                                            onclick="event.stopPropagation(); showCustomFilter()">
                                            Custom Range →
                                        </a>
                                    </div>

                                    <!-- Custom Form (hidden initially) -->
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
                        <div class="card-body pt-2">
                            @php $isFiltered = request()->has('from') || request()->has('filter'); @endphp
                            <div class="saas-attendance-ring mb-3">
                                <div id="attendance-rate-ring"></div>
                                <div class="saas-attendance-rate">
                                    <span class="rate-value">{{ $isFiltered ? $rangeAttendanceRate : $attendanceRate }}%</span>
                                    <span class="rate-label">Attendance</span>
                                </div>
                            </div>

                            <div class="p-3 rounded-3" style="background:#f8fafc;">
                                <div class="saas-metric-row">
                                    <span class="d-flex align-items-center small fw-semibold"><span class="saas-metric-dot" style="background:#10b981"></span>Present</span>
                                    <span class="small fw-bold">{{ $isFiltered ? $rangePresent : $present }}/{{ $totalEmployees }}</span>
                                </div>
                                <div class="saas-metric-row">
                                    <span class="d-flex align-items-center small fw-semibold"><span class="saas-metric-dot" style="background:#3b82f6"></span>WFH</span>
                                    <span class="small fw-bold">{{ $isFiltered ? $rangeWFH : $wfh }}/{{ $totalEmployees }}</span>
                                </div>
                                <div class="saas-metric-row">
                                    <span class="d-flex align-items-center small fw-semibold"><span class="saas-metric-dot" style="background:#f59e0b"></span>Late</span>
                                    <span class="small fw-bold">{{ $isFiltered ? $rangeLate : $late }}/{{ $totalEmployees }}</span>
                                </div>
                                <div class="saas-metric-row">
                                    <span class="d-flex align-items-center small fw-semibold"><span class="saas-metric-dot" style="background:#8b5cf6"></span>Half Day</span>
                                    <span class="small fw-bold">{{ $isFiltered ? $rangeHalfday : $half_day }}/{{ $totalEmployees }}</span>
                                </div>
                                <div class="saas-metric-row">
                                    <span class="d-flex align-items-center small fw-semibold"><span class="saas-metric-dot" style="background:#06b6d4"></span>Leave</span>
                                    <span class="small fw-bold">{{ $isFiltered ? $rangeLeave : $leave }}/{{ $totalEmployees }}</span>
                                </div>
                                <div class="saas-metric-row">
                                    <span class="d-flex align-items-center small fw-semibold"><span class="saas-metric-dot" style="background:#64748b"></span>Early Out</span>
                                    <span class="small fw-bold">{{ $isFiltered ? $rangeEarly : $early }}/{{ $totalEmployees }}</span>
                                </div>
                                <div class="saas-metric-row">
                                    <span class="d-flex align-items-center small fw-semibold"><span class="saas-metric-dot" style="background:#ef4444"></span>Absent</span>
                                    <span class="small fw-bold">{{ $isFiltered ? $rangeAbsent : $absent }}/{{ $totalEmployees }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer border-top p-3 text-center">
                            <a href="{{ route('payroll.attendance.add') }}" class="fs-12 fw-bold text-primary text-uppercase">
                                <i class="feather-plus-circle me-1"></i> Add Daily Records
                            </a>
                        </div>
                    </div>
                </div>
            </div>

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

    <!-- Holiday Modal Deleted -->

    <!-- [ Main Content ] end -->
@endsection

@push('scripts')
    <script>
        @if($canViewPayrollAnalytics)
        let payrollChart = null;
        let summaryModalInstance = null;

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
                        document.querySelector('#summaryModal .modal-title').textContent = 'Financial Breakdown History';
                        summaryModalInstance.show();
                    }
                });
        }

        function showFullYearBreakdown(year) {
            fetch(`/dashboard/full-year?year=${year}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        data.breakdown.forEach(item => {
                            html += `
                                <tr>
                                    <td class="ps-4"><div class="fw-bold">${item.month}</div></td>
                                    <td>₹${Number(item.total_gross).toLocaleString()}</td>
                                    <td>${item.staff_count} staff</td>
                                    <td class="fw-bold text-primary">₹${Number(item.total_net).toLocaleString()}</td>
                                    <td class="pe-4 text-end"><span class="badge bg-soft-${item.status === 'Completed' ? 'success' : 'warning'} text-${item.status === 'Completed' ? 'success' : 'warning'}">${item.status}</span></td>
                                </tr>
                            `;
                        });
                        document.getElementById('modalHistoryTable').innerHTML = html;
                        document.querySelector('#summaryModal .modal-title').textContent = `Yearly Breakdown — ${year}`;
                        summaryModalInstance.show();
                    }
                });
        }
        @endif

        function animateCountUp(el) {
            const target = parseFloat(el.dataset.target || '0');
            if (el.dataset.skipAnimate === '1' || target <= 0) return;

            const prefix = el.dataset.prefix !== undefined ? el.dataset.prefix : '₹';
            const duration = 900;
            const start = performance.now();

            function frame(now) {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const value = Math.round(target * eased);
                el.textContent = prefix + value.toLocaleString('en-IN');
                if (progress < 1) requestAnimationFrame(frame);
            }
            el.textContent = prefix + '0';
            requestAnimationFrame(frame);
        }

        $(document).ready(function () {
            @if($canViewPayrollAnalytics)
            summaryModalInstance = new bootstrap.Modal(document.getElementById('summaryModal'));
            @endif

            document.querySelectorAll('.saas-count-up').forEach(animateCountUp);
            setTimeout(() => {
                document.querySelectorAll('.saas-progress-fill').forEach(el => el.classList.add('is-animated'));
            }, 300);

            @if($canViewPayrollAnalytics)
            const payrollOptions = {
                chart: {
                    height: 320,
                    type: 'area',
                    toolbar: { show: false },
                    fontFamily: 'Inter, sans-serif',
                    animations: { enabled: true, easing: 'easeinout', speed: 800 }
                },
                stroke: { curve: 'smooth', width: 2.5 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] }
                },
                colors: ['#1d4ed8', '#10b981', '#f59e0b'],
                series: [
                    { name: 'Total Payroll', data: {!! json_encode($chartTotal) !!} },
                    { name: 'Paid', data: {!! json_encode($chartPaid) !!} },
                    { name: 'Pending', data: {!! json_encode($chartPending) !!} }
                ],
                xaxis: {
                    categories: {!! json_encode($chartMonths) !!},
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: '#64748b', fontWeight: 600, fontSize: '11px' } }
                },
                yaxis: {
                    labels: {
                        formatter: val => '₹' + Number(val).toLocaleString('en-IN'),
                        style: { colors: '#64748b', fontWeight: 600 }
                    }
                },
                grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                dataLabels: { enabled: false },
                tooltip: {
                    theme: 'dark',
                    y: { formatter: val => '₹' + Number(val).toLocaleString('en-IN') }
                },
                legend: { position: 'top', horizontalAlign: 'right', fontWeight: 600, fontSize: '12px' }
            };

            const payrollContainer = document.querySelector('#saas-payroll-chart');
            if (payrollContainer) {
                payrollChart = new ApexCharts(payrollContainer, payrollOptions);
                payrollChart.render();
            }
            @endif

            const donutContainer = document.querySelector('#attendance-donut-chart');
            if (donutContainer) {
                const mixSeries = [{{ $present }}, {{ $wfh }}, {{ $late }}, {{ $half_day }}, {{ $leave }}, {{ $early }}, {{ $absent }}];
                const mixLabels = ['Present', 'WFH', 'Late', 'Half Day', 'Leave', 'Early', 'Absent'];
                const mixColors = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4', '#64748b', '#ef4444'];

                new ApexCharts(donutContainer, {
                    chart: { type: 'donut', height: 280, animations: { enabled: true, speed: 700 } },
                    labels: mixLabels,
                    series: mixSeries,
                    colors: mixColors,
                    legend: { show: true, position: 'bottom', fontSize: '11px', fontWeight: 500 },
                    dataLabels: { enabled: false },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '68%',
                                labels: {
                                    show: true,
                                    name: { show: false },
                                    value: { show: false },
                                    total: {
                                        show: true,
                                        label: 'Total Staff',
                                        formatter: () => '{{ $totalEmployees }}'
                                    }
                                }
                            }
                        }
                    },
                    stroke: { width: 2, colors: ['#fff'] }
                }).render();
            }

            const ringContainer = document.querySelector('#attendance-rate-ring');
            if (ringContainer) {
                const rateValue = Math.min(parseFloat('{{ $displayAttendanceRate }}') || 0, 100);
                new ApexCharts(ringContainer, {
                    chart: { type: 'radialBar', height: 140, sparkline: { enabled: true }, animations: { enabled: true, speed: 900 } },
                    series: [rateValue],
                    colors: [rateValue >= 75 ? '#10b981' : (rateValue >= 50 ? '#f59e0b' : '#1d4ed8')],
                    plotOptions: {
                        radialBar: {
                            hollow: { size: '72%' },
                            track: { background: '#e2e8f0' },
                            dataLabels: { show: false }
                        }
                    }
                }).render();
            }

            @if($canViewPayrollAnalytics)
            document.querySelectorAll('#payrollRangePills button').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('#payrollRangePills button').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const range = this.dataset.range;
                    fetch(`/dashboard/chart?range=${range}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && payrollChart) {
                                payrollChart.updateOptions({ xaxis: { categories: data.labels } });
                                payrollChart.updateSeries(data.series.map(s => ({ name: s.name, data: s.data })));
                            }
                        });
                });
            });
            @endif

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
                    item.classList.toggle('d-none', !(index >= start && index < end));
                });
                if (prevHolidayBtn) {
                    prevHolidayBtn.classList.toggle('disabled', currentHolidayPage === 0);
                    prevHolidayBtn.style.opacity = currentHolidayPage === 0 ? '0.5' : '1';
                }
                if (nextHolidayBtn) {
                    const atEnd = (currentHolidayPage + 1) * holidaysPerPage >= totalHolidays;
                    nextHolidayBtn.classList.toggle('disabled', atEnd);
                    nextHolidayBtn.style.opacity = atEnd ? '0.5' : '1';
                }
            }

            nextHolidayBtn?.addEventListener('click', () => {
                if ((currentHolidayPage + 1) * holidaysPerPage < totalHolidays) {
                    currentHolidayPage++;
                    updateHolidayView();
                }
            });
            prevHolidayBtn?.addEventListener('click', () => {
                if (currentHolidayPage > 0) {
                    currentHolidayPage--;
                    updateHolidayView();
                }
            });
        });

        function showCustomFilter() {
            document.getElementById('normalFilters').style.display = 'none';
            document.getElementById('customFilterBox').style.display = 'block';
        }

        function hideCustomFilter() {
            document.getElementById('normalFilters').style.display = 'block';
            document.getElementById('customFilterBox').style.display = 'none';
        }

        const leaveReportFilters = {
            employee_id: @json(request('employee_id', '')),
            leave_filter: @json(request('leave_filter', '')),
            leave_from: @json(request('leave_from', '')),
            leave_to: @json(request('leave_to', '')),
        };

        let leaveReportRequest = null;

        function closeLeaveDropdowns() {
            ['leaveEmployeeDropdown', 'leaveRangeDropdown'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                const toggle = el.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle) bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
            });
        }

        function syncLeaveReportUrl() {
            const url = new URL(window.location.href);
            ['employee_id', 'leave_filter', 'leave_from', 'leave_to'].forEach(key => url.searchParams.delete(key));

            if (leaveReportFilters.employee_id) {
                url.searchParams.set('employee_id', leaveReportFilters.employee_id);
            }
            if (leaveReportFilters.leave_filter) {
                url.searchParams.set('leave_filter', leaveReportFilters.leave_filter);
            }
            if (leaveReportFilters.leave_from && leaveReportFilters.leave_to) {
                url.searchParams.set('leave_from', leaveReportFilters.leave_from);
                url.searchParams.set('leave_to', leaveReportFilters.leave_to);
            }

            history.replaceState(null, '', url);
        }

        function loadLeaveReport() {
            const wrap = document.getElementById('leaveReportTableWrap');
            const tbody = document.getElementById('leaveReportTableBody');
            if (!wrap || !tbody) return;

            const params = new URLSearchParams();
            if (leaveReportFilters.employee_id) {
                params.set('employee_id', leaveReportFilters.employee_id);
            }
            if (leaveReportFilters.leave_filter) {
                params.set('leave_filter', leaveReportFilters.leave_filter);
            }
            if (leaveReportFilters.leave_from && leaveReportFilters.leave_to) {
                params.set('leave_from', leaveReportFilters.leave_from);
                params.set('leave_to', leaveReportFilters.leave_to);
            }

            if (leaveReportRequest) {
                leaveReportRequest.abort();
            }

            leaveReportRequest = new AbortController();
            wrap.classList.add('saas-ajax-loading');

            fetch(`{{ route('dashboard.leave-report') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: leaveReportRequest.signal,
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) return;

                    tbody.innerHTML = data.html;

                    const employeeLabel = document.getElementById('leaveEmployeeFilterLabel');
                    const rangeLabel = document.getElementById('leaveRangeFilterLabel');
                    if (employeeLabel) employeeLabel.textContent = data.employee_label;
                    if (rangeLabel) rangeLabel.textContent = data.range_label;

                    syncLeaveReportUrl();
                    closeLeaveDropdowns();
                    hideLeaveCustomFilter();
                })
                .catch(error => {
                    if (error.name !== 'AbortError') {
                        console.error('Leave report load failed:', error);
                    }
                })
                .finally(() => {
                    wrap.classList.remove('saas-ajax-loading');
                    leaveReportRequest = null;
                });
        }

        function applyLeaveEmployeeFilter(employeeId) {
            leaveReportFilters.employee_id = employeeId || '';

            document.querySelectorAll('#leaveEmployeeDropdown .wghrm-custom-dropdown-item').forEach(item => {
                item.classList.remove('active');
            });

            if (event?.target?.classList?.contains('wghrm-custom-dropdown-item')) {
                event.target.classList.add('active');
            }

            loadLeaveReport();
        }

        function applyLeaveRangeFilter(range) {
            leaveReportFilters.leave_filter = range;
            leaveReportFilters.leave_from = '';
            leaveReportFilters.leave_to = '';
            clearLeaveCustomDates();
            loadLeaveReport();
        }

        function applyLeaveCustomRangeFilter() {
            const from = document.getElementById('leave_from')?.value;
            const to = document.getElementById('leave_to')?.value;

            if (!from || !to) {
                alert('Please select both from and to dates.');
                return;
            }

            leaveReportFilters.leave_filter = '';
            leaveReportFilters.leave_from = from;
            leaveReportFilters.leave_to = to;
            loadLeaveReport();
        }

        function showLeaveCustomFilter() {
            const box = document.getElementById('leaveRangeDropdown')?.querySelector('.dropdown-menu');
            if (!box) return;
            box.querySelector('#normalFiltersLeave').style.display = 'none';
            box.querySelector('#customFilterBoxLeave').style.display = 'block';
        }

        function hideLeaveCustomFilter() {
            const box = document.getElementById('leaveRangeDropdown')?.querySelector('.dropdown-menu');
            if (!box) return;
            box.querySelector('#customFilterBoxLeave').style.display = 'none';
            box.querySelector('#normalFiltersLeave').style.display = 'block';
        }

        const lateArrivalsFilters = {
            late_employee: @json(request('late_employee', '')),
            late_range: @json(request('late_range', 'today')),
            late_custom_start: @json(request('late_custom_start', '')),
            late_custom_end: @json(request('late_custom_end', '')),
        };

        let lateArrivalsRequest = null;

        function closeLateDropdowns() {
            ['lateEmployeeDropdown', 'lateRangeDropdown'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                const toggle = el.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle) bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
            });
        }

        function syncLateArrivalsUrl() {
            const url = new URL(window.location.href);
            ['late_employee', 'late_range', 'late_custom_start', 'late_custom_end'].forEach(key => url.searchParams.delete(key));

            if (lateArrivalsFilters.late_employee) {
                url.searchParams.set('late_employee', lateArrivalsFilters.late_employee);
            }
            if (lateArrivalsFilters.late_range) {
                url.searchParams.set('late_range', lateArrivalsFilters.late_range);
            }
            if (lateArrivalsFilters.late_range === 'custom') {
                if (lateArrivalsFilters.late_custom_start) {
                    url.searchParams.set('late_custom_start', lateArrivalsFilters.late_custom_start);
                }
                if (lateArrivalsFilters.late_custom_end) {
                    url.searchParams.set('late_custom_end', lateArrivalsFilters.late_custom_end);
                }
            }

            history.replaceState(null, '', url);
        }

        function loadLateArrivals() {
            const wrap = document.getElementById('lateArrivalsListWrap');
            const listBody = document.getElementById('lateArrivalsListBody');
            if (!wrap || !listBody) return;

            const params = new URLSearchParams();
            if (lateArrivalsFilters.late_employee) {
                params.set('late_employee', lateArrivalsFilters.late_employee);
            }
            params.set('late_range', lateArrivalsFilters.late_range || 'today');
            if (lateArrivalsFilters.late_range === 'custom') {
                params.set('late_custom_start', lateArrivalsFilters.late_custom_start);
                params.set('late_custom_end', lateArrivalsFilters.late_custom_end);
            }

            if (lateArrivalsRequest) {
                lateArrivalsRequest.abort();
            }

            lateArrivalsRequest = new AbortController();
            wrap.classList.add('saas-ajax-loading');

            fetch(`{{ route('dashboard.late-arrivals') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: lateArrivalsRequest.signal,
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) return;

                    listBody.innerHTML = data.html;

                    const employeeLabel = document.getElementById('lateEmployeeFilterLabel');
                    const rangeLabel = document.getElementById('lateRangeFilterLabel');
                    const countBadge = document.getElementById('lateArrivalsCount');
                    if (employeeLabel) employeeLabel.textContent = data.employee_label;
                    if (rangeLabel) rangeLabel.textContent = data.range_label;
                    if (countBadge) countBadge.textContent = data.count;

                    syncLateArrivalsUrl();
                    closeLateDropdowns();
                    hideLateCustomFilter();
                })
                .catch(error => {
                    if (error.name !== 'AbortError') {
                        console.error('Late arrivals load failed:', error);
                    }
                })
                .finally(() => {
                    wrap.classList.remove('saas-ajax-loading');
                    lateArrivalsRequest = null;
                });
        }

        function applyLateEmployeeFilter(employeeId) {
            lateArrivalsFilters.late_employee = employeeId || '';

            document.querySelectorAll('#lateEmployeeDropdown .wghrm-custom-dropdown-item').forEach(item => {
                item.classList.remove('active');
            });

            if (event?.target?.classList?.contains('wghrm-custom-dropdown-item')) {
                event.target.classList.add('active');
            }

            loadLateArrivals();
        }

        function applyLateRangeFilter(range) {
            lateArrivalsFilters.late_range = range;
            lateArrivalsFilters.late_custom_start = '';
            lateArrivalsFilters.late_custom_end = '';
            document.getElementById('late_from').value = '';
            document.getElementById('late_to').value = '';
            loadLateArrivals();
        }

        function applyLateCustomRangeFilter() {
            const from = document.getElementById('late_from')?.value;
            const to = document.getElementById('late_to')?.value;

            if (!from || !to) {
                alert('Please select both from and to dates.');
                return;
            }

            lateArrivalsFilters.late_range = 'custom';
            lateArrivalsFilters.late_custom_start = from;
            lateArrivalsFilters.late_custom_end = to;
            loadLateArrivals();
        }

        function showLateCustomFilter() {
            const box = document.getElementById('lateRangeDropdown')?.querySelector('.dropdown-menu');
            if (!box) return;
            box.querySelector('#normalFiltersLate').style.display = 'none';
            box.querySelector('#customFilterBoxLate').style.display = 'block';
        }

        function hideLateCustomFilter() {
            const box = document.getElementById('lateRangeDropdown')?.querySelector('.dropdown-menu');
            if (!box) return;
            box.querySelector('#customFilterBoxLate').style.display = 'none';
            box.querySelector('#normalFiltersLate').style.display = 'block';
        }

        function wghrmFilterItems(input) {
            const filter = input.value.toLowerCase();
            input.closest('.wghrm-custom-dropdown-menu').querySelectorAll('.wghrm-custom-dropdown-item').forEach(item => {
                item.style.setProperty('display', item.textContent.toLowerCase().includes(filter) ? 'block' : 'none', 'important');
            });
        }

        function clearLeaveCustomDates() {
            const from = document.getElementById('leave_from');
            const to = document.getElementById('leave_to');
            if (from) from.value = '';
            if (to) to.value = '';
        }
    </script>
    @include('partials.urgent-announcements-scripts')
@endpush
