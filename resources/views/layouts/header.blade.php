@php
    $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
    $isAdminNav = in_array($role, ['super_admin', 'manager', 'hr_executive']);
    // Waiting for HR to approve the self-filled profile: only /my-details and logout work, so hide the rest.
    $profileLocked = (bool) auth()->user()->employee?->isProfileLocked();
    $pageTitle = trim($__env->yieldContent('page_title')) ?: match (true) {
        request()->routeIs('dashboard') => 'Home',
        request()->routeIs('employees.*') => 'Employees',
        request()->routeIs('payroll.attendance*', 'payroll.attendace.employee') => 'Attendance History',
        request()->routeIs('payroll.*') => 'Payroll',
        request()->routeIs('daily-tasks.*') => 'Daily Tasks',
        request()->routeIs('leave.*', 'holidays.*') => 'Leave',
        request()->routeIs('projects.*') => 'Projects',
        request()->routeIs('tickets.*') => 'Support',
        request()->routeIs('profile.*') => 'Profile',
        default => 'Home',
    };
    $currentRoute = request()->route()?->getName() ?? '';
@endphp

<header class="nxl-header">
    <div class="zoho-header-stack">
        {{-- Zoho-style primary top bar --}}
        <div class="zoho-topbar">
            <div class="d-flex align-items-center">
                <button type="button" class="zoho-mobile-toggle" id="mobile-collapse" aria-label="Toggle menu">
                    <i class="feather-menu"></i>
                </button>
                <span class="zoho-topbar-badge">HRM</span>
                <ul class="zoho-topbar-nav">
                    @if($profileLocked)
                    <li><a href="{{ route('profile.complete') }}" class="active">Complete your profile</a></li>
                    @else
                    <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Home</a></li>
                    @if($isAdminNav)
                        <li><a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">Employees</a></li>
                        <li><a href="{{ route('payroll.index') }}" class="{{ request()->routeIs('payroll.index') ? 'active' : '' }}">Payroll</a></li>
                        <li><a href="{{ route('payroll.attendance') }}" class="{{ request()->routeIs('payroll.attendance*', 'payroll.attendace.employee') ? 'active' : '' }}">Attendance History</a></li>
                    @endif
                    <li><a href="{{ route('daily-tasks.index') }}" class="{{ request()->routeIs('daily-tasks.*') ? 'active' : '' }}">Tasks</a></li>
                    <li><a href="{{ route('leave.history') }}" class="{{ request()->routeIs('leave.*', 'holidays.*') ? 'active' : '' }}">Leave</a></li>
                    @if($isAdminNav)
                        <li><a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">Projects</a></li>
                    @endif
                    <li><a href="{{ route('tickets.index') }}" class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}">Support</a></li>
                    @endif
                </ul>
            </div>

            <div class="zoho-topbar-actions">
                @unless($profileLocked)
                <button type="button" class="zoho-topbar-icon" data-bs-toggle="modal" data-bs-target="#ticketModal" title="Raise Ticket">
                    <i class="feather-plus"></i>
                </button>

                <button type="button" class="zoho-topbar-icon" data-bs-toggle="modal" data-bs-target="#suggestionModal" title="Suggestion Box">
                    <i class="feather-message-square"></i>
                </button>

                <button type="button" class="zoho-topbar-icon" data-bs-toggle="modal" data-bs-target="#qhNoteModal" title="Quick Note / Task">
                    <i class="feather-edit-3"></i>
                </button>
                @endunless

                @php
                    // Bell contents (also served live by /notifications/feed — see the script at the end).
                    $bell = app(\App\Services\HeaderNotificationService::class)->build(auth()->user());
                    $notifications = $bell['notifications'];
                    $pendingProfileApprovals = $bell['pendingProfileApprovals'];
                    $awaitingLeaves = $bell['awaitingLeaves'];
                    $roleUpper = $bell['roleUpper'];
                    $headerEmployee = auth()->user()->employee_id ? \App\Models\Employee::find(auth()->user()->employee_id) : null;
                    $firstName = explode(' ', auth()->user()->name ?? 'User')[0];
                    $userInitials = collect(explode(' ', trim(auth()->user()->name ?? 'User')))
                        ->filter()
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->take(2)
                        ->join('');
                @endphp

                @unless($profileLocked)
                <button type="button" id="zohoNotifyBell"
                        class="zoho-topbar-icon zoho-topbar-notify"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#zohoNotifyOffcanvas"
                        aria-controls="zohoNotifyOffcanvas"
                        title="Notifications">
                    <i class="feather-bell"></i>
                    @php $bellCount = $bell['count']; @endphp
                    <span class="zoho-notify-badge {{ $bellCount > 0 ? '' : 'd-none' }}" id="zohoNotifyBadge">{{ $bellCount > 9 ? '9+' : $bellCount }}</span>
                </button>
                @endunless

                <button type="button"
                        class="zoho-topbar-profile"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#zohoProfileOffcanvas"
                        aria-controls="zohoProfileOffcanvas"
                        title="Account">
                    @if($headerEmployee && $headerEmployee->photo)
                        <img src="{{ asset('storage/' . $headerEmployee->photo) }}" alt="" class="zoho-topbar-profile-avatar">
                    @else
                        <span class="zoho-topbar-profile-avatar zoho-topbar-profile-avatar--initial">{{ $userInitials ?: 'U' }}</span>
                    @endif
                    <span class="zoho-topbar-profile-meta d-none d-xl-flex">
                        <span class="zoho-topbar-profile-name">{{ $firstName }}</span>
                        <span class="zoho-topbar-profile-role">{{ ucwords(str_replace('_', ' ', auth()->user()->role ?? 'Employee')) }}</span>
                    </span>
                    <i class="feather-chevron-down zoho-topbar-profile-chevron d-none d-md-inline"></i>
                </button>
            </div>
        </div>

        {{-- Sub-header: dashboard only — title + refresh (greeting lives in page content) --}}
        @if(request()->routeIs('dashboard'))
        <div class="zoho-page-header">
            <div class="zoho-page-header-left">
                <h1 class="zoho-page-title">{{ $pageTitle }}</h1>
                <span class="zoho-page-date">{{ now()->format('l, d M Y') }}</span>
            </div>
            <div class="zoho-page-header-right">
                <button type="button" class="zoho-header-btn" onclick="window.location.reload()" title="Refresh dashboard">
                    <i class="feather-refresh-cw"></i>
                    <span class="zoho-header-btn-label">Refresh</span>
                </button>
            </div>
        </div>
        @endif
    </div>

    {{-- Legacy header wrapper kept for theme JS compatibility --}}
    <div class="header-wrapper d-none">
        <div class="header-left d-flex align-items-center gap-4">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse-legacy">
                <div class="hamburger hamburger--arrowturn"><div class="hamburger-box"><div class="hamburger-inner"></div></div></div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-arrow-right"></i></a>
            </div>
        </div>
    </div>
