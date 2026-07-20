@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/leave-history-management.css') }}?v={{ filemtime(public_path('assets/css/leave-history-management.css')) ?: time() }}">
@endpush

@section('content')
    @php
        $selectedCategoryLabel = match (request('category')) {
            'Gatepass Leave' => 'Early Leave',
            'wfh' => 'WFH',
            default => request('category'),
        };
        $applyLeaveBtn = '<button type="button" class="zoho-btn-primary" data-bs-toggle="offcanvas" data-bs-target="#applyLeaveModal" aria-controls="applyLeaveModal"><i class="feather-plus"></i> Apply For Leave</button>';
        // Export stays admin-only — team leaders get view-only access to this page.
        $exportBtn = ($isAdmin ?? false)
            ? '<a href="' . route('leave.export', request()->all()) . '" class="zoho-btn-outline"><i class="feather-download"></i> Export</a>'
            : '';
    @endphp

    <div class="zoho-page-shell leave-history-page">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Leave Applications',
            'viewLabel' => 'Leave History',
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'Leave', 'url' => route('leave.history'), 'active' => true],
                ['label' => 'Applications', 'url' => route('leave.history'), 'active' => true],
            ],
            'primaryAction' => $applyLeaveBtn,
            'extraActions' => $exportBtn,
        ])

        <div class="main-content zoho-module-content">
            @include('leave.partials.history-main')
        </div>
    </div>

    @include('leave.partials.apply-leave-drawer')

    <div class="offcanvas offcanvas-end zoho-ui-drawer lh-side-drawer lh-action-drawer" tabindex="-1" id="leaveActionModal" aria-labelledby="leaveActionModalLabel">
        <div class="offcanvas-header zoho-offcanvas-head border-bottom">
            <h5 class="offcanvas-title zoho-offcanvas-title" id="leaveActionModalLabel">Update Application</h5>
            <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="feather-x"></i>
            </button>
        </div>
        <div class="offcanvas-body lh-action-drawer-body">
            <div id="actionModalContent">
                <div class="lh-action-meta">
                    <div class="lh-action-meta-item">
                        <span class="lh-action-meta-label">Application ID</span>
                        <span id="displayAppCode" class="lh-action-meta-value">—</span>
                    </div>
                    <div class="lh-action-meta-item text-end">
                        <span class="lh-action-meta-label">Leave Balance</span>
                        <span id="displayBalanceBadge" class="lh-balance-badge lh-balance-badge--ok">—</span>
                    </div>
                </div>

                <form id="actionForm">
                    @csrf
                    <input type="hidden" name="leave_id" id="actionLeaveId">
                    <div class="lh-form-field">
                        <label>Set Status</label>
                        <select name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="on_hold">On Hold</option>
                            <option value="rejected">Rejected</option>
                            <option value="unpaid">Unpaid Leave</option>
                            <option value="unauthorised">Unauthorised</option>
                        </select>
                    </div>

                    <button type="submit" class="zoho-btn-primary w-100 mt-3">
                        <i class="feather-check"></i> Update Application
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end zoho-ui-drawer lh-side-drawer lh-view-drawer" tabindex="-1" id="viewLeaveModal" aria-labelledby="viewLeaveModalLabel">
        <div class="offcanvas-header zoho-offcanvas-head border-bottom lh-view-drawer-head">
            <div class="d-flex align-items-center w-100 gap-3">
                <div class="lh-view-avatar">
                    <span id="viewAvatarLetter">E</span>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="offcanvas-title zoho-offcanvas-title mb-0 text-truncate" id="viewLeaveModalLabel">
                        <span id="viewEmployeeName">Employee Name</span>
                    </h5>
                    <span class="lh-view-subtitle">Leave application details</span>
                </div>
                <button type="button" class="zoho-offcanvas-close flex-shrink-0" data-bs-dismiss="offcanvas" aria-label="Close">
                    <i class="feather-x"></i>
                </button>
            </div>
        </div>
        <div class="offcanvas-body p-0 lh-view-drawer-body">
            <ul class="nav lh-view-tabs" id="viewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab"
                        data-bs-target="#detailsContent" type="button" role="tab">Current Application</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab"
                        data-bs-target="#historyContent" type="button" role="tab">Full History</button>
                </li>
            </ul>

            <div class="tab-content" id="viewTabsContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active p-4" id="detailsContent" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="lh-detail-card">
                                <label>Leave Info</label>
                                <div class="lh-detail-value" id="viewLeaveType">—</div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span id="viewCategoryBadge" class="lh-cat lh-cat--default">—</span>
                                    <span id="viewStatusBadge" class="lh-status lh-status--pending">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="lh-detail-card">
                                <label>Duration & Days</label>
                                <div id="viewTotalDays" class="lh-detail-highlight">—</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="lh-detail-card">
                                <label>Timeframe</label>
                                <div class="lh-timeframe-grid">
                                    <div>
                                        <div class="lh-timeframe-label">From</div>
                                        <div id="viewStartDateText" class="lh-detail-value">—</div>
                                        <div id="viewStartTimeText" class="lh-timeframe-sub">—</div>
                                    </div>
                                    <div class="lh-timeframe-arrow"><i class="feather-arrow-right"></i></div>
                                    <div>
                                        <div class="lh-timeframe-label">To / End</div>
                                        <div id="viewEndDateText" class="lh-detail-value">—</div>
                                        <div id="viewEndTimeText" class="lh-timeframe-sub">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="lh-detail-card">
                                <label>Reason</label>
                                <div id="viewReason" class="lh-detail-value">—</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="lh-detail-card lh-detail-card--message">
                                <label>Employee Message</label>
                                <div id="viewMessage" class="lh-detail-message">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade p-4" id="historyContent" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">Leave History</h6>
                        <span id="historyCountBadge" class="lh-history-count">0 Total</span>
                    </div>
                    <div class="lh-history-table-wrap">
                        <div class="table-responsive">
                            <table class="table zoho-data-table mb-0 lh-history-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Details</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end">Days</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToast" class="custom-toast lh-custom-toast">
        <span id="toastIcon" class="toast-icon"></span>
        <span id="toastMessage"></span>
    </div>

    @include('leave.partials.history-scripts')
@endsection
