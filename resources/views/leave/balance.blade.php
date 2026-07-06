@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/leave-allotment-management.css') }}?v={{ filemtime(public_path('assets/css/leave-allotment-management.css')) ?: time() }}">
@endpush

@section('content')
@php
    $totalEmployees = count($balances);
    $totalAllotted = collect($balances)->sum('total_allotted');
    $totalTaken = collect($balances)->sum('total_taken');
    $avgBalance = $totalEmployees > 0 ? collect($balances)->avg('balance') : 0;
    $headerActions = '<a href="' . route('leave.allotment') . '" class="zoho-btn-primary"><i class="feather-plus-circle"></i> Add Allotment</a>';
@endphp

<div class="zoho-page-shell leave-balance-page">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Leave Balance',
        'viewLabel' => 'Employee Leave Balances',
        'scopeLinks' => [
            ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Leave', 'url' => route('leave.history'), 'active' => false],
            ['label' => 'Balance', 'url' => route('leave.balance'), 'active' => true],
        ],
        'primaryAction' => $headerActions,
    ])

    <div class="main-content zoho-module-content">
        <div class="la-stat-row">
            <span class="la-stat-chip">
                <i class="feather-users"></i>
                <span><strong>{{ $totalEmployees }}</strong> Employees</span>
            </span>
            <span class="la-stat-chip">
                <i class="feather-layers"></i>
                <span><strong>{{ number_format($totalAllotted, 1) }}</strong> Total allotted</span>
            </span>
            <span class="la-stat-chip">
                <i class="feather-minus-circle"></i>
                <span><strong>{{ number_format($totalTaken, 1) }}</strong> Total used</span>
            </span>
            <span class="la-stat-chip la-stat-chip--month">
                <i class="feather-trending-up"></i>
                <span><strong>{{ number_format($avgBalance, 1) }}</strong> Avg. balance</span>
            </span>
        </div>

        <div class="zoho-people-table-card">
            <div class="zoho-people-table-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h3 class="mb-0" style="font-size:14px;font-weight:700;">Employee Leave Balances</h3>
                    <p class="mb-0 text-muted small">Summary of total leaves allotted and taken per employee</p>
                </div>
                <div class="zoho-people-table-search">
                    <i class="feather-search"></i>
                    <input type="text" id="balancePageSearch" placeholder="Search employee...">
                </div>
            </div>

            <div class="card-body p-0 zoho-list-body">
                <div class="table-responsive zoho-table-wrap">
                    <table class="table zoho-data-table mb-0" id="balanceTable">
                        <thead>
                            <tr>
                                <th style="width:70px;">Sr.No</th>
                                <th>Employee</th>
                                <th class="text-center">Leave Taken / Allotted</th>
                                <th class="text-center" style="width:180px;">Current Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($balances as $index => $b)
                                @php
                                    $percent = $b->total_allotted > 0 ? min(($b->total_taken / $b->total_allotted) * 100, 100) : 0;
                                @endphp
                                <tr class="balance-row">
                                    <td><span class="text-muted fw-semibold">{{ $index + 1 }}</span></td>
                                    <td>
                                        <div class="la-emp-cell">
                                            <span class="la-emp-avatar">{{ strtoupper(substr($b->name, 0, 1)) }}</span>
                                            <div>
                                                <span class="la-emp-name d-block">{{ $b->name }}</span>
                                                <span class="text-muted" style="font-size:11px;">#EC{{ str_pad($b->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="la-ratio">
                                            <span class="used">{{ number_format($b->total_taken, 1) }}</span>
                                            <span class="sep">/</span>
                                            <span class="total">{{ number_format($b->total_allotted, 1) }}</span>
                                        </div>
                                        <div class="la-usage-bar">
                                            <span class="{{ $percent > 80 ? 'is-high' : '' }}" style="width: {{ $percent }}%;"></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="la-balance-pill la-balance-pill--ok">
                                            {{ number_format($b->balance, 1) }} Days
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <div class="la-empty-state">
                                            <i class="feather-inbox"></i>
                                            <p>No leave balances found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('balancePageSearch')?.addEventListener('input', function () {
        const term = (this.value || '').trim().toLowerCase();
        document.querySelectorAll('#balanceTable tbody tr.balance-row').forEach(function (row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
</script>
@endsection
