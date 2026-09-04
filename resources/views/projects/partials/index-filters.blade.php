@php
    $currentStatus = request('status', 'all');
    $statusFilters = [
        ['key' => 'all', 'label' => 'All', 'count' => $projectStats['total'] ?? 0, 'class' => ''],
        ['key' => 'Pending', 'label' => 'Pending', 'count' => $projectStats['pending'] ?? 0, 'class' => 'pm-stat-chip--pending'],
        ['key' => 'In Process', 'label' => 'In Process', 'count' => $projectStats['in_process'] ?? 0, 'class' => 'pm-stat-chip--process'],
        ['key' => 'Review', 'label' => 'Review', 'count' => $projectStats['review'] ?? 0, 'class' => 'pm-stat-chip--review'],
        ['key' => 'On Hold', 'label' => 'On Hold', 'count' => $projectStats['on_hold'] ?? 0, 'class' => 'pm-stat-chip--hold'],
        ['key' => 'Rework', 'label' => 'Rework', 'count' => $projectStats['rework'] ?? 0, 'class' => 'pm-stat-chip--rework'],
        ['key' => 'Completed', 'label' => 'Completed', 'count' => $projectStats['completed'] ?? 0, 'class' => 'pm-stat-chip--completed'],
    ];
    $currentSort = $sort ?? request('sort', 'latest');
@endphp

<div class="pm-insights-row">
    <div class="pm-insight-card">
        <span class="pm-insight-card__icon"><i class="feather-layers"></i></span>
        <span class="pm-insight-card__body">
            <strong>{{ number_format($projectStats['total'] ?? 0) }}</strong>
            <span>Total projects</span>
        </span>
    </div>
    <div class="pm-insight-card pm-insight-card--active">
        <span class="pm-insight-card__icon"><i class="feather-activity"></i></span>
        <span class="pm-insight-card__body">
            <strong>{{ number_format($projectInsights['active'] ?? 0) }}</strong>
            <span>Active now</span>
        </span>
    </div>
    <div class="pm-insight-card pm-insight-card--danger">
        <span class="pm-insight-card__icon"><i class="feather-alert-circle"></i></span>
        <span class="pm-insight-card__body">
            <strong>{{ number_format($projectInsights['overdue'] ?? 0) }}</strong>
            <span>Overdue</span>
        </span>
    </div>
    <div class="pm-insight-card pm-insight-card--warning">
        <span class="pm-insight-card__icon"><i class="feather-calendar"></i></span>
        <span class="pm-insight-card__body">
            <strong>{{ number_format($projectInsights['due_this_week'] ?? 0) }}</strong>
            <span>Due this week</span>
        </span>
    </div>
</div>

<div class="pm-stat-row">
    @foreach($statusFilters as $filter)
        @php
            $isActive = ($currentStatus === $filter['key']) || ($filter['key'] === 'all' && ($currentStatus === '' || $currentStatus === 'all'));
            $query = request()->except('page');
            if ($filter['key'] === 'all') {
                unset($query['status']);
            } else {
                $query['status'] = $filter['key'];
            }
        @endphp
        <a href="{{ route('projects.index', $query) }}"
           class="pm-stat-chip {{ $filter['class'] }} {{ $isActive ? 'is-active' : '' }}">
            <span class="pm-stat-chip__value">{{ number_format($filter['count']) }}</span>
            <span class="pm-stat-chip__label">{{ $filter['label'] }}</span>
        </a>
    @endforeach
</div>

<div class="pm-filter-card">
    <form action="{{ route('projects.index') }}" method="GET" class="pm-filter-grid">
        @if(($view ?? 'list') === 'board')
            <input type="hidden" name="view" value="board">
        @endif
        @if(request('status') && request('status') !== 'all')
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <div class="pm-filter-field pm-filter-field--wide">
            <label>Search Projects</label>
            <input type="text" name="search" class="form-control pm-filter-input" value="{{ request('search') }}" placeholder="Name, technology, description...">
        </div>
        <div class="pm-filter-field">
            <label>Department</label>
            <select name="department_id" class="form-select pm-filter-input pm-native-select">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="pm-filter-field">
            <label>Sort By</label>
            <select name="sort" class="form-select pm-filter-input pm-native-select">
                <option value="latest" {{ $currentSort === 'latest' ? 'selected' : '' }}>Newest first</option>
                <option value="oldest" {{ $currentSort === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                <option value="name" {{ $currentSort === 'name' ? 'selected' : '' }}>Name A–Z</option>
                <option value="due_soon" {{ $currentSort === 'due_soon' ? 'selected' : '' }}>Due date</option>
            </select>
        </div>
        <div class="pm-filter-field">
            <label>Per Page</label>
            <select name="per_page" class="form-select pm-filter-input pm-native-select">
                @foreach([20, 50, 100] as $size)
                    <option value="{{ $size }}" {{ ($perPage ?? 20) == $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="pm-filter-field pm-filter-actions">
            <button type="submit" class="zoho-btn-primary">Apply</button>
            <a href="{{ route('projects.index', ($view ?? 'list') === 'board' ? ['view' => 'board'] : []) }}" class="zoho-btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="pm-toolbar">
    <div class="pm-view-toggle" role="group" aria-label="Project view">
        <a href="{{ route('projects.index', array_merge(request()->except('view', 'page'), ['view' => 'list'])) }}"
           class="{{ ($view ?? 'list') === 'list' ? 'is-active' : '' }}">
            <i class="feather-list"></i> List
        </a>
        <a href="{{ route('projects.index', array_merge(request()->except('page'), ['view' => 'board'])) }}"
           class="{{ ($view ?? 'list') === 'board' ? 'is-active' : '' }}">
            <i class="feather-columns"></i> Board
        </a>
    </div>

    <div class="pm-toolbar-right">
        <div class="pm-bulk-bar" id="pmBulkBar">
            <span class="small fw-semibold text-danger" id="pmBulkCount">0 selected</span>
            <button type="button" class="zoho-btn-outline btn-sm" id="pmBulkDeleteBtn">
                <i class="feather-trash-2"></i> Delete selected
            </button>
        </div>
    </div>
</div>
