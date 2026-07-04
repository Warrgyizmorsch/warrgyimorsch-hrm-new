@forelse($todayLateEmployees as $lateEmp)
    <div class="saas-list-item">
        <div class="d-flex align-items-center gap-3">
            <div class="saas-avatar bg-soft-warning text-warning">
                {{ strtoupper(substr($lateEmp['employee']->name ?? 'N', 0, 1)) }}
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold fs-13">{{ $lateEmp['employee']->name ?? 'N/A' }}</div>
                <div class="fs-11 text-muted">Late by {{ $lateEmp['late_duration'] }}</div>
            </div>
            <span class="badge bg-soft-danger text-danger">{{ $lateEmp['late_days'] }}x</span>
        </div>
    </div>
@empty
    <div class="text-center py-4 text-muted">
        No late arrivals found.
    </div>
@endforelse
