{{-- Salary increments: history + add form (EmployeeIncrementController). Opened by openIncrementModal(id). --}}
<div class="modal fade" id="incrementModal" tabindex="-1" aria-labelledby="incrementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header p-4" style="background: linear-gradient(135deg, #15803d 0%, #166534 100%); border: none;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0" id="incrementModalLabel">Salary Increments</h5>
                    <div id="incEmpName" style="font-size: 11px; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 1px; margin-top: 2px;">-</div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="small text-muted">Current monthly gross</div>
                            <div class="fs-5 fw-bold" id="incCurrentGross">—</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="small text-muted">Date of joining</div>
                            <div class="fs-6 fw-semibold" id="incDoj">—</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="small text-muted">Next increment due (<span id="incCycle">12</span>-month cycle)</div>
                            <div class="fs-6 fw-semibold" id="incNextDue">—</div>
                        </div>
                    </div>
                </div>

                <form id="incrementForm" class="border rounded-3 p-3 mb-4" novalidate>
                    <h6 class="fw-bold mb-3">Add increment</h6>
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold" for="incDate">Effective date</label>
                            <input type="date" class="form-control" id="incDate" name="effective_date" required>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold" for="incAmount">Increment amount (₹ / month)</label>
                            <input type="number" class="form-control" id="incAmount" name="increment_amount" min="1" step="0.01" required>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">New gross</div>
                            <div class="fw-bold" id="incPreview">—</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="incRemarks">Remarks (optional)</label>
                            <input type="text" class="form-control" id="incRemarks" name="remarks" maxlength="500" placeholder="e.g. Annual appraisal 2026">
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        The salary is updated automatically on the effective date (today or earlier = right away). Use the 1st of a month —
                        payroll uses the new salary for the whole month the increment falls in. Already-saved payrolls are not changed.
                    </div>
                    <div class="text-danger small mt-2 d-none" id="incError"></div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-success" id="incSubmit">Save increment</button>
                    </div>
                </form>

                <h6 class="fw-bold mb-2">History</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Effective</th>
                                <th class="text-end">Old gross</th>
                                <th class="text-end">Increment</th>
                                <th class="text-end">%</th>
                                <th class="text-end">New gross</th>
                                <th>Remarks</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="incHistory">
                            <tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const indexUrl = @json(route('employees.increments.index', ['id' => '__ID__']));
        const destroyUrl = @json(route('employees.increments.destroy', ['id' => '__ID__']));
        const inr = n => '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const $ = id => document.getElementById(id);
        let employeeId = null;
        let currentGross = 0;
        let changed = false;

        function notify(icon, title) {
            Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 3000 });
        }

        function updatePreview() {
            const amount = parseFloat($('incAmount').value);
            if (!amount || amount <= 0) {
                $('incPreview').textContent = '—';
                return;
            }
            const pct = currentGross > 0 ? (amount / currentGross * 100).toFixed(2) + '%' : 'n/a';
            $('incPreview').textContent = inr(currentGross + amount) + ' (+' + pct + ')';
        }

        function render(data) {
            currentGross = Number(data.current_gross || 0);
            $('incEmpName').textContent = data.employee.name;
            $('incCurrentGross').textContent = inr(currentGross);
            $('incDoj').textContent = data.date_of_joining || '—';
            $('incCycle').textContent = data.cycle_months;
            const overdue = data.next_due_iso && data.next_due_iso < new Date().toISOString().slice(0, 10);
            $('incNextDue').innerHTML = data.next_due
                ? esc(data.next_due) + (overdue ? ' <span class="badge bg-danger">Overdue</span>' : '')
                : '—';
            $('incDate').min = data.min_date || '';
            updatePreview();

            $('incHistory').innerHTML = data.increments.length
                ? data.increments.map(i => `
                    <tr>
                        <td class="text-nowrap">${esc(i.effective_date)}${i.applied ? '' : ' <span class="badge bg-warning text-dark">Scheduled</span>'}</td>
                        <td class="text-end">${inr(i.previous_gross)}</td>
                        <td class="text-end text-success fw-semibold">+${inr(i.increment_amount)}</td>
                        <td class="text-end">${Number(i.increment_percent).toFixed(2)}%</td>
                        <td class="text-end fw-semibold">${inr(i.new_gross)}</td>
                        <td class="small">${esc(i.remarks || '')}${i.created_by ? '<div class="text-muted">by ' + esc(i.created_by) + '</div>' : ''}</td>
                        <td class="text-end">${i.applied ? '' : `<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteIncrement(${i.id})" title="Remove scheduled increment"><i class="feather-trash-2"></i></button>`}</td>
                    </tr>`).join('')
                : '<tr><td colspan="7" class="text-center text-muted py-3">No increments recorded yet.</td></tr>';
        }

        function load() {
            return fetch(indexUrl.replace('__ID__', employeeId), { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : Promise.reject(new Error('Could not load increments')))
                .then(render)
                .catch(e => {
                    $('incHistory').innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">' + esc(e.message) + '</td></tr>';
                });
        }

        window.openIncrementModal = function (id) {
            employeeId = id;
            changed = false;
            $('incrementForm').reset();
            $('incError').classList.add('d-none');
            $('incPreview').textContent = '—';
            $('incHistory').innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr>';
            bootstrap.Modal.getOrCreateInstance($('incrementModal')).show();
            load();
        };

        window.deleteIncrement = function (incId) {
            Swal.fire({ icon: 'warning', title: 'Remove this scheduled increment?', showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#b91c1c' })
                .then(res => {
                    if (!res.isConfirmed) return;
                    fetch(destroyUrl.replace('__ID__', incId), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                        .then(r => r.json().then(body => ({ ok: r.ok, body })))
                        .then(({ ok, body }) => {
                            notify(ok ? 'success' : 'error', body.message || 'Something went wrong');
                            if (ok) { changed = true; load(); }
                        });
                });
        };

        $('incAmount').addEventListener('input', updatePreview);

        $('incrementForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = $('incSubmit');
            const err = $('incError');
            err.classList.add('d-none');
            btn.disabled = true;
            fetch(indexUrl.replace('__ID__', employeeId), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: new FormData(this),
            })
                .then(r => r.json().then(body => ({ ok: r.ok, body })))
                .then(({ ok, body }) => {
                    if (!ok) {
                        err.textContent = body.errors ? Object.values(body.errors).flat().join(' ') : (body.message || 'Could not save');
                        err.classList.remove('d-none');
                        return;
                    }
                    notify('success', body.message);
                    changed = true;
                    this.reset();
                    load();
                })
                .catch(() => { err.textContent = 'Could not save — please try again.'; err.classList.remove('d-none'); })
                .finally(() => { btn.disabled = false; });
        });

        // Refresh the list so the Increment column shows the new figures.
        $('incrementModal').addEventListener('hidden.bs.modal', () => { if (changed) window.location.reload(); });
    })();
</script>
