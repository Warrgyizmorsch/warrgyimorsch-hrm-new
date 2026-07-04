<script>
$(document).ready(function () {
    const $panel = $('#announcementBox');

    $('#btnMinimize').on('click', function () {
        const $body = $('#announcementBody');
        const $btn = $(this);
        const $subtitle = $('#bcSubtitle');
        const expanded = $btn.attr('aria-expanded') !== 'false';

        $body.slideToggle(200);
        $panel.toggleClass('is-collapsed', expanded);
        $btn.attr('aria-expanded', expanded ? 'false' : 'true');
        $btn.attr('title', expanded ? 'Expand' : 'Collapse');

        if ($subtitle.length) {
            const nextText = expanded
                ? ($subtitle.data('collapsed-text') || $subtitle.data('expanded-text'))
                : $subtitle.data('expanded-text');
            if (nextText) $subtitle.text(nextText);
        }
    });

    $panel.on('click', '.bc-head-main', function (e) {
        if (!$panel.hasClass('is-collapsed')) return;
        if ($(e.target).closest('.bc-head-actions, button, a').length) return;
        $('#btnMinimize').trigger('click');
    });

    $('#btnClose').on('click', function () {
        $panel.fadeOut(200);
    });

    $('.bc-filter').on('click', function () {
        const filter = $(this).data('filter');
        $('.bc-filter').removeClass('is-active').attr('aria-selected', 'false');
        $(this).addClass('is-active').attr('aria-selected', 'true');

        let visible = 0;
        $('.bc-item').each(function () {
            const show = filter === 'all' || $(this).data('read') === 0;
            $(this).toggle(show);
            if (show) visible++;
        });

        $('#bcFilterEmpty').prop('hidden', visible > 0 || filter === 'all');
    });

    $(document).on('click', '.bc-expand-btn', function (e) {
        e.stopPropagation();
        const $msg = $('#' + $(this).data('target'));
        const expanded = $msg.toggleClass('bc-item-message--clamp').hasClass('bc-item-message--clamp');
        $(this).text(expanded ? 'Show more' : 'Show less');
    });

    $(document).on('click', '.bc-item.is-unread', function (e) {
        if ($(e.target).closest('.bc-mark-btn, .bc-expand-btn').length) return;
        const id = $(this).data('id');
        markBroadcastRead(id, $(this));
    });

    $(document).on('click', '.btn-mark-read', function (e) {
        e.stopPropagation();
        markBroadcastRead($(this).data('id'), $('#msg-card-' + $(this).data('id')));
    });

    $('#btnMarkAllRead').on('click', function () {
        const $unread = $('.bc-item.is-unread');
        if (!$unread.length) return;

        const $btn = $(this);
        $btn.prop('disabled', true);

        let pending = $unread.length;
        $unread.each(function () {
            markBroadcastRead($(this).data('id'), $(this), function () {
                pending--;
                if (pending <= 0) {
                    $btn.fadeOut(200, function () { $(this).remove(); });
                }
            });
        });
    });

    function markBroadcastRead(broadcastId, $card, callback) {
        if (!$card.length || $card.hasClass('is-read')) {
            if (callback) callback();
            return;
        }

        $.ajax({
            url: `{{ url('/broadcasts') }}/${broadcastId}/read`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (!response.success) {
                    if (callback) callback();
                    return;
                }

                $card.removeClass('is-unread').addClass('is-read').attr('data-read', '1');
                $card.find('.bc-new-chip').remove();
                $card.find('.bc-mark-btn').replaceWith('<span class="bc-read-icon" title="Read"><i class="feather-check-circle"></i></span>');

                updateBroadcastCounts();
                if (callback) callback();
            },
            error: function () {
                if (callback) callback();
            }
        });
    }

    function updateBroadcastCounts() {
        const remaining = $('.bc-item.is-unread').length;
        const total = $('.bc-item').length;
        const $badge = $('#bcUnreadBadge');
        const $subtitle = $('#bcSubtitle');

        $panel.toggleClass('bc-panel--has-unread', remaining > 0);

        if (remaining === 0) {
            $badge.remove();
            $('#bcUnreadStat').closest('.bc-stat--alert').replaceWith('<span class="bc-stat bc-stat--ok"><i class="feather-check-circle"></i> All read</span>');
            if ($subtitle.length) {
                const text = total > 0 ? "You're up to date on all broadcasts" : 'No active broadcasts';
                $subtitle.text(text).data('expanded-text', text);
            }
            $('.bc-filter-count--alert').remove();
            $('.bc-pulse-dot').remove();
        } else {
            if ($badge.length) {
                $badge.text(remaining);
            }
            if ($('#bcUnreadStat').length) {
                $('#bcUnreadStat').text(remaining);
            }
            if ($subtitle.length) {
                const text = remaining + ' new message' + (remaining !== 1 ? 's' : '') + ' require your attention';
                $subtitle.text(text).data('expanded-text', text);
            }
            $('.bc-filter[data-filter="unread"] .bc-filter-count').text(remaining);
        }

        if ($('.bc-filter.is-active').data('filter') === 'unread' && remaining === 0) {
            $('.bc-item').hide();
            $('#bcFilterEmpty').prop('hidden', false);
        }
    }
});
</script>
