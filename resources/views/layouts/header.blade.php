@php
    $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
    $isAdminNav = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
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
                </ul>
            </div>

            <div class="zoho-topbar-actions">
                <button type="button" class="zoho-topbar-icon" data-bs-toggle="modal" data-bs-target="#ticketModal" title="Raise Ticket">
                    <i class="feather-plus"></i>
                </button>

                <button type="button" class="zoho-topbar-icon" data-bs-toggle="modal" data-bs-target="#suggestionModal" title="Suggestion Box">
                    <i class="feather-message-square"></i>
                </button>

                <button type="button" class="zoho-topbar-icon" data-bs-toggle="modal" data-bs-target="#qhNoteModal" title="Quick Note / Task">
                    <i class="feather-edit-3"></i>
                </button>

                @php
                    $notifications = collect();
                    $roleUpper = strtoupper(auth()->user()->role);

                    $todayDate = now();
                    $today = $todayDate->format('m-d');
                    $celebrations = \App\Models\Employee::where(function ($q) use ($todayDate) {
                            $q->where(function ($q2) use ($todayDate) {
                                $q2->whereMonth('date_of_birth', $todayDate->month)
                                    ->whereDay('date_of_birth', $todayDate->day);
                            })->orWhere(function ($q2) use ($todayDate) {
                                $q2->whereMonth('date_of_joining', $todayDate->month)
                                    ->whereDay('date_of_joining', $todayDate->day);
                            });
                        })
                        ->get()
                        ->map(function ($emp) use ($today) {
                            $isBirthday = \Carbon\Carbon::parse($emp->date_of_birth)->format('m-d') == $today;
                            $type = $isBirthday ? 'Birthday' : 'Work Anniversary';
                            $years = (int) \Carbon\Carbon::parse($emp->date_of_joining)->diffInYears(now());

                            return (object) [
                                'id' => 'celebration-' . $emp->id,
                                'type' => 'celebration',
                                'event_type' => $type,
                                'employee' => $emp,
                                'years' => $years,
                                'message' => $isBirthday
                                    ? "Today is {$emp->name}'s Birthday! 🎂"
                                    : "Today is {$emp->name}'s {$years}" . ($years == 1 ? 'st' : ($years == 2 ? 'nd' : ($years == 3 ? 'rd' : 'th'))) . " Work Anniversary! 🏆",
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        });

                    if ($roleUpper == 'ADMIN' || $roleUpper == 'SUPER_ADMIN' || $roleUpper == 'SUPER ADMIN') {
                        $leaveNotifications = \App\Models\LeaveApplication::with('employee')->latest()->get();
                        $payrollNotifications = \App\Models\Payroll::with('employee')
                            ->whereNotNull('remarks')
                            ->where('remarks', '!=', '')
                            ->latest()
                            ->get();

                        $notifications = $celebrations
                            ->concat($leaveNotifications)
                            ->concat($payrollNotifications)
                            ->sortByDesc(function ($item) {
                                return isset($item->type) && $item->type == 'celebration'
                                    ? now()
                                    : (isset($item->remarks) ? $item->updated_at : $item->created_at);
                            })->take(5);
                    } else {
                        $myLeaves = \App\Models\LeaveApplication::where('employee_id', auth()->user()->employee_id)
                            ->whereIn('status', ['approved', 'rejected', 'Approved', 'Rejected'])
                            ->where('updated_at', '>=', now()->subDays(3))
                            ->latest()
                            ->get();

                        $notifications = $celebrations
                            ->concat($myLeaves)
                            ->sortByDesc('updated_at')
                            ->take(5);
                    }
                    $headerEmployee = auth()->user()->employee_id ? \App\Models\Employee::find(auth()->user()->employee_id) : null;
                    $firstName = explode(' ', auth()->user()->name ?? 'User')[0];
                    $userInitials = collect(explode(' ', trim(auth()->user()->name ?? 'User')))
                        ->filter()
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->take(2)
                        ->join('');
                @endphp

                <button type="button"
                        class="zoho-topbar-icon zoho-topbar-notify"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#zohoNotifyOffcanvas"
                        aria-controls="zohoNotifyOffcanvas"
                        title="Notifications">
                    <i class="feather-bell"></i>
                    @if(count($notifications) > 0)
                        <span class="zoho-notify-badge">{{ count($notifications) > 9 ? '9+' : count($notifications) }}</span>
                    @endif
                </button>

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
                    <p class="text-muted small mb-3">Help us improve work culture — share your feedback or suggestions. Your name and designation will only be visible to management.</p>
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
                            @php $qhEmployeesByDept = \App\Models\Employee::active()->where('id', '!=', auth()->user()->employee_id)->orderBy('name')->get()->groupBy(fn($e) => $e->department ?: 'Other'); @endphp
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
    @forelse($notifications as $item)
        @php
            $isCelebration = isset($item->type) && $item->type == 'celebration';
            $isPayroll = !$isCelebration && isset($item->remarks);
            $emp = (in_array($roleUpper, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']) || $isCelebration) ? $item->employee : auth()->user()->employee;
            $photo = ($emp && $emp->photo) ? asset('storage/' . $emp->photo) : null;
            $notifyType = $isCelebration ? 'celebration' : ($isPayroll ? 'payroll' : 'leave');
            $notifyIcon = $isCelebration ? 'gift' : ($isPayroll ? 'message-circle' : 'calendar');
            $notifyUrl = $isCelebration ? route('employees.employeeDays') : ($isPayroll ? route('payroll.index') : route('leave.history'));
        @endphp
        <a href="{{ $notifyUrl }}" class="zoho-notify-item zoho-notify-item--{{ $notifyType }}">
            <span class="zoho-notify-item-icon"><i class="feather-{{ $notifyIcon }}"></i></span>
            <span class="zoho-notify-item-body">
                @if($isCelebration)
                    <span class="zoho-notify-item-title">{{ $item->message }}</span>
                    <span class="zoho-notify-item-meta">Happening today</span>
                @elseif(in_array($roleUpper, ['ADMIN', 'SUPER_ADMIN', 'SUPER ADMIN']))
                    @if($isPayroll)
                        <span class="zoho-notify-item-title">{{ $item->employee->name ?? 'Someone' }} commented on payroll</span>
                        <span class="zoho-notify-item-desc">"{{ Str::limit($item->remarks, 120) }}"</span>
                        <span class="zoho-notify-item-meta">{{ $item->updated_at->format('d M, h:i A') }}</span>
                    @else
                        <span class="zoho-notify-item-title">{{ $emp->name ?? 'Someone' }} applied for leave</span>
                        <span class="zoho-notify-item-desc">{{ $item->leave_type }} leave request</span>
                        <span class="zoho-notify-item-meta">{{ $item->created_at->format('d M, h:i A') }}</span>
                    @endif
                @else
                    <span class="zoho-notify-item-title">Leave {{ strtoupper($item->status) }}</span>
                    <span class="zoho-notify-item-desc">Your {{ $item->leave_type }} application was {{ $item->status }}.</span>
                    <span class="zoho-notify-item-meta">{{ $item->updated_at->format('d M, h:i A') }}</span>
                @endif
            </span>
            @if($photo)
                <img src="{{ $photo }}" alt="" class="zoho-notify-item-avatar">
            @else
                <span class="zoho-notify-item-avatar zoho-notify-item-avatar--initial">{{ strtoupper(substr($emp->name ?? '?', 0, 1)) }}</span>
            @endif
        </a>
    @empty
        <div class="zoho-offcanvas-empty">
            <div class="zoho-offcanvas-empty-art">
                <i class="feather-bell"></i>
            </div>
            <p class="zoho-offcanvas-empty-title">No Notification found</p>
            <p class="zoho-offcanvas-empty-desc">You're all caught up. New alerts will appear here.</p>
        </div>
    @endforelse

    @if(count($notifications) > 0)
        <x-slot name="footer">
            <a href="{{ route('notifications.index') }}" class="zoho-offcanvas-foot-link">View all notifications</a>
        </x-slot>
    @endif
</x-ui.drawer>

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
