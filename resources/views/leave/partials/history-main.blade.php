        <div class="lh-stat-row m-2">
            <div class="lh-stat-chip">
                <span class="lh-stat-chip__icon"><i class="feather-layers"></i></span>
                <span class="lh-stat-chip__content">
                    <strong class="lh-stat-chip__value">{{ number_format($leaveStats['total'] ?? $leaves->total()) }}</strong>
                    <span class="lh-stat-chip__label">Total applications</span>
                </span>
            </div>
            <div class="lh-stat-chip lh-stat-chip--pending">
                <span class="lh-stat-chip__icon"><i class="feather-clock"></i></span>
                <span class="lh-stat-chip__content">
                    <strong class="lh-stat-chip__value">{{ number_format($leaveStats['pending'] ?? 0) }}</strong>
                    <span class="lh-stat-chip__label">Pending</span>
                </span>
            </div>
            <div class="lh-stat-chip lh-stat-chip--approved">
                <span class="lh-stat-chip__icon"><i class="feather-check-circle"></i></span>
                <span class="lh-stat-chip__content">
                    <strong class="lh-stat-chip__value">{{ number_format($leaveStats['approved'] ?? 0) }}</strong>
                    <span class="lh-stat-chip__label">Approved</span>
                </span>
            </div>
            <div class="lh-stat-chip lh-stat-chip--rejected">
                <span class="lh-stat-chip__icon"><i class="feather-x-circle"></i></span>
                <span class="lh-stat-chip__content">
                    <strong class="lh-stat-chip__value">{{ number_format($leaveStats['rejected'] ?? 0) }}</strong>
                    <span class="lh-stat-chip__label">Rejected</span>
                </span>
            </div>
        </div>

        <div class="lh-filter-card">
            <form action="{{ route('leave.history') }}" method="GET" class="lh-filter-grid">
                <div class="lh-filter-field">
                    <label>Search Employee</label>
                    <div class="wghrm-search-dropdown" id="filterEmployeeDropdown">
                        <div class="wghrm-dropdown-trigger">
                            <span class="wghrm-trigger-text">{{ request('search') ?: 'All Employees' }}</span>
                            <i data-feather="chevron-down"></i>
                        </div>
                        <div class="wghrm-dropdown-menu">
                            <div class="wghrm-search-container">
                                <i data-feather="search" class="wghrm-search-icon"></i>
                                <input type="text" class="wghrm-search-input" placeholder="Type name...">
                            </div>
                            <div class="wghrm-items-list">
                                <div class="wghrm-item {{ !request('search') ? 'selected' : '' }}" data-value="" data-text="All Employees">
                                    <span class="wghrm-item-text">All Employees</span>
                                    <i data-feather="check" class="wghrm-item-check"></i>
                                </div>
                                @foreach($employees as $emp)
                                    <div class="wghrm-item {{ request('search') == $emp->name ? 'selected' : '' }}" data-value="{{ $emp->name }}" data-text="{{ $emp->name }}">
                                        <span class="wghrm-item-text">{{ $emp->name }}</span>
                                        <i data-feather="check" class="wghrm-item-check"></i>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="lh-filter-field">
                    <label>Category</label>
                    <div class="wghrm-search-dropdown" id="filterCategoryDropdown">
                        <div class="wghrm-dropdown-trigger">
                            <span class="wghrm-trigger-text">{{ $selectedCategoryLabel ?: 'All Categories' }}</span>
                            <i data-feather="chevron-down"></i>
                        </div>
                        <div class="wghrm-dropdown-menu">
                            <div class="wghrm-items-list">
                                <div class="wghrm-item {{ !request('category') ? 'selected' : '' }}" data-value="" data-text="All Categories">
                                    <span class="wghrm-item-text">All Categories</span>
                                    <i data-feather="check" class="wghrm-item-check"></i>
                                </div>
                                @foreach([
                                    'Paid Leave' => 'Paid Leave',
                                    'Sick Leave' => 'Sick Leave',
                                    'Casual Leave' => 'Casual Leave',
                                    'Gatepass Leave' => 'Early Leave',
                                    'wfh' => 'WFH',
                                ] as $catValue => $catLabel)
                                    <div class="wghrm-item {{ request('category') == $catValue ? 'selected' : '' }}" data-value="{{ $catValue }}" data-text="{{ $catLabel }}">
                                        <span class="wghrm-item-text">{{ $catLabel }}</span>
                                        <i data-feather="check" class="wghrm-item-check"></i>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    </div>
                </div>
                <div class="lh-filter-field">
                    <label>Status</label>
                    <div class="wghrm-search-dropdown" id="filterStatusDropdown">
                        <div class="wghrm-dropdown-trigger">
                            <span class="wghrm-trigger-text text-capitalize">{{ request('status') ? str_replace('_', ' ', request('status')) : 'All Status' }}</span>
                            <i data-feather="chevron-down"></i>
                        </div>
                        <div class="wghrm-dropdown-menu">
                            <div class="wghrm-items-list">
                                <div class="wghrm-item {{ !request('status') ? 'selected' : '' }}" data-value="" data-text="All Status">
                                    <span class="wghrm-item-text">All Status</span>
                                    <i data-feather="check" class="wghrm-item-check"></i>
                                </div>
                                @foreach(['pending', 'approved', 'on_hold', 'rejected', 'unauthorised'] as $stat)
                                    <div class="wghrm-item {{ request('status') == $stat ? 'selected' : '' }}" data-value="{{ $stat }}" data-text="{{ ucfirst(str_replace('_', ' ', $stat)) }}">
                                        <span class="wghrm-item-text text-capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                        <i data-feather="check" class="wghrm-item-check"></i>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    </div>
                </div>
                <div class="lh-filter-field">
                    <label>From</label>
                    <input type="date" name="from_date" class="form-control lh-filter-input" value="{{ request('from_date') }}">
                </div>
                <div class="lh-filter-field">
                    <label>Upto</label>
                    <input type="date" name="to_date" class="form-control lh-filter-input" value="{{ request('to_date') }}">
                </div>
                <div class="lh-filter-field lh-filter-actions">
                    <button type="submit" class="zoho-btn-primary">Search</button>
                    <a href="{{ route('leave.history') }}" class="zoho-btn-outline text-center">Reset</a>
                </div>
            </form>
        </div>

        <div class="zoho-people-table-card lh-table-card">
            <div class="zoho-people-table-toolbar lh-table-toolbar d-flex flex-wrap align-items-center justify-content-between">
                <div class="lh-table-toolbar-left d-flex align-items-center">
                    <span class="lh-table-toolbar-label">Show</span>
                    <select class="form-select form-select-sm lh-per-page-select" onchange="window.location.href=this.value">
                        @foreach([20, 50, 100] as $size)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" {{ ($perPage ?? 20) == $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                    <span class="lh-table-toolbar-label">entries</span>
                </div>
                <div class="zoho-people-table-search lh-table-search">
                    <i class="feather-search"></i>
                    <input type="text" id="leaveHistoryQuickSearch" placeholder="Quick filter in list...">
                </div>
            </div>

            <div class="card-body p-0 zoho-list-body">
                <div class="table-responsive zoho-table-wrap">
                    <table class="table zoho-data-table mb-0" id="leaveHistoryTable">
                        <thead>
                            <tr>
                                <th class="lh-col-sr">Sr.No</th>
                                <th class="lh-col-employee">Employee</th>
                                <th class="lh-col-center">Status</th>
                                <th class="lh-col-type">Leave Type</th>
                                <th class="lh-col-center">Category</th>
                                <th class="lh-col-duration">Duration</th>
                                <th class="lh-col-center">Days</th>
                                <th class="lh-col-action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaves as $key => $leave)
                                @php
                                    $catRaw = strtolower(trim($leave->leave_category ?? ''));
                                    if (str_contains($catRaw, 'gatepass')) {
                                        $catDisplay = 'Early Leave';
                                        $catClass = 'lh-cat--early';
                                    } elseif (str_contains($catRaw, 'wfh')) {
                                        $catDisplay = 'WFH';
                                        $catClass = 'lh-cat--wfh';
                                    } elseif (str_contains($catRaw, 'sick')) {
                                        $catDisplay = 'Sick Leave';
                                        $catClass = 'lh-cat--sick';
                                    } elseif (str_contains($catRaw, 'casual')) {
                                        $catDisplay = 'Casual Leave';
                                        $catClass = 'lh-cat--casual';
                                    } elseif (str_contains($catRaw, 'paid')) {
                                        $catDisplay = 'Paid Leave';
                                        $catClass = 'lh-cat--paid';
                                    } else {
                                        $catDisplay = $leave->leave_category ?: '—';
                                        $catClass = 'lh-cat--default';
                                    }

                                    $totalDays = (float) ($leave->total_days ?? 0);
                                    $statusKey = str_replace(' ', '_', strtolower($leave->status));
                                    $leaveTypeDisplay = $leave->leave_type == 'Gatepass Leave' ? 'Early Leave' : $leave->leave_type;
                                @endphp
                                <tr class="leave-history-row">
                                    <td class="lh-col-sr text-muted fw-semibold">{{ $leaves->firstItem() + $key }}</td>
                                    <td class="lh-col-employee">
                                        <a href="javascript:void(0)" class="lh-emp-link" onclick="openViewModal({{ $leave->id }})">
                                            {{ $leave->employee->name }}
                                        </a>
                                    </td>
                                    <td class="lh-col-center">
                                        <span class="lh-status lh-status--{{ $statusKey }}">
                                            {{ str_replace('_', ' ', $leave->status) }}
                                        </span>
                                    </td>
                                    <td class="lh-col-type">{{ $leaveTypeDisplay }}</td>
                                    <td class="lh-col-center">
                                        <span class="lh-cat {{ $catClass }}">{{ $catDisplay }}</span>
                                    </td>
                                    <td class="lh-col-duration">
                                        <div class="lh-duration-main">{{ $leave->start_date->format('d M Y') }}</div>
                                        @if(str_contains(strtolower($leave->leave_category ?? ''), 'gatepass') && $leave->start_time)
                                            <div class="lh-duration-sub">
                                                {{ \Carbon\Carbon::parse($leave->start_time)->format('h:i A') }}
                                                @if($leave->end_time)
                                                    – {{ \Carbon\Carbon::parse($leave->end_time)->format('h:i A') }}
                                                @endif
                                            </div>
                                        @elseif($leave->end_date && $leave->end_date->gt($leave->start_date))
                                            <div class="lh-duration-sub">to {{ $leave->end_date->format('d M Y') }}</div>
                                        @endif
                                    </td>
                                    <td class="lh-col-center">
                                        <span class="lh-days-pill">
                                            @if(str_contains(strtolower($leave->leave_category ?? ''), 'gatepass'))
                                                1 Hour
                                            @elseif(str_contains(strtolower($leave->leave_type ?? ''), 'half'))
                                                0.5 Day
                                            @else
                                                {{ rtrim(rtrim(number_format($totalDays, 2, '.', ''), '0'), '.') }} Day{{ $totalDays == 1 ? '' : 's' }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="lh-col-action">
                                        <div class="lh-row-actions">
                                            <button type="button" class="zoho-icon-btn" onclick="openViewModal({{ $leave->id }})" title="View">
                                                <i class="feather-eye"></i>
                                            </button>
                                            @if($isAdmin)
                                                <button type="button" class="zoho-icon-btn" onclick="openActionModal({{ $leave->id }})" title="Update Status">
                                                    <i class="feather-edit-3"></i>
                                                </button>
                                                <button type="button" class="zoho-icon-btn zoho-icon-btn--danger" onclick="deleteApplication({{ $leave->id }})" title="Delete">
                                                    <i class="feather-trash-2"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">No leave applications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($leaves->hasPages() || $leaves->total() > 0)
                <div class="lh-list-footer">
                    <span class="lh-list-footer-info">
                        Showing {{ $leaves->firstItem() ?? 0 }} to {{ $leaves->lastItem() ?? 0 }} of {{ $leaves->total() }} results
                    </span>
                    @if($leaves->hasPages())
                        <div>{{ $leaves->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
                    @endif
                </div>
            @endif
        </div>
