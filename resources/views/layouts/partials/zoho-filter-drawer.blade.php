{{-- Zoho People-style right filter panel --}}
<div class="offcanvas offcanvas-end zoho-filter-drawer" tabindex="-1" id="zohoFilterDrawer" aria-labelledby="zohoFilterDrawerLabel">
    <div class="zoho-filter-drawer-header">
        <h5 class="zoho-filter-drawer-title" id="zohoFilterDrawerLabel">Filter</h5>
        <button type="button" class="zoho-filter-drawer-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <div class="zoho-filter-drawer-search">
        <i class="feather-search"></i>
        <input type="text" id="zohoFilterDrawerSearch" placeholder="Search filter fields..." autocomplete="off">
    </div>
    <div class="offcanvas-body zoho-filter-drawer-body">
        @if(!empty($filterContent))
            {!! $filterContent !!}
        @else
            @yield('filter_drawer_content')
        @endif
    </div>
    <div class="zoho-filter-drawer-footer">
        <button type="button" class="zoho-btn-primary" onclick="{{ $applyAction ?? 'applyFilters()' }}">Apply</button>
        <a href="{{ $resetUrl ?? url()->current() }}" class="zoho-btn-outline">Reset</a>
    </div>
</div>
