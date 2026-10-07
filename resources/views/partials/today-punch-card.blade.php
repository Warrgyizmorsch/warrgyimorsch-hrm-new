{{-- The logged-in user's own check-in for today with late / on-time status (DashboardController::getMyTodayPunch). --}}
@if(!empty($myTodayPunch))
    @php
        $punchCategory = $myTodayPunch['category'];
        $punchIsLate = in_array($punchCategory, [\App\Services\LateArrivalService::CATEGORY_LATE, \App\Services\LateArrivalService::CATEGORY_VERY_LATE], true);
        [$punchTone, $punchText] = match (true) {
            !$myTodayPunch['check_in'] => ['secondary', 'Not punched in yet'],
            $punchIsLate => ['danger', ($punchCategory === \App\Services\LateArrivalService::CATEGORY_VERY_LATE ? 'Very late by ' : 'Late by ') . $myTodayPunch['duration']],
            // Anything not counted as late (within allowance, excused, off day) is on time.
            $punchCategory === \App\Services\LateArrivalService::CATEGORY_EXCUSED => ['success', 'On time · ' . $myTodayPunch['excuse']],
            default => ['success', 'On time'],
        };
    @endphp
    <div class="card today-punch-card today-punch-{{ $punchTone }} mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3 gap-md-4 py-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-text avatar-lg today-punch-icon">
                    <i class="feather-clock"></i>
                </div>
                <div>
                    <div class="fs-11 fw-semibold text-muted text-uppercase">Today's Check In</div>
                    <div class="fs-4 fw-bold text-dark">{{ $myTodayPunch['check_in'] ?? '--:--' }}</div>
                </div>
            </div>
            <div class="vr d-none d-md-block"></div>
            <div>
                <div class="fs-11 fw-semibold text-muted text-uppercase">Shift Start</div>
                <div class="fs-6 fw-bold text-dark">{{ $myTodayPunch['shift_start'] }}</div>
            </div>
            <div>
                <div class="fs-11 fw-semibold text-muted text-uppercase">Check Out</div>
                <div class="fs-6 fw-bold text-dark">{{ $myTodayPunch['check_out'] ?? '--:--' }}</div>
            </div>
            <div class="ms-md-auto">
                <span class="badge rounded-pill px-3 py-2 fw-bold today-punch-badge">{{ $punchText }}</span>
            </div>
        </div>
    </div>
    <style>
        .today-punch-card { border-left: 4px solid var(--tp-color) !important; }
        .today-punch-card .today-punch-icon,
        .today-punch-card .today-punch-badge { background: var(--tp-soft); color: var(--tp-color); }
        .today-punch-card .today-punch-badge { font-size: 12px; white-space: normal; text-align: left; }
        .today-punch-success { --tp-color: #22c55e; --tp-soft: rgba(34, 197, 94, 0.1); }
        .today-punch-danger { --tp-color: #ef4444; --tp-soft: rgba(239, 68, 68, 0.1); }
        .today-punch-info { --tp-color: #06b6d4; --tp-soft: rgba(6, 182, 212, 0.1); }
        .today-punch-secondary { --tp-color: #64748b; --tp-soft: rgba(100, 116, 139, 0.1); }
    </style>
@endif
