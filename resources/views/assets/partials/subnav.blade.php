{{-- Section switcher for HR/Admin asset pages. Pass $current = route name of the active page. --}}
@php
    $openIncidentCount = \App\Models\AssetIncident::open()->count();
    $amLinks = [
        ['assets.dashboard', 'Dashboard', 'feather-pie-chart', 0],
        ['assets.index', 'Inventory & Requests', 'feather-package', 0],
        ['assets.incidents.index', 'Damage & Loss', 'feather-alert-triangle', $openIncidentCount],
        ['assets.audits.index', 'Audits', 'feather-clipboard', 0],
    ];
@endphp
<nav class="am-subnav mb-4" aria-label="Asset management sections">
    @foreach($amLinks as [$route, $label, $icon, $count])
        <a href="{{ route($route) }}" class="{{ ($current ?? '') === $route ? 'active' : '' }}">
            <i class="{{ $icon }}"></i> {{ $label }}
            @if($count > 0)<span class="am-count">{{ $count }}</span>@endif
        </a>
    @endforeach
</nav>
