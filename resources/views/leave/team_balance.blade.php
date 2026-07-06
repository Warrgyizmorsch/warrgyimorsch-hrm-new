@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/leave-allotment-management.css') }}?v={{ filemtime(public_path('assets/css/leave-allotment-management.css')) ?: time() }}">
@endpush

@section('content')
@php
    $teamCount = count($balances);
    $totalAllotted = collect($balances)->sum('total_allotted');
    $totalUsed = collect($balances)->sum('total_taken');
    $totalAvailable = collect($balances)->sum('balance');
    $monthLabel = date('F Y', mktime(0, 0, 0, (int) $selectedMonth, 1));
@endphp

<div class="zoho-page-shell leave-allotment-page">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Leave Allotment',
        'viewLabel' => 'Team Balance · ' . $monthLabel,
        'scopeLinks' => [
            ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Leave', 'url' => route('leave.history'), 'active' => false],
            ['label' => 'Allotment', 'url' => route('leave.allotment'), 'active' => true],
        ],
    ])

    <div class="main-content zoho-module-content">
        <div class="la-stat-row">
            <span class="la-stat-chip la-stat-chip--month">
                <i class="feather-calendar"></i>
                <span><strong>{{ $monthLabel }}</strong></span>
            </span>
            <span class="la-stat-chip">
                <i class="feather-users"></i>
                <span><strong>{{ $teamCount }}</strong> Team members</span>
            </span>
            <span class="la-stat-chip">
                <i class="feather-layers"></i>
                <span><strong>{{ number_format($totalAllotted, 1) }}</strong> Total allotted</span>
            </span>
            <span class="la-stat-chip">
                <i class="feather-check-circle"></i>
                <span><strong>{{ number_format($totalAvailable, 1) }}</strong> Available</span>
            </span>
        </div>

        <div class="zoho-people-table-card mb-3">
            <div class="zoho-people-table-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h3 class="mb-0" style="font-size:14px;font-weight:700;">Team Leave Balances</h3>
                    <p class="mb-0 text-muted small">Overview of allotted, used, and available leave for your team</p>
                </div>
                <div class="zoho-people-table-search">
                    <i class="feather-search"></i>
                    <input type="text" id="teamBalanceSearch" placeholder="Search team member...">
                </div>
            </div>
        </div>

        <div class="la-team-grid" id="teamBalanceGrid">
            @forelse($balances as $employee)
                <div class="la-team-card team-member-card" data-employee-name="{{ strtolower($employee->name) }}">
                    <div class="la-team-card-head">
                        <span class="la-emp-avatar">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
                        <div>
                            <h4>{{ $employee->name }}</h4>
                            <span>Team Member</span>
                        </div>
                    </div>
                    <div class="la-team-stats">
                        <div class="la-team-stat">
                            <small>Total</small>
                            <strong>{{ number_format($employee->total_allotted, 1) }}</strong>
                        </div>
                        <div class="la-team-stat la-team-stat--used">
                            <small>Used</small>
                            <strong>{{ number_format($employee->total_taken, 1) }}</strong>
                        </div>
                        <div class="la-team-stat la-team-stat--avail">
                            <small>Available</small>
                            <strong>{{ number_format($employee->balance, 1) }}</strong>
                        </div>
                    </div>
                </div>
            @empty
                <div class="la-empty-state w-100">
                    <i class="feather-inbox"></i>
                    <p>No team leave balances found.</p>
                </div>
            @endforelse

            <div class="la-empty-state w-100 d-none" id="teamBalanceEmptyState">
                <i class="feather-search"></i>
                <p>No matching team members found.</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('teamBalanceSearch')?.addEventListener('input', function () {
        const keyword = (this.value || '').trim().toLowerCase();
        const cards = document.querySelectorAll('.team-member-card');
        const emptyState = document.getElementById('teamBalanceEmptyState');
        let visibleCount = 0;

        cards.forEach(function (card) {
            const name = card.dataset.employeeName || '';
            const match = name.includes(keyword);
            card.classList.toggle('d-none', !match);
            if (match) visibleCount++;
        });

        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount !== 0);
        }
    });
</script>
@endsection
