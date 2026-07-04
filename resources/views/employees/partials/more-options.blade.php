@if(in_array(strtolower(auth()->user()->role), ['admin', 'super_admin', 'super admin']))
    <li>
        <a class="dropdown-item" href="{{ route('employees.export') }}">
            <i class="feather-download"></i> Export
        </a>
    </li>
    <li>
        <a class="dropdown-item" href="javascript:void(0)" onclick="location.reload()">
            <i class="feather-refresh-cw"></i> Refresh List
        </a>
    </li>
    <li><hr class="dropdown-divider"></li>
    <li>
        <a class="dropdown-item text-danger" href="javascript:void(0)" id="deleteSelectedBtn" onclick="deleteSelectedEmployees()">
            <i class="feather-trash-2"></i> Delete Selected
        </a>
    </li>
@else
    <li>
        <a class="dropdown-item" href="javascript:void(0)" onclick="location.reload()">
            <i class="feather-refresh-cw"></i> Refresh List
        </a>
    </li>
@endif
