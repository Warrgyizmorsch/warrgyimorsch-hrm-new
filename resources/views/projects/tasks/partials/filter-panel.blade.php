<div class="dt-hero-stats">
    <div class="dt-hero-stat dt-hero-stat--today">
        <div class="dt-hero-stat-icon"><i class="feather-sun"></i></div>
        <div>
            <span class="dt-hero-stat-value">{{ $todayCount ?? 0 }}</span>
            <span class="dt-hero-stat-label">Today's plans</span>
        </div>
    </div>
    <div class="dt-hero-stat">
        <div class="dt-hero-stat-icon"><i class="feather-layers"></i></div>
        <div>
            <span class="dt-hero-stat-value">{{ $tasks->total() }}</span>
            <span class="dt-hero-stat-label">Total tasks</span>
        </div>
    </div>
    <div class="dt-hero-stat dt-hero-stat--logged">
        <div class="dt-hero-stat-icon"><i class="feather-clock"></i></div>
        <div>
            <span class="dt-hero-stat-value">{{ $loggedHoursDisplay ?? '0m' }}</span>
            <span class="dt-hero-stat-label">Time logged</span>
        </div>
    </div>
    <div class="dt-hero-stat dt-hero-stat--pending">
        <div class="dt-hero-stat-icon"><i class="feather-alert-circle"></i></div>
        <div>
            <span class="dt-hero-stat-value">{{ $pendingCount ?? 0 }}</span>
            <span class="dt-hero-stat-label">Pending</span>
        </div>
    </div>
</div>

<div class="attendance-filter-panel dt-filter-panel">
    <div class="dt-filter-panel-head">
        <span><i class="feather-filter"></i> Filters</span>
        <button type="button" class="dt-filter-toggle" data-bs-toggle="collapse" data-bs-target="#dtFilterBody" aria-expanded="true">
            <i class="feather-chevron-up"></i>
        </button>
    </div>
    <div class="collapse show" id="dtFilterBody">
        <form method="GET" action="{{ route('daily-tasks.index') }}" class="dt-filter-form">
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
            @if(request('per_page'))
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            @endif

            <div class="attendance-filter-grid dt-filter-grid">
                <div class="attendance-filter-field">
                    <label>Project</label>
                    <select name="project_id" class="form-control">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="attendance-filter-field">
                    <label>Employee</label>
                    <select name="employee_id" class="form-control">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="attendance-filter-field">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="In Process" {{ request('status') == 'In Process' ? 'selected' : '' }}>In Process</option>
                        <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Incomplete" {{ request('status') == 'Incomplete' ? 'selected' : '' }}>Incomplete</option>
                        <option value="On Hold" {{ request('status') == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                        <option value="Review" {{ request('status') == 'Review' ? 'selected' : '' }}>Review</option>
                        <option value="Rework" {{ request('status') == 'Rework' ? 'selected' : '' }}>Rework</option>
                    </select>
                </div>

                <div class="attendance-filter-field">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>

                <div class="attendance-filter-field">
                    <label>Upto Date</label>
                    <input type="date" name="upto_date" class="form-control" value="{{ request('upto_date') }}">
                </div>

                <div class="attendance-filter-actions">
                    <button type="submit" class="zoho-btn-primary">
                        <i class="feather-search"></i> Apply
                    </button>
                    <a href="{{ route('daily-tasks.index') }}" class="zoho-btn-outline">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>
