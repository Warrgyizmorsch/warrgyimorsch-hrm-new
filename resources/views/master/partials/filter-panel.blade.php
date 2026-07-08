@php
    $status = request('status', '');
    $statusLabel = match ($status) {
        'active' => 'Active',
        'inactive' => 'Inactive',
        default => 'All Status',
    };
@endphp

<div class="attendance-filter-panel mst-filter-panel">
    <form method="GET" action="{{ $filterRoute }}" class="mst-filter-form">
        @if(request('search'))
            <input type="hidden" name="search" value="{{ request('search') }}">
        @endif
        @if(request('show'))
            <input type="hidden" name="show" value="{{ request('show') }}">
        @endif
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="attendance-filter-grid mst-filter-grid">
            <div class="attendance-filter-field">
                <label>Status</label>
                <div class="dropdown">
                    <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" id="masterStatusBtn">
                        {{ $statusLabel }}
                    </button>
                    <div class="dropdown-menu wghrm-custom-dropdown-menu">
                        <div class="wghrm-items-container">
                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $status === '' ? 'active' : '' }}"
                               href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => 1]) }}">All Status</a>
                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $status === 'active' ? 'active' : '' }}"
                               href="{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => 1]) }}">Active</a>
                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $status === 'inactive' ? 'active' : '' }}"
                               href="{{ request()->fullUrlWithQuery(['status' => 'inactive', 'page' => 1]) }}">Inactive</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="attendance-filter-actions">
                <button type="submit" class="zoho-btn-primary">
                    <i class="feather-search"></i> Apply
                </button>
                <a href="{{ $filterRoute }}" class="zoho-btn-outline">Reset</a>
            </div>

            <div class="mst-filter-summary">
                <span class="att-stat-chip mst-summary-chip mst-summary-chip--total">
                    Total <strong>{{ $totalCount ?? 0 }}</strong>
                </span>
                <span class="att-stat-chip mst-summary-chip mst-summary-chip--active">
                    Active <strong>{{ $activeCount ?? 0 }}</strong>
                </span>
                @if(($totalCount ?? 0) - ($activeCount ?? 0) > 0)
                    <span class="att-stat-chip mst-summary-chip mst-summary-chip--inactive">
                        Inactive <strong>{{ ($totalCount ?? 0) - ($activeCount ?? 0) }}</strong>
                    </span>
                @endif
            </div>
        </div>
    </form>
</div>
