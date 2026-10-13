{{-- Milestones / phases tab: Jira-style epics that group this project's daily tasks. --}}
<div class="pd-card">
    <div class="pd-card-head pd-card-head--split">
        <div>
            <h2>Milestones</h2>
            <p class="pd-card-subtitle">Plan the project in phases and track the tasks inside each one</p>
        </div>
        <button type="button" class="zoho-btn-primary btn-sm d-none" id="pmsNewPhaseBtn">
            <i class="feather-plus"></i> New milestone
        </button>
    </div>
    <div class="pd-card-body">
        <div id="pmsSummary" class="pms-summary d-none"></div>
        <div id="pmsPhaseList"></div>
        <div id="pmsEmpty" class="pms-empty d-none">
            <i class="feather-flag"></i>
            <p class="mb-1"><strong>No milestones yet</strong></p>
            <p class="text-muted mb-0" id="pmsEmptyHint">Break the project into phases such as Discovery, Design, Development, UAT and Go-live.</p>
        </div>
        <p id="pmsLoading" class="text-muted mb-0">Loading milestones…</p>

        <div id="pmsBacklogWrap" class="pms-backlog d-none">
            <div class="pms-backlog-head">
                <h3><i class="feather-inbox"></i> Backlog <span class="pms-count" id="pmsBacklogCount">0</span></h3>
                <p class="text-muted mb-0">Project tasks not in any milestone. Use “Move to” to place them.</p>
            </div>
            <div id="pmsBacklogList"></div>
        </div>
    </div>
</div>

{{-- Milestone create / edit --}}
<div class="modal fade" id="pmsPhaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form class="modal-content" id="pmsPhaseForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="pmsPhaseModalTitle">New milestone</h5>
                <button type="button" class="btn-close" data-pms-close aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" maxlength="255" placeholder="e.g. Phase 1 — Design" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="pmsPhaseDesc" class="form-control" rows="6" placeholder="Goal, scope, acceptance criteria…"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Documents</label>
                    <div id="pmsPhaseDocs" class="pms-doc-edit-list"></div>
                    <label class="pms-dropzone" for="pmsPhaseFiles">
                        <i class="feather-upload-cloud"></i>
                        <span>Click to attach files</span>
                        <small>PDF, Word, Excel, PowerPoint, images, TXT, CSV or ZIP · up to 10 MB each</small>
                    </label>
                    <input type="file" id="pmsPhaseFiles" class="d-none" multiple
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.webp,.zip">
                    <div id="pmsPhaseNewFiles" class="pms-doc-edit-list"></div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Start date</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Due date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select"></select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Owner</label>
                        <select name="owner_id" class="form-select"></select>
                    </div>
                </div>
                <div class="pms-form-error text-danger small mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="zoho-btn-outline btn-sm" data-pms-close>Cancel</button>
                <button type="submit" class="zoho-btn-primary btn-sm">Save milestone</button>
            </div>
        </form>
    </div>
</div>

{{-- Task in milestone --}}
<div class="modal fade" id="pmsTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form class="modal-content" id="pmsTaskForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">Add task to <span id="pmsTaskPhaseName"></span></h5>
                <button type="button" class="btn-close" data-pms-close aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="phase_id">
                <div class="mb-3">
                    <label class="form-label">Task title <span class="text-danger">*</span></label>
                    <input type="text" name="task_title" class="form-control" maxlength="255" required>
                </div>
                <div class="mb-3" id="pmsTaskAssigneeWrap">
                    <label class="form-label">Assign to <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select"></select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="Hard">Hard</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Pending" selected>Pending</option>
                            <option value="In Process">In Process</option>
                            <option value="Review">Review</option>
                            <option value="On Hold">On Hold</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Start date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Due date</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" id="pmsTaskDesc" class="form-control" rows="5"></textarea>
                </div>
                <div class="pms-form-error text-danger small mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="zoho-btn-outline btn-sm" data-pms-close>Cancel</button>
                <button type="submit" class="zoho-btn-primary btn-sm">Add task</button>
            </div>
        </form>
    </div>
</div>

@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    @endpush
@endonce

<script>
(function () {
    const urls = {
        index: {{ Js::from(route('projects.phases.index', $project)) }},
        store: {{ Js::from(route('projects.phases.store', $project)) }},
        reorder: {{ Js::from(route('projects.phases.reorder', $project)) }},
        phase: id => `{{ url('/project-phases') }}/${id}`,
        phaseTasks: id => `{{ url('/project-phases') }}/${id}/tasks`,
        moveTask: id => `{{ url('/project-phases/tasks') }}/${id}/move`,
        taskStatus: id => `{{ url('/daily-tasks') }}/${id}/status`,
    };
    const csrfToken = {{ Js::from(csrf_token()) }};
    const TASK_STATUSES = ['Pending', 'In Process', 'Review', 'Rework', 'On Hold', 'Completed'];
    const COLLAPSE_KEY = 'pms-collapsed-{{ $project->id }}';

    const el = id => document.getElementById(id);
    const phaseList = el('pmsPhaseList');
    const summary = el('pmsSummary');
    const emptyState = el('pmsEmpty');
    const loading = el('pmsLoading');
    const newPhaseBtn = el('pmsNewPhaseBtn');
    const backlogWrap = el('pmsBacklogWrap');
    const backlogList = el('pmsBacklogList');
    const phaseForm = el('pmsPhaseForm');
    const taskForm = el('pmsTaskForm');

    let state = null;
    let loaded = false;
    let collapsed = {};
    const expandedDesc = {};
    try { collapsed = JSON.parse(localStorage.getItem(COLLAPSE_KEY) || '{}'); } catch (e) { collapsed = {}; }

    // ---------- helpers ----------
    function esc(v) {
        const d = document.createElement('div');
        d.textContent = v == null ? '' : String(v);
        return d.innerHTML;
    }

    function toast(message, icon) {
        if (window.Swal) {
            Swal.fire({ toast: true, position: 'top-end', icon: icon || 'success', title: message, showConfirmButton: false, timer: 2500 });
        }
    }

    function confirmBox(title, text) {
        if (window.Swal) {
            return Swal.fire({ title, text, icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc2626' })
                .then(r => r.isConfirmed);
        }
        return Promise.resolve(window.confirm(title + '\n' + text));
    }

    function request(url, method, body) {
        const isForm = body instanceof FormData;
        const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken };
        if (!isForm) headers['Content-Type'] = 'application/json';
        return fetch(url, {
            method,
            headers,
            body: isForm ? body : (body ? JSON.stringify(body) : undefined),
        }).then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(first || data.error || data.message || 'Something went wrong.');
            }
            return data;
        });
    }

    // Modals live inside a tab pane; move them to <body> so the pane can't hide or clip them.
    ['pmsPhaseModal', 'pmsTaskModal'].forEach(id => { const n = document.getElementById(id); if (n && n.parentNode !== document.body) document.body.appendChild(n); });

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
    const phaseModal = modal(el('pmsPhaseModal'));
    const taskModal = modal(el('pmsTaskModal'));
    document.querySelectorAll('#pmsPhaseModal [data-pms-close]').forEach(b => b.addEventListener('click', () => phaseModal.hide()));
    document.querySelectorAll('#pmsTaskModal [data-pms-close]').forEach(b => b.addEventListener('click', () => taskModal.hide()));

    function showFormError(form, message) {
        const box = form.querySelector('.pms-form-error');
        box.textContent = message || '';
        box.classList.toggle('d-none', !message);
    }

    function statusTone(status) {
        switch ((status || '').toLowerCase()) {
            case 'completed': case 'done': return 'completed';
            case 'in process': case 'in progress': return 'process';
            case 'on hold': return 'hold';
            case 'review': return 'review';
            case 'rework': return 'rework';
            case 'pending': case 'planned': return 'pending';
            default: return 'default';
        }
    }

    function today() {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    function fillOptions(select, items, placeholder) {
        select.innerHTML = placeholder !== undefined ? `<option value="">${esc(placeholder)}</option>` : '';
        items.forEach(it => {
            const o = document.createElement('option');
            o.value = it.value; o.textContent = it.label;
            select.appendChild(o);
        });
    }

    // ---------- rendering ----------
    function moveSelect(task) {
        if (!task.can_edit || state.phases.length === 0) return '';
        const opts = [`<option value="" ${task.phase_id ? '' : 'selected'}>Backlog</option>`]
            .concat(state.phases.map(p => `<option value="${p.id}" ${p.id === task.phase_id ? 'selected' : ''}>${esc(p.name)}</option>`));
        return `<select class="form-select form-select-sm pms-move" data-task="${task.id}" title="Move to milestone">${opts.join('')}</select>`;
    }

    function statusControl(task) {
        if (!task.can_edit) {
            return `<span class="pm-status pm-status--${statusTone(task.status)}">${esc(task.status)}</span>`;
        }
        const list = TASK_STATUSES.includes(task.status) ? TASK_STATUSES : [task.status].concat(TASK_STATUSES);
        return `<select class="form-select form-select-sm pms-status pms-status--${statusTone(task.status)}" data-task="${task.id}" data-current="${esc(task.status)}">`
            + list.map(s => `<option ${s === task.status ? 'selected' : ''}>${esc(s)}</option>`).join('')
            + `</select>`;
    }

    function taskRow(task) {
        return `
            <div class="pms-task ${task.is_done ? 'pms-task--done' : ''}">
                <div class="pms-task-main">
                    <span class="pms-task-title">${esc(task.title)}</span>
                    <div class="pms-task-meta">
                        <span><i class="feather-user"></i> ${esc(task.assignee || 'Unassigned')}</span>
                        ${task.priority ? `<span class="pms-prio pms-prio--${esc((task.priority || '').toLowerCase())}">${esc(task.priority)}</span>` : ''}
                        ${task.end_date_label ? `<span class="${task.is_overdue ? 'pms-overdue' : ''}"><i class="feather-calendar"></i> ${esc(task.end_date_label)}${task.is_overdue ? ' · overdue' : ''}</span>` : ''}
                    </div>
                </div>
                <div class="pms-task-actions">
                    ${statusControl(task)}
                    ${moveSelect(task)}
                </div>
            </div>`;
    }

    function phaseCard(phase, index) {
        const isCollapsed = !!collapsed[phase.id];
        const manage = state.can_manage;
        const dates = [phase.start_label, phase.due_label].filter(Boolean).join(' → ');
        const tasksHtml = phase.tasks.length
            ? phase.tasks.map(taskRow).join('')
            : `<p class="pms-phase-empty">No tasks in this milestone yet.</p>`;

        return `
            <div class="pms-phase ${phase.is_overdue ? 'pms-phase--overdue' : ''}" data-phase="${phase.id}">
                <div class="pms-phase-head">
                    <button type="button" class="pms-toggle" data-action="toggle" aria-expanded="${!isCollapsed}" title="Show/hide tasks">
                        <i class="feather-chevron-${isCollapsed ? 'right' : 'down'}"></i>
                    </button>
                    <span class="pms-phase-num">${index + 1}</span>
                    <div class="pms-phase-info">
                        <div class="pms-phase-title-row">
                            <strong class="pms-phase-name">${esc(phase.name)}</strong>
                            <span class="pm-status pm-status--${statusTone(phase.status)}">${esc(phase.status)}</span>
                            ${phase.is_overdue ? '<span class="pms-badge-overdue">Overdue</span>' : ''}
                        </div>
                        <div class="pms-phase-meta">
                            ${dates ? `<span><i class="feather-calendar"></i> ${esc(dates)}</span>` : '<span class="text-muted">No dates set</span>'}
                            ${phase.owner_name ? `<span><i class="feather-user"></i> ${esc(phase.owner_name)}</span>` : ''}
                            <span><i class="feather-check-square"></i> ${phase.task_done}/${phase.task_total} done</span>
                            ${phase.task_overdue ? `<span class="pms-overdue">${phase.task_overdue} overdue</span>` : ''}
                        </div>
                        ${phase.description ? `
                            <div class="pms-phase-desc ${expandedDesc[phase.id] ? '' : 'pms-phase-desc--clamped'}">${phase.description}</div>
                            <button type="button" class="pms-more" data-action="desc">${expandedDesc[phase.id] ? 'Show less' : 'Show more'}</button>` : ''}
                        ${phase.documents && phase.documents.length ? `
                            <div class="pms-docs">${phase.documents.map(d => `
                                <a class="pms-doc" href="${esc(d.url)}" target="_blank" rel="noopener" title="${esc(d.name)}">
                                    <i class="${fileIcon(d.name)}"></i><span>${esc(d.name)}</span>
                                </a>`).join('')}
                            </div>` : ''}
                    </div>
                    <div class="pms-phase-progress">
                        <div class="pms-bar"><span style="width:${phase.progress}%"></span></div>
                        <small>${phase.progress}%</small>
                    </div>
                    <div class="pms-phase-actions">
                        <button type="button" class="zoho-btn-outline btn-sm" data-action="add-task"><i class="feather-plus"></i> Task</button>
                        ${manage ? `
                            <button type="button" class="pms-icon-btn" data-action="up" title="Move up" ${index === 0 ? 'disabled' : ''}><i class="feather-arrow-up"></i></button>
                            <button type="button" class="pms-icon-btn" data-action="down" title="Move down" ${index === state.phases.length - 1 ? 'disabled' : ''}><i class="feather-arrow-down"></i></button>
                            <button type="button" class="pms-icon-btn" data-action="edit" title="Edit"><i class="feather-edit-2"></i></button>
                            <button type="button" class="pms-icon-btn pms-icon-btn--danger" data-action="delete" title="Delete"><i class="feather-trash-2"></i></button>
                        ` : ''}
                    </div>
                </div>
                <div class="pms-phase-body ${isCollapsed ? 'd-none' : ''}">${tasksHtml}</div>
            </div>`;
    }

    function render(data) {
        state = data;
        loading.classList.add('d-none');
        newPhaseBtn.classList.toggle('d-none', !data.can_manage);
        el('pmsEmptyHint').textContent = data.can_manage
            ? 'Break the project into phases such as Discovery, Design, Development, UAT and Go-live. Click “New milestone” to start.'
            : 'A project leader has not set up milestones for this project yet.';

        const phases = data.phases;
        emptyState.classList.toggle('d-none', phases.length > 0);
        phaseList.innerHTML = phases.map(phaseCard).join('');

        if (phases.length) {
            const total = phases.reduce((n, p) => n + p.task_total, 0);
            const done = phases.reduce((n, p) => n + p.task_done, 0);
            const completedPhases = phases.filter(p => p.status === 'Completed').length;
            const current = phases.find(p => p.status !== 'Completed');
            summary.innerHTML = `
                <div><strong>${completedPhases}/${phases.length}</strong><span>milestones completed</span></div>
                <div><strong>${done}/${total}</strong><span>tasks done</span></div>
                <div><strong>${current ? esc(current.name) : 'All done'}</strong><span>current milestone</span></div>`;
            summary.classList.remove('d-none');
        } else {
            summary.classList.add('d-none');
        }

        backlogWrap.classList.toggle('d-none', data.backlog.length === 0);
        el('pmsBacklogCount').textContent = data.backlog.length;
        backlogList.innerHTML = data.backlog.map(taskRow).join('');
    }

    function load() {
        return request(urls.index, 'GET')
            .then(render)
            .catch(err => { loading.classList.remove('d-none'); loading.textContent = 'Failed to load milestones: ' + err.message; });
    }

    // ---------- rich text editor (Summernote, same as the project form) ----------
    // Looked up lazily: jQuery and Summernote load at the end of the page.
    const EDITOR_TOOLBAR = [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link', 'table', 'hr']],
        ['misc', ['undo', 'redo']],
    ];
    function hasEditor() { return !!(window.jQuery && jQuery.fn.summernote); }
    function editorInit(textarea, height) {
        if (!hasEditor() || textarea.dataset.editor === '1') return;
        jQuery(textarea).summernote({
            height: height || 220,
            toolbar: EDITOR_TOOLBAR,
            placeholder: textarea.getAttribute('placeholder') || '',
            disableDragAndDrop: true, // files go in Documents, not inline
        });
        textarea.dataset.editor = '1';
    }
    function editorSet(textarea, html) {
        if (textarea.dataset.editor === '1') jQuery(textarea).summernote('code', html || '');
        else textarea.value = html || '';
    }
    function editorGet(textarea) {
        const html = textarea.dataset.editor === '1' ? jQuery(textarea).summernote('code') : textarea.value;
        const plain = html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
        return plain ? html : null; // treat "<p><br></p>" as empty
    }

    function formatSize(bytes) {
        if (!bytes) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    function fileIcon(name) {
        const ext = (name.split('.').pop() || '').toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'feather-image';
        if (['xls', 'xlsx', 'csv'].includes(ext)) return 'feather-grid';
        if (ext === 'zip') return 'feather-archive';
        return 'feather-file-text';
    }

    // ---------- phase modal ----------
    const phaseDesc = el('pmsPhaseDesc');
    const taskDesc = el('pmsTaskDesc');
    const phaseFilesInput = el('pmsPhaseFiles');
    let existingDocs = [];
    let removedDocs = new Set();
    let pendingFiles = [];

    function renderDocEditor() {
        el('pmsPhaseDocs').innerHTML = existingDocs.map(d => `
            <div class="pms-doc-edit ${removedDocs.has(d.path) ? 'pms-doc-edit--removed' : ''}">
                <i class="${fileIcon(d.name)}"></i>
                <a href="${esc(d.url)}" target="_blank" rel="noopener">${esc(d.name)}</a>
                <small>${formatSize(d.size)}</small>
                <button type="button" class="pms-icon-btn ${removedDocs.has(d.path) ? '' : 'pms-icon-btn--danger'}" data-doc-toggle="${esc(d.path)}"
                    title="${removedDocs.has(d.path) ? 'Keep' : 'Remove'}"><i class="feather-${removedDocs.has(d.path) ? 'rotate-ccw' : 'x'}"></i></button>
            </div>`).join('');
        el('pmsPhaseNewFiles').innerHTML = pendingFiles.map((f, i) => `
            <div class="pms-doc-edit pms-doc-edit--new">
                <i class="${fileIcon(f.name)}"></i>
                <span>${esc(f.name)}</span>
                <small>${formatSize(f.size)}${f.size > 10485760 ? ' · too large' : ''}</small>
                <button type="button" class="pms-icon-btn pms-icon-btn--danger" data-new-remove="${i}" title="Remove"><i class="feather-x"></i></button>
            </div>`).join('');
    }
    el('pmsPhaseDocs').addEventListener('click', e => {
        const b = e.target.closest('[data-doc-toggle]');
        if (!b) return;
        const path = b.dataset.docToggle;
        removedDocs.has(path) ? removedDocs.delete(path) : removedDocs.add(path);
        renderDocEditor();
    });
    el('pmsPhaseNewFiles').addEventListener('click', e => {
        const b = e.target.closest('[data-new-remove]');
        if (!b) return;
        pendingFiles.splice(Number(b.dataset.newRemove), 1);
        renderDocEditor();
    });
    phaseFilesInput.addEventListener('change', () => {
        pendingFiles = pendingFiles.concat(Array.from(phaseFilesInput.files));
        phaseFilesInput.value = '';
        renderDocEditor();
    });

    function openPhaseModal(phase) {
        phaseForm.reset();
        showFormError(phaseForm, '');
        el('pmsPhaseModalTitle').textContent = phase ? 'Edit milestone' : 'New milestone';
        fillOptions(phaseForm.status, state.statuses.map(s => ({ value: s, label: s })));
        fillOptions(phaseForm.owner_id, state.members.map(m => ({ value: m.id, label: m.name })), 'No owner');
        phaseForm.id.value = phase ? phase.id : '';
        phaseForm.name.value = phase ? phase.name : '';
        editorInit(phaseDesc, 220);
        editorSet(phaseDesc, phase ? (phase.description || '') : '');
        existingDocs = phase ? (phase.documents || []) : [];
        removedDocs = new Set();
        pendingFiles = [];
        renderDocEditor();
        phaseForm.start_date.value = phase ? (phase.start_date || '') : '';
        phaseForm.due_date.value = phase ? (phase.due_date || '') : '';
        phaseForm.status.value = phase ? phase.status : 'Planned';
        phaseForm.owner_id.value = phase && phase.owner_id ? phase.owner_id : '';
        phaseModal.show();
        setTimeout(() => phaseForm.name.focus(), 300);
    }

    phaseForm.addEventListener('submit', e => {
        e.preventDefault();
        const id = phaseForm.id.value;
        const name = phaseForm.name.value.trim();
        if (!name) return showFormError(phaseForm, 'Name is required.');
        if (pendingFiles.some(f => f.size > 10485760)) return showFormError(phaseForm, 'Each document must be 10 MB or smaller.');

        // Multipart so documents can go with the form. PUT is sent as POST + _method (PHP can't read files on PUT).
        const body = new FormData();
        if (id) body.append('_method', 'PUT');
        body.append('name', name);
        body.append('description', editorGet(phaseDesc) || '');
        body.append('start_date', phaseForm.start_date.value || '');
        body.append('due_date', phaseForm.due_date.value || '');
        body.append('status', phaseForm.status.value);
        body.append('owner_id', phaseForm.owner_id.value || '');
        pendingFiles.forEach(f => body.append('documents[]', f));
        removedDocs.forEach(p => body.append('removed_documents[]', p));

        const btn = phaseForm.querySelector('[type=submit]');
        btn.disabled = true;
        request(id ? urls.phase(id) : urls.store, 'POST', body)
            .then(res => { phaseModal.hide(); toast(res.success); return load(); })
            .catch(err => showFormError(phaseForm, err.message))
            .finally(() => { btn.disabled = false; });
    });

    // ---------- task modal ----------
    function openTaskModal(phase) {
        taskForm.reset();
        showFormError(taskForm, '');
        el('pmsTaskPhaseName').textContent = phase.name;
        taskForm.phase_id.value = phase.id;
        const wrap = el('pmsTaskAssigneeWrap');
        wrap.classList.toggle('d-none', !state.can_manage);
        if (state.can_manage) {
            fillOptions(taskForm.employee_id, state.members.map(m => ({ value: m.id, label: m.name })), 'Select member');
        }
        editorInit(taskDesc, 180);
        editorSet(taskDesc, '');
        taskForm.start_date.value = today();
        taskForm.end_date.value = phase.due_date || '';
        taskModal.show();
        setTimeout(() => taskForm.task_title.focus(), 300);
    }

    taskForm.addEventListener('submit', e => {
        e.preventDefault();
        const body = {
            task_title: taskForm.task_title.value.trim(),
            employee_id: state.can_manage ? (taskForm.employee_id.value || null) : null,
            priority: taskForm.priority.value,
            status: taskForm.status.value,
            start_date: taskForm.start_date.value,
            end_date: taskForm.end_date.value || null,
            description: editorGet(taskDesc),
        };
        if (!body.task_title) return showFormError(taskForm, 'Task title is required.');
        if (state.can_manage && !body.employee_id) return showFormError(taskForm, 'Choose who this task is for.');
        if (!body.start_date) return showFormError(taskForm, 'Start date is required.');

        const btn = taskForm.querySelector('[type=submit]');
        btn.disabled = true;
        request(urls.phaseTasks(taskForm.phase_id.value), 'POST', body)
            .then(res => { taskModal.hide(); toast(res.success); return load(); })
            .catch(err => showFormError(taskForm, err.message))
            .finally(() => { btn.disabled = false; });
    });

    // ---------- list actions ----------
    newPhaseBtn.addEventListener('click', () => openPhaseModal(null));

    phaseList.addEventListener('click', e => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const card = btn.closest('[data-phase]');
        const id = Number(card.dataset.phase);
        const index = state.phases.findIndex(p => p.id === id);
        const phase = state.phases[index];

        switch (btn.dataset.action) {
            case 'toggle':
                collapsed[id] = !collapsed[id];
                try { localStorage.setItem(COLLAPSE_KEY, JSON.stringify(collapsed)); } catch (err) { /* ignore */ }
                render(state);
                break;
            case 'desc':
                expandedDesc[id] = !expandedDesc[id];
                render(state);
                break;
            case 'add-task':
                openTaskModal(phase);
                break;
            case 'edit':
                openPhaseModal(phase);
                break;
            case 'delete':
                confirmBox(`Delete “${phase.name}”?`, `Its ${phase.task_total} task(s) are kept and moved to the backlog.`)
                    .then(ok => ok && request(urls.phase(id), 'DELETE')
                        .then(res => { toast(res.success); return load(); })
                        .catch(err => toast(err.message, 'error')));
                break;
            case 'up':
            case 'down': {
                const ids = state.phases.map(p => p.id);
                const swap = btn.dataset.action === 'up' ? index - 1 : index + 1;
                if (swap < 0 || swap >= ids.length) return;
                [ids[index], ids[swap]] = [ids[swap], ids[index]];
                request(urls.reorder, 'POST', { ids }).then(load).catch(err => toast(err.message, 'error'));
                break;
            }
        }
    });

    function onTaskChange(e) {
        const target = e.target;
        if (target.classList.contains('pms-move')) {
            request(urls.moveTask(target.dataset.task), 'PATCH', { phase_id: target.value ? Number(target.value) : null })
                .then(res => { toast(res.success); return load(); })
                .catch(err => { toast(err.message, 'error'); load(); });
        }
        if (target.classList.contains('pms-status')) {
            // Reuses the Daily Tasks status endpoint so status history is recorded as usual.
            request(urls.taskStatus(target.dataset.task), 'PATCH', { status: target.value })
                .then(res => { toast(res.success || 'Status updated.'); return load(); })
                .catch(err => { toast(err.message, 'error'); target.value = target.dataset.current; });
        }
    }
    phaseList.addEventListener('change', onTaskChange);
    backlogList.addEventListener('change', onTaskChange);

    const tabBtn = document.getElementById('pdMilestonesTabBtn');
    if (tabBtn) {
        tabBtn.addEventListener('shown.bs.tab', () => {
            if (!loaded) { loaded = true; load(); }
        });
        if (tabBtn.classList.contains('active')) { loaded = true; load(); }
    }
})();
</script>