</header>

<div class="modal fade" id="ticketModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('/tickets/store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Raise Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control">
                            <option value="">Select Type</option>
                            <option value="HR">HR</option>
                            <option value="Payroll">Payroll</option>
                            <option value="Attendance">Attendance</option>
                            <option value="Leave">Leave</option>
                            <option value="IT Support">IT Support</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="5" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="suggestionModal" tabindex="-1" aria-labelledby="suggestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('suggestions.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="suggestionModalLabel">
                        <i class="feather-message-square me-1"></i> Suggestion Box
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="suggestionVisibilityNote">Help us improve work culture — share your feedback or suggestions. Your name and designation will only be visible to management.</p>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            @foreach(\App\Models\Suggestion::CATEGORIES as $suggestionCategory)
                                <option value="{{ $suggestionCategory }}" {{ old('category') === $suggestionCategory ? 'selected' : '' }}>{{ $suggestionCategory }}</option>
                            @endforeach
                        </select>
                        @error('category') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Your Suggestion</span>
                            <span class="small text-muted"><span id="suggestionMessageCount">0</span>/5000</span>
                        </label>
                        <textarea name="message" id="suggestionMessage" class="form-control" maxlength="5000" placeholder="What's working well? What could be better?" required style="height: 180px; min-height: 180px; resize: vertical;">{{ old('message') }}</textarea>
                        @error('message') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_anonymous" id="suggestionAnonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }}>
                        <label class="form-check-label" for="suggestionAnonymous">Submit anonymously</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit Suggestion</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="qhNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="qhNoteForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="feather-edit-3 me-1"></i> Add Quick Note or Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="qhFeedback" class="qn-assign-feedback d-none"></div>

                    <input type="text" id="qhTitle" class="qn-input qn-input--full" placeholder="Add a quick note or reminder…" required maxlength="255" autocomplete="off">

                    <div class="qn-type-pills qn-type-pills--modal">
                        <button type="button" class="qn-pill qn-pill--priority active" data-type="priority" onclick="qhSelectType(this)">
                            <i class="feather-flag"></i> Priority
                        </button>
                        <button type="button" class="qn-pill qn-pill--meeting" data-type="meeting" onclick="qhSelectType(this)">
                            <i class="feather-users"></i> Meeting
                        </button>
                        <input type="datetime-local" id="qhRemindAt" class="qn-time-input d-none">
                    </div>
                    <input type="hidden" id="qhType" value="priority">

                    <div class="qn-assign-row" id="qhAssignRow">
                        <span class="qn-assign-label"><i class="feather-user-plus"></i> Assign to</span>
                        <select id="qhAssignTo" class="qn-assign-select" onchange="qhAssignChanged(this)">
                            <option value="">Just me (personal note)</option>
                            @php $qhEmployeesByDept = \App\Models\Employee::active()->where('id', '!=', auth()->user()->employee_id)->with('departmentRef')->orderBy('name')->get()->groupBy(fn($e) => $e->departmentRef->name ?? 'Other'); @endphp
                            @foreach($qhEmployeesByDept as $qhDepartment => $qhDeptEmployees)
                                <optgroup label="{{ $qhDepartment }}">
                                    @foreach($qhDeptEmployees as $qhEmp)
                                        <option value="{{ $qhEmp->id }}" data-name="{{ $qhEmp->name }}">{{ $qhEmp->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div id="qhAssignHint" class="qn-assign-hint d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="qhSubmitBtn">Add Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function initSuggestionMessageCounter() {
        var suggestionMessage = document.getElementById('suggestionMessage');
        var suggestionMessageCount = document.getElementById('suggestionMessageCount');
        if (suggestionMessage && suggestionMessageCount) {
            var updateSuggestionCount = function () {
                suggestionMessageCount.innerText = suggestionMessage.value.length;
            };
            suggestionMessage.addEventListener('input', updateSuggestionCount);
            updateSuggestionCount();
        }

        var suggestionAnonymous = document.getElementById('suggestionAnonymous');
        var suggestionVisibilityNote = document.getElementById('suggestionVisibilityNote');
        if (suggestionAnonymous && suggestionVisibilityNote) {
            var updateVisibilityNote = function () {
                suggestionVisibilityNote.textContent = suggestionAnonymous.checked
                    ? 'Help us improve work culture — share your feedback or suggestions. This suggestion will be submitted anonymously; management will not see your name.'
                    : 'Help us improve work culture — share your feedback or suggestions. Your name and designation will only be visible to management.';
            };
            suggestionAnonymous.addEventListener('change', updateVisibilityNote);
            updateVisibilityNote();
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSuggestionMessageCounter);
    } else {
        initSuggestionMessageCounter();
    }
</script>

<style>
    /* Shared with resources/views/partials/quick-notes-card.blade.php — same classes,
       duplicated here so the header's Quick Note modal works on pages that don't
       include the dashboard's Quick Notes card. */
    .qn-input {
        width: 100%;
        height: 40px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 12px;
        font-size: 13px;
        outline: none;
        margin-bottom: 12px;
    }
    .qn-input:focus { border-color: #1070e0; }
    .qn-type-pills { display: flex; align-items: center; gap: 6px; }
    .qn-type-pills--modal { margin-bottom: 12px; flex-wrap: wrap; }
    .qn-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        border-radius: 20px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .qn-pill.active.qn-pill--priority { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); color: #ef4444; }
    .qn-pill.active.qn-pill--meeting { background: rgba(16, 112, 224, 0.1); border-color: rgba(16, 112, 224, 0.3); color: #1070e0; }
    .qn-time-input {
        height: 38px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 10px;
        font-size: 12px;
        flex: 1 1 180px;
    }
    .qn-assign-row {
        display: flex;
        align-items: center;
        gap: 8px;
        height: 40px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 10px;
        padding: 0 4px 0 12px;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }
    .qn-assign-row.qn-assign-row--active {
        background: rgba(16, 112, 224, 0.06);
        border-color: rgba(16, 112, 224, 0.3);
    }
    .qn-assign-label {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .qn-assign-label i { font-size: 13px; }
    .qn-assign-row--active .qn-assign-label { color: #1070e0; }
    .qn-assign-select {
        flex: 1 1 auto;
        height: 100%;
        border: none;
        background: transparent;
        padding: 0 8px;
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        outline: none;
        max-width: 100%;
        cursor: pointer;
    }
    .qn-assign-hint {
        font-size: 11px;
        color: #1070e0;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .qn-assign-feedback {
        font-size: 12px;
        color: #16a34a;
        background: rgba(22, 163, 74, 0.08);
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 10px;
        font-weight: 600;
    }
</style>

<script>
    function qhSelectType(btn) {
        document.querySelectorAll('#qhNoteModal .qn-pill').forEach(function (p) { p.classList.remove('active'); });
        btn.classList.add('active');
        var type = btn.dataset.type;
        document.getElementById('qhType').value = type;
        document.getElementById('qhRemindAt').classList.toggle('d-none', type !== 'meeting');
    }

    function qhAssignChanged(select) {
        var row = document.getElementById('qhAssignRow');
        var hint = document.getElementById('qhAssignHint');
        var selectedOption = select.options[select.selectedIndex];
        var isAssigned = !!select.value;

        row.classList.toggle('qn-assign-row--active', isAssigned);

        if (isAssigned) {
            hint.classList.remove('d-none');
            hint.textContent = 'This will be added as a task, visible only to ' + selectedOption.dataset.name + '.';
        } else {
            hint.classList.add('d-none');
        }

        document.getElementById('qhSubmitBtn').textContent = isAssigned ? 'Assign Task' : 'Add Note';
    }

    function qhResetForm() {
        var form = document.getElementById('qhNoteForm');
        if (!form) return;
        form.reset();
        document.querySelectorAll('#qhNoteModal .qn-pill').forEach(function (p) { p.classList.remove('active'); });
        document.querySelector('#qhNoteModal .qn-pill--priority').classList.add('active');
        document.getElementById('qhType').value = 'priority';
        document.getElementById('qhRemindAt').classList.add('d-none');
        document.getElementById('qhAssignRow').classList.remove('qn-assign-row--active');
        document.getElementById('qhAssignHint').classList.add('d-none');
        document.getElementById('qhSubmitBtn').textContent = 'Add Note';
    }

    function qhShowFeedback(message, isError) {
        var el = document.getElementById('qhFeedback');
        el.textContent = message;
        el.style.color = isError ? '#ef4444' : '#16a34a';
        el.style.background = isError ? 'rgba(239, 68, 68, 0.08)' : 'rgba(22, 163, 74, 0.08)';
        el.classList.remove('d-none');
        setTimeout(function () { el.classList.add('d-none'); }, 4000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('qhNoteForm');
        if (!form) return;

        var modalEl = document.getElementById('qhNoteModal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', qhResetForm);
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var titleInput = document.getElementById('qhTitle');
            var title = titleInput.value.trim();
            if (!title) return;

            var type = document.getElementById('qhType').value;
            var remindAt = document.getElementById('qhRemindAt').value;
            var assignTo = document.getElementById('qhAssignTo').value;
            var token = document.querySelector('meta[name="csrf-token"]').content;
            var modalInstance = bootstrap.Modal.getInstance(modalEl);

            if (assignTo) {
                fetch('/daily-tasks/quick-assign', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        task_title: title,
                        employee_id: assignTo,
                        priority: type === 'priority' ? 'Hard' : 'Medium',
                        remind_at: remindAt || null,
                    }),
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            if (modalInstance) modalInstance.hide();
                            qhShowFeedback(data.message);
                        } else if (data.error) {
                            qhShowFeedback(data.error, true);
                        }
                    });
                return;
            }

            fetch('/notes', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ title: title, type: type, remind_at: remindAt || null }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        if (modalInstance) modalInstance.hide();
                        qhShowFeedback('Note added.');
                    }
                });
        });
    });
