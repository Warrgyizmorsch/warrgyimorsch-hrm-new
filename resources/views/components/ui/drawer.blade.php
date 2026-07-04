@props([
    'id',
    'title' => null,
    'position' => 'end',
    'scroll' => false,
    'backdrop' => true,
    'closeOnOutsideClick' => true,
    'bodyClass' => '',
    'footerClass' => '',
    'group' => null,
])

@php
    $backdropValue = $backdrop === false ? 'false' : 'true';
@endphp

<div class="offcanvas offcanvas-{{ $position }}"
     @if($scroll) data-bs-scroll="true" @endif
     data-bs-backdrop="{{ $backdropValue }}"
     @if($group) data-drawer-group="{{ $group }}" @endif
     tabindex="-1"
     id="{{ $id }}"
     aria-labelledby="{{ $id }}Label"
     {{ $attributes->merge(['class' => 'zoho-ui-drawer']) }}>

    @if(isset($header))
        {{ $header }}
    @elseif($title)
        <div class="offcanvas-header zoho-offcanvas-head border-bottom">
            <h5 class="offcanvas-title zoho-offcanvas-title" id="{{ $id }}Label">{{ $title }}</h5>
            <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="feather-x"></i>
            </button>
        </div>
    @endif

    <div class="offcanvas-body {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="zoho-ui-drawer-footer {{ $footerClass }}">
            {{ $footer }}
        </div>
    @endif
</div>

<script>
(function () {
    var drawerEl = document.getElementById(@json($id));
    if (!drawerEl) return;

    document.body.appendChild(drawerEl);

    if (!window.__zohoDrawerClickTracking) {
        window.__zohoDrawerClickTracking = true;
        document.addEventListener('mousedown', function (e) {
            window.__zohoDrawerLastClickTarget = e.target;
        }, true);
    }

    var closeOnOutsideClick = @json((bool) $closeOnOutsideClick);
    var drawerGroup = @json($group);

    drawerEl.addEventListener('hide.bs.offcanvas', function (e) {
        if (!closeOnOutsideClick) {
            var lastClickTarget = window.__zohoDrawerLastClickTarget;
            if (lastClickTarget) {
                var clickedInside = drawerEl.contains(lastClickTarget);
                var isCloseBtn = lastClickTarget.closest('[data-bs-dismiss="offcanvas"]');
                if (!clickedInside && !isCloseBtn) {
                    e.preventDefault();
                }
            }
        }
    });

    drawerEl.addEventListener('show.bs.offcanvas', function () {
        if (!drawerGroup || typeof bootstrap === 'undefined') return;
        document.querySelectorAll('.offcanvas[data-drawer-group="' + drawerGroup + '"]').forEach(function (el) {
            if (el !== drawerEl) {
                bootstrap.Offcanvas.getInstance(el)?.hide();
            }
        });
    });

    drawerEl.addEventListener('hidden.bs.offcanvas', function () {
        document.querySelectorAll('.offcanvas-backdrop').forEach(function (backdrop) {
            backdrop.remove();
        });
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
})();
</script>
