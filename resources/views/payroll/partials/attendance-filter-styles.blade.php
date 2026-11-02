    <style>

        .wghrm-custom-select-btn {
            background-color: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            border-radius: 10px;
            color: #475569;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            height: 44px;
            font-size: 14px;
            text-align: left;
            transition: all 0.2s;
        }

        .wghrm-custom-select-btn:focus,
        .wghrm-custom-select-btn:active {
            border-color: #3858f9;
            box-shadow: 0 0 0 3px rgba(56, 88, 249, 0.1);
            outline: none;
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
            border-radius: 12px !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08) !important;
            padding: 8px !important;
            margin-top: 8px !important;
            z-index: 99 !important;
            background: #fff !important;
            border: 1px solid #e2e8f0 !important;
            width: 100%;
            min-width: 250px;
        }

        .wghrm-custom-search-box {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            padding: 4px;
            margin-bottom: 8px;
        }

        .wghrm-custom-search-input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            outline: none;
            background: #f8fafc;
            font-weight: 500;
        }

        .wghrm-custom-search-input:focus {
            border-color: #3858f9;
            background: #fff;
        }

        .wghrm-items-container {
            max-height: 250px;
            overflow-y: auto;
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

        .ref-badge {
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            letter-spacing: 0.5px;
            min-width: 90px;
            display: inline-block;
            text-align: center;
            text-transform: uppercase;
        }

        .clickable {
            cursor: pointer;
            transition: transform 0.1s;
        }

        .clickable:hover {
            transform: scale(1.05);
        }

        .badge-green {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .badge-blue {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
        }

        .badge-yellow {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fef3c7;
        }

        .badge-red {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .status-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .status-badge-success {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .status-badge-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .status-badge-warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fef3c7;
        }

        .status-badge-info {
            background: #f0f9ff;
            color: #0284c7;
            border: 1px solid #e0f2fe;
        }

        .btn-soft-primary {
            background-color: rgba(56, 88, 249, 0.1);
            color: #3858f9;
            border: none;
            transition: all 0.2s;
        }

        .btn-soft-primary:hover {
            background-color: #3858f9;
            color: #fff;
        }

        .btn-soft-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: none;
            transition: all 0.2s;
        }

        .btn-soft-danger:hover {
            background-color: #ef4444;
            color: #fff;
        }

        .mobile-attendance-card {
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9 !important;
        }

        .mobile-attendance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            border-color: #e2e8f0 !important;
        }

        @media (max-width: 768px) {
            .offcanvas {
                width: 100% !important;
            }

            .container-fluid {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }

            .card-header {
                padding: 15px !important;
            }
        }

        .action-btn-outline {
            background: transparent !important;
            border: 0 !important;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            color: #64748b;
            box-shadow: none !important;
        }

        .action-btn-outline:hover {
            background: #f1f5f9 !important;
            color: #3858f9;
            border: 0 !important;
        }

        .badge-purple {
            background: #f3e8ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
        }

        .attendance-pagination .pagination {
            margin-bottom: 0;
            justify-content: center;
            gap: 0.35rem;
        }

        .attendance-pagination .page-link {
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

        .attendance-pagination .page-item.active .page-link {
            background: #3858f9;
            border-color: #3858f9;
            color: #fff;
        }

        .attendance-pagination .page-item.disabled .page-link {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .attendance-pagination .page-link svg {
            width: 14px;
            height: 14px;
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
            cursor: pointer;
            opacity: 0.6;
            transition: all 0.2s;
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover,
        input[type="month"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        /* Custom Scrollbar (Slider) */
        .wghrm-items-container::-webkit-scrollbar,
        .wghrm-custom-dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .wghrm-items-container::-webkit-scrollbar-track,
        .wghrm-custom-dropdown-menu::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
        }

        .wghrm-items-container::-webkit-scrollbar-thumb,
        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .wghrm-items-container::-webkit-scrollbar-thumb:hover,
        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
