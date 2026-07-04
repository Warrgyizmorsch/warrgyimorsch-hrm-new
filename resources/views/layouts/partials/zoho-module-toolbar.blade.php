{{-- Zoho CRM-style module toolbar --}}
<div class="zoho-module-toolbar">
    <div class="zoho-module-toolbar-main">
        <h1 class="zoho-module-title">{{ $title ?? 'Module' }}</h1>
        @if(!empty($breadcrumbs))
            <nav class="zoho-breadcrumb" aria-label="Breadcrumb">
                @foreach($breadcrumbs as $i => $crumb)
                    @if($i > 0)<span class="zoho-breadcrumb-sep">/</span>@endif
                    @if(!empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span class="zoho-breadcrumb-current">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif
    </div>
    @if(!empty($actions))
        <div class="zoho-module-toolbar-actions">
            {!! $actions !!}
        </div>
    @endif
</div>
