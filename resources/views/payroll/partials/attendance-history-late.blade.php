{{-- One day's late-arrival cell; $late is a LateArrivalService::report() day row or null. --}}
@php
    $category = $late['category'] ?? null;
    $duration = \App\Services\LateArrivalService::formatMinutes((int) ($late['minutes'] ?? 0));
@endphp
@switch($category)
    @case(\App\Services\LateArrivalService::CATEGORY_VERY_LATE)
    @case(\App\Services\LateArrivalService::CATEGORY_LATE)
        <span class="badge px-3 py-2 rounded-pill fw-bold bg-soft-danger text-danger" style="font-size: 11px;">{{ $duration }}</span>
        <small class="d-block text-muted mt-1" style="font-size: 10px;">
            {{ $category === \App\Services\LateArrivalService::CATEGORY_VERY_LATE ? 'Very late' : 'Late' }} · shift {{ $late['shift_start'] }}
        </small>
        @break
    @case(\App\Services\LateArrivalService::CATEGORY_ALLOWANCE)
        <span class="badge px-3 py-2 rounded-pill fw-bold bg-soft-secondary text-secondary" style="font-size: 11px;">{{ $duration }}</span>
        <small class="d-block text-muted mt-1" style="font-size: 10px;">Within allowance</small>
        @break
    @case(\App\Services\LateArrivalService::CATEGORY_EXCUSED)
        <span class="badge px-3 py-2 rounded-pill fw-bold bg-soft-info text-info" style="font-size: 11px;">{{ $duration }}</span>
        <small class="d-block text-muted mt-1" style="font-size: 10px;">{{ $late['excuse'] ?? 'Excused' }}</small>
        @break
    @default
        @if(!empty($late['worked']) && !in_array($category, ['holiday', 'sunday'], true))
            <span class="text-success fw-semibold" style="font-size: 12px;">On time</span>
        @else
            <span class="text-muted">--</span>
        @endif
@endswitch