</script>

@auth
    @if(!session('suggestion_box_prompted') || $errors->has('category') || $errors->has('message'))
        @php
            session(['suggestion_box_prompted' => true]);
        @endphp
        <script>
            function showSuggestionBoxModal() {
                var suggestionModalEl = document.getElementById('suggestionModal');
                if (suggestionModalEl && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(suggestionModalEl).show();
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showSuggestionBoxModal);
            } else {
                showSuggestionBoxModal();
            }
        </script>
    @endif
@endauth

{{-- Notifications drawer (Zoho People style) --}}
<x-ui.drawer
    id="zohoNotifyOffcanvas"
    title="Notifications"
    group="header"
    class="zoho-header-offcanvas zoho-notify-offcanvas border-0"
    body-class="zoho-notify-offcanvas-body p-0"
    footer-class="zoho-offcanvas-foot">
    <div class="zoho-notify-prefs">
        <span>Alerts</span>
        <span class="zoho-notify-prefs__btns">
            <button type="button" class="zoho-notify-pref" id="zohoPrefSound" data-allow-repeat><i class="feather-volume-2"></i> Sound on</button>
            <button type="button" class="zoho-notify-pref" id="zohoPrefDesktop" data-allow-repeat><i class="feather-monitor"></i> Desktop off</button>
        </span>
    </div>
    <div id="zohoNotifyList">
        @include('layouts.partials.notification-items')
    </div>

    @if(count($notifications) > 0)
        <x-slot name="footer">
            <a href="{{ route('notifications.index') }}" class="zoho-offcanvas-foot-link">View all notifications</a>
        </x-slot>
    @endif
