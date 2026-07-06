@extends('layouts.app')

@section('content')
@php
    $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
    $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
    $isTeamLeader = in_array($role, ['team_leader']);
    $employeeCount = $employees->count();
    $historyCount = $history->count();
    $totalAllottedDays = $history->sum('leave_count');
    $monthLabel = date('F Y', mktime(0, 0, 0, (int) $selectedMonth, 1));
    $totalUsedLeaves = collect($balances ?? [])->sum('total_taken');
    $totalAvailableBalance = collect($balances ?? [])->sum('balance');
    $totalUnpaidLeaves = collect($balances ?? [])->sum('unpaid_leave_days');
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/leave-allotment-management.css') }}?v={{ filemtime(public_path('assets/css/leave-allotment-management.css')) ?: time() }}">
@endpush

<div class="zoho-page-shell leave-allotment-page">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Leave Allotment',
        'viewLabel' => $monthLabel,
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
                <span><strong>{{ $employeeCount }}</strong> Employees</span>
            </span>
            <span class="la-stat-chip">
                <i class="feather-check-circle"></i>
                <span><strong>{{ $historyCount }}</strong> Allotted entries</span>
            </span>
            <span class="la-stat-chip">
                <i class="feather-layers"></i>
                <span><strong>{{ number_format($totalAllottedDays, 1) }}</strong> Total days</span>
            </span>
            <span class="la-stat-chip la-stat-chip--used">
                <i class="feather-minus-circle"></i>
                <span><strong>{{ number_format($totalUsedLeaves, 1) }}</strong> Used leaves</span>
            </span>
            <span class="la-stat-chip la-stat-chip--avail">
                <i class="feather-check-circle"></i>
                <span><strong>{{ number_format($totalAvailableBalance, 1) }}</strong> Available balance</span>
            </span>
            @if($totalUnpaidLeaves > 0)
                <span class="la-stat-chip la-stat-chip--warn">
                    <i class="feather-alert-circle"></i>
                    <span><strong>{{ number_format($totalUnpaidLeaves, 1) }}</strong> Salary deduction</span>
                </span>
            @endif
        </div>

        <div class="la-page-tabs" role="tablist" aria-label="Allotment views">
            <button type="button" class="la-page-tab is-active" data-la-tab="history" role="tab" aria-selected="true" aria-controls="laTabHistory">
                <i class="feather-clock"></i> Allotment History
            </button>
            <button type="button" class="la-page-tab" data-la-tab="balance" role="tab" aria-selected="false" aria-controls="laTabBalance">
                <i class="feather-pie-chart"></i> Leave Balance
            </button>
        </div>

        <div class="row g-3">
            {{-- Monthly allotment panel --}}
            <div class="col-lg-5">
                <div class="la-panel">
                    <div class="la-panel-head">
                        <div class="la-panel-head-main">
                            <span class="la-panel-icon"><i class="feather-plus-circle"></i></span>
                            <div>
                                <h3>Monthly Allotment</h3>
                                <p>Set leave count per employee for the selected month</p>
                            </div>
                        </div>
                        <div class="la-panel-toolbar">
                            <div class="la-month-field">
                                <label for="monthSelect">Allotment Month</label>
                                <select id="monthSelect" class="form-select la-month-field-select" onchange="updateView()">
                                    @foreach(range(1, 12) as $m)
                                        @php $val = sprintf('%02d', $m); @endphp
                                        <option value="{{ $val }}" {{ $selectedMonth == $val ? 'selected' : '' }}>
                                            {{ date('F Y', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="la-panel-body">
                        <div class="table-responsive la-desktop-table">
                            <table class="table zoho-data-table mb-0" id="employeeTable">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th class="text-center" style="width:120px;">Leave Count</th>
                                        @if($isAdmin)
                                            <th class="text-center" style="width:80px;">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employees as $emp)
                                        <tr>
                                            <td>
                                                <div class="la-emp-cell">
                                                    <span class="la-emp-avatar">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                                                    <span class="la-emp-name">{{ $emp->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($isAdmin)
                                                    <input type="number"
                                                           step="0.5"
                                                           min="0"
                                                           class="form-control la-allot-input allotment-input"
                                                           data-employee-id="{{ $emp->id }}"
                                                           value="{{ $allotments[$emp->id]->leave_count ?? 1.5 }}">
                                                @else
                                                    <span class="la-count-readonly">{{ $allotments[$emp->id]->leave_count ?? 1.5 }}</span>
                                                @endif
                                            </td>
                                            @if($isAdmin)
                                                <td class="text-center">
                                                    <button type="button"
                                                            class="zoho-icon-btn zoho-icon-btn--danger"
                                                            onclick="removeRow(this)"
                                                            title="Remove from list">
                                                        <i class="feather-minus"></i>
                                                    </button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="la-mobile-list">
                            @foreach($employees as $emp)
                                <div class="la-mobile-card">
                                    <div class="la-mobile-card-head">
                                        <div class="la-emp-cell">
                                            <span class="la-emp-avatar">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                                            <span class="la-emp-name">{{ $emp->name }}</span>
                                        </div>
                                        @if($isAdmin)
                                            <button type="button" class="zoho-icon-btn zoho-icon-btn--danger" onclick="removeRow(this)">
                                                <i class="feather-minus"></i>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="la-field-label mb-0">Leave Count</span>
                                        @if($isAdmin)
                                            <input type="number"
                                                   step="0.5"
                                                   min="0"
                                                   class="form-control la-allot-input allotment-input"
                                                   data-employee-id="{{ $emp->id }}"
                                                   value="{{ $allotments[$emp->id]->leave_count ?? 1.5 }}">
                                        @else
                                            <span class="la-count-readonly">{{ $allotments[$emp->id]->leave_count ?? 1.5 }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($isAdmin)
                        <div class="la-panel-foot">
                            <button type="button" class="zoho-btn-primary w-100" onclick="saveAllotments()">
                                <i class="feather-save"></i> Save Allotments
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- History & balance panel --}}
            <div class="col-lg-7">
                <div class="la-panel">
                    <div class="zoho-people-table-card border-0 shadow-none h-100" style="border-radius:0;">
                        {{-- Allotment History tab --}}
                        <div id="laTabHistory" class="la-tab-panel is-active" role="tabpanel">
                        <div class="zoho-people-table-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="la-panel-head-main px-0 py-0 border-0 bg-transparent">
                                <span class="la-panel-icon"><i class="feather-clock"></i></span>
                                <div>
                                    <h3 class="mb-0" style="font-size:14px;">Recent Allotment History</h3>
                                    <p class="mb-0">{{ $monthLabel }}</p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted small fw-semibold">Show</span>
                                    <select id="entriesPerPage" class="form-select la-entries-select" onchange="changeEntriesPerPage()" style="width:72px;">
                                        <option value="10" selected>10</option>
                                        <option value="15">15</option>
                                        <option value="20">20</option>
                                    </select>
                                </div>
                                <div class="zoho-people-table-search">
                                    <i class="feather-search"></i>
                                    <input type="text" id="historySearch" placeholder="Search history...">
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0 zoho-list-body la-panel-body--history">
                            <div class="table-responsive la-desktop-table">
                                <table class="table zoho-data-table mb-0" id="historyTable">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th class="text-center">Allotted</th>
                                            <th class="text-center">Month</th>
                                            <th class="text-end">Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyBody">
                                        @foreach($history as $h)
                                            <tr class="history-row">
                                                <td><span class="la-emp-name">{{ $h->employee->name }}</span></td>
                                                <td class="text-center">
                                                    <span class="la-badge la-badge--primary">{{ number_format($h->leave_count, 1) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="la-badge la-badge--month">{{ date('F', mktime(0, 0, 0, $h->month, 1)) }}</span>
                                                </td>
                                                <td class="text-end text-muted small">{{ $h->created_at->format('d M, Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="la-mobile-list" id="historyMobileBody">
                                @foreach($history as $h)
                                    <div class="la-mobile-card history-row">
                                        <div class="la-mobile-card-head">
                                            <span class="la-emp-name">{{ $h->employee->name }}</span>
                                            <span class="la-badge la-badge--primary">{{ number_format($h->leave_count, 1) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted">
                                            <span>{{ date('F', mktime(0, 0, 0, $h->month, 1)) }}</span>
                                            <span>{{ $h->created_at->format('d M, Y') }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($history->isEmpty())
                                <div class="la-empty-state">
                                    <i class="feather-inbox"></i>
                                    <p>No allotment history for this month.</p>
                                </div>
                            @endif
                        </div>

                        <div class="la-list-footer">
                            <span class="la-pagination-info" id="paginationInfo">Showing entries</span>
                            <nav>
                                <ul class="pagination pagination-sm mb-0 gap-1 la-pagination-controls" id="paginationControls"></ul>
                            </nav>
                        </div>
                        </div>

                        {{-- Leave Balance tab --}}
                        <div id="laTabBalance" class="la-tab-panel" role="tabpanel" hidden>
                            <div class="zoho-people-table-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="la-panel-head-main px-0 py-0 border-0 bg-transparent">
                                    <span class="la-panel-icon"><i class="feather-pie-chart"></i></span>
                                    <div>
                                        <h3 class="mb-0" style="font-size:14px;">Employee Leave Balance</h3>
                                        <p class="mb-0">{{ $monthLabel }} · Used leaves deducted from balance</p>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <a href="{{ route('leave.balance.export') }}" class="zoho-btn-outline btn-sm">
                                        <i class="feather-download"></i> Export
                                    </a>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted small fw-semibold">Show</span>
                                        <select id="balanceEntriesPerPage" class="form-select la-entries-select" style="width:72px;">
                                            <option value="10" selected>10</option>
                                            <option value="15">15</option>
                                            <option value="20">20</option>
                                        </select>
                                    </div>
                                    <div class="zoho-people-table-search">
                                        <i class="feather-search"></i>
                                        <input type="text" id="balanceSearch" placeholder="Search employee...">
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-0 zoho-list-body la-panel-body--history">
                                <div class="table-responsive la-desktop-table">
                                    <table class="table zoho-data-table mb-0" id="balanceTable">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th class="text-center">Opening</th>
                                                <th class="text-center">Used</th>
                                                <th class="text-center">Available</th>
                                                <th class="text-center">Salary Ded.</th>
                                            </tr>
                                        </thead>
                                        <tbody id="balanceTableBody">
                                            @forelse($balances as $b)
                                                <tr class="balance-row">
                                                    <td>
                                                        <div class="la-emp-cell">
                                                            <span class="la-emp-avatar">{{ strtoupper(substr($b->name, 0, 1)) }}</span>
                                                            <span class="la-emp-name">{{ $b->name }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="la-badge la-badge--primary">{{ number_format($b->total_allotted, 1) }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="la-badge la-badge--month">{{ number_format($b->total_taken, 1) }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="la-balance-pill la-balance-pill--ok">{{ number_format($b->balance, 1) }} Days</span>
                                                    </td>
                                                    <td class="text-center">
                                                        @if(($b->unpaid_leave_days ?? 0) > 0)
                                                            <span class="la-balance-pill la-balance-pill--low">{{ number_format($b->unpaid_leave_days, 1) }}</span>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-5 text-muted">No balance records found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="la-mobile-list" id="balanceMobileBody">
                                    @foreach($balances as $b)
                                        <div class="la-mobile-card balance-row">
                                            <div class="la-mobile-card-head">
                                                <span class="la-emp-name">{{ $b->name }}</span>
                                                <span class="la-balance-pill la-balance-pill--ok">{{ number_format($b->balance, 1) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between small text-muted">
                                                <span>Used: {{ number_format($b->total_taken, 1) }}</span>
                                                <span>Opening: {{ number_format($b->total_allotted, 1) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="la-list-footer">
                                <span class="la-pagination-info" id="balancePaginationInfo">Showing entries</span>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0 gap-1 la-pagination-controls" id="balancePaginationControls"></ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('leave.partials.allotment-scripts')
@endsection