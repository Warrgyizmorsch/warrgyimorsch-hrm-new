<div class="pd-card">
    <div class="pd-card-head pd-card-head--split">
        <div>
            <h2>Weekly SOP checklist</h2>
            <p class="pd-card-subtitle">Recurring checklist for this project — resets each week, history kept</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="pd-checklist-view-toggle" role="group">
                <button type="button" class="pd-checklist-view-btn active" id="pcViewListBtn">List</button>
                <button type="button" class="pd-checklist-view-btn" id="pcViewCalendarBtn">Calendar</button>
            </div>
            <input type="date" id="pcWeekPicker" class="form-control form-control-sm" style="width: auto;">
            <button type="button" class="zoho-btn-primary btn-sm" id="pcAddItemBtn">
                <i class="feather-plus"></i> Add item
            </button>
        </div>
    </div>
    <div class="pd-card-body">
        <div id="pcWeekBanner" class="pd-checklist-week-banner d-none"></div>

        <div id="pcAddItemRow" class="d-none mb-3">
            <div class="d-flex gap-2 flex-wrap">
                <input type="text" id="pcNewLabel" class="form-control form-control-sm flex-grow-1" placeholder="e.g. Deploy to staging verified" maxlength="255" style="min-width: 220px;">
                <select id="pcNewAssignee" class="form-select form-select-sm d-none" style="width: auto;">
                    <option value="">Unassigned</option>
                </select>
                <select id="pcNewDueDay" class="form-select form-select-sm d-none" style="width: auto;">
                    <option value="">No due day</option>
                    <option value="1">Due Mon</option>
                    <option value="2">Due Tue</option>
                    <option value="3">Due Wed</option>
                    <option value="4">Due Thu</option>
                    <option value="5">Due Fri</option>
                    <option value="6">Due Sat</option>
                    <option value="7">Due Sun</option>
                </select>
                <button type="button" class="zoho-btn-primary btn-sm" id="pcSaveItemBtn">Save</button>
                <button type="button" class="zoho-btn-outline btn-sm" id="pcCancelItemBtn">Cancel</button>
            </div>
        </div>

        <div id="pcChecklistList" data-project-slug="{{ $project->slug }}"></div>
        <div id="pcCalendarView" class="pd-checklist-calendar d-none"></div>
        <p id="pcEmptyState" class="text-muted d-none mb-0">No checklist items yet. Add the first SOP step above.</p>
        <p id="pcLoadingState" class="text-muted mb-0">Loading checklist…</p>
    </div>
</div>

{{-- Edit item --}}
<div class="modal fade" id="pcEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="pcEditForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">Edit checklist item</h5>
                <button type="button" class="btn-close" data-pc-close aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Label <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control" maxlength="255" required>
                </div>
                <div class="row g-2" id="pcEditAssignRow">
                    <div class="col-7">
                        <label class="form-label">Assigned to</label>
                        <select name="assigned_to" class="form-select"></select>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Due day</label>
                        <select name="due_weekday" class="form-select">
                            <option value="">No due day</option>
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                            <option value="6">Saturday</option>
                            <option value="7">Sunday</option>
                        </select>
                    </div>
                </div>
                <div class="pc-form-error text-danger small mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="zoho-btn-outline btn-sm" data-pc-close>Cancel</button>
                <button type="submit" class="zoho-btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Note for this week --}}
