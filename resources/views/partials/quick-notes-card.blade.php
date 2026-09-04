{{-- Quick Notes: personal reminders (Priority / Meeting) + ad-hoc tasks assigned to you outside any project --}}
<div class="card stretch stretch-full qn-card">
    <div class="card-header hrm-resp-card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title">Quick Notes</h5>
        <button type="button" class="qn-header-add-btn" data-bs-toggle="modal" data-bs-target="#qnAddModal" title="Add note or task">
            <i class="feather-plus"></i>
        </button>
    </div>
    <div class="card-body">
        <div id="qnAssignFeedback" class="qn-assign-feedback d-none"></div>

        <div id="qnList" class="qn-list">
            @if($myAdhocTasks->isNotEmpty())
                <div class="qn-section-label">Assigned to you</div>
                @foreach($myAdhocTasks as $task)
                    <div class="qn-item qn-item--task">
                        <span class="qn-badge qn-badge--task">Task</span>
                        <div class="qn-item-body">
                            <div class="qn-item-title">{{ $task->task_title }}</div>
                            <div class="qn-item-meta">
                                Assigned by {{ $task->creator->name ?? 'Someone' }} @if($task->priority) &middot; {{ $task->priority }} @endif
                                <span class="qn-status qn-status--{{ \Illuminate\Support\Str::slug($task->status) }}">{{ $task->status }}</span>
                            </div>
                        </div>
                        <button type="button" class="qn-status-btn" data-task-id="{{ $task->id }}" title="Update status" onclick="qnOpenStatusModal({{ $task->id }}, '{{ $task->status }}')"><i class="feather-check-square"></i></button>
                        <a href="{{ route('daily-tasks.index') }}" class="qn-item-link" title="Open in Daily Tasks"><i class="feather-arrow-up-right"></i></a>
                    </div>
                @endforeach
            @endif

            @if($tasksIAssigned->isNotEmpty())
                <div class="qn-section-label">Assigned by you</div>
                @foreach($tasksIAssigned as $task)
                    <div class="qn-item qn-item--task">
                        <span class="qn-badge qn-badge--task">Task</span>
                        <div class="qn-item-body">
                            <div class="qn-item-title">{{ $task->task_title }}</div>
                            <div class="qn-item-meta">
                                To {{ $task->employee->name ?? 'Someone' }} @if($task->priority) &middot; {{ $task->priority }} @endif
                                <span class="qn-status qn-status--{{ \Illuminate\Support\Str::slug($task->status) }}">{{ $task->status }}</span>
                            </div>
                            @if($task->latestStatusHistory && $task->latestStatusHistory->comment)
                                <div class="qn-item-feedback">
                                    <i class="feather-message-circle"></i> {{ $task->latestStatusHistory->comment }}
                                </div>
                            @endif
                        </div>
                        <button type="button" class="qn-status-btn" data-task-id="{{ $task->id }}" title="Update status" onclick="qnOpenStatusModal({{ $task->id }}, '{{ $task->status }}')"><i class="feather-check-square"></i></button>
                        <a href="{{ route('daily-tasks.index') }}" class="qn-item-link" title="Open in Daily Tasks"><i class="feather-arrow-up-right"></i></a>
                    </div>
                @endforeach
            @endif

            @if($myAdhocTasks->isNotEmpty() || $tasksIAssigned->isNotEmpty())
                <div class="qn-section-label">Your notes</div>
            @endif

            @forelse($myNotes as $note)
                <div class="qn-item {{ $note->is_completed ? 'qn-item--done' : '' }}" data-note-id="{{ $note->id }}" data-type="{{ $note->type }}" @if($note->remind_at) data-remind-iso="{{ $note->remind_at->toIso8601String() }}" @endif>
                    <input type="checkbox" class="qn-check" {{ $note->is_completed ? 'checked' : '' }} onchange="qnToggle({{ $note->id }}, this)">
                    <span class="qn-badge qn-badge--{{ $note->type }}">{{ ucfirst($note->type) }}</span>
                    <div class="qn-item-body">
                        <div class="qn-item-title">{{ $note->title }}</div>
                        @if($note->remind_at)
                            <div class="qn-item-meta"><i class="feather-clock"></i> {{ $note->remind_at->format('d M, h:i A') }}</div>
                        @endif
                    </div>
                    <button type="button" class="qn-delete" onclick="qnDelete({{ $note->id }}, this)" title="Delete"><i class="feather-x"></i></button>
                </div>
            @empty
                @if($myAdhocTasks->isEmpty() && $tasksIAssigned->isEmpty())
                    <div class="qn-empty" id="qnEmptyState">
                        <i class="feather-file-text"></i>
                        <p>No notes yet. Click + to add a quick reminder or assign a task.</p>
                    </div>
                @endif
            @endforelse
        </div>
    </div>
