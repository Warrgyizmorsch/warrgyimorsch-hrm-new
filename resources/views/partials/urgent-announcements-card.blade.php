@php
    use Illuminate\Support\Str;

    $announcements = $announcements ?? collect();
    $unreadCount = $announcements->filter(function ($announcement) {
        return !$announcement->readByUsers->contains(auth()->id());
    })->count();
    $totalCount = $announcements->count();
    $latestPreview = $announcements->first()
        ? Str::limit(trim($announcements->first()->message), 90)
        : '';
    $subtitleExpanded = $unreadCount > 0
        ? $unreadCount . ' new ' . Str::plural('message', $unreadCount) . ' require your attention'
        : ($totalCount > 0 ? "You're up to date on all broadcasts" : 'No active broadcasts');
@endphp

<div class="bc-panel mb-4 {{ $unreadCount > 0 ? 'bc-panel--has-unread' : '' }}" id="announcementBox">
    <div class="bc-accent-bar" aria-hidden="true"></div>

    <div class="bc-head">
        <div class="bc-head-main">
            <span class="bc-head-icon" aria-hidden="true">
                <i class="feather-radio"></i>
                @if($unreadCount > 0)
                    <span class="bc-pulse-dot"></span>
                @endif
            </span>
            <div class="bc-head-text">
                <div class="bc-head-title-row">
                    <h2 class="bc-title">Company Broadcasts</h2>
                    @if($unreadCount > 0)
                        <span class="bc-unread-badge" id="bcUnreadBadge">{{ $unreadCount }}</span>
                    @endif
                </div>
                <p class="bc-subtitle"
                   id="bcSubtitle"
                   data-expanded-text="{{ $subtitleExpanded }}"
                   data-collapsed-text="{{ $latestPreview ?: $subtitleExpanded }}">
                    {{ $subtitleExpanded }}
                </p>
                @if($totalCount > 0)
                    <div class="bc-head-stats">
                        <span class="bc-stat"><strong>{{ $totalCount }}</strong> total</span>
                        @if($unreadCount > 0)
                            <span class="bc-stat bc-stat--alert"><strong id="bcUnreadStat">{{ $unreadCount }}</strong> unread</span>
                        @else
                            <span class="bc-stat bc-stat--ok"><i class="feather-check-circle"></i> All read</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        <div class="bc-head-actions">
            @if($totalCount > 0)
                <a href="{{ route('broadcasts.index') }}" class="bc-text-btn bc-text-btn--link" title="Manage broadcasts">
                    <i class="feather-external-link"></i><span class="d-none d-md-inline">View all</span>
                </a>
            @endif
            @if($unreadCount > 0)
                <button type="button" class="bc-text-btn" id="btnMarkAllRead" title="Mark all as read">
                    <i class="feather-check-circle"></i><span class="d-none d-md-inline">Mark all read</span>
                </button>
            @endif
            <button type="button" class="zoho-icon-btn bc-toggle" id="btnMinimize" title="Collapse" aria-expanded="true">
                <i class="feather-chevron-up"></i>
            </button>
            <button type="button" class="zoho-icon-btn" id="btnClose" title="Dismiss panel">
                <i class="feather-x"></i>
            </button>
        </div>
    </div>

    @if($totalCount > 0)
        <div class="bc-filter-bar" id="announcementBody">
            <div class="bc-tabs" role="tablist">
                <button type="button" class="bc-filter bc-tab is-active" data-filter="all" role="tab" aria-selected="true">
                    All <span class="bc-filter-count">{{ $totalCount }}</span>
                </button>
                <button type="button" class="bc-filter bc-tab" data-filter="unread" role="tab" aria-selected="false">
                    Unread @if($unreadCount > 0)<span class="bc-filter-count bc-filter-count--alert">{{ $unreadCount }}</span>@endif
                </button>
            </div>

            <div class="bc-feed" id="bcFeed">
                @foreach($announcements as $announcement)
                    @php
                        $isRead = $announcement->readByUsers->contains(auth()->id());
                        $message = trim($announcement->message);
                        $isLong = strlen($message) > 180;
                        $dept = strtolower($announcement->department ?? 'company');
                        $deptLabel = ucfirst(str_replace('_', ' ', $announcement->department ?? 'Company'));
                        $deptSlug = Str::slug($dept ?: 'company');
                    @endphp
                    <article class="bc-item bc-card {{ $isRead ? 'is-read' : 'is-unread' }}"
                             id="msg-card-{{ $announcement->id }}"
                             data-id="{{ $announcement->id }}"
                             data-read="{{ $isRead ? '1' : '0' }}">
                        @if(!$isRead)
                            <div class="bc-card-accent" aria-hidden="true"></div>
                        @endif
                        <div class="bc-card-main">
                            <span class="bc-card-icon" aria-hidden="true">
                                <i class="feather-{{ $isRead ? 'check-circle' : 'megaphone' }}"></i>
                            </span>
                            <div class="bc-card-content">
                                <div class="bc-item-top">
                                    <span class="bc-dept-chip bc-dept-chip--{{ $deptSlug }}">{{ $deptLabel }}</span>
                                    @if(!$isRead)
                                        <span class="bc-new-chip">New</span>
                                    @endif
                                    <time class="bc-item-time"
                                          datetime="{{ $announcement->created_at->toIso8601String() }}"
                                          title="{{ $announcement->created_at->format('M d, Y h:i A') }}">
                                        {{ $announcement->created_at->diffForHumans() }}
                                    </time>
                                </div>
                                <div class="bc-item-message-wrap">
                                    <p class="bc-item-message {{ $isLong ? 'bc-item-message--clamp' : '' }}"
                                       id="bc-msg-{{ $announcement->id }}">{{ $message }}</p>
                                    @if($isLong)
                                        <button type="button"
                                                class="bc-expand-btn"
                                                data-target="bc-msg-{{ $announcement->id }}">Show more</button>
                                    @endif
                                </div>
                                @if(!empty($announcement->documents))
                                    <div class="bc-item-attachments">
                                        @foreach($announcement->documents as $doc)
                                            <a href="{{ \Storage::url($doc) }}" target="_blank" rel="noopener"
                                               class="bc-item-attachment-chip"
                                               title="{{ Str::after(basename($doc), '_') }}">
                                                <i class="feather-paperclip"></i>
                                                {{ Str::limit(Str::after(basename($doc), '_'), 24) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="bc-card-aside">
                            @if(!$isRead)
                                <button type="button"
                                        class="bc-mark-btn btn-mark-read"
                                        data-id="{{ $announcement->id }}"
                                        title="Mark as read"
                                        aria-label="Mark as read">
                                    <i class="feather-check"></i>
                                </button>
                            @else
                                <span class="bc-read-icon" title="Read"><i class="feather-check-circle"></i></span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="bc-feed-empty" id="bcFilterEmpty" hidden>
                <div class="bc-empty-icon"><i class="feather-inbox"></i></div>
                <p class="bc-empty-title">No unread broadcasts</p>
                <p class="bc-empty-desc">Switch to All to see previous announcements.</p>
            </div>
        </div>
    @else
        <div class="bc-empty" id="announcementBody">
            <div class="bc-empty-icon"><i class="feather-radio"></i></div>
            <p class="bc-empty-title">No broadcasts right now</p>
            <p class="bc-empty-desc">Company announcements and urgent updates will appear here.</p>
        </div>
    @endif
</div>
