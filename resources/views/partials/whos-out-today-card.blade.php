{{--
    "Who's Out Today": everyone on leave / WFH / absent today, company-wide. Used on the admin dashboard
    and the employee dashboard. Needs $todayLeaveEmployees (DashboardController). Optional style hooks,
    because the two dashboards use different card styles:
    $cardClass, $itemClass, $avatarClass, $scrollClass, $colClass, $colStyle
--}}
@php
    $cardClass = $cardClass ?? 'card stretch stretch-full';
    $itemClass = $itemClass ?? 'p-3 border border-dashed rounded-3 mb-2';
    $avatarClass = $avatarClass ?? 'avatar-text avatar-md';
    $scrollClass = $scrollClass ?? '';
    $scrollStyle = $scrollClass ? '' : 'max-height: 320px; overflow-y: auto;';

    // Split "who's out" into Leave / WFH / Absent (WFH people are working, so not "away").
    $outKind = function ($row) {
        $t = strtolower((string) ($row->leave_type ?? ''));
        if (str_contains($t, 'home') || str_contains($t, 'wfh')) return 'wfh';
        if (str_contains($t, 'absent') || str_contains($t, 'left early')) return 'absent';
        return 'leave';
    };
    $outGroups = collect($todayLeaveEmployees)->unique(fn ($r) => $r->employee_id . '|' . $r->leave_type)->groupBy($outKind);
    $outTabs = [
        'leave' => ['label' => 'Leave', 'empty' => 'No one is on leave today.', 'avatar' => 'bg-soft-danger text-danger', 'active' => 'btn-danger'],
        'wfh' => ['label' => 'WFH', 'empty' => 'No one is working from home today.', 'avatar' => 'bg-soft-primary text-primary', 'active' => 'btn-primary'],
        'absent' => ['label' => 'Absent', 'empty' => 'No one is absent today.', 'avatar' => 'bg-soft-warning text-warning', 'active' => 'btn-warning'],
    ];
    $leaveCount = ($outGroups['leave'] ?? collect())->count();
    $wfhCount = ($outGroups['wfh'] ?? collect())->count();
    $absentCount = ($outGroups['absent'] ?? collect())->count();
@endphp
<div class="col-md-4 {{ $colClass ?? '' }}" style="{{ $colStyle ?? '' }}">
    <div class="{{ $cardClass }}">
        <div class="card-header hrm-resp-card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title">Who's Out Today</h5>
                <p class="text-muted fs-12 mb-0">{{ $leaveCount }} on leave · {{ $wfhCount }} WFH · {{ $absentCount }} absent</p>
            </div>
            <span class="badge bg-soft-danger text-danger" title="On leave or absent (WFH not included)">{{ $leaveCount + $absentCount }}</span>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2 mb-3" role="tablist" aria-label="Leave, WFH or absent">
                @foreach($outTabs as $key => $tab)
                    @php $n = ($outGroups[$key] ?? collect())->count(); @endphp
                    <button type="button" role="tab" data-allow-repeat
                        class="btn btn-sm flex-fill fw-semibold today-out-tab {{ $loop->first ? $tab['active'] : 'btn-light' }}"
                        data-target="todayOut-{{ $key }}" data-active-class="{{ $tab['active'] }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        onclick="todayOutSwitch(this)" style="border-radius: 8px;">
                        {{ $tab['label'] }} <span class="ms-1 opacity-75">{{ $n }}</span>
                    </button>
                @endforeach
            </div>
            @foreach($outTabs as $key => $tab)
                <div class="{{ $scrollClass }} today-out-panel {{ $loop->first ? '' : 'd-none' }}" id="todayOut-{{ $key }}" role="tabpanel" style="{{ $scrollStyle }}">
                    @forelse($outGroups[$key] ?? collect() as $todayLeave)
                        <div class="{{ $itemClass }}">
                            <div class="d-flex align-items-center gap-3">
                                <div class="{{ $avatarClass }} {{ $tab['avatar'] }}">
                                    {{ strtoupper(substr($todayLeave->employee_name ?? 'N', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold fs-13">{{ $todayLeave->employee_name ?? 'N/A' }}</div>
                                    <div class="fs-11 text-muted">{{ $todayLeave->leave_type }}</div>
                                    <div class="fs-11 {{ !empty($todayLeave->check_in) ? 'text-success' : 'text-muted' }}">
                                        @if(!empty($todayLeave->check_in))
                                            <i class="feather-log-in"></i> In {{ $todayLeave->check_in }}
                                            · Out {{ $todayLeave->check_out ?? '—' }}
                                        @else
                                            No punch today
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">{{ $tab['empty'] }}</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</div>
<script>
    function todayOutSwitch(btn) {
        document.querySelectorAll('.today-out-tab').forEach(b => {
            b.classList.remove(b.dataset.activeClass);
            b.classList.add('btn-light');
            b.setAttribute('aria-selected', 'false');
        });
        btn.classList.remove('btn-light');
        btn.classList.add(btn.dataset.activeClass);
        btn.setAttribute('aria-selected', 'true');
        document.querySelectorAll('.today-out-panel').forEach(p => p.classList.toggle('d-none', p.id !== btn.dataset.target));
    }
</script>