</div>

<div class="modal fade" id="qnAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content qn-modal-content">
            <form id="quickNoteForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Quick Note or Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="title" id="qnTitle" class="qn-input qn-input--full" placeholder="Add a quick note or reminder…" required maxlength="255" autocomplete="off">

                    <div class="qn-type-pills qn-type-pills--modal">
                        <button type="button" class="qn-pill qn-pill--priority active" data-type="priority" onclick="qnSelectType(this)">
                            <i class="feather-flag"></i> Priority
                        </button>
                        <button type="button" class="qn-pill qn-pill--meeting" data-type="meeting" onclick="qnSelectType(this)">
                            <i class="feather-users"></i> Meeting
                        </button>
                        <input type="datetime-local" name="remind_at" id="qnRemindAt" class="qn-time-input d-none">
                    </div>
                    <input type="hidden" name="type" id="qnType" value="priority">

                    <div class="qn-assign-row" id="qnAssignRow">
                        <span class="qn-assign-label"><i class="feather-user-plus"></i> Assign to</span>
                        <select id="qnAssignTo" class="qn-assign-select" onchange="qnAssignChanged(this)">
                            <option value="">Just me (personal note)</option>
                            @php $employeesByDept = ($employees ?? collect())->where('id', '!=', auth()->user()->employee_id)->groupBy(fn($e) => $e->departmentRef->name ?? 'Other'); @endphp
                            @foreach($employeesByDept as $department => $deptEmployees)
                                <optgroup label="{{ $department }}">
                                    @foreach($deptEmployees as $emp)
                                        <option value="{{ $emp->id }}" data-name="{{ $emp->name }}">{{ $emp->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div id="qnAssignHint" class="qn-assign-hint d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="qnSubmitBtn">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="qnStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content qn-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Task Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="qnStatusTaskId">
                <label class="qn-form-label">Status</label>
                <select id="qnStatusSelect" class="qn-input qn-input--full qn-status-select">
                    <option value="Pending">Pending</option>
                    <option value="In Process">In Process</option>
                    <option value="Completed">Completed</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Review">Review</option>
                    <option value="Rework">Rework</option>
                </select>
                <label class="qn-form-label">Feedback (optional)</label>
                <textarea id="qnStatusComment" class="qn-input qn-input--full qn-status-comment" rows="3" placeholder="Add a comment for the record…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="qnSubmitStatus()">Save Status</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qnReminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content qn-modal-content">
            <div class="modal-body qn-reminder-body">
                <div class="qn-reminder-icon"><i class="feather-bell"></i></div>
                <h5 class="qn-reminder-title">Meeting Reminder</h5>
                <p class="qn-reminder-text" id="qnReminderModalText"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<style>
    .qn-header-add-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: #1070e0;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    .qn-header-add-btn:hover { background: #0d5fc0; }

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

    .qn-list { max-height: 320px; overflow-y: auto; }
    .qn-section-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #94a3b8;
        margin: 10px 0 6px;
    }
    .qn-section-label:first-child { margin-top: 0; }
    .qn-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 4px;
        border-bottom: 1px solid #f1f5f9;
    }
    .qn-item:last-child { border-bottom: none; }
    .qn-item--done .qn-item-title { text-decoration: line-through; color: #94a3b8; }
    .qn-check { margin-top: 3px; flex-shrink: 0; cursor: pointer; }
    .qn-badge {
        flex-shrink: 0;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 4px 8px;
        border-radius: 12px;
        margin-top: 1px;
    }
    .qn-badge--priority { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .qn-badge--meeting { background: rgba(16, 112, 224, 0.1); color: #1070e0; }
    .qn-badge--task { background: rgba(245, 158, 11, 0.12); color: #b45309; }
    .qn-item-body { flex: 1; min-width: 0; }
    .qn-item-title { font-size: 13px; font-weight: 600; color: #1e293b; word-break: break-word; }
    .qn-item-meta { font-size: 11px; color: #94a3b8; margin-top: 2px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .qn-item-feedback {
        font-size: 11px;
        color: #475569;
        background: #f8fafc;
        border-left: 2px solid #1070e0;
        padding: 4px 8px;
        border-radius: 4px;
        margin-top: 5px;
        display: flex;
        align-items: flex-start;
        gap: 5px;
    }
    .qn-item-feedback i { font-size: 11px; margin-top: 2px; flex-shrink: 0; }
    .qn-status {
        display: inline-block;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 2px 7px;
        border-radius: 10px;
    }
    .qn-status--pending { background: rgba(148, 163, 184, 0.15); color: #64748b; }
    .qn-status--in-process { background: rgba(16, 112, 224, 0.12); color: #1070e0; }
    .qn-status--completed { background: rgba(22, 163, 74, 0.12); color: #16a34a; }
    .qn-status--on-hold { background: rgba(245, 158, 11, 0.12); color: #b45309; }
    .qn-status--review { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }
    .qn-status--rework { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
    .qn-status--reassign { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
    .qn-delete, .qn-item-link, .qn-status-btn {
        flex-shrink: 0;
        width: 26px;
        height: 26px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
    }
    .qn-delete:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .qn-item-link:hover { background: rgba(16, 112, 224, 0.1); color: #1070e0; }
    .qn-status-btn:hover { background: rgba(22, 163, 74, 0.1); color: #16a34a; }
    .qn-form-label { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; }
    .qn-status-select { cursor: pointer; }
    .qn-status-comment { height: auto; padding: 10px 12px; resize: vertical; }

    .qn-reminder-body { text-align: center; padding: 32px 24px 8px; }
    .qn-reminder-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 16px;
        border-radius: 50%;
        background: rgba(16, 112, 224, 0.1);
        color: #1070e0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        animation: qn-reminder-pulse 1.2s ease-in-out infinite;
    }
    @keyframes qn-reminder-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.08); }
    }
    .qn-reminder-title { font-weight: 700; color: #1e293b; margin-bottom: 6px; }
    .qn-reminder-text { font-size: 14px; color: #64748b; margin-bottom: 0; }
    .qn-empty {
        text-align: center;
        padding: 24px 12px;
        color: #94a3b8;
    }
    .qn-empty i { font-size: 22px; display: block; margin-bottom: 8px; }
    .qn-empty p { font-size: 13px; margin: 0; }
</style>

@once
    @push('scripts')
        <script>
            const qnReminderTimeouts = {};

            // Short two-tone beep via Web Audio — no external sound asset needed.
            function qnBeep() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    [0, 220].forEach(function (delayMs) {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.value = 880;
                        gain.gain.value = 0.2;
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        const start = ctx.currentTime + delayMs / 1000;
                        osc.start(start);
                        osc.stop(start + 0.15);
                    });
                } catch (e) { /* Web Audio unsupported — silently skip the beep */ }
            }

            // Checkpoints (minutes before the meeting) that each trigger their own popup.
            const QN_REMINDER_CHECKPOINTS = [10, 5];

            const qnReminderQueue = [];
            let qnReminderModalShowing = false;

            // Dedupe so refreshing the dashboard doesn't replay a reminder already shown.
            function qnReminderAlreadyFired(id, minutesBefore) {
                try {
                    return localStorage.getItem('qn_reminder_fired_' + id + '_' + minutesBefore) === '1';
                } catch (e) {
                    return false;
                }
            }

            function qnMarkReminderFired(id, minutesBefore) {
                try {
                    localStorage.setItem('qn_reminder_fired_' + id + '_' + minutesBefore, '1');
                } catch (e) { /* ignore */ }
            }

            function qnFireReminder(id, title, minutesBefore) {
                if (qnReminderAlreadyFired(id, minutesBefore)) return;
                qnMarkReminderFired(id, minutesBefore);

                qnBeep();

                if (window.Notification && Notification.permission === 'granted') {
                    try {
                        new Notification('Meeting in ' + minutesBefore + ' minutes', { body: title });
                    } catch (e) { /* ignore */ }
                }

                qnReminderQueue.push({ title: title, minutesBefore: minutesBefore });
                qnProcessReminderQueue();
            }

            // Popups are queued so two reminders firing close together don't fight over the
            // same modal — the next one shows as soon as the current one is dismissed.
            function qnProcessReminderQueue() {
                if (qnReminderModalShowing || qnReminderQueue.length === 0) return;

                const next = qnReminderQueue.shift();
                qnReminderModalShowing = true;

                const minuteWord = next.minutesBefore === 1 ? 'minute' : 'minutes';
                document.getElementById('qnReminderModalText').textContent =
                    '"' + next.title + '" starts in ' + next.minutesBefore + ' ' + minuteWord + '.';

                bootstrap.Modal.getOrCreateInstance(document.getElementById('qnReminderModal')).show();
            }

            // Schedules a popup at each checkpoint (10 and 5 minutes before) for a "meeting"
            // note. Only fires while this tab stays open — there is no push/service-worker
            // backend to ring the reminder if the browser is closed. If the page loads (or
            // reloads) after a checkpoint's alert time has already passed — but before the
            // meeting itself starts — that checkpoint fires immediately as a catch-up, rather
            // than staying silent for the rest of the wait.
            function qnScheduleReminder(id, title, remindAtIso) {
                qnCancelReminder(id);

                if (!remindAtIso) return;

                const remindTime = new Date(remindAtIso).getTime();
                if (isNaN(remindTime)) return;

                const now = Date.now();

                // The meeting itself has already started/passed — nothing left to remind about.
                if (now >= remindTime) return;

                qnReminderTimeouts[id] = {};

                QN_REMINDER_CHECKPOINTS.forEach(function (minutesBefore) {
                    if (qnReminderAlreadyFired(id, minutesBefore)) return;

                    const alertTime = remindTime - minutesBefore * 60 * 1000;
                    const delay = Math.max(alertTime - now, 0);

                    // Still skip a checkpoint so far out that catching up on it would be pointless
                    // (the tab won't realistically stay open that long anyway).
                    if (delay > 24 * 60 * 60 * 1000) return;

                    qnReminderTimeouts[id][minutesBefore] = setTimeout(function () {
                        qnFireReminder(id, title, minutesBefore);
                    }, delay);
                });
            }

            function qnCancelReminder(id) {
                const timeouts = qnReminderTimeouts[id];
                if (!timeouts) return;
                Object.values(timeouts).forEach(function (t) { clearTimeout(t); });
                delete qnReminderTimeouts[id];
            }

            function qnAssignChanged(select) {
                const row = document.getElementById('qnAssignRow');
                const hint = document.getElementById('qnAssignHint');
                const selectedOption = select.options[select.selectedIndex];
                const isAssigned = !!select.value;

                row.classList.toggle('qn-assign-row--active', isAssigned);

                if (isAssigned) {
                    hint.classList.remove('d-none');
                    hint.textContent = 'This will be added as a task, visible only to ' + selectedOption.dataset.name + '.';
                } else {
                    hint.classList.add('d-none');
                }

                document.getElementById('qnSubmitBtn').textContent = isAssigned ? 'Assign Task' : 'Add Note';
            }

            function qnSelectType(btn) {
                document.querySelectorAll('.qn-pill').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const type = btn.dataset.type;
                document.getElementById('qnType').value = type;
                document.getElementById('qnRemindAt').classList.toggle('d-none', type !== 'meeting');
            }

            function qnResetForm() {
                const form = document.getElementById('quickNoteForm');
                if (!form) return;
                form.reset();
                document.querySelectorAll('.qn-pill').forEach(p => p.classList.remove('active'));
                document.querySelector('.qn-pill--priority').classList.add('active');
                document.getElementById('qnType').value = 'priority';
                document.getElementById('qnRemindAt').classList.add('d-none');
                document.getElementById('qnAssignRow').classList.remove('qn-assign-row--active');
                document.getElementById('qnAssignHint').classList.add('d-none');
                document.getElementById('qnSubmitBtn').textContent = 'Add Note';
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('quickNoteForm');
                if (!form) return;

                // Move modals to <body> so they aren't nested inside an animated
                // ancestor (e.g. ".saas-animate-in" leaves a `transform` applied
                // after its entrance animation, which breaks position:fixed modals).
                ['qnAddModal', 'qnStatusModal', 'qnReminderModal'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el && el.parentElement !== document.body) {
                        document.body.appendChild(el);
                    }
                });

                const modalEl = document.getElementById('qnAddModal');
                if (modalEl) {
                    modalEl.addEventListener('hidden.bs.modal', qnResetForm);
                }

                document.getElementById('qnReminderModal')?.addEventListener('hidden.bs.modal', function () {
                    qnReminderModalShowing = false;
                    qnProcessReminderQueue();
                });

                if (window.Notification && Notification.permission === 'default') {
                    Notification.requestPermission().catch(function () {});
                }

                document.querySelectorAll('.qn-item[data-remind-iso]').forEach(function (item) {
                    if (item.classList.contains('qn-item--done')) return;
                    const titleEl = item.querySelector('.qn-item-title');
                    qnScheduleReminder(item.dataset.noteId, titleEl ? titleEl.textContent : 'Meeting', item.dataset.remindIso);
                });

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const titleInput = document.getElementById('qnTitle');
                    const title = titleInput.value.trim();
                    if (!title) return;

                    const type = document.getElementById('qnType').value;
                    const remindAt = document.getElementById('qnRemindAt').value;
                    const assignTo = document.getElementById('qnAssignTo').value;
                    const token = document.querySelector('meta[name="csrf-token"]').content;
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('qnAddModal'));

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
                                    qnShowAssignFeedback(data.message);
                                } else if (data.error) {
                                    qnShowAssignFeedback(data.error, true);
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
                                qnPrependNote(data.note);
                            }
                        });
                });
            });

            function qnShowAssignFeedback(message, isError) {
                const el = document.getElementById('qnAssignFeedback');
                el.textContent = message;
                el.style.color = isError ? '#ef4444' : '#16a34a';
                el.style.background = isError ? 'rgba(239, 68, 68, 0.08)' : 'rgba(22, 163, 74, 0.08)';
                el.classList.remove('d-none');
                setTimeout(function () { el.classList.add('d-none'); }, 4000);
            }

            function qnPrependNote(note) {
                const list = document.getElementById('qnList');
                const emptyState = document.getElementById('qnEmptyState');
                if (emptyState) emptyState.remove();

                const div = document.createElement('div');
                div.className = 'qn-item';
                div.dataset.noteId = note.id;
                div.dataset.type = note.type;
                if (note.remind_at_iso) {
                    div.dataset.remindIso = note.remind_at_iso;
                }

                const check = document.createElement('input');
                check.type = 'checkbox';
                check.className = 'qn-check';
                check.addEventListener('change', function () { qnToggle(note.id, check); });

                const badge = document.createElement('span');
                badge.className = 'qn-badge qn-badge--' + note.type;
                badge.textContent = note.type.charAt(0).toUpperCase() + note.type.slice(1);

                const body = document.createElement('div');
                body.className = 'qn-item-body';

                const titleEl = document.createElement('div');
                titleEl.className = 'qn-item-title';
                titleEl.textContent = note.title;
                body.appendChild(titleEl);

                if (note.remind_at) {
                    const meta = document.createElement('div');
                    meta.className = 'qn-item-meta';
                    meta.textContent = note.remind_at;
                    body.appendChild(meta);
                }

                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'qn-delete';
                del.title = 'Delete';
                del.innerHTML = '<i class="feather-x"></i>';
                del.addEventListener('click', function () { qnDelete(note.id, del); });

                div.appendChild(check);
                div.appendChild(badge);
                div.appendChild(body);
                div.appendChild(del);

                const firstPersonalItem = list.querySelector('.qn-item:not(.qn-item--task)');
                if (firstPersonalItem) {
                    list.insertBefore(div, firstPersonalItem);
                } else {
                    list.appendChild(div);
                }

                if (window.feather) { feather.replace(); }

                if (note.type === 'meeting' && note.remind_at_iso) {
                    qnScheduleReminder(note.id, note.title, note.remind_at_iso);
                }
            }

            function qnToggle(id, checkbox) {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                fetch('/notes/' + id + '/toggle', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            checkbox.closest('.qn-item').classList.toggle('qn-item--done', data.is_completed);
                            if (data.is_completed) {
                                qnCancelReminder(id);
                            }
                        }
                    });
            }

            function qnOpenStatusModal(taskId, currentStatus) {
                document.getElementById('qnStatusTaskId').value = taskId;
                document.getElementById('qnStatusSelect').value = currentStatus;
                document.getElementById('qnStatusComment').value = '';
                const modalEl = document.getElementById('qnStatusModal');
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            function qnSubmitStatus() {
                const taskId = document.getElementById('qnStatusTaskId').value;
                const status = document.getElementById('qnStatusSelect').value;
                const comment = document.getElementById('qnStatusComment').value;
                const token = document.querySelector('meta[name="csrf-token"]').content;

                fetch('/daily-tasks/' + taskId + '/status', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: status, comment: comment }),
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            const modalEl = document.getElementById('qnStatusModal');
                            bootstrap.Modal.getInstance(modalEl)?.hide();
                            qnShowAssignFeedback('Status updated to ' + status + '.');

                            const btn = document.querySelector('.qn-status-btn[data-task-id="' + taskId + '"]');
                            const item = btn ? btn.closest('.qn-item') : null;
                            if (item) {
                                if (status === 'Completed') {
                                    item.remove();
                                } else {
                                    const badge = item.querySelector('.qn-status');
                                    if (badge) {
                                        badge.className = 'qn-status qn-status--' + status.toLowerCase().replace(/\s+/g, '-');
                                        badge.textContent = status;
                                    }
                                }
                            }
                        } else if (data.error) {
                            qnShowAssignFeedback(data.error, true);
                        }
                    });
            }

            function qnDelete(id, btn) {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                fetch('/notes/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            btn.closest('.qn-item').remove();
                            qnCancelReminder(id);
                            QN_REMINDER_CHECKPOINTS.forEach(function (minutesBefore) {
                                try { localStorage.removeItem('qn_reminder_fired_' + id + '_' + minutesBefore); } catch (e) {}
                            });
                        }
                    });
            }
        </script>
    @endpush
@endonce
