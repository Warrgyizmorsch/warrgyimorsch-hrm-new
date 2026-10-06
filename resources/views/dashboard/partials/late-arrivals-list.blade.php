@forelse($todayLateEmployees as $lateEmp)
    <div class="saas-list-item">
        <div class="d-flex align-items-center gap-3">
            <div class="saas-avatar bg-soft-warning text-warning">
                {{ strtoupper(substr($lateEmp['employee']->name ?? 'N', 0, 1)) }}
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold fs-13">{{ $lateEmp['employee']->name ?? 'N/A' }}</div>
                <div class="fs-11 text-muted">
                    Late by {{ $lateEmp['late_duration'] }}
                    @if(($lateEmp['very_late_days'] ?? 0) > 0)
                        · <span class="text-danger">{{ $lateEmp['very_late_days'] }}x over {{ \App\Services\AttendanceStatusService::VERY_LATE_ARRIVAL_MINUTES }} min</span>
                    @endif
                </div>
            </div>
            <span class="badge bg-soft-danger text-danger" title="Late days">{{ $lateEmp['late_days'] }}x</span>
        </div>
    </div>
@empty
    <div class="text-center py-4 text-muted">
        No late arrivals found.
    </div>
@endforelse
