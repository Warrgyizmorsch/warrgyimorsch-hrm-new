<div class="pm-table-card">
    <div class="pm-table-toolbar">
        <div class="pm-table-toolbar-left">
            <span class="pm-table-toolbar-label">Showing</span>
            <strong>{{ method_exists($projects, 'total') ? number_format($projects->total()) : number_format($projects->count()) }} projects</strong>
            @if(($projectInsights['overdue'] ?? 0) > 0)
                <span class="pm-toolbar-pill pm-toolbar-pill--danger">
                    <i class="feather-alert-circle"></i> {{ $projectInsights['overdue'] }} overdue
                </span>
            @endif
        </div>
        <div class="pm-table-toolbar-right">
            <div class="pm-quick-search">
                <i class="feather-search"></i>
                <input type="search" id="pmQuickSearch" placeholder="Filter visible rows..." autocomplete="off">
            </div>
        </div>
    </div>

    <div class="pm-table-scroll">
        <table class="table zoho-data-table mb-0" id="projectList">
            <thead>
                <tr>
                    <th class="pm-col-check">
                        <input type="checkbox" class="form-check-input shadow-none" id="checkAllProject">
                    </th>
                    <th class="pm-col-project">Project</th>
                    <th class="pm-col-lead">Lead</th>
                    <th class="pm-col-team">Team</th>
                    <th class="pm-col-status">Status</th>
                    <th class="pm-col-dates">Timeline</th>
                    <th class="pm-col-actions text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                    @include('projects.partials.project-row', ['project' => $project])
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="pm-empty-state">
                                <i class="feather-folder"></i>
                                <p>No projects match your filters.</p>
                                <a href="{{ route('projects.create') }}" class="zoho-btn-primary btn-sm">Create your first project</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($projects, 'hasPages') && ($view ?? 'list') === 'list')
        <div class="pm-list-footer">
            <span class="pm-list-footer-info">
                Showing {{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }} of {{ number_format($projects->total()) }} projects
            </span>
            @if($projects->hasPages())
                <div>{{ $projects->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    @endif
</div>
