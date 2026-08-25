@push('modals')
@php
    $applyEmployeeName = $employees->first()->name ?? auth()->user()->name;
    $applyEmployeeInitial = strtoupper(substr($applyEmployeeName, 0, 1));
    $leaveCategories = [
        ['value' => 'Paid Leave', 'label' => 'Paid Leave', 'icon' => 'briefcase', 'tone' => 'paid'],
        ['value' => 'Sick Leave', 'label' => 'Sick Leave', 'icon' => 'activity', 'tone' => 'sick'],
        ['value' => 'Casual Leave', 'label' => 'Casual Leave', 'icon' => 'sun', 'tone' => 'casual'],
        ['value' => 'Gatepass Leave', 'label' => 'Early Leave', 'icon' => 'log-out', 'tone' => 'early'],
        ['value' => 'wfh', 'label' => 'WFH', 'icon' => 'home', 'tone' => 'wfh'],
    ];
@endphp

<x-ui.drawer
    id="applyLeaveModal"
    bodyClass="lv-apply-drawer-body"
    footerClass="lv-apply-drawer-foot"
    class="lv-apply-drawer"
    scroll="true"
>
    <x-slot:header>
        <div class="lv-apply-head">
            <div class="lv-apply-head__brand">
                <span class="lv-apply-head__icon"><i class="feather-calendar"></i></span>
                <div class="lv-apply-head__copy">
                    <h5 class="lv-apply-head__title" id="applyLeaveModalLabel">Apply For Leave</h5>
                    <p class="lv-apply-head__subtitle">Submit a new request for manager approval</p>
                </div>
            </div>
            <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="feather-x"></i>
            </button>
        </div>
    </x-slot:header>

    <form id="applyLeaveForm" class="lv-apply-form">
        @csrf

        <div class="lv-apply-applicant">
            @if($isAdmin || $isTeamLeader)
                <div class="lv-apply-field">
                    <label class="lv-apply-label">Employee <span class="text-danger">*</span></label>
                    <div class="wghrm-search-dropdown" id="applyEmployeeDropdown">
                        <div class="wghrm-dropdown-trigger lv-apply-input">
                            <span class="wghrm-trigger-text">Select employee</span>
                            <i data-feather="chevron-down"></i>
                        </div>
                        <div class="wghrm-dropdown-menu">
                            <div class="wghrm-search-container">
                                <i data-feather="search" class="wghrm-search-icon"></i>
                                <input type="text" class="wghrm-search-input" placeholder="Search employee...">
                            </div>
                            <div class="wghrm-items-list">
                                @foreach($employees as $emp)
                                    <div class="wghrm-item" data-value="{{ $emp->id }}" data-text="{{ $emp->name }}">
                                        <span class="wghrm-item-text">{{ $emp->name }}</span>
                                        <i data-feather="check" class="wghrm-item-check"></i>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="employee_id" id="applyEmployeeId" required>
                    </div>
                </div>
            @else
                <div class="lv-applicant-card">
                    <span class="lv-applicant-card__avatar">{{ $applyEmployeeInitial }}</span>
                    <div class="lv-applicant-card__info">
                        <strong>{{ $applyEmployeeName }}</strong>
                        <span>Applying as employee</span>
                    </div>
                    <input type="hidden" name="employee_id" id="applyEmployeeId" value="{{ auth()->user()->employee_id }}">
                </div>
            @endif
            <div class="lv-apply-date-badge">
                <span class="lv-apply-date-badge__label">Today</span>
                <strong>{{ date('d M Y') }}</strong>
            </div>
        </div>

        <div class="lv-apply-block">
            <div class="lv-apply-block__head">
                <span class="lv-apply-block__title">Leave Category</span>
                <span class="lv-apply-block__hint" id="applyBalanceHint">Select one</span>
            </div>
            <div class="lv-cat-picker" id="leaveCategoryPicker" role="group" aria-label="Leave category">
                @foreach($leaveCategories as $cat)
                    <button type="button"
                        class="lv-cat-chip lv-cat-chip--{{ $cat['tone'] }}"
                        data-value="{{ $cat['value'] }}"
                        data-label="{{ $cat['label'] }}">
                        <i class="feather-{{ $cat['icon'] }}"></i>
                        <span>{{ $cat['label'] }}</span>
                    </button>
                @endforeach
            </div>
            <input type="hidden" name="leave_category" id="leaveCategory" required>
        </div>

        <div class="lv-apply-block" id="leaveCategoryWrapper">
            <div class="lv-apply-block__head">
                <span class="lv-apply-block__title">Day Type</span>
            </div>
            <div class="lv-day-type-segment" role="group" aria-label="Leave day type">
                <input type="radio" name="leave_type" value="Full Day" id="catFull" checked onchange="toggleCategoryFields()">
                <label for="catFull" class="lv-day-type-option">
                    <i class="feather-sun"></i>
                    <span>Full Day</span>
                </label>
                <input type="radio" name="leave_type" value="Half Day" id="catHalf" onchange="toggleCategoryFields()">
                <label for="catHalf" class="lv-day-type-option">
                    <i class="feather-clock"></i>
                    <span>Half Day</span>
                </label>
            </div>
        </div>

        <div class="lv-apply-duration-panel">
            <div class="lv-apply-block__head">
                <span class="lv-apply-block__title">Duration</span>
            </div>

            <div class="lv-duration-grid">
                <div class="lv-apply-field">
                    <label class="lv-apply-label" for="startDate">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" id="startDate" class="form-control lv-apply-input" required onchange="calculateDays()">
                </div>
                <div class="lv-apply-field" id="endDateWrapper">
                    <label class="lv-apply-label" for="endDate">End Date</label>
                    <input type="date" name="end_date" id="endDate" class="form-control lv-apply-input" onchange="calculateDays()">
                </div>
                <div class="lv-apply-field" id="halfDayOptionWrapper" style="display:none;">
                    <label class="lv-apply-label">Which Half? <span class="text-danger">*</span></label>
                    <select name="half_day_type" class="form-select lv-apply-input">
                        <option value="First Half">First Half</option>
                        <option value="Second Half">Second Half</option>
                    </select>
                </div>
                <div class="lv-apply-field" id="startTimeWrapper" style="display:none;">
                    <label class="lv-apply-label" for="startTime">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" id="startTime" class="form-control lv-apply-input" onchange="calculateDays()">
                </div>
                <div id="endTimeWrapper" style="display:none;">
                    <input type="hidden" name="end_time" id="endTime">
                </div>
            </div>

            <div class="lv-duration-summary">
                <div class="lv-duration-summary__copy">
                    <span class="lv-duration-summary__label">Total duration</span>
                    <span class="lv-duration-summary__hint">Excludes Sundays & holidays</span>
                </div>
                <div class="lv-duration-summary__value" id="totalDaysDisplay">0 Days</div>
                <input type="hidden" name="total_days" id="totalDays" value="0">
            </div>
            <div id="applyOverflowWarning" style="display:none; margin-top: 10px; padding: 10px 14px; border-radius: 10px; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); color: #b45309; font-size: 13px; font-weight: 600;"></div>
        </div>

        <div class="lv-apply-block lv-apply-block--last">
            <div class="lv-apply-block__head">
                <span class="lv-apply-block__title">Reason & Message</span>
            </div>
            <div class="lv-apply-field">
                <label class="lv-apply-label" for="applyReason">Reason <span class="text-danger">*</span></label>
                <input type="text" name="reason" id="applyReason" class="form-control lv-apply-input" placeholder="Brief reason for leave" required>
            </div>
            <div class="lv-apply-field mb-0">
                <label class="lv-apply-label" for="applyMessage">Message <span class="lv-apply-optional">(optional)</span></label>
                <textarea name="message" id="applyMessage" class="form-control lv-apply-textarea" rows="3" placeholder="Add any additional details for your manager"></textarea>
            </div>
        </div>
    </form>

    <x-slot:footer>
        <div class="lv-apply-footer-actions">
            <button type="button" class="zoho-btn-outline lv-apply-cancel-btn" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" form="applyLeaveForm" class="zoho-btn-primary lv-apply-submit-btn">
                <i class="feather-send"></i> Apply Now
            </button>
        </div>
    </x-slot:footer>
</x-ui.drawer>
@endpush
