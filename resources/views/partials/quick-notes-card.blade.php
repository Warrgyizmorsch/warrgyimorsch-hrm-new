{{-- Quick Notes: personal reminders (Priority / Meeting) + ad-hoc tasks assigned to you outside any project --}}
<div class="card stretch stretch-full qn-card">
    <div class="card-header hrm-resp-card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title">Quick Notes</h5>
    </div>
    <div class="card-body">
        <form id="quickNoteForm" class="qn-add-form">
            @csrf
            <div class="qn-add-row">
                <input type="text" name="title" id="qnTitle" class="qn-input" placeholder="Add a quick note or reminder…" required maxlength="255" autocomplete="off">
                <div class="qn-type-pills">
                    <button type="button" class="qn-pill qn-pill--priority active" data-type="priority" onclick="qnSelectType(this)">
                        <i class="feather-flag"></i> Priority
                    </button>
                    <button type="button" class="qn-pill qn-pill--meeting" data-type="meeting" onclick="qnSelectType(this)">
                        <i class="feather-users"></i> Meeting
                    </button>
                </div>
                <input type="datetime-local" name="remind_at" id="qnRemindAt" class="qn-time-input d-none">
                <input type="hidden" name="type" id="qnType" value="priority">
                <button type="submit" class="qn-add-btn" title="Add note"><i class="feather-plus"></i></button>
            </div>
        </form>

        <div id="qnList" class="qn-list">
            @if($myAdhocTasks->isNotEmpty())
                <div class="qn-section-label">Assigned to you</div>
                @foreach($myAdhocTasks as $task)
                    <div class="qn-item qn-item--task">
                        <span class="qn-badge qn-badge--task">Task</span>
                        <div class="qn-item-body">
                            <div class="qn-item-title">{{ $task->task_title }}</div>
                            <div class="qn-item-meta">Assigned by {{ $task->creator->name ?? 'Someone' }} @if($task->priority) &middot; {{ $task->priority }} @endif</div>
                        </div>
                        <a href="{{ route('daily-tasks.index') }}" class="qn-item-link" title="Open in Daily Tasks"><i class="feather-arrow-up-right"></i></a>
                    </div>
                @endforeach
                <div class="qn-section-label">Your notes</div>
            @endif

            @forelse($myNotes as $note)
                <div class="qn-item {{ $note->is_completed ? 'qn-item--done' : '' }}" data-note-id="{{ $note->id }}">
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
                @if($myAdhocTasks->isEmpty())
                    <div class="qn-empty" id="qnEmptyState">
                        <i class="feather-file-text"></i>
                        <p>No notes yet. Add a quick reminder above.</p>
                    </div>
                @endif
            @endforelse
        </div>
    </div>
</div>

<style>
    .qn-add-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
    }
    .qn-input {
        flex: 1 1 220px;
        min-width: 160px;
        height: 40px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 12px;
        font-size: 13px;
        outline: none;
    }
    .qn-input:focus { border-color: #1070e0; }
    .qn-type-pills { display: flex; gap: 6px; }
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
        height: 40px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 10px;
        font-size: 12px;
    }
    .qn-add-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: none;
        background: #1070e0;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    .qn-add-btn:hover { background: #0d5fc0; }

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
    .qn-item-meta { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .qn-delete, .qn-item-link {
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
            function qnSelectType(btn) {
                document.querySelectorAll('.qn-pill').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const type = btn.dataset.type;
                document.getElementById('qnType').value = type;
                document.getElementById('qnRemindAt').classList.toggle('d-none', type !== 'meeting');
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('quickNoteForm');
                if (!form) return;

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const titleInput = document.getElementById('qnTitle');
                    const title = titleInput.value.trim();
                    if (!title) return;

                    const type = document.getElementById('qnType').value;
                    const remindAt = document.getElementById('qnRemindAt').value;
                    const token = document.querySelector('meta[name="csrf-token"]').content;

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
                                qnPrependNote(data.note);
                                titleInput.value = '';
                                document.getElementById('qnRemindAt').value = '';
                            }
                        });
                });
            });

            function qnPrependNote(note) {
                const list = document.getElementById('qnList');
                const emptyState = document.getElementById('qnEmptyState');
                if (emptyState) emptyState.remove();

                const div = document.createElement('div');
                div.className = 'qn-item';
                div.dataset.noteId = note.id;

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
                        }
                    });
            }
        </script>
    @endpush
@endonce
