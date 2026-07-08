@include('layouts.partials.zoho-people-list-header', [
    'title' => 'Daily Tasks',
    'viewLabel' => 'Plan · Progress · Track',
    'scopeLinks' => [
        ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
        ['label' => 'Projects', 'url' => route('projects.index'), 'active' => false],
        ['label' => 'Daily Tasks', 'url' => route('daily-tasks.index'), 'active' => true],
    ],
    'primaryAction' => view('projects.tasks.partials.toolbar-actions')->render(),
])
