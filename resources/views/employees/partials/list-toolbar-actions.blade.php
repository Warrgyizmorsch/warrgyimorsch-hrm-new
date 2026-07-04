<div class="zoho-list-toolbar-actions">
    <div class="zoho-list-search d-none d-lg-flex">
        <i class="feather-search"></i>
        <input type="text" class="employee-page-search-input" value="{{ $search ?? '' }}" onkeyup="syncAndFilter(this)" placeholder="Search employees...">
    </div>
    <button type="button" class="zoho-icon-btn" data-bs-toggle="collapse" data-bs-target="#filterSection" title="Filter">
        <i class="feather-filter"></i>
    </button>
    <button type="button" class="zoho-icon-btn" onclick="location.reload()" title="Refresh">
        <i class="feather-refresh-cw"></i>
    </button>
    @if(in_array(strtolower(auth()->user()->role), ['admin', 'super_admin', 'super admin']))
        <a href="{{ route('employees.export') }}" class="zoho-icon-btn" title="Export">
            <i class="feather-download"></i>
        </a>
        <button type="button" class="zoho-icon-btn zoho-icon-btn-danger" id="deleteSelectedBtn" onclick="deleteSelectedEmployees()" title="Delete Selected">
            <i class="feather-trash-2"></i>
        </button>
        <a href="{{ route('employees.create') }}" class="zoho-btn-primary">
            <i class="feather-plus"></i> Create Employee
        </a>
    @endif
    <button type="button" class="zoho-icon-btn d-lg-none" onclick="$('#mobileSearchSection').toggleClass('d-none')" title="Search">
        <i class="feather-search"></i>
    </button>
</div>
