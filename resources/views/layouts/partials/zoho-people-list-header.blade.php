{{-- Zoho People-style list page header (Candidate / Employee listing) --}}
<div class="zoho-people-list-header">
    <div class="zoho-people-list-header-left">
        <h1 class="zoho-people-list-title">{{ $title ?? 'Module' }}</h1>
        @if(!empty($viewLabel))
            <div class="zoho-people-view-meta">
                <span class="zoho-people-view-name">{{ $viewLabel }}</span>
                @if(!empty($viewEditUrl))
                    <a href="{{ $viewEditUrl }}" class="zoho-people-view-edit">Edit</a>
                @endif
            </div>
        @endif
        @if(!empty($scopeLinks))
            <div class="zoho-people-scope-links">
                @foreach($scopeLinks as $link)
                    <a href="{{ $link['url'] ?? 'javascript:void(0)' }}"
                       class="{{ !empty($link['active']) ? 'active' : '' }}">{{ $link['label'] }}</a>
                @endforeach
            </div>
        @endif
    </div>
    <div class="zoho-people-list-header-right">
        @if(!empty($primaryAction))
            {!! $primaryAction !!}
        @endif
        @if(!empty($showFilter))
            <button type="button" class="zoho-icon-btn" data-bs-toggle="offcanvas" data-bs-target="#zohoFilterDrawer" title="Filter">
                <i class="feather-filter"></i>
            </button>
        @endif
        @if(!empty($moreMenu))
            <div class="dropdown zoho-more-dropdown">
                <button type="button" class="zoho-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="More options">
                    <i class="feather-more-horizontal"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end zoho-more-menu">
                    {!! $moreMenu !!}
                </ul>
            </div>
        @endif
        @if(!empty($extraActions))
            {!! $extraActions !!}
        @endif
    </div>
</div>
