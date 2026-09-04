@extends('layouts.app')

@section('content')
    @php
        $totalCount = $tickets->count();
        $openCount = $tickets->where('status', 'Open')->count();
        $progressCount = $tickets->where('status', 'In Progress')->count();
        $resolvedCount = $tickets->whereIn('status', ['Resolved', 'Closed'])->count();

        $ticketRaisers = $tickets->pluck('user')->filter()->unique('id')->sortBy('name')->values();
    @endphp

    <div class="tickets-container">
        <!-- Hero Header -->
        <div class="tickets-hero">
            <div class="hero-content">
                <h1 class="hero-title">Support <span class="gradient-text">Tickets</span></h1>
                <p class="hero-subtitle">Manage, track, and resolve employee queries and system issues.</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="tickets-stats-grid">
            <div class="stat-card" onclick="filterByStatus('All')">
                <div class="stat-icon-wrapper all-theme">
                    <span class="stat-icon">🎟️</span>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ $totalCount }}</span>
                    <span class="stat-label">Total Tickets</span>
                </div>
            </div>

            <div class="stat-card" onclick="filterByStatus('Open')">
                <div class="stat-icon-wrapper open-theme">
                    <span class="stat-icon">🔓</span>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ $openCount }}</span>
                    <span class="stat-label">Open Tickets</span>
                </div>
            </div>

            <div class="stat-card" onclick="filterByStatus('In Progress')">
                <div class="stat-icon-wrapper progress-theme">
                    <span class="stat-icon">⚙️</span>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ $progressCount }}</span>
                    <span class="stat-label">In Progress</span>
                </div>
            </div>

            <div class="stat-card" onclick="filterByStatus('Resolved')">
                <div class="stat-icon-wrapper resolved-theme">
                    <span class="stat-icon">✅</span>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ $resolvedCount }}</span>
                    <span class="stat-label">Resolved / Closed</span>
                </div>
            </div>
        </div>

        <!-- Actions Panel (Switcher) -->
        <div class="tickets-header-actions">
            <div class="tickets-switcher">
                <button class="switcher-btn active" onclick="switchTicketTab('All', this)">
                    All
                </button>
                <button class="switcher-btn" onclick="switchTicketTab('Open', this)">
                    Open
                </button>
                <button class="switcher-btn" onclick="switchTicketTab('In Progress', this)">
                    In Progress
                </button>
                <button class="switcher-btn" onclick="switchTicketTab('Resolved', this)">
                    Resolved / Closed
                </button>
            </div>

            @if($ticketRaisers->count() > 1)
                <div class="tickets-employee-filter">
                    <select id="employeeFilterSelect" class="employee-filter-select" onchange="filterByEmployee(this.value)">
                        <option value="All">All Employees</option>
                        @foreach($ticketRaisers as $raiser)
                            <option value="{{ $raiser->id }}">{{ $raiser->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <!-- Tickets Grid Section -->
        <div class="tickets-section">
            <div class="premium-grid" id="tickets-grid">
                @forelse($tickets as $ticket)
                    @php
                        // Set status style wrapper class
                        $statusClass = 'status-open';
                        if ($ticket->status == 'In Progress') {
                            $statusClass = 'status-progress';
                        } elseif ($ticket->status == 'Resolved') {
                            $statusClass = 'status-resolved';
                        } elseif ($ticket->status == 'Closed') {
                            $statusClass = 'status-closed';
                        }

                        // Determine badge class
                        $badgeClass = 'bg-info-soft';
                        $lowerType = strtolower($ticket->type);
                        if (str_contains($lowerType, 'bug') || str_contains($lowerType, 'technical') || str_contains($lowerType, 'error')) {
                            $badgeClass = 'bg-danger-soft';
                        } elseif (str_contains($lowerType, 'hr') || str_contains($lowerType, 'leave') || str_contains($lowerType, 'salary')) {
                            $badgeClass = 'bg-success-soft';
                        }
                    @endphp

                    <div class="premium-card-wrapper ticket-item-card" data-status="{{ $ticket->status }}" data-user-id="{{ $ticket->user_id }}">
                        <div class="premium-card ticket-theme">
                            <div class="card-glow"></div>
                            <div class="premium-card-body">
                                
                                <!-- Card Header -->
                                <div class="ticket-card-header">
                                    <span class="ticket-type-badge {{ $badgeClass }}">
                                        {{ $ticket->type }}
                                    </span>
                                </div>

                                <!-- User Info Area -->
                                <div class="ticket-user-section">
                                    <div class="user-avatar-wrapper">
                                        @if($ticket->user && $ticket->user->photo)
                                            <img src="{{ asset('storage/' . $ticket->user->photo) }}" alt="{{ $ticket->user->name }}" class="user-avatar-img">
                                        @else
                                            <div class="user-avatar-initials">
                                                {{ substr($ticket->user->name ?? 'U', 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="user-info-text">
                                        <span class="user-label">Raised By</span>
                                        <h4 class="user-name">{{ $ticket->user->name ?? 'Unknown User' }}</h4>
                                        @if($ticket->user && ($ticket->user->employee->departmentRef->name ?? null))
                                            <span class="user-dept" style="font-size: 11px; color: #94a3b8; font-weight: 500;">{{ $ticket->user->employee->departmentRef->name }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="ticket-desc-section">
                                    <span class="desc-label">Description</span>
                                    <div class="description-box">
                                        {{ $ticket->description }}
                                    </div>
                                </div>

                                <!-- Actions Row (Status Change Trigger & Eye Button Side-by-Side) -->
                                <div class="ticket-status-action-section mb-3">
                                    <span class="status-label">Status</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn-status {{ $statusClass }}" onclick="openStatusModal({{ $ticket->id }}, '{{ $ticket->status }}')">
                                            <span>{{ $ticket->status }}</span>
                                            <i class="feather-chevron-down"></i>
                                        </button>
                                        <button class="history-btn" type="button" onclick="showHistory({{ $ticket->id }})" title="View tracking history">
                                            <i class="feather-eye"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <!-- Card Footer -->
                            <div class="ticket-card-footer">
                                <div class="date-info">
                                    <span class="calendar-icon">📅</span>
                                    <span class="date-text">{{ $ticket->created_at->format('d M, Y') }}</span>
                                </div>
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <div class="empty-state-premium py-4">
                            <div class="empty-icon">🎟️</div>
                            <h3>No Tickets Found</h3>
                            <p>There are no support tickets in this category right now.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Empty State Container -->
            <div class="empty-state-premium d-none" id="tickets-empty-state" style="margin-top: 20px;">
                <div class="empty-icon">🎟️</div>
                <h3>No Tickets Found</h3>
                <p>There are no support tickets in this category right now.</p>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Update Ticket Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="statusTicketId">
                    <div class="mb-3">
                        <label class="fw-semibold mb-2">Select Status</label>
                        <select class="form-select border" id="statusTicketStatus" style="height: 48px; border-radius: 10px; font-weight: 600;">
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="fw-semibold mb-2">Comment</label>
                        <textarea class="form-control border" id="statusComment" rows="3" placeholder="Enter status update comment..." style="border-radius: 10px;"></textarea>
                    </div>
                    <div class="text-center">
                        <button class="btn btn-primary px-5 py-2 fw-bold" onclick="submitStatus()" style="border-radius: 10px; background: #3858f9; border: none; font-size: 12px;">
                            Confirm Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History / Tracking Timeline Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="historyModalLabel">Status Tracking Timeline</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="historyBody" style="max-height: 400px; overflow-y: auto;">
                    <!-- Timelines populate here -->
                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

        .tickets-container {
            --primary: #3858f9;
            --secondary: #8b5cf6;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --card-bg: #ffffff;
            --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.04);
            --shadow-strong: 0 20px 40px -12px rgba(56, 88, 249, 0.08);

            padding: 30px;
            font-family: 'Outfit', sans-serif;
            box-sizing: border-box;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* Fix for squashed layout on mobile */
        @media (max-width: 1024px) {
            .nxl-container {
                margin-left: 0 !important;
                padding-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .nxl-content {
                padding: 0 !important;
                margin: 0 !important;
            }
        }

        .tickets-container * {
            box-sizing: border-box;
        }

        /* Hero Header */
        .tickets-hero {
            text-align: center;
            margin-bottom: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero-title {
            font-size: 42px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }

        .gradient-text {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        /* Stats Grid */
        .tickets-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 40px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-soft);
            border: 1px solid #f1f5f9;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(56, 88, 249, 0.06);
            border-color: rgba(56, 88, 249, 0.15);
        }

        .stat-icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-icon-wrapper.all-theme {
            background: rgba(56, 88, 249, 0.08);
            color: #3858f9;
        }

        .stat-icon-wrapper.open-theme {
            background: rgba(239, 68, 68, 0.08);
            color: #ef4444;
        }

        .stat-icon-wrapper.progress-theme {
            background: rgba(139, 92, 246, 0.08);
            color: #8b5cf6;
        }

        .stat-icon-wrapper.resolved-theme {
            background: rgba(34, 197, 94, 0.08);
            color: #22c55e;
        }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.1;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-light);
            margin-top: 4px;
        }

        /* Switcher Tabs */
        .tickets-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 30px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .tickets-employee-filter {
            display: flex;
            align-items: center;
        }

        .employee-filter-select {
            appearance: none;
            background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2364748b'/%3E%3C/svg%3E") no-repeat right 14px center;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 10px 34px 10px 18px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            min-width: 200px;
        }

        .employee-filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(56, 88, 249, 0.12);
        }

        .tickets-switcher {
            display: inline-flex;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 30px;
            gap: 4px;
            border: 1px solid #e2e8f0;
        }

        .switcher-btn {
            border: none;
            background: transparent;
            padding: 8px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .switcher-btn:hover {
            color: var(--text-dark);
        }

        .switcher-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.02);
        }

        /* Grid */
        .premium-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 32px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }

        /* Card */
        .premium-card-wrapper {
            perspective: 2000px;
            transition: all 0.3s ease;
            min-width: 0;
        }

        .premium-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: var(--shadow-soft);
            border: 1px solid #f1f5f9;
            min-height: 380px;
            min-width: 0;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .premium-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-strong);
            border-color: rgba(56, 88, 249, 0.15);
        }

        .card-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(56, 88, 249, 0.02) 0%, transparent 70%);
            pointer-events: none;
            transition: background 0.4s ease;
        }

        .premium-card:hover .card-glow {
            background: radial-gradient(circle, rgba(56, 88, 249, 0.05) 0%, transparent 70%);
        }

        .premium-card-body {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        /* Header */
        .ticket-card-header {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 20px;
        }

        .ticket-type-badge {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 12px;
            border-radius: 10px;
        }

        .bg-info-soft {
            background: rgba(6, 182, 212, 0.08);
            color: #06b6d4;
        }

        .bg-danger-soft {
            background: rgba(239, 68, 68, 0.08);
            color: #ef4444;
        }

        .bg-success-soft {
            background: rgba(34, 197, 94, 0.08);
            color: #22c55e;
        }

        /* User Info Section */
        .ticket-user-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .user-avatar-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            padding: 2px;
            flex-shrink: 0;
        }

        .user-avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-avatar-initials {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
        }

        .user-info-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .user-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Description Box */
        .ticket-desc-section {
            margin-bottom: 20px;
        }

        .desc-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 6px;
        }

        .description-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            font-size: 14px;
            color: #334155;
            line-height: 1.5;
            min-height: 90px;
            max-height: 130px;
            overflow-y: auto;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        /* Custom Scrollbar for Description Box */
        .description-box::-webkit-scrollbar {
            width: 4px;
        }
        .description-box::-webkit-scrollbar-track {
            background: transparent;
        }
        .description-box::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .description-box::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Actions Row Layout */
        .ticket-action-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            margin-top: 10px;
        }

        .ticket-status-section {
            flex-grow: 1;
            margin-bottom: 0 !important;
            display: flex;
            flex-direction: column;
        }

        .ticket-view-btn-container {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }

        .btn-action-eye {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f1f5f9;
            border: 1.5px solid #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 0;
        }

        .btn-action-eye:hover {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(56, 88, 249, 0.2);
        }

        .icon-eye {
            width: 18px;
            height: 18px;
        }

        .status-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 6px;
        }

        /* Styled button status trigger */
        .btn-status {
            min-width: 120px;
            height: 32px;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none !important;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            outline: none !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }

        .btn-status:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .status-open {
            background-color: rgba(56, 88, 249, 0.08) !important;
            color: #3858f9 !important;
            border: none !important;
        }
        .status-open:hover {
            background-color: rgba(56, 88, 249, 0.12) !important;
            box-shadow: 0 0 0 3px rgba(56, 88, 249, 0.15) !important;
        }

        .status-progress {
            background-color: rgba(139, 92, 246, 0.08) !important;
            color: #8b5cf6 !important;
            border: none !important;
        }
        .status-progress:hover {
            background-color: rgba(139, 92, 246, 0.12) !important;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15) !important;
        }

        .status-resolved {
            background-color: rgba(34, 197, 94, 0.08) !important;
            color: #22c55e !important;
            border: none !important;
        }
        .status-resolved:hover {
            background-color: rgba(34, 197, 94, 0.12) !important;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15) !important;
        }

        .status-closed {
            background-color: rgba(100, 116, 139, 0.08) !important;
            color: #64748b !important;
            border: none !important;
        }
        .status-closed:hover {
            background-color: rgba(100, 116, 139, 0.12) !important;
            box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.15) !important;
        }

        .history-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 10px;
            background: #f8fafc;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            cursor: pointer;
        }

        .history-btn:hover {
            background: #3858f9;
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Footer */
        .ticket-card-footer {
            border-top: 1px dashed #e2e8f0;
            padding-top: 16px;
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .date-info {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-light);
            font-weight: 500;
        }

        .calendar-icon {
            font-size: 14px;
        }

        .date-text {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        /* Empty State */
        .empty-state-premium {
            background: #ffffff;
            border-radius: 28px;
            padding: 60px 40px;
            text-align: center;
            border: 2px dashed #e2e8f0;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.01);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            animation: float-emoji 3s ease-in-out infinite;
        }

        @keyframes float-emoji {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-8px) scale(1.08); }
        }

        .empty-state-premium h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .empty-state-premium p {
            font-size: 15px;
            color: var(--text-light);
            max-width: 360px;
            margin: 0;
        }

        /* Premium Modal Styling */
        .premium-modal {
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        .premium-modal .modal-header {
            border-bottom: 1px solid #f1f5f9;
            padding: 20px 24px;
        }

        .premium-modal .modal-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 20px;
        }

        .premium-modal .modal-body {
            padding: 24px;
        }

        /* Timeline histories */
        .timeline-card {
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 16px;
            border-left: 4px solid #3858f9;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .timeline-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .timeline-card .badge {
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .premium-grid {
                max-width: 100%;
                gap: 24px;
            }
        }

        @media (max-width: 1200px) {
            .premium-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .tickets-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .premium-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .tickets-stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .tickets-container {
                padding: 20px 15px;
            }
            .hero-title {
                font-size: 32px;
            }
            .switcher-btn {
                padding: 8px 16px;
                font-size: 12px;
            }
        }
    </style>

    <script>
        let statusModal;
        let historyModal;

        document.addEventListener('DOMContentLoaded', function() {
            statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
        });

        let currentStatusFilter = 'All';
        let currentEmployeeFilter = 'All';

        function switchTicketTab(status, el) {
            document.querySelectorAll('.switcher-btn').forEach(btn => btn.classList.remove('active'));
            el.classList.add('active');

            filterByStatus(status);
        }

        function filterByStatus(status) {
            currentStatusFilter = status;

            // Sync switcher button highlight
            const switcherButtons = document.querySelectorAll('.switcher-btn');
            switcherButtons.forEach(btn => {
                const text = btn.textContent.trim().toLowerCase();
                const targetStatus = status.toLowerCase();
                if (text === targetStatus || (targetStatus === 'resolved' && text.includes('resolved'))) {
                    document.querySelectorAll('.switcher-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                }
            });

            applyTicketFilters();
        }

        function filterByEmployee(userId) {
            currentEmployeeFilter = userId;
            applyTicketFilters();
        }

        function applyTicketFilters() {
            const cards = document.querySelectorAll('.ticket-item-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                const cardUserId = card.getAttribute('data-user-id');

                let statusMatch = false;
                if (currentStatusFilter === 'All') {
                    statusMatch = true;
                } else if (currentStatusFilter === 'Open') {
                    statusMatch = (cardStatus === 'Open');
                } else if (currentStatusFilter === 'In Progress') {
                    statusMatch = (cardStatus === 'In Progress');
                } else if (currentStatusFilter === 'Resolved') {
                    statusMatch = (cardStatus === 'Resolved' || cardStatus === 'Closed');
                }

                const employeeMatch = (currentEmployeeFilter === 'All' || cardUserId === currentEmployeeFilter);
                const isVisible = statusMatch && employeeMatch;

                if (isVisible) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Toggle Empty State
            const emptyState = document.getElementById('tickets-empty-state');
            if (visibleCount === 0) {
                emptyState.classList.remove('d-none');
            } else {
                emptyState.classList.add('d-none');
            }
        }

        function openStatusModal(id, status) {
            $("#statusTicketId").val(id);
            $("#statusTicketStatus").val(status);
            $("#statusComment").val('');
            statusModal.show();
        }

        function submitStatus() {
            let id = $("#statusTicketId").val();
            let status = $("#statusTicketStatus").val();
            let comment = $("#statusComment").val();

            fetch("{{ url('tickets/update-status') }}/" + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status,
                    comment: comment
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    statusModal.hide();
                    Toast.fire({ icon: 'success', title: data.success })
                    .then(() => location.reload());
                }
            });
        }

        function showHistory(id) {
            fetch("{{ url('ticket-history') }}/" + id)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Failed to load history');
                }
                return res.json();
            })
            .then(data => {
                let html = '';
                if (data.length === 0) {
                    html = `<div class="text-center text-muted py-4">No Tracking Found</div>`;
                } else {
                    data.forEach(item => {
                        html += `
                            <div class="timeline-card mb-3 p-3 rounded-4 border bg-light" style="border-color: #e2e8f0 !important;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <b class="text-dark">${item.user?.name ?? 'Unknown'}</b> 
                                        <span class="text-muted">updated status</span>
                                    </div>
                                    <div class="small text-muted">${new Date(item.created_at).toLocaleString()}</div>
                                </div>
                                <div class="mb-2">
                                    <span class="badge bg-secondary text-uppercase">${item.old_status ?? 'N/A'}</span>
                                    <span class="mx-2 text-muted">→</span>
                                    <span class="badge bg-success text-uppercase">${item.new_status}</span>
                                </div>
                                ${item.comment ? `
                                    <div class="mt-2 pt-2 border-top" style="border-color: #e2e8f0 !important;">
                                        <div class="small fw-bold text-dark fs-6">Comment:</div>
                                        <div class="text-muted small fs-6">${item.comment}</div>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    });
                }
                $("#historyBody").html(html);
                historyModal.show();
            })
            .catch(err => {
                console.error("Tracking history error:", err);
                Toast.fire({ icon: 'error', title: 'Could not load tracking history' });
            });
        }
    </script>
@endsection