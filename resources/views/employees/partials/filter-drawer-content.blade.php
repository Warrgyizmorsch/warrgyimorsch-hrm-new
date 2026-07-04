<div class="zoho-filter-section" data-filter-section>
    <button type="button" class="zoho-filter-section-toggle" data-bs-toggle="collapse" data-bs-target="#filterSystemDefined">
        <span>System Defined Filters</span>
        <i class="feather-chevron-down"></i>
    </button>
    <div class="collapse show" id="filterSystemDefined">
        <div class="zoho-filter-field" data-filter-field>
            <label class="zoho-filter-label">Employee</label>
            <div class="wghrm-search-dropdown" id="employeeFilterDropdown">
                <div class="wghrm-dropdown-trigger zoho-filter-input">
                    @php $selectedEmployeeOption = $employeeFilterOptions->firstWhere('id', $employeeFilter); @endphp
                    <span class="wghrm-trigger-text">
                        {{ $selectedEmployeeOption
                            ? $selectedEmployeeOption->name . ' (' . ($selectedEmployeeOption->employee_code ?? $selectedEmployeeOption->id) . ')'
                            : 'All Employees' }}
                    </span>
                    <i data-feather="chevron-down"></i>
                </div>
                <div class="wghrm-dropdown-menu">
                    <div class="wghrm-search-container">
                        <i data-feather="search" class="wghrm-search-icon"></i>
                        <input type="text" class="wghrm-search-input" placeholder="Search employee...">
                    </div>
                    <div class="wghrm-items-list">
                        <div class="wghrm-item {{ empty($employeeFilter) ? 'selected' : '' }}" data-value="" data-text="All Employees">
                            <span class="wghrm-item-text">All Employees</span>
                            <i data-feather="check" class="wghrm-item-check"></i>
                        </div>
                        @foreach ($employeeFilterOptions as $employee)
                            @php $employeeLabel = trim(($employee->name ?? 'Unknown') . ' (' . ($employee->employee_code ?? $employee->id) . ')'); @endphp
                            <div class="wghrm-item {{ (string) $employeeFilter === (string) $employee->id ? 'selected' : '' }}" data-value="{{ $employee->id }}" data-text="{{ $employeeLabel }}">
                                <span class="wghrm-item-text">{{ $employeeLabel }}</span>
                                <i data-feather="check" class="wghrm-item-check"></i>
                            </div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" id="filterEmployeeName" value="{{ $employeeFilter ?? '' }}">
            </div>
        </div>
        <div class="zoho-filter-field" data-filter-field>
            <label class="zoho-filter-label">Role</label>
            <div class="wghrm-search-dropdown" id="roleFilterDropdown">
                <div class="wghrm-dropdown-trigger zoho-filter-input">
                    <span class="wghrm-trigger-text">{{ $roleFilter ? ucfirst(str_replace('_', ' ', $roleFilter)) : 'All Roles' }}</span>
                    <i data-feather="chevron-down"></i>
                </div>
                <div class="wghrm-dropdown-menu">
                    <div class="wghrm-search-container">
                        <i data-feather="search" class="wghrm-search-icon"></i>
                        <input type="text" class="wghrm-search-input" placeholder="Search role...">
                    </div>
                    <div class="wghrm-items-list">
                        <div class="wghrm-item {{ empty($roleFilter) ? 'selected' : '' }}" data-value="" data-text="All Roles">
                            <span class="wghrm-item-text">All Roles</span>
                            <i data-feather="check" class="wghrm-item-check"></i>
                        </div>
                        @foreach (\App\Models\Role::all() as $role)
                            <div class="wghrm-item {{ strtolower($role->slug) === strtolower($roleFilter ?? '') ? 'selected' : '' }}" data-value="{{ strtolower($role->slug) }}" data-text="{{ $role->name }}">
                                <span class="wghrm-item-text">{{ $role->name }}</span>
                                <i data-feather="check" class="wghrm-item-check"></i>
                            </div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" id="filterRole" value="{{ $roleFilter ?? '' }}">
            </div>
        </div>
        <div class="zoho-filter-field" data-filter-field>
            <label class="zoho-filter-label">Department</label>
            <div class="wghrm-search-dropdown" id="departmentFilterDropdown">
                <div class="wghrm-dropdown-trigger zoho-filter-input">
                    <span class="wghrm-trigger-text">{{ $departmentFilter ? ucfirst(str_replace('_', ' ', $departmentFilter)) : 'All Departments' }}</span>
                    <i data-feather="chevron-down"></i>
                </div>
                <div class="wghrm-dropdown-menu">
                    <div class="wghrm-search-container">
                        <i data-feather="search" class="wghrm-search-icon"></i>
                        <input type="text" class="wghrm-search-input" placeholder="Search department...">
                    </div>
                    <div class="wghrm-items-list">
                        <div class="wghrm-item {{ empty($departmentFilter) ? 'selected' : '' }}" data-value="" data-text="All Departments">
                            <span class="wghrm-item-text">All Departments</span>
                            <i data-feather="check" class="wghrm-item-check"></i>
                        </div>
                        @foreach (\App\Models\Department::all() as $dept)
                            <div class="wghrm-item {{ strtolower($dept->name) === strtolower($departmentFilter ?? '') ? 'selected' : '' }}" data-value="{{ strtolower($dept->name) }}" data-text="{{ $dept->name }}">
                                <span class="wghrm-item-text">{{ $dept->name }}</span>
                                <i data-feather="check" class="wghrm-item-check"></i>
                            </div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" id="filterDepartment" value="{{ $departmentFilter ?? '' }}">
            </div>
        </div>
    </div>
</div>

<div class="zoho-filter-section" data-filter-section>
    <button type="button" class="zoho-filter-section-toggle" data-bs-toggle="collapse" data-bs-target="#filterByFields">
        <span>Filter By Fields</span>
        <i class="feather-chevron-down"></i>
    </button>
    <div class="collapse show" id="filterByFields">
        <div class="zoho-filter-checklist" data-filter-field>
            <label class="zoho-filter-check"><input type="checkbox" checked disabled> Name</label>
            <label class="zoho-filter-check"><input type="checkbox" checked disabled> Role</label>
            <label class="zoho-filter-check"><input type="checkbox" checked disabled> Department</label>
            <label class="zoho-filter-check"><input type="checkbox" checked disabled> Status</label>
            <label class="zoho-filter-check"><input type="checkbox" checked disabled> Employee Code</label>
        </div>
    </div>
</div>