<div class="modal fade" id="pcNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="pcNoteForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">Note for this week</h5>
                <button type="button" class="btn-close" data-pc-close aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2" id="pcNoteItemLabel"></p>
                <textarea name="note" class="form-control" rows="4" maxlength="2000" placeholder="e.g. Waiting on client credentials"></textarea>
                <div class="pc-form-error text-danger small mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="zoho-btn-outline btn-sm" data-pc-close>Cancel</button>
                <button type="submit" class="zoho-btn-primary btn-sm">Save note</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const indexUrl = {{ Js::from(route('projects.checklist.index', $project)) }};
    const templatesUrl = {{ Js::from(route('projects.checklist.templates.store', $project)) }};
    const csrfToken = {{ Js::from(csrf_token()) }};
    const weekdayLabels = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' };

    const list = document.getElementById('pcChecklistList');
    const calendarView = document.getElementById('pcCalendarView');
    const emptyState = document.getElementById('pcEmptyState');
    const loadingState = document.getElementById('pcLoadingState');
    const weekPicker = document.getElementById('pcWeekPicker');
    const weekBanner = document.getElementById('pcWeekBanner');
    const addBtn = document.getElementById('pcAddItemBtn');
    const addRow = document.getElementById('pcAddItemRow');
    const newLabelInput = document.getElementById('pcNewLabel');
    const newAssigneeSelect = document.getElementById('pcNewAssignee');
    const newDueDaySelect = document.getElementById('pcNewDueDay');
    const saveItemBtn = document.getElementById('pcSaveItemBtn');
    const cancelItemBtn = document.getElementById('pcCancelItemBtn');
    const viewListBtn = document.getElementById('pcViewListBtn');
    const viewCalendarBtn = document.getElementById('pcViewCalendarBtn');

    let loaded = false;
    let isCurrentWeek = true;
    let canAssign = false;
    let lastAssignableEmployees = [];
    let lastData = null;
    let viewMode = 'list';

    function templateUrl(id, suffix) {
        return `/projects/checklist/templates/${id}${suffix || ''}`;
    }

    function renderItem(item, compact) {
        const row = document.createElement('div');
        row.className = 'pd-checklist-item' + (isCurrentWeek ? '' : ' pd-checklist-item--readonly') + (compact ? ' pd-checklist-item--compact' : '');
        row.dataset.templateId = item.id;

        const canToggleThis = isCurrentWeek && item.can_toggle !== false; // server enforces the same rule
        const canEditThis = isCurrentWeek && item.can_edit;

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'pd-checklist-check';
        checkbox.checked = item.is_done;
        checkbox.disabled = !canToggleThis;
        checkbox.addEventListener('change', () => toggleItem(item.id, checkbox, noteEl.textContent.trim()));

        const mainCol = document.createElement('div');
        mainCol.className = 'pd-checklist-main';

        const labelRow = document.createElement('div');
        labelRow.className = 'pd-checklist-label-row';

        const label = document.createElement('span');
        label.className = 'pd-checklist-label' + (item.is_done ? ' pd-checklist-label--done' : '');
        label.textContent = item.label;
        labelRow.appendChild(label);

        if (item.assigned_to_name) {
            const assignedTag = document.createElement('span');
            assignedTag.className = 'pd-checklist-tag pd-checklist-tag--assignee';
            assignedTag.textContent = item.assigned_to_name;
            labelRow.appendChild(assignedTag);
        }

        if (item.due_weekday_label && !compact) {
            const dueTag = document.createElement('span');
            dueTag.className = 'pd-checklist-tag';
            dueTag.textContent = 'Due ' + item.due_weekday_label;
            labelRow.appendChild(dueTag);
        }

        if (item.is_overdue) {
            const overdueTag = document.createElement('span');
            overdueTag.className = 'pd-checklist-tag pd-checklist-tag--overdue';
            overdueTag.textContent = 'Overdue';
            labelRow.appendChild(overdueTag);
        }

        mainCol.appendChild(labelRow);

        const noteEl = document.createElement('div');
        noteEl.className = 'pd-checklist-note' + (item.note ? '' : ' d-none');
        noteEl.textContent = item.note || '';
        mainCol.appendChild(noteEl);

        const meta = document.createElement('small');
        meta.className = 'text-muted pd-checklist-meta';
        meta.textContent = item.completed_by_name ? `by ${item.completed_by_name}` : '';
        mainCol.appendChild(meta);

        const noteBtn = document.createElement('button');
        noteBtn.type = 'button';
        noteBtn.className = 'pd-checklist-note-btn';
        noteBtn.title = 'Add/edit note';
        noteBtn.innerHTML = '<i class="feather-message-square"></i>';
        noteBtn.disabled = !canToggleThis;
        noteBtn.addEventListener('click', () => openNoteModal(item, checkbox, noteEl.textContent));

        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'pd-checklist-edit';
        editBtn.title = 'Edit';
        editBtn.innerHTML = '<i class="feather-edit-2"></i>';
        editBtn.addEventListener('click', () => editItem(item, label));

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'pd-checklist-remove';
        removeBtn.title = 'Delete';
        removeBtn.innerHTML = '<i class="feather-x"></i>';
        removeBtn.addEventListener('click', () => deleteItem(item.id));

        row.append(checkbox, mainCol, noteBtn);
        if (canEditThis) {
            row.append(editBtn, removeBtn);
        }
        return row;
    }

    function populateAssigneeSelects(employees) {
        [newAssigneeSelect].forEach(select => {
            select.innerHTML = '<option value="">Unassigned</option>';
            employees.forEach(emp => {
                const opt = document.createElement('option');
                opt.value = emp.id;
                opt.textContent = emp.name;
                select.appendChild(opt);
            });
        });
    }

    function renderList(data) {
        list.innerHTML = '';
        data.items.forEach(item => list.appendChild(renderItem(item)));
    }

    function renderCalendar(data) {
        calendarView.innerHTML = '';

        const weekStartDate = new Date(data.week_start + 'T00:00:00');
        const todayKey = new Date().toDateString();

        const byDay = { 1: [], 2: [], 3: [], 4: [], 5: [], 6: [], 7: [], anytime: [] };
        data.items.forEach(item => {
            byDay[item.due_weekday || 'anytime'].push(item);
        });

        [1, 2, 3, 4, 5, 6, 7, 'anytime'].forEach(dayKey => {
            const col = document.createElement('div');
            col.className = 'pd-checklist-cal-col';

            const colDate = dayKey !== 'anytime'
                ? new Date(weekStartDate.getTime() + (dayKey - 1) * 86400000)
                : null;

            if (colDate && colDate.toDateString() === todayKey) {
                col.classList.add('pd-checklist-cal-col--today');
            }

            const head = document.createElement('div');
            head.className = 'pd-checklist-cal-head';
            head.textContent = dayKey === 'anytime'
                ? 'Anytime'
                : `${weekdayLabels[dayKey]} ${colDate.getDate()}/${colDate.getMonth() + 1}`;
            col.appendChild(head);

            const body = document.createElement('div');
            body.className = 'pd-checklist-cal-body';
            const items = byDay[dayKey];
            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'pd-checklist-cal-empty';
                empty.textContent = '—';
                body.appendChild(empty);
            } else {
                items.forEach(item => body.appendChild(renderItem(item, true)));
            }
            col.appendChild(body);

            calendarView.appendChild(col);
        });
    }

    function render(data) {
        lastData = data;
        isCurrentWeek = data.is_current_week;
        canAssign = data.can_assign;
        weekPicker.value = data.week_start;

        newAssigneeSelect.classList.toggle('d-none', !canAssign);
        newDueDaySelect.classList.toggle('d-none', !canAssign);
        lastAssignableEmployees = data.assignable_employees || [];
        if (canAssign) populateAssigneeSelects(lastAssignableEmployees);

        weekBanner.classList.toggle('d-none', isCurrentWeek);
        if (!isCurrentWeek) {
            weekBanner.textContent = 'Viewing a past week — read only.';
        }

        addBtn.classList.toggle('d-none', !isCurrentWeek);

        if (data.items.length === 0) {
            emptyState.classList.remove('d-none');
            list.innerHTML = '';
            calendarView.innerHTML = '';
        } else {
            emptyState.classList.add('d-none');
            if (viewMode === 'calendar') {
                renderCalendar(data);
            } else {
                renderList(data);
            }
        }
        loadingState.classList.add('d-none');
    }

    function setViewMode(mode) {
        viewMode = mode;
        viewListBtn.classList.toggle('active', mode === 'list');
        viewCalendarBtn.classList.toggle('active', mode === 'calendar');
        list.classList.toggle('d-none', mode !== 'list');
        calendarView.classList.toggle('d-none', mode !== 'calendar');
        if (lastData) render(lastData);
    }

    viewListBtn.addEventListener('click', () => setViewMode('list'));
    viewCalendarBtn.addEventListener('click', () => setViewMode('calendar'));

    function loadChecklist(weekStart) {
        const url = weekStart ? `${indexUrl}?week=${weekStart}` : indexUrl;
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(render)
            .catch(() => { loadingState.textContent = 'Failed to load checklist.'; });
    }

    function toggleItem(id, checkbox, note) {
        fetch(templateUrl(id, '/toggle'), {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ is_done: checkbox.checked, note: note || null })
        })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok || data.error) {
                checkbox.checked = !checkbox.checked;
                toast(data.error || data.message || 'Could not update this item.', 'error');
                return;
            }
            loadChecklist(weekPicker.value);
        })
        .catch(() => loadChecklist(weekPicker.value));
    }

    // ---- modals (replace the old browser prompt()/confirm() dialogs) ----
    // Modals live inside a tab pane; move them to <body> so the pane can't hide or clip them.
    ['pcEditModal', 'pcNoteModal'].forEach(id => { const n = document.getElementById(id); if (n && n.parentNode !== document.body) document.body.appendChild(n); });

    // Resolved when the dialog opens (not at parse time): Bootstrap/jQuery load at the end of the page.
    function modal(node) {
        function impl() {
            if (window.bootstrap && bootstrap.Modal) {
                const inst = bootstrap.Modal.getOrCreateInstance(node);
                return { show: () => inst.show(), hide: () => inst.hide() };
            }
            if (window.jQuery && jQuery.fn.modal) {
                return { show: () => jQuery(node).modal('show'), hide: () => jQuery(node).modal('hide') };
            }
            return {
                show: () => { node.classList.add('show'); node.style.display = 'block'; },
                hide: () => { node.classList.remove('show'); node.style.display = 'none'; },
            };
        }
        return { show: () => impl().show(), hide: () => impl().hide() };
    }
    const editModalEl = document.getElementById('pcEditModal');
    const noteModalEl = document.getElementById('pcNoteModal');
    const editModal = modal(editModalEl);
    const noteModal = modal(noteModalEl);
    const editForm = document.getElementById('pcEditForm');
    const noteForm = document.getElementById('pcNoteForm');
    editModalEl.querySelectorAll('[data-pc-close]').forEach(b => b.addEventListener('click', () => editModal.hide()));
    noteModalEl.querySelectorAll('[data-pc-close]').forEach(b => b.addEventListener('click', () => noteModal.hide()));
    let editingItem = null;
    let notingItem = null;

    function formError(form, message) {
        const box = form.querySelector('.pc-form-error');
        box.textContent = message || '';
        box.classList.toggle('d-none', !message);
    }

    function toast(message, icon) {
        if (window.Swal) {
            Swal.fire({ toast: true, position: 'top-end', icon: icon || 'success', title: message, showConfirmButton: false, timer: 2500 });
        }
    }

    function sendJson(url, method, body) {
        return fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
        }).then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(first || data.error || data.message || 'Something went wrong.');
            }
            return data;
        });
    }

    function editItem(item) {
        editingItem = item;
        formError(editForm, '');
        editForm.label.value = item.label;
        document.getElementById('pcEditAssignRow').classList.toggle('d-none', !canAssign);
        if (canAssign) {
            editForm.assigned_to.innerHTML = '<option value="">Unassigned</option>';
            lastAssignableEmployees.forEach(emp => {
                const opt = document.createElement('option');
                opt.value = emp.id;
                opt.textContent = emp.name;
                editForm.assigned_to.appendChild(opt);
            });
            editForm.assigned_to.value = item.assigned_to || '';
            editForm.due_weekday.value = item.due_weekday || '';
        }
        editModal.show();
        setTimeout(() => editForm.label.focus(), 300);
    }

    editForm.addEventListener('submit', e => {
        e.preventDefault();
        const label = editForm.label.value.trim();
        if (!label) return formError(editForm, 'Label is required.');

        const body = { label };
        if (canAssign) {
            body.assigned_to = editForm.assigned_to.value || null;
            body.due_weekday = editForm.due_weekday.value || null;
        }

        sendJson(templateUrl(editingItem.id), 'PATCH', body)
            .then(res => { editModal.hide(); toast(res.success); loadChecklist(weekPicker.value); })
            .catch(err => formError(editForm, err.message));
    });

    function openNoteModal(item, checkbox, currentNote) {
        notingItem = { item, checkbox };
        formError(noteForm, '');
        document.getElementById('pcNoteItemLabel').textContent = item.label;
        noteForm.note.value = currentNote || '';
        noteModal.show();
        setTimeout(() => noteForm.note.focus(), 300);
    }

    noteForm.addEventListener('submit', e => {
        e.preventDefault();
        const { item, checkbox } = notingItem;
        sendJson(templateUrl(item.id, '/toggle'), 'PATCH', { is_done: checkbox.checked, note: noteForm.note.value.trim() || null })
            .then(res => { noteModal.hide(); toast('Note saved.'); loadChecklist(weekPicker.value); })
            .catch(err => formError(noteForm, err.message));
    });

    function deleteItem(id) {
        const ask = window.Swal
            ? Swal.fire({
                title: 'Delete this checklist item?',
                text: 'This also removes its history for past weeks.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#dc2626',
            }).then(r => r.isConfirmed)
            : Promise.resolve(window.confirm('Delete this checklist item? This removes its history too.'));

        ask.then(ok => {
            if (!ok) return;
            sendJson(templateUrl(id), 'DELETE')
                .then(res => { toast(res.success); loadChecklist(weekPicker.value); })
                .catch(err => toast(err.message, 'error'));
        });
    }

    addBtn.addEventListener('click', () => {
        addRow.classList.remove('d-none');
        newLabelInput.focus();
    });

    cancelItemBtn.addEventListener('click', () => {
        addRow.classList.add('d-none');
        newLabelInput.value = '';
        newAssigneeSelect.value = '';
        newDueDaySelect.value = '';
    });

    saveItemBtn.addEventListener('click', () => {
        const label = newLabelInput.value.trim();
        if (!label) return;

        fetch(templatesUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                label,
                assigned_to: newAssigneeSelect.value || null,
                due_weekday: newDueDaySelect.value || null,
            })
        })
        .then(r => r.json())
        .then(() => {
            newLabelInput.value = '';
            newAssigneeSelect.value = '';
            newDueDaySelect.value = '';
            addRow.classList.add('d-none');
            loadChecklist(weekPicker.value);
        });
    });

    weekPicker.addEventListener('change', () => loadChecklist(weekPicker.value));

    const tabBtn = document.getElementById('pdChecklistTabBtn');
    if (tabBtn) {
        tabBtn.addEventListener('shown.bs.tab', () => {
            if (!loaded) {
                loaded = true;
                loadChecklist();
            }
        });
    }
})();
</script>
