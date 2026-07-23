<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="base-url" content="{{ url('/') }}">
    <title>@yield('title', 'Dashboard')</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" />

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/daterangepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/zoho-portal.css') }}">

    @stack('styles')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --bs-body-font-family: 'Inter', sans-serif !important;
            --brand-nav: #1a2138;
            --brand-primary: #1070e0;
            --brand-primary-hover: #0d5fc0;
            --brand-primary-soft: #e6f1fc;
            --brand-primary-ghost: rgba(16, 112, 224, 0.08);
            --brand-primary-ring: rgba(16, 112, 224, 0.2);
            --brand-secondary: #5c6575;
            --brand-border: #e0e0e0;
            --brand-page-bg: #f4f7f9;
        }

        body,
        html,
        input,
        button,
        select,
        textarea {
            font-family: 'Inter', sans-serif !important;
        }

        .btn-soft-danger {
            background: rgba(239, 68, 68, 0.08) !important;
            color: #ef4444 !important;
            border: 1.5px solid rgba(239, 68, 68, 0.2) !important;
        }

        .btn-soft-danger:hover {
            background: #ef4444 !important;
            color: #fff !important;
            border-color: #ef4444 !important;
        }

        .btn-soft-primary {
            background: var(--brand-primary-ghost) !important;
            color: var(--brand-primary) !important;
            border: 1.5px solid var(--brand-primary-soft) !important;
        }

        .btn-soft-primary:hover {
            background: var(--brand-primary) !important;
            color: #fff !important;
            border-color: var(--brand-primary) !important;
        }

        .btn-soft-success {
            background: rgba(34, 197, 94, 0.08) !important;
            color: #22c55e !important;
            border: 1.5px solid rgba(34, 197, 94, 0.2) !important;
        }

        .btn-soft-success:hover {
            background: #22c55e !important;
            color: #fff !important;
            border-color: #22c55e !important;
        }

        .btn-soft-secondary {
            background: rgba(100, 116, 139, 0.08) !important;
            color: #64748b !important;
            border: 1.5px solid rgba(100, 116, 139, 0.2) !important;
        }

        .btn-soft-secondary:hover {
            background: #64748b !important;
            color: #fff !important;
            border-color: #64748b !important;
        }

        .dropdown-menu {
            z-index: 9999999 !important;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2) !important;
        }

        @media (min-width: 992px) {
            .table-responsive {
                overflow: visible !important;
            }
        }

        /* Global Premium Input Styling */
        .form-control, .form-select, .premium-input, .premium-select {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            height: 48px !important;
            padding: 10px 16px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            transition: all 0.2s ease !important;
            box-shadow: none !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: #fff !important;
            border-color: var(--brand-primary) !important;
            box-shadow: 0 0 0 4px var(--brand-primary-ring) !important;
        }
        textarea.form-control { height: auto !important; }

        /* Global Select2 Premium Overrides */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            min-height: 48px !important;
            padding: 6px 12px !important;
            display: flex !important;
            align-items: center !important;
            transition: all 0.2s ease !important;
        }
        .select2-container--default .select2-selection--multiple {
            padding: 4px 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-weight: 500 !important;
            padding-left: 4px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 12px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            background-color: #fff !important;
            border-color: var(--brand-primary) !important;
        }
        .select2-container .select2-selection--multiple .select2-selection__choice {
            background-color: var(--brand-primary) !important;
            border: none !important;
            color: #fff !important;
            border-radius: 6px !important;
            padding: 4px 10px !important;
            margin-top: 2px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
        }
        .select2-container .select2-selection--multiple .select2-selection__choice__remove {
            color: rgba(255, 255, 255, 0.8) !important;
            margin-right: 8px !important;
            border: none !important;
        }
        .select2-search__field::placeholder { color: #94a3b8 !important; }

        /* Premium Status & Priority UI */
        .premium-status-dropdown .btn-status,
        .priority-badge,
        .lead-select-btn {
            position: relative;
        }

        .premium-status-dropdown .btn-status.dropdown-toggle::after,
        .priority-badge.dropdown-toggle::after,
        .lead-select-btn.dropdown-toggle::after {
            display: none !important;
            /* Hide default Bootstrap caret */
        }

        .premium-status-dropdown .btn-status {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
            cursor: pointer;
            letter-spacing: 0.3px;
        }

        /* Status Colors */
        .status-pending {
            background: rgba(100, 116, 139, 0.1) !important;
            color: #64748b !important;
        }

        .status-in-process {
            background: var(--brand-primary-ghost) !important;
            color: var(--brand-primary) !important;
        }

        .status-completed {
            background: rgba(34, 197, 94, 0.1) !important;
            color: #22c55e !important;
        }

        .status-on-hold {
            background: rgba(245, 158, 11, 0.1) !important;
            color: #f59e0b !important;
        }

        .status-review {
            background: rgba(6, 182, 212, 0.1) !important;
            color: #06b6d4 !important;
        }

        .status-rework {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
        }

        /* Priority UI */
        .priority-badge {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.3px;
            cursor: pointer;
            border: none;
        }

        .priority-badge.dropdown-toggle::after {
            display: none;
            /* We will use custom arrow if needed or none as per image */
        }

        .priority-hard {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
        }

        .priority-medium {
            background: rgba(245, 158, 11, 0.1) !important;
            color: #f59e0b !important;
        }

        .priority-low {
            background: rgba(34, 197, 94, 0.1) !important;
            color: #22c55e !important;
        }

        .priority-normal {
            background: var(--brand-primary-ghost) !important;
            color: var(--brand-primary) !important;
        }

        /* Lead Select UI */
        .lead-select-btn {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            background: #fff;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #334155;
            cursor: pointer;
            width: 120px;
            /* Reduced fixed width */
            justify-content: space-between;
        }

        .lead-select-btn span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            max-width: 85px;
            /* Reduced max-width */
            text-align: left;
        }

        .lead-select-btn:hover {
            border-color: var(--brand-primary);
            background: var(--brand-primary-ghost);
        }

        .premium-attachment-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 12px;
            background: var(--brand-primary-ghost);
            color: var(--brand-primary);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 6px;
            transition: all 0.3s;
            text-decoration: none !important;
            border: 1px solid var(--brand-primary-soft);
        }

        .premium-attachment-link:hover {
            background: var(--brand-primary);
            color: #fff !important;
            box-shadow: 0 4px 12px var(--brand-primary-ring);
            transform: translateY(-1px);
        }

        /* Searchable Dropdown Styles */
        .wghrm-search-dropdown {
            position: relative;
            width: 100%;
        }

        .wghrm-dropdown-trigger {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-weight: 500;
            color: #1e293b;
            transition: all 0.2s;
        }

        .wghrm-dropdown-trigger:hover {
            border-color: var(--brand-primary) !important;
            background-color: #fff !important;
        }

        .wghrm-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 100%;
            min-width: 280px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.15);
            z-index: 1050;
            display: none;
            padding: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .wghrm-dropdown-menu.show {
            display: block;
            animation: wghrmSlideDown 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes wghrmSlideDown {
            from { opacity: 0; transform: translateY(-10px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .wghrm-search-container {
            margin-bottom: 12px;
            position: relative;
        }

        .wghrm-search-input {
            width: 100%;
            height: 44px;
            padding: 0 12px 0 40px;
            background: #f1f5f9;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: all 0.2s;
        }

        .wghrm-search-input:focus {
            border-color: var(--brand-primary);
            background: white;
            box-shadow: 0 0 0 4px var(--brand-primary-ring);
        }

        .wghrm-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            width: 16px;
            height: 16px;
        }

        .wghrm-items-list {
            max-height: 250px;
            overflow-y: auto !important;
            margin: 0 -4px;
            padding: 0 4px;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        /* Custom Scrollbar for Dropdown */
        .wghrm-items-list::-webkit-scrollbar {
            width: 6px;
            display: block !important;
        }
        .wghrm-items-list::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .wghrm-items-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
            border: 2px solid #f1f5f9;
        }
        .wghrm-items-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Mobile Card Styles */
        @media (max-width: 768px) {
            .wghrm-mobile-card {
                background: white;
                border-radius: 16px;
                padding: 16px;
                margin-bottom: 16px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }
            .wghrm-mobile-card-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 12px;
            }
            .wghrm-mobile-card-body {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            .wghrm-mobile-label {
                font-size: 11px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 2px;
            }
            .wghrm-mobile-value {
                font-size: 13px;
                font-weight: 600;
                color: #1e293b;
            }
            .wghrm-mobile-full-width {
                grid-column: span 2;
            }
            .desktop-only { display: none !important; }
        }
        @media (min-width: 769px) {
            .mobile-only { display: none !important; }
        }

        .wghrm-item {
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            color: #334155;
            transition: all 0.15s;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 500;
        }

        .wghrm-item:hover {
            background: var(--brand-primary-soft);
            color: var(--brand-primary);
        }

        .wghrm-item.selected {
            background: var(--brand-primary-soft);
            color: var(--brand-primary);
            font-weight: 700;
        }

        .wghrm-item-text {
            flex: 1;
        }

        .wghrm-item-check {
            color: var(--brand-primary);
            display: none;
        }

        .wghrm-item.selected .wghrm-item-check {
            display: block;
        }

        .wghrm-no-results {
            padding: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
        }
    </style>
</head>

<body class="zoho-portal @if(request()->routeIs('dashboard')) zoho-has-subheader @endif">

    {{-- Sidebar / Navigation --}}
    @include('layouts.nav')

    {{-- Header --}}
    @include('layouts.header')

    {{-- Main Content --}}
    <main class="nxl-container">
        <div class="nxl-content">
            @yield('content')
        </div>

        {{-- Footer --}}
        @include('layouts.footer')
    </main>

    <!-- JS -->
    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/circle-progress.min.js') }}"></script>

    <script src="{{ asset('assets/js/common-init.min.js') }}"></script>
    <script>
        $(function () {
            function keepZohoSidebarExpanded() {
                if (!$('body').hasClass('zoho-portal')) {
                    return;
                }
                $('html').removeClass('minimenu');
            }

            keepZohoSidebarExpanded();
            $(window).on('resize', keepZohoSidebarExpanded);
        });
    </script>
    @unless(request()->routeIs('dashboard'))
    <script src="{{ asset('assets/js/dashboard-init.min.js') }}"></script>
    @endunless
    <script src="{{ asset('assets/js/theme-customizer-init.min.js') }}"></script>

    <script>
        function toggleMenu(element) {
            const parent = element.parentElement;
            const submenu = parent.querySelector(".nxl-submenu");

            if (submenu) {
                submenu.style.display =
                    submenu.style.display === "block" ? "none" : "block";
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>

        // function apiUrl(path) {
        //     const base = document.querySelector('meta[name="base-url"]').content;
        //     return `${base}${path}`;
        // }

        const originalFetch = window.fetch;
        window.fetch = function (url, options) {
            const base = document.querySelector('meta[name="base-url"]').content;

            if (typeof url === "string" && url.startsWith("/")) {
                url = base + url;
            }

            return originalFetch(url, options);
        };


        // Toast UI configuration
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end', // Aap isko 'center', 'top', ya 'bottom-end' bhi kar sakte hain
            showConfirmButton: false,
            timer: 3000, // 3 seconds tak dikhega
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Success Alert
        @if(Session::has('success'))
            Toast.fire({
                icon: 'success',
                title: "{{ Session::get('success') }}"
            });
        @endif

        // Error Alert
        @if(Session::has('error'))
            Toast.fire({
                icon: 'error',
                title: "{{ Session::get('error') }}"
            });
        @endif

        // Warning Alert
        @if(Session::has('warning'))
            Toast.fire({
                icon: 'warning',
                title: "{{ Session::get('warning') }}"
            });
        @endif

        // Info Alert
        @if(Session::has('info'))
            Toast.fire({
                icon: 'info',
                title: "{{ Session::get('info') }}"
            });
        @endif

        // Global Delete Confirmation
        function deleteData(event) {
            event.preventDefault();
            const form = event.currentTarget;
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
                    form.submit();
                }
            });
            return false;
        }

        // Global Searchable Dropdown Initializer
        function initWghrmDropdown(containerId, options = {}) {
            const container = document.getElementById(containerId);
            if (!container || container.dataset.initialized) return;
            container.dataset.initialized = 'true';

            const trigger = container.querySelector('.wghrm-dropdown-trigger');
            const menu = container.querySelector('.wghrm-dropdown-menu');
            const searchInput = container.querySelector('.wghrm-search-input');
            const itemsList = container.querySelector('.wghrm-items-list');
            const hiddenInput = container.querySelector('input[type="hidden"]');
            const triggerText = trigger.querySelector('.wghrm-trigger-text');

            // Toggle Menu
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Close all other open dropdowns
                document.querySelectorAll('.wghrm-dropdown-menu.show').forEach(m => {
                    if (m !== menu) m.classList.remove('show');
                });

                const isOpen = menu.classList.contains('show');
                menu.classList.toggle('show');
                
                if (!isOpen) {
                    setTimeout(() => {
                        if (searchInput) {
                            searchInput.value = '';
                            searchInput.dispatchEvent(new Event('input'));
                            searchInput.focus();
                        }
                    }, 50);
                }
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!container.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });

            // Search Logic
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const term = e.target.value.toLowerCase();
                    let hasResults = false;
                    const items = itemsList.querySelectorAll('.wghrm-item');

                    items.forEach(item => {
                        const dataText = item.getAttribute('data-text') || item.textContent;
                        const text = dataText.toLowerCase();
                        if (text.includes(term)) {
                            item.style.setProperty('display', 'flex', 'important');
                            hasResults = true;
                        } else {
                            item.style.setProperty('display', 'none', 'important');
                        }
                    });

                    let noResults = itemsList.querySelector('.wghrm-no-results');
                    if (!hasResults) {
                        if (!noResults) {
                            noResults = document.createElement('div');
                            noResults.className = 'wghrm-no-results';
                            noResults.style.padding = '20px';
                            noResults.style.textAlign = 'center';
                            noResults.style.color = '#94a3b8';
                            noResults.style.fontSize = '13px';
                            noResults.textContent = 'No results found';
                            itemsList.appendChild(noResults);
                        }
                    } else if (noResults) {
                        noResults.remove();
                    }
                });
            }

            // Item Selection (Using event delegation for dynamic items)
            itemsList.addEventListener('click', (e) => {
                const item = e.target.closest('.wghrm-item');
                if (!item) return;

                const val = item.getAttribute('data-value');
                const text = item.getAttribute('data-text') || item.textContent;

                // Update UI
                itemsList.querySelectorAll('.wghrm-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
                triggerText.textContent = text;
                
                if (hiddenInput) {
                    hiddenInput.value = val;
                    hiddenInput.dispatchEvent(new Event('change'));
                }
                
                menu.classList.remove('show');

                // Trigger callback
                if (options.onSelect) {
                    options.onSelect(val, text);
                }
            });
        }

        // Auto-initialize all dropdowns on the page
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.wghrm-search-dropdown').forEach(dropdown => {
                if (dropdown.id) initWghrmDropdown(dropdown.id);
            });
        });

        // Re-run auto-init periodically for dynamic content (optional but helpful)
        const observer = new MutationObserver((mutations) => {
            document.querySelectorAll('.wghrm-search-dropdown').forEach(dropdown => {
                if (dropdown.id) initWghrmDropdown(dropdown.id);
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    </script>
    @auth
    <script>
        (function () {
            const heartbeatUrl = document.querySelector('meta[name="base-url"]').content + '/login-activity/heartbeat';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function sendHeartbeat(useBeacon) {
                if (useBeacon && navigator.sendBeacon) {
                    const data = new Blob([JSON.stringify({ _token: csrfToken })], { type: 'application/json' });
                    navigator.sendBeacon(heartbeatUrl, data);
                    return;
                }

                fetch(heartbeatUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    keepalive: true,
                }).catch(() => {});
            }

            setInterval(() => sendHeartbeat(false), 60000);

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    sendHeartbeat(true);
                }
            });

            window.addEventListener('beforeunload', () => sendHeartbeat(true));
        })();
    </script>
    @endauth

    @yield('modals')
    @stack('modals')
    @stack('scripts')
</body>

</html>
