@extends('layouts.app')

@section('content')
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Suggestion Box',
        'scopeLinks' => [
            ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Suggestion Box', 'url' => null, 'active' => true],
        ],
    ])

    <!-- [ stat cards ] start -->
    <div class="page-header-collapse">
        <div class="accordion-body pb-2">
            <div class="row g-3">
                <div class="col-xxl col-md-6">
                    <div class="card stretch stretch-full border-start border-4 border-primary">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Total Suggestions</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $totalSuggestions }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-primary text-primary">
                                    <i class="feather-message-square"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-6">
                    <div class="card stretch stretch-full border-start border-4 border-info">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">This Month</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $thisMonth }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-info text-info">
                                    <i class="feather-calendar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ stat cards ] end -->

    @php
        $suggestionCategoryColors = [
            'Work Culture' => 'primary',
            'Management & Leadership' => 'info',
            'Process & Productivity' => 'success',
            'Facilities & Resources' => 'warning',
            'Compensation & Benefits' => 'danger',
            'Other' => 'secondary',
        ];
        $suggestionStatusColors = [
            'Open' => 'primary',
            'In Progress' => 'warning',
            'Resolved' => 'success',
            'Closed' => 'secondary',
        ];
    @endphp

    <div class="main-content">
        <div class="card stretch stretch-full sgst-toolbar-card">
            <div class="card-body px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <form method="GET" class="d-flex flex-wrap align-items-center gap-2">
                    <div class="zoho-people-table-search" style="min-width: 260px;">
                        <i class="feather-search"></i>
                        <input type="text" name="search" placeholder="Search by employee or message…" value="{{ request('search') }}" autocomplete="off">
                    </div>
                    <select name="category" class="form-control" style="width: auto; height: 40px;" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach(\App\Models\Suggestion::CATEGORIES as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @if(request('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif
                    <button type="submit" class="btn btn-light-brand btn-sm">Search</button>
                    @if(request('search') || request('category'))
                        <a href="{{ route('suggestions.index') }}" class="btn btn-sm btn-light">Reset</a>
                    @endif
                </form>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small fw-bold text-uppercase">Show</span>
                    <div class="dropdown">
                        <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" id="showEntriesBtn"
                            style="width: 80px; height: 44px; padding: 0 15px;">
                            {{ $perPage ?? 20 }}
                        </button>
                        <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 12px;">
                            <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 20) == 20 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}">20</a>
                            <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 50) == 50 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}">50</a>
                            <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 100) == 100 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}">100</a>
                        </div>
                    </div>
                    <span class="text-muted small fw-bold text-uppercase">entries</span>
                </div>
            </div>
        </div>

        @if($suggestions->isEmpty())
            <div class="card stretch stretch-full">
                <div class="card-body text-center py-5">
                    <div class="text-muted">
                        <i class="feather-message-square d-block mb-2" style="font-size: 32px;"></i>
                        No suggestions submitted yet.
                    </div>
                </div>
            </div>
        @else
            <div class="row g-3 sgst-grid">
                @foreach($suggestions as $index => $suggestion)
                    @php
                        $submitter = $suggestion->user;
                        $employee = $submitter?->employee;
                        $photo = $employee?->photo;
                        $color = $suggestionCategoryColors[$suggestion->category] ?? 'secondary';
                        $statusColor = $suggestionStatusColors[$suggestion->status] ?? 'primary';
                        $delay = min($index, 12) * 0.06;
                    @endphp
                    <div class="col-xl-4 col-md-6">
                        <div class="card stretch stretch-full sgst-card" style="animation-delay: {{ $delay }}s;">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-soft-{{ $color }} text-{{ $color }} sgst-badge">{{ $suggestion->category }}</span>
                                    <button type="button" class="btn-sgst-status sgst-status-{{ $statusColor }}"
                                        data-suggestion-id="{{ $suggestion->id }}"
                                        data-suggestion-status="{{ $suggestion->status }}"
                                        data-suggestion-reply="{{ $suggestion->manager_reply ?? '' }}"
                                        onclick="openSuggestionStatusModalFromButton(this)">
                                        <span>{{ $suggestion->status }}</span>
                                        <i class="feather-chevron-down"></i>
                                    </button>
                                </div>

                                <div class="sgst-quote bc-message-wrap" style="animation-delay: {{ $delay + 0.2 }}s;">
                                    <span class="sgst-quote-mark sgst-quote-mark-open">&ldquo;</span>
                                    <div class="sgst-quote-text bc-message bc-message--clamped">{{ $suggestion->message }}</div>
                                    <span class="sgst-quote-mark sgst-quote-mark-close">&rdquo;</span>
                                    <a href="javascript:void(0)" class="bc-message-toggle small text-primary d-none">Show more</a>
                                </div>

                                @if($suggestion->manager_reply)
                                    <div class="sgst-reply-box">
                                        <div class="sgst-reply-head">
                                            <i class="feather-corner-down-right"></i> Management response
                                        </div>
                                        <p class="mb-1">{{ $suggestion->manager_reply }}</p>
                                        <span class="small text-muted">
                                            {{ $suggestion->repliedBy->name ?? 'Management' }}
                                            @if($suggestion->replied_at) · {{ $suggestion->replied_at->diffForHumans() }} @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer bg-transparent border-top pt-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="zoho-emp-photo-wrap" style="width: 36px; height: 36px;">
                                        @if($photo)
                                            <img src="{{ asset('storage/' . $photo) }}" class="zoho-emp-avatar" alt=""
                                                onerror="this.classList.add('d-none');this.parentElement.querySelector('.zoho-emp-initial').classList.remove('d-none');">
                                            <div class="zoho-emp-initial d-none">{{ strtoupper(substr($submitter->name ?? 'U', 0, 1)) }}</div>
                                        @else
                                            <div class="zoho-emp-initial">{{ strtoupper(substr($submitter->name ?? 'U', 0, 1)) }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small">{{ $submitter->name ?? 'Unknown' }}</div>
                                        <div class="small text-muted">{{ $employee->designation ?? '—' }}</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn-appreciate {{ $suggestion->appreciated ? 'active' : '' }}"
                                        onclick="toggleSuggestionAppreciation({{ $suggestion->id }}, this)" title="Appreciate this suggestion">
                                        <i class="feather-heart"></i>
                                    </button>
                                    <span class="small text-muted" title="{{ $suggestion->created_at->format('d M Y, h:i A') }}">
                                        <i class="feather-clock me-1"></i>{{ $suggestion->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($suggestions->hasPages())
                <div class="card stretch stretch-full mt-1">
                    <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                        {{ $suggestions->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Update Status / Reply Modal --}}
    <div class="modal fade" id="suggestionStatusModal" tabindex="-1" aria-labelledby="suggestionStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title" id="suggestionStatusModalLabel">Update Suggestion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="sgstStatusId">
                    <div class="mb-3">
                        <label class="fw-semibold mb-2">Status</label>
                        <select class="form-select border" id="sgstStatusSelect" style="height: 48px; border-radius: 10px; font-weight: 600;">
                            @foreach(\App\Models\Suggestion::STATUSES as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="fw-semibold mb-2">Reply to Employee <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control border" id="sgstReplyText" rows="4" placeholder="Write a response or note of appreciation…" style="border-radius: 10px;"></textarea>
                    </div>
                    <div class="text-center">
                        <button class="btn btn-primary px-5 py-2 fw-bold" onclick="submitSuggestionStatus()" style="border-radius: 10px;">
                            Save Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .zoho-emp-photo-wrap {
            position: relative;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }
        .zoho-emp-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .zoho-emp-initial {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eaf2ff;
            color: #0d6efd;
            font-weight: 700;
            font-size: 14px;
        }
        .bc-message {
            white-space: pre-line;
            line-height: 1.5;
        }
        .bc-message--clamped {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .bc-message-toggle {
            cursor: pointer;
        }
        .zoho-people-table-search {
            position: relative;
            display: flex;
            align-items: center;
        }
        .zoho-people-table-search i {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 14px;
        }
        .zoho-people-table-search input {
            width: 100%;
            height: 40px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0 12px 0 34px;
            font-size: 14px;
            outline: none;
        }
        .wghrm-custom-select-btn {
            background-color: #fff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            color: #1e293b !important;
            padding: 10px 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            height: 48px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            text-align: left !important;
        }
        .wghrm-custom-dropdown-menu {
            border-radius: 12px !important;
            padding: 4px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #e4e6ef !important;
            min-width: 80px;
        }
        .wghrm-custom-dropdown-item {
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 14px;
            color: #4b5563 !important;
        }
        .wghrm-custom-dropdown-item.active, .wghrm-custom-dropdown-item:hover {
            background-color: #f1f3f9 !important;
            color: #4e73df !important;
        }

        .sgst-toolbar-card { margin-bottom: 1rem; }

        @keyframes sgstFadeInUp {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .sgst-card {
            opacity: 0;
            animation: sgstFadeInUp 0.45s ease forwards;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 14px;
        }
        .sgst-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }
        .sgst-card .bc-message--clamped {
            -webkit-line-clamp: 4;
            min-height: 84px;
        }
        .sgst-badge {
            font-weight: 600;
            font-size: 11px;
            padding: 6px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .sgst-quote {
            position: relative;
            padding: 4px 6px 2px;
            opacity: 0;
            animation: sgstFadeInUp 0.5s ease forwards;
        }
        @keyframes sgstQuotePop {
            0% { opacity: 0; transform: scale(0.4) rotate(-8deg); }
            60% { opacity: 1; transform: scale(1.15) rotate(2deg); }
            100% { opacity: 1; transform: scale(1) rotate(0deg); }
        }
        .sgst-quote-mark {
            display: inline-block;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 34px;
            line-height: 0.5;
            font-weight: 700;
            color: rgba(16, 112, 224, 0.28);
            animation: sgstQuotePop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            animation-delay: inherit;
        }
        .sgst-quote-mark-open { vertical-align: -0.35em; margin-right: 2px; }
        .sgst-quote-mark-close { vertical-align: -0.65em; margin-left: 2px; }
        .sgst-quote-text {
            font-size: 15px;
            font-weight: 500;
            color: #1e293b;
        }
        .btn-sgst-status {
            border: none !important;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 5px 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-sgst-status:hover { transform: translateY(-1px); }
        .btn-sgst-status i { font-size: 12px; }
        .sgst-status-primary { background: rgba(16, 112, 224, 0.1); color: #1070e0; }
        .sgst-status-warning { background: rgba(245, 158, 11, 0.12); color: #b45309; }
        .sgst-status-success { background: rgba(34, 197, 94, 0.12); color: #16a34a; }
        .sgst-status-secondary { background: rgba(100, 116, 139, 0.12); color: #475569; }

        .sgst-reply-box {
            margin-top: 12px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px dashed #dbe3f0;
            border-radius: 10px;
            font-size: 13px;
        }
        .sgst-reply-head {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #1070e0;
            margin-bottom: 4px;
        }

        .btn-appreciate {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-appreciate:hover { border-color: #f43f5e; color: #f43f5e; }
        .btn-appreciate.active {
            background: rgba(244, 63, 94, 0.1);
            border-color: rgba(244, 63, 94, 0.3);
            color: #f43f5e;
        }
    </style>

@push('scripts')
    <script>
        document.querySelectorAll('.bc-message-wrap').forEach(function (wrap) {
            const msg = wrap.querySelector('.bc-message');
            const toggle = wrap.querySelector('.bc-message-toggle');
            if (msg.scrollHeight > msg.clientHeight + 2) {
                toggle.classList.remove('d-none');
                toggle.addEventListener('click', function () {
                    const collapsed = msg.classList.toggle('bc-message--clamped');
                    toggle.innerText = collapsed ? 'Show more' : 'Show less';
                });
            }
        });

        let suggestionStatusModal = null;
        function initSuggestionStatusModal() {
            const modalEl = document.getElementById('suggestionStatusModal');
            if (modalEl && window.bootstrap) {
                suggestionStatusModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSuggestionStatusModal);
        } else {
            initSuggestionStatusModal();
        }

        function openSuggestionStatusModal(id, status, reply) {
            document.getElementById('sgstStatusId').value = id;
            document.getElementById('sgstStatusSelect').value = status;
            document.getElementById('sgstReplyText').value = reply || '';
            if (suggestionStatusModal) {
                suggestionStatusModal.show();
            }
        }

        function openSuggestionStatusModalFromButton(btn) {
            openSuggestionStatusModal(
                btn.dataset.suggestionId,
                btn.dataset.suggestionStatus,
                btn.dataset.suggestionReply
            );
        }

        function submitSuggestionStatus() {
            const id = document.getElementById('sgstStatusId').value;
            const status = document.getElementById('sgstStatusSelect').value;
            const reply = document.getElementById('sgstReplyText').value;
            const token = document.querySelector('meta[name="csrf-token"]').content;

            fetch('/suggestions/' + id + '/status', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: status, reply: reply }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        if (suggestionStatusModal) {
                            suggestionStatusModal.hide();
                        }
                        if (window.Toast) {
                            Toast.fire({ icon: 'success', title: 'Suggestion updated' }).then(function () {
                                location.reload();
                            });
                        } else {
                            location.reload();
                        }
                    }
                })
                .catch(function () {
                    if (window.Toast) {
                        Toast.fire({ icon: 'error', title: 'Could not update suggestion' });
                    }
                });
        }

        function toggleSuggestionAppreciation(id, btn) {
            const token = document.querySelector('meta[name="csrf-token"]').content;

            fetch('/suggestions/' + id + '/appreciate', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        btn.classList.toggle('active', data.appreciated);
                    }
                })
                .catch(function () {
                    if (window.Toast) {
                        Toast.fire({ icon: 'error', title: 'Could not update appreciation' });
                    }
                });
        }
    </script>
@endpush
@endsection