</x-ui.drawer>

{{-- Live bell: refreshes the count and list without reloading the page, with optional
     sound and desktop (Windows/macOS) notifications. --}}
<style>
    @keyframes zohoBellRing {
        0%, 100% { transform: rotate(0); }
        15% { transform: rotate(14deg); }
        30% { transform: rotate(-12deg); }
        45% { transform: rotate(9deg); }
        60% { transform: rotate(-6deg); }
        75% { transform: rotate(3deg); }
    }
    #zohoNotifyBell.is-ringing .feather-bell { display: inline-block; animation: zohoBellRing .9s ease-in-out 2; transform-origin: 50% 0; }
    #zohoNotifyBell.is-ringing .zoho-notify-badge { animation: zohoBellRing .9s ease-in-out 1; }
    .zoho-notify-prefs { display: flex; gap: 8px; align-items: center; justify-content: space-between; padding: 10px 16px; border-bottom: 1px solid #eef1f5; background: #fafbfc; font-size: 12px; color: #475569; }
    .zoho-notify-prefs__btns { display: flex; gap: 6px; }
    .zoho-notify-pref { border: 1px solid #e2e8f0; background: #fff; border-radius: 999px; padding: 3px 10px; font-size: 12px; color: #475569; display: inline-flex; align-items: center; gap: 5px; }
    .zoho-notify-pref.is-on { border-color: #1070e0; color: #1070e0; background: #eff6ff; }
    .zoho-notify-pref:disabled { opacity: .55; cursor: not-allowed; }
</style>
<script>
(function () {
    if (@json($profileLocked)) return; // feed/push routes are blocked until the profile is approved
    var FEED_URL = @json(route('notifications.feed'));
    var ICON_URL = @json(asset('favicon.ico'));
    var PUSH = {
        key: @json(route('push.key')),
        subscribe: @json(route('push.subscribe')),
        unsubscribe: @json(route('push.unsubscribe')),
        worker: @json(asset('sw.js')),
        csrf: @json(csrf_token()),
        user: @json(auth()->id()),
    };
    var pushActive = false; // true once this browser is subscribed to real push
    var POLL_VISIBLE_MS = 20000;  // tab in front
    var POLL_HIDDEN_MS = 60000;   // tab in background — still checks, so desktop alerts arrive
    var PREF_SOUND = 'hrmNotifySound';
    var PREF_DESKTOP = 'hrmNotifyDesktop';
    var knownKeys = null;
    var lastHtml = null;
    var timer = null;
    var inFlight = false;

    // ---- preferences (per browser) ----
    function pref(name, fallback) {
        try { var v = localStorage.getItem(name); return v === null ? fallback : v === '1'; } catch (e) { return fallback; }
    }
    function setPref(name, on) {
        try { localStorage.setItem(name, on ? '1' : '0'); } catch (e) { /* private mode */ }
    }
    function soundOn() { return pref(PREF_SOUND, true); }
    function desktopSupported() { return 'Notification' in window; }
    function desktopOn() { return desktopSupported() && Notification.permission === 'granted' && pref(PREF_DESKTOP, true); }

    // ---- sound: a short two-tone chime made in the browser (no audio file needed) ----
    var audioCtx = null;
    function unlockAudio() {
        // Browsers only allow sound after the user has clicked/typed on the page once.
        try {
            if (!audioCtx) { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
            if (audioCtx.state === 'suspended') { audioCtx.resume(); }
        } catch (e) { audioCtx = null; }
    }
    ['click', 'keydown', 'touchstart'].forEach(function (ev) {
        document.addEventListener(ev, unlockAudio, { once: true, capture: true });
    });
    function chime() {
        if (!soundOn() || !audioCtx || audioCtx.state !== 'running') return;
        // Several open tabs: only one of them plays the sound.
        try {
            var last = parseInt(localStorage.getItem('hrmNotifyLastChime') || '0', 10);
            if (Date.now() - last < 4000) return;
            localStorage.setItem('hrmNotifyLastChime', String(Date.now()));
        } catch (e) { /* ignore */ }
        var now = audioCtx.currentTime;
        [[880, 0], [1318.5, 0.16]].forEach(function (n) {
            var osc = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.value = n[0];
            gain.gain.setValueAtTime(0.0001, now + n[1]);
            gain.gain.exponentialRampToValueAtTime(0.25, now + n[1] + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + n[1] + 0.45);
            osc.connect(gain).connect(audioCtx.destination);
            osc.start(now + n[1]);
            osc.stop(now + n[1] + 0.5);
        });
    }

    // ---- desktop notifications ----
    function desktopNotify(items) {
        // With real push on, the service worker shows system notifications (even with the tab closed).
        if (!desktopOn() || pushActive) return;
        items.slice(0, 3).forEach(function (item) {
            try {
                var n = new Notification(item.title, {
                    body: item.body || '',
                    icon: ICON_URL,
                    tag: 'hrm-' + item.key, // same item in several tabs → shown once
                });
                n.onclick = function () {
                    window.focus();
                    if (item.url) { window.location.href = item.url; }
                    n.close();
                };
            } catch (e) { /* some browsers only allow this from a service worker */ }
        });
        if (items.length > 3) {
            try { new Notification(items.length + ' new notifications', { icon: ICON_URL, tag: 'hrm-many' }); } catch (e) {}
        }
    }

    // ---- real push (service worker) ----
    function pushSupported() {
        return 'serviceWorker' in navigator && 'PushManager' in window && window.isSecureContext;
    }

    function urlBase64ToUint8Array(base64) {
        var padding = '='.repeat((4 - base64.length % 4) % 4);
        var raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
        var out = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
        return out;
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': PUSH.csrf },
            body: JSON.stringify(body),
        });
    }

    // Subscribe this browser (or refresh its subscription — also re-links a shared PC to whoever is logged in).
    function enablePush() {
        if (!pushSupported()) return Promise.resolve(false);
        return fetch(PUSH.key, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (cfg) {
                if (!cfg || !cfg.enabled || !cfg.publicKey) return false;
                return navigator.serviceWorker.register(PUSH.worker)
                    .then(function () { return navigator.serviceWorker.ready; })
                    .then(function (reg) {
                        return reg.pushManager.getSubscription().then(function (existing) {
                            return existing || reg.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlBase64ToUint8Array(cfg.publicKey),
                            });
                        });
                    })
                    .then(function (sub) {
                        // Tell the server once per tab session (and again if the user or device changes).
                        var mark = PUSH.user + '|' + sub.endpoint;
                        try { if (sessionStorage.getItem('hrmPushSynced') === mark) { return { ok: true }; } } catch (e) {}
                        return postJson(PUSH.subscribe, sub.toJSON()).then(function (res) {
                            if (res.ok) { try { sessionStorage.setItem('hrmPushSynced', mark); } catch (e) {} }
                            return res;
                        });
                    })
                    .then(function (res) { pushActive = !!res.ok; return pushActive; });
            })
            .catch(function (e) { console.warn('Push not enabled:', e); pushActive = false; return false; });
    }

    function disablePush() {
        pushActive = false;
        if (!pushSupported()) return Promise.resolve();
        return navigator.serviceWorker.getRegistration(PUSH.worker)
            .then(function (reg) { return reg ? reg.pushManager.getSubscription() : null; })
            .then(function (sub) {
                if (!sub) return;
                try { sessionStorage.removeItem('hrmPushSynced'); } catch (e) {}
                return postJson(PUSH.unsubscribe, { endpoint: sub.endpoint }).then(function () { return sub.unsubscribe(); });
            })
            .catch(function () {});
    }

    if ('serviceWorker' in navigator) {
        // A push arrived: refresh the bell straight away instead of waiting for the next check.
        navigator.serviceWorker.addEventListener('message', function (e) {
            if (e.data && e.data.type === 'hrm-push') refresh();
        });
    }

    // ---- bell UI ----
    function setBadge(count) {
        var badge = document.getElementById('zohoNotifyBadge');
        if (!badge) return;
        badge.textContent = count > 9 ? '9+' : String(count);
        badge.classList.toggle('d-none', !count);
        var base = document.title.replace(/^\(\d+\+?\)\s*/, '');
        document.title = count ? '(' + (count > 9 ? '9+' : count) + ') ' + base : base;
    }

    function ring() {
        var bell = document.getElementById('zohoNotifyBell');
        if (!bell) return;
        bell.classList.remove('is-ringing');
        void bell.offsetWidth;
        bell.classList.add('is-ringing');
    }

    function announce(items) {
        if (!items.length) return;
        ring();
        chime();
        if (document.visibilityState === 'hidden') {
            desktopNotify(items);
        } else if (typeof Toast !== 'undefined') {
            Toast.fire({ icon: 'info', title: items.length === 1 ? items[0].title : items.length + ' new notifications', timer: 5000 });
        }
    }

    function refresh() {
        if (inFlight) return;
        inFlight = true;
        fetch(FEED_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
            .then(function (res) {
                var type = res.headers.get('content-type') || '';
                return res.ok && type.indexOf('application/json') !== -1 ? res.json() : null;
            })
            .then(function (data) {
                if (!data) return;
                setBadge(data.count);
                if (data.html !== lastHtml) {
                    var list = document.getElementById('zohoNotifyList');
                    if (list) list.innerHTML = data.html;
                    lastHtml = data.html;
                }
                var items = data.items || [];
                if (knownKeys) {
                    announce(items.filter(function (i) { return knownKeys.indexOf(i.key) === -1; }));
                }
                knownKeys = items.map(function (i) { return i.key; });
            })
            .catch(function () { /* offline — try again on the next tick */ })
            .then(function () { inFlight = false; });
    }

    function schedule() {
        clearInterval(timer);
        timer = setInterval(refresh, document.visibilityState === 'hidden' ? POLL_HIDDEN_MS : POLL_VISIBLE_MS);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') { refresh(); }
        schedule();
    });

    // ---- alert preferences bar in the bell drawer ----
    function renderPrefs() {
        var soundBtn = document.getElementById('zohoPrefSound');
        var deskBtn = document.getElementById('zohoPrefDesktop');
        if (soundBtn) {
            soundBtn.classList.toggle('is-on', soundOn());
            soundBtn.innerHTML = '<i class="feather-' + (soundOn() ? 'volume-2' : 'volume-x') + '"></i> Sound ' + (soundOn() ? 'on' : 'off');
        }
        if (deskBtn) {
            if (!desktopSupported()) {
                deskBtn.disabled = true;
                deskBtn.textContent = 'Desktop alerts not supported';
            } else if (Notification.permission === 'denied') {
                deskBtn.disabled = true;
                deskBtn.classList.remove('is-on');
                deskBtn.innerHTML = '<i class="feather-monitor"></i> Blocked in browser settings';
                deskBtn.title = 'Allow notifications for this site in the browser (padlock icon next to the address).';
            } else {
                deskBtn.disabled = false;
                deskBtn.classList.toggle('is-on', desktopOn());
                deskBtn.innerHTML = '<i class="feather-monitor"></i> Desktop ' + (desktopOn() ? 'on' : 'off');
            }
        }
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('#zohoPrefSound')) {
            setPref(PREF_SOUND, !soundOn());
            unlockAudio();
            renderPrefs();
            chime(); // preview
        } else if (e.target.closest('#zohoPrefDesktop')) {
            if (!desktopSupported()) return;
            if (Notification.permission === 'granted') {
                var turnOn = !desktopOn();
                setPref(PREF_DESKTOP, turnOn);
                renderPrefs();
                if (turnOn) { enablePush(); } else { disablePush(); }
            } else {
                Notification.requestPermission().then(function (p) {
                    setPref(PREF_DESKTOP, p === 'granted');
                    renderPrefs();
                    if (p === 'granted') {
                        enablePush().then(function (viaPush) {
                            try {
                                new Notification('Desktop alerts are on', {
                                    body: viaPush
                                        ? 'You will be notified even when the HRM is closed.'
                                        : 'You will be notified while the HRM is open in a tab.',
                                    icon: ICON_URL, tag: 'hrm-welcome',
                                });
                            } catch (err) {}
                        });
                    }
                });
            }
        }
    });

    document.addEventListener('DOMContentLoaded', renderPrefs);
    renderPrefs();
    if (desktopOn()) {
        enablePush(); // keep this device's subscription linked to the logged-in user
    }

    // The first call only records what is already shown (no alert for existing items).
    setTimeout(function () { refresh(); schedule(); }, 3000);
})();
</script>


