{{-- Employee list: last increment + next due date (IncrementService). Click opens the increment modal. --}}
@php
    $lastInc = $emp->latestIncrement;
    $nextDue = $isInactive ? null : app(\App\Services\IncrementService::class)->nextDueDate($emp, $lastInc);
    $dueClass = '';
    $dueNote = '';
    if ($nextDue) {
        if ($nextDue->isPast()) {
            $dueClass = 'text-danger fw-semibold';
            $dueNote = ' (overdue)';
        } elseif ($nextDue->lte(today()->addDays((int) config('increment.due_soon_days', 30)))) {
            $dueClass = 'text-warning fw-semibold';
            $dueNote = ' (due soon)';
        }
    }
@endphp
<a href="javascript:void(0)" class="text-reset text-decoration-none" onclick="openIncrementModal({{ $emp->id }})" title="View / add increments">
    @if($lastInc)
        <div>
            <span class="fw-semibold text-success">+{{ rtrim(rtrim(number_format($lastInc->increment_percent, 2), '0'), '.') }}%</span>
            <span class="small text-muted">· {{ $lastInc->effective_date->format('d M Y') }}{{ $lastInc->isApplied() ? '' : ' (scheduled)' }}</span>
        </div>
    @else
        <div class="small text-muted">No increment yet</div>
    @endif
    @if($nextDue)
        <div class="small {{ $dueClass ?: 'text-muted' }}">Next: {{ $nextDue->format('d M Y') }}{{ $dueNote }}</div>
    @endif
</a>
