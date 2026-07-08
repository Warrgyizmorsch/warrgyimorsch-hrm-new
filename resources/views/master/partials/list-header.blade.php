@include('layouts.partials.zoho-people-list-header', [
    'title' => $masterTitle ?? 'Master Module',
    'viewLabel' => $masterViewLabel ?? 'Master List',
    'scopeLinks' => [
        ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
        ['label' => 'Departments', 'url' => route('master.departments'), 'active' => ($masterActive ?? '') === 'departments'],
        ['label' => 'Designations', 'url' => route('master.designations'), 'active' => ($masterActive ?? '') === 'designations'],
        ['label' => 'Roles', 'url' => route('master.roles'), 'active' => ($masterActive ?? '') === 'roles'],
    ],
    'primaryAction' => $primaryAction ?? null,
])