{{-- Profile drawer (Zoho People style) --}}
<x-ui.drawer
    id="zohoProfileOffcanvas"
    group="header"
    class="zoho-header-offcanvas zoho-profile-offcanvas border-0"
    body-class="zoho-profile-offcanvas-body p-0">
    <x-slot name="header">
        <div class="zoho-profile-offcanvas-hero">
            <button type="button" class="zoho-offcanvas-close zoho-offcanvas-close--light" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="feather-x"></i>
            </button>
            <div class="zoho-profile-hero-user">
                @if($headerEmployee && $headerEmployee->photo)
                    <img src="{{ asset('storage/' . $headerEmployee->photo) }}" alt="" class="zoho-profile-hero-avatar">
                @else
                    <span class="zoho-profile-hero-avatar zoho-profile-hero-avatar--initial">{{ $userInitials ?: 'U' }}</span>
                @endif
                <div class="zoho-profile-hero-info">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->email }}</span>
                    <span class="zoho-profile-hero-meta">User ID : {{ auth()->user()->id }}</span>
                    @if($headerEmployee && $headerEmployee->employee_code)
                        <span class="zoho-profile-hero-meta">Employee ID : {{ $headerEmployee->employee_code }}</span>
                    @endif
                    <span class="zoho-profile-hero-meta">Role : {{ ucwords(str_replace('_', ' ', auth()->user()->role ?? 'Employee')) }}</span>
                </div>
            </div>
            <div class="zoho-profile-hero-actions">
                <a href="{{ route('profile.show') }}" class="zoho-profile-hero-action">
                    <i class="feather-user"></i> My Account
                </a>
                <form method="POST" action="{{ route('logout') }}" class="zoho-profile-hero-logout-form">
                    @csrf
                    <button type="submit" class="zoho-profile-hero-action zoho-profile-hero-action--signout">
                        <i class="feather-power"></i> Sign Out
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <nav class="zoho-profile-nav">
        <a href="{{ route('profile.show') }}" class="zoho-profile-nav-link">
            <i class="feather-user"></i><span>Profile Details</span><i class="feather-chevron-right"></i>
        </a>
        <a href="{{ route('profile.leave-balance') }}" class="zoho-profile-nav-link">
            <i class="feather-box"></i><span>Leave Balance</span><i class="feather-chevron-right"></i>
        </a>
        <a href="{{ route('profile.leave-history') }}" class="zoho-profile-nav-link">
            <i class="feather-list"></i><span>Leave History</span><i class="feather-chevron-right"></i>
        </a>
        <a href="{{ route('attendance-history') }}" class="zoho-profile-nav-link">
            <i class="feather-calendar"></i><span>Attendance History</span><i class="feather-chevron-right"></i>
        </a>
        <a href="{{ route('tickets.index') }}" class="zoho-profile-nav-link">
            <i class="feather-life-buoy"></i><span>Help &amp; Support</span><i class="feather-chevron-right"></i>
        </a>
    </nav>
