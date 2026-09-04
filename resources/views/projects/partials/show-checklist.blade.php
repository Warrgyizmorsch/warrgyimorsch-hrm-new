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

        const canToggleThis = isCurrentWeek; // server enforces the assignee/admin/TL rule; UI just disables for past weeks

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
        noteBtn.addEventListener('click', () => {
            const newNote = prompt('Note for this item (this week)', noteEl.textContent);
            if (newNote === null) return;
            toggleItem(item.id, checkbox, newNote.trim());
        });

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

        row.append(checkbox, mainCol, noteBtn, editBtn, removeBtn);
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
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                checkbox.checked = !checkbox.checked;
                alert(data.error);
                return;
            }
            loadChecklist(weekPicker.value);
        })
        .catch(() => loadChecklist(weekPicker.value));
    }

    function editItem(item, labelEl) {
        const newLabel = prompt('Edit checklist item label', item.label);
        if (newLabel === null || !newLabel.trim()) return;

        const body = { label: newLabel.trim() };

        if (canAssign) {
            const names = lastAssignableEmployees.map(e => `${e.id} = ${e.name}`).join('\n');
            const assigneeInput = prompt(`Assign to employee ID (leave blank for unassigned):\n${names}`, item.assigned_to || '');
            if (assigneeInput !== null) {
                body.assigned_to = assigneeInput.trim() || null;
            }
            const dueInput = prompt('Due weekday 1=Mon .. 7=Sun (leave blank for none)', item.due_weekday || '');
            if (dueInput !== null) {
                body.due_weekday = dueInput.trim() || null;
            }
        }

        fetch(templateUrl(item.id), {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(r => r.json())
        .then(() => loadChecklist(weekPicker.value));
    }

    function deleteItem(id) {
        if (!confirm('Delete this checklist item? This removes its history too.')) return;

        fetch(templateUrl(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(() => loadChecklist(weekPicker.value));
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
