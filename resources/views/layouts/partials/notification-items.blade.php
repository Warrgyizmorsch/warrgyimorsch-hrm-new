{{-- Bell drawer items. Rendered with the page and re-rendered by NotificationController@feed. --}}
    @foreach($awaitingLeaves as $awaitingLeave)
        <a href="{{ route('leave.history', ['leave' => $awaitingLeave->id]) }}" data-leave-id="{{ $awaitingLeave->id }}" class="zoho-notify-item zoho-notify-item--leave">
            <span class="zoho-notify-item-icon"><i class="feather-check-square"></i></span>
            <span class="zoho-notify-item-body">
                <span class="zoho-notify-item-title">{{ $awaitingLeave->employee->name ?? 'An employee' }} — {{ \Illuminate\Support\Str::contains(strtolower((string) $awaitingLeave->leave_category), ['wfh', 'work from home']) ? 'WFH request' : 'leave' }} awaiting your approval</span>
                <span class="zoho-notify-item-desc">
                    {{ $awaitingLeave->leave_category }} · {{ $awaitingLeave->start_date?->format('d M') }}{{ $awaitingLeave->end_date && $awaitingLeave->end_date->gt($awaitingLeave->start_date) ? ' – ' . $awaitingLeave->end_date->format('d M') : '' }}
                    · {{ $awaitingLeave->approval_stage === 'tl' ? 'Level 1 (TL)' : 'Level 2 (Manager)' }}
                </span>
                <span class="zoho-notify-item-meta">{{ $awaitingLeave->created_at?->format('d M, h:i A') }}</span>
            </span>
            <span class="zoho-notify-item-avatar zoho-notify-item-avatar--initial">{{ strtoupper(substr($awaitingLeave->employee->name ?? '?', 0, 1)) }}</span>
        </a>
    @endforeach
    @foreach($pendingProfileApprovals as $pendingProfile)
        <a href="{{ route('profile-approvals.show', $pendingProfile) }}" class="zoho-notify-item zoho-notify-item--leave">
            <span class="zoho-notify-item-icon"><i class="feather-user-check"></i></span>
            <span class="zoho-notify-item-body">
                <span class="zoho-notify-item-title">{{ $pendingProfile->employee->name ?? 'An employee' }} submitted {{ $pendingProfile->type === 'onboarding' ? 'their profile' : 'a details change' }}</span>
                <span class="zoho-notify-item-desc">Waiting for your approval</span>
                <span class="zoho-notify-item-meta">{{ $pendingProfile->submitted_at?->format('d M, h:i A') }}</span>
            </span>
            <span class="zoho-notify-item-avatar zoho-notify-item-avatar--initial">{{ strtoupper(substr($pendingProfile->employee->name ?? '?', 0, 1)) }}</span>
        </a>
    @endforeach
    @forelse($notifications as $item)
        @php
            $isCelebration = isset($item->type) && $item->type == 'celebration';
            $isPayroll = !$isCelebration && isset($item->remarks);
            $emp = (in_array($roleUpper, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']) || $isCelebration) ? $item->employee : auth()->user()->employee;
            $photo = ($emp && $emp->photo) ? asset('storage/' . $emp->photo) : null;
            $notifyType = $isCelebration ? 'celebration' : ($isPayroll ? 'payroll' : 'leave');
            $notifyIcon = $isCelebration ? 'gift' : ($isPayroll ? 'message-circle' : 'calendar');
            $isAdminFeed = in_array($roleUpper, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']);
            $leaveLinkId = (!$isCelebration && !$isPayroll) ? $item->id : null;
            // Leave items open that exact leave: admins in Leave Applications, employees in My Leave History.
            $notifyUrl = $isCelebration ? route('employees.employeeDays')
                : ($isPayroll ? route('payroll.index')
                : ($isAdminFeed ? route('leave.history', ['leave' => $item->id]) : route('profile.leave-history', ['leave' => $item->id])));
        @endphp
        <a href="{{ $notifyUrl }}" @if($leaveLinkId) data-leave-id="{{ $leaveLinkId }}" data-leave-scope="{{ $isAdminFeed ? 'manage' : 'mine' }}" @endif class="zoho-notify-item zoho-notify-item--{{ $notifyType }}">
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
        @if($pendingProfileApprovals->isEmpty() && $awaitingLeaves->isEmpty())
            <div class="zoho-offcanvas-empty">
                <div class="zoho-offcanvas-empty-art">
                    <i class="feather-bell"></i>
                </div>
                <p class="zoho-offcanvas-empty-title">No Notification found</p>
                <p class="zoho-offcanvas-empty-desc">You're all caught up. New alerts will appear here.</p>
            </div>
        @endif
    @endforelse