</x-ui.drawer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nav = document.querySelector('.nxl-navigation');
        const toggleBtn = document.getElementById('mobile-collapse');
        const closeBtn = document.getElementById('zohoSidebarClose');
        const MOBILE_BP = 1024;

        let overlay = document.querySelector('.zoho-sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'zoho-sidebar-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlay);
        }

        function isMobile() {
            return window.innerWidth <= MOBILE_BP;
        }

        function closeSidebar() {
            if (!nav) return;
            nav.classList.remove('mob-navigation-active');
            overlay.classList.remove('active');
            document.body.classList.remove('zoho-sidebar-open');
        }

        function openSidebar() {
            if (!nav) return;
            nav.classList.add('mob-navigation-active');
            overlay.classList.add('active');
            document.body.classList.add('zoho-sidebar-open');
        }

        function toggleDesktopCollapse() {
            const collapsed = document.documentElement.classList.toggle('minimenu');
            try { localStorage.setItem('zoho-sidebar-minimenu', collapsed ? '1' : '0'); } catch (e) {}
        }

        function toggleSidebar() {
            if (isMobile()) {
                if (!nav) return;
                nav.classList.contains('mob-navigation-active') ? closeSidebar() : openSidebar();
            } else {
                toggleDesktopCollapse();
            }
        }

        toggleBtn?.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            toggleSidebar();
        });

        closeBtn?.addEventListener('click', function (e) {
            e.preventDefault();
            closeSidebar();
        });

        overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });

        nav?.querySelectorAll('.nxl-link[href]').forEach(function (link) {
            const href = link.getAttribute('href');
            if (!href || href.indexOf('javascript') === 0) return;
            link.addEventListener('click', function () {
                if (isMobile()) closeSidebar();
            });
        });

        window.addEventListener('resize', function () {
            if (!isMobile()) {
                closeSidebar();
            } else {
                document.documentElement.classList.remove('minimenu');
            }
        });

        // Legacy theme hook (hidden fallback button)
        const legacyBtn = document.getElementById('mobile-collapse-legacy');
        if (legacyBtn && toggleBtn) {
            legacyBtn.addEventListener('click', toggleSidebar);
        }
    });
</script>

<style>
    html.modal-open, body.modal-open,
    body.modal-open .nxl-container, body.modal-open .nxl-header,
    body.modal-open .nxl-navigation, body.modal-open .main-content {
        filter: none !important;
        backdrop-filter: none !important;
    }
    .modal-backdrop { background-color: rgba(0, 0, 0, 0.5) !important; }
</style>
