@push('scripts')
<script>
    const holidays = @json($holidays ?? []);

    function isNonWorkingDate(dateString) {
        const date = new Date(dateString + 'T00:00:00');
        return date.getDay() === 0 || holidays.includes(dateString);
    }

    function calculateWorkingDayCount(startDate, endDate) {
        let current = new Date(startDate + 'T00:00:00');
        const end = new Date(endDate + 'T00:00:00');
        let count = 0;

        while (current <= end) {
            const currentDate =
                current.getFullYear() + '-' +
                String(current.getMonth() + 1).padStart(2, '0') + '-' +
                String(current.getDate()).padStart(2, '0');

            if (!isNonWorkingDate(currentDate)) {
                count++;
            }

            current.setDate(current.getDate() + 1);
        }

        return count;
    }

    function toggleCategoryFields() {
        const leaveTypeEl = document.querySelector('#applyLeaveForm input[name="leave_type"]:checked');
        const leaveCategoryEl = document.getElementById('leaveCategory');
        if (!leaveTypeEl || !leaveCategoryEl) return;

        const leaveType = leaveTypeEl.value;
        const leaveCategory = leaveCategoryEl.value;

        const endDateWrapper = document.getElementById('endDateWrapper');
        const startTimeWrapper = document.getElementById('startTimeWrapper');
        const endTimeWrapper = document.getElementById('endTimeWrapper');
        const halfDayOptionWrapper = document.getElementById('halfDayOptionWrapper');
        const leaveCategoryWrapper = document.getElementById('leaveCategoryWrapper');

        halfDayOptionWrapper.style.display = 'none';
        endTimeWrapper.style.display = 'none';

        if (leaveCategory === 'Gatepass Leave') {
            endDateWrapper.style.display = 'none';
            startTimeWrapper.style.display = 'block';
            leaveCategoryWrapper.style.display = 'none';

            document.getElementById('endDate').required = false;
            document.getElementById('startTime').required = true;

            document.querySelectorAll('input[name="leave_type"]').forEach(radio => {
                radio.required = false;
            });
        } else {
            startTimeWrapper.style.display = 'none';
            endDateWrapper.style.display = 'block';
            leaveCategoryWrapper.style.display = '';

            document.getElementById('startTime').required = false;

            if (leaveType === 'Half Day') {
                endDateWrapper.style.display = 'none';
                halfDayOptionWrapper.style.display = 'block';
            }

            document.getElementById('endDate').required = false;
        }

        calculateDays();
    }

    let applyLeaveBalance = null;

    function setTotalDuration(displayText, numericValue) {
        const display = document.getElementById('totalDaysDisplay');
        const hidden = document.getElementById('totalDays');
        if (display) {
            display.textContent = displayText;
        }
        if (hidden) {
            hidden.value = numericValue ?? (parseFloat(String(displayText).replace(/[^0-9.]/g, '')) || 0);
        }
        updateApplyOverflowWarning();
    }

    function fetchApplyLeaveBalance(employeeId) {
        const hint = document.getElementById('applyBalanceHint');
        applyLeaveBalance = null;
        if (!employeeId) {
            if (hint) hint.textContent = 'Select one';
            updateApplyOverflowWarning();
            return;
        }

        fetch(`/api/leave/employee/${employeeId}/balance`)
            .then(res => res.json())
            .then(data => {
                applyLeaveBalance = Math.max(Number(data.balance ?? 0), 0);
                if (hint) {
                    const shown = Number.isInteger(applyLeaveBalance) ? applyLeaveBalance : applyLeaveBalance.toFixed(1);
                    hint.textContent = `${shown} day${applyLeaveBalance === 1 ? '' : 's'} available (Paid/Sick)`;
                }
                updateApplyOverflowWarning();
            })
            .catch(() => {
                applyLeaveBalance = null;
            });
    }

    function updateApplyOverflowWarning() {
        const warningEl = document.getElementById('applyOverflowWarning');
        if (!warningEl) return;

        const category = document.getElementById('leaveCategory')?.value;
        const totalDays = parseFloat(document.getElementById('totalDays')?.value || '0') || 0;
        const isQuotaChecked = category === 'Paid Leave' || category === 'Sick Leave';

        if (isQuotaChecked && applyLeaveBalance !== null && totalDays > applyLeaveBalance) {
            const fitDays = Math.max(0, applyLeaveBalance);
            warningEl.style.display = '';
            warningEl.textContent = fitDays > 0
                ? `Only ${fitDays} day(s) of ${category} remain — the rest will be submitted as Casual Leave.`
                : `No ${category} balance remaining — this request will be submitted as Casual Leave.`;
        } else {
            warningEl.style.display = 'none';
        }
    }

    function initLeaveCategoryPicker() {
        const picker = document.getElementById('leaveCategoryPicker');
        const hidden = document.getElementById('leaveCategory');
        if (!picker || !hidden || picker.dataset.initialized) {
            return;
        }
        picker.dataset.initialized = 'true';

        picker.querySelectorAll('.lv-cat-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                picker.querySelectorAll('.lv-cat-chip').forEach(c => c.classList.remove('is-selected'));
                chip.classList.add('is-selected');
                hidden.value = chip.dataset.value || '';
                hidden.dispatchEvent(new Event('change'));
            });
        });
    }

    function resetApplyLeaveDrawer() {
        const form = document.getElementById('applyLeaveForm');
        if (!form) return;

        // form.reset() empties the hidden employee field while the picker still shows the
        // chosen name — the next request then went out without an employee. Keep them in sync.
        const employeeInput = document.getElementById('applyEmployeeId');
        const keptEmployeeId = employeeInput ? employeeInput.value : '';

        form.reset();

        if (employeeInput) {
            employeeInput.value = keptEmployeeId;
        }
        document.getElementById('leaveCategoryPicker')?.querySelectorAll('.lv-cat-chip').forEach(c => c.classList.remove('is-selected'));
        document.getElementById('leaveCategory').value = '';
        document.getElementById('catFull').checked = true;
        applyLeaveBalance = null;
        const hint = document.getElementById('applyBalanceHint');
        if (hint) hint.textContent = 'Select one';
        setTotalDuration('0 Days', 0);
        toggleCategoryFields();
    }

    function calculateDays() {
        const leaveTypeEl = document.querySelector('#applyLeaveForm input[name="leave_type"]:checked');
        const leaveCategoryEl = document.getElementById('leaveCategory');
        if (!leaveTypeEl || !leaveCategoryEl) return;

        const leaveType = leaveTypeEl.value;
        const leaveCategory = leaveCategoryEl.value;
        const start = document.getElementById('startDate')?.value;
        const end = document.getElementById('endDate')?.value;

        if (!leaveCategory) {
            setTotalDuration('0 Days', 0);
            return;
        }

        if (leaveCategory === 'Gatepass Leave') {
            const startTimeInput = document.getElementById('startTime');
            const endTimeInput = document.getElementById('endTime');

            if (startTimeInput && startTimeInput.value && endTimeInput) {
                const [hours, minutes] = startTimeInput.value.split(':');
                const dateObj = new Date();
                dateObj.setHours(parseInt(hours, 10) + 1, parseInt(minutes, 10));
                endTimeInput.value = dateObj.toTimeString().substring(0, 5);
            }

            setTotalDuration('1 Hour', 1);
            return;
        }

        if (leaveType === 'Half Day') {
            setTotalDuration('0.5 Day', 0.5);
            return;
        }

        if (start && end) {
            const count = calculateWorkingDayCount(start, end);
            setTotalDuration(count + (count === 1 ? ' Day' : ' Days'), count);
        } else if (start) {
            const count = isNonWorkingDate(start) ? 0 : 1;
            setTotalDuration(count + (count === 1 ? ' Day' : ' Days'), count);
        } else {
            setTotalDuration('0 Days', 0);
        }
    }

    // ---------- two-level approval helpers ----------
    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    const LEVEL_STATUS_TEXT = {
        pending: 'Waiting',
        approved: 'Approved',
        rejected: 'Rejected',
        skipped: 'Not required',
        bypassed: 'Skipped by manager',
        on_hold: 'On hold',
        unpaid: 'Marked unpaid',
        unauthorised: 'Marked unauthorised',
    };

    function trailStep(level, title, step, waitingText) {
        const status = step && step.status ? step.status : null;
        const key = status || 'none';
        const label = status ? (LEVEL_STATUS_TEXT[status] || status.replace(/_/g, ' ')) : (waitingText || 'Waiting');
        const meta = [step && step.by, step && step.at].filter(Boolean).map(escapeHtml).join(' · ');
        return `
            <div class="lh-trail-step lh-trail-step--${escapeHtml(key)}">
                <div class="lh-trail-dot">${level}</div>
                <div class="lh-trail-body">
                    <div class="lh-trail-head">
                        <strong>${escapeHtml(title)}</strong>
                        <span class="lh-trail-status lh-trail-status--${escapeHtml(key)}">${escapeHtml(label)}</span>
                    </div>
                    ${meta ? `<div class="lh-trail-meta">${meta}</div>` : ''}
                    ${step && step.waiting_on && status === 'pending' ? `<div class="lh-trail-meta">With: ${escapeHtml(step.waiting_on)}</div>` : ''}
                    ${step && step.remarks ? `<div class="lh-trail-remarks">“${escapeHtml(step.remarks)}”</div>` : ''}
                </div>
            </div>`;
    }

    // Timeline: applied → TL → manager (every change), each with exact time and time taken.
    function renderApprovalTrail(container, approval) {
        if (!container) return;
        const events = approval && Array.isArray(approval.timeline) ? approval.timeline : [];
        if (!events.length) {
            container.innerHTML = '<span class="text-muted">No timeline recorded.</span>';
            return;
        }
        container.innerHTML = '<div class="lh-timeline">' + events.map(ev => `
            <div class="lh-tl-item lh-tl-item--${escapeHtml(ev.state)} ${ev.pending ? 'is-pending' : ''}">
                <span class="lh-tl-dot"></span>
                <div class="lh-tl-body">
                    <div class="lh-tl-head">
                        <strong>${escapeHtml(ev.title)}</strong>
                        ${ev.gap ? `<span class="lh-tl-gap">${escapeHtml(ev.gap)}</span>` : ''}
                    </div>
                    <div class="lh-tl-meta">
                        <i class="feather-clock"></i> ${escapeHtml(ev.at)}${ev.by ? ` · <i class="feather-user"></i> ${escapeHtml(ev.by)}` : ''}
                    </div>
                    ${ev.remarks ? `<div class="lh-trail-remarks">“${escapeHtml(ev.remarks)}”</div>` : ''}
                </div>
            </div>`).join('') + '</div>';
    }

    // Loads one application for the drawers; shows a clear message instead of failing silently.
    function loadLeaveDetails(id) {
        return fetch(`/api/leave/details/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(async res => {
                const data = await res.json().catch(() => null);
                if (!res.ok || !data) {
                    const msg = (data && data.message) || `Could not load this application (server error ${res.status}). Please check storage/logs/laravel.log.`;
                    showToast(msg, 'error');
                    throw new Error(msg);
                }
                return data;
            });
    }

    function openActionModal(id) {
        loadLeaveDetails(id)
            .then(data => {
                document.getElementById('actionLeaveId').value = data.id;
                document.getElementById('displayAppCode').textContent = `LA-${String(data.id).padStart(4, '0')}`;
                updateBalanceBadge(data.balance);
                document.querySelector('#actionForm select[name="status"]').value = data.status;
                document.getElementById('actionRemarks').value = '';
                renderApprovalTrail(document.getElementById('actionTrail'), data.approval);
                const tlPending = data.approval && data.approval.tl && data.approval.tl.status === 'pending';
                document.getElementById('actionTlWarning').classList.toggle('d-none', !tlPending);
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('leaveActionModal')).show();
            });
    }

    function openTlReview(id) {
        loadLeaveDetails(id)
            .then(data => {
                if (!data.can_tl_act) {
                    showToast('This request is no longer waiting for your approval.', 'error');
                    setTimeout(() => window.location.reload(), 1200);
                    return;
                }
                const fmt = d => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
                const range = data.end_date && data.end_date !== data.start_date
                    ? `${fmt(data.start_date)} → ${fmt(data.end_date)}` : fmt(data.start_date);
                document.getElementById('tlLeaveId').value = data.id;
                document.getElementById('tlEmployee').textContent = data.employee ? data.employee.name : '—';
                const bal = Math.max(Number(data.balance ?? 0), 0);
                const balEl = document.getElementById('tlBalance');
                balEl.textContent = Number.isInteger(bal) ? bal : bal.toFixed(1);
                balEl.className = `lh-balance-badge ${bal == 0 ? 'lh-balance-badge--zero' : 'lh-balance-badge--ok'}`;
                document.getElementById('tlSummary').textContent =
                    `${data.leave_category || ''} · ${data.leave_type || ''} · ${range} · ${data.total_days} day(s)`;
                document.getElementById('tlReason').textContent = data.reason || 'No reason given.';
                document.getElementById('tlRemarks').value = '';
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('leaveTlModal')).show();
                setTimeout(() => document.getElementById('tlRemarks').focus(), 300);
            });
    }

    function updateBalanceBadge(balance) {
        const badge = document.getElementById('displayBalanceBadge');
        if (!badge) return;

        const numericBalance = Math.max(Number(balance ?? 0), 0);
        badge.textContent = Number.isInteger(numericBalance) ? numericBalance : numericBalance.toFixed(1);
        badge.className = `lh-balance-badge ${numericBalance == 0 ? 'lh-balance-badge--zero' : 'lh-balance-badge--ok'}`;
    }

    function getReturnDateText(endDateString) {
        const returnDate = new Date(endDateString + 'T00:00:00');
        returnDate.setDate(returnDate.getDate() + 1);
        return returnDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function openViewModal(id) {
        loadLeaveDetails(id)
            .then(data => {
                bootstrap.Tab.getOrCreateInstance(document.getElementById('details-tab')).show();

                document.getElementById('viewEmployeeName').textContent = data.employee.name;
                document.getElementById('viewAvatarLetter').textContent = data.employee.name.charAt(0);
                document.getElementById('viewLeaveType').textContent = data.leave_type;

                const catRaw = data.leave_category.toLowerCase();
                let catDisp = data.leave_category.toUpperCase();
                let catClass = 'lh-cat--paid';

                if (catRaw.includes('half')) {
                    catDisp = catDisp.replace('HALF', 'HALF DAY').replace('HALF DAY DAY', 'HALF DAY');
                    catClass = 'lh-cat--half';
                } else if (catRaw === 'full' || catRaw.trim() === 'full') {
                    catDisp = 'FULL DAY';
                } else if (catRaw.includes('gatepass')) {
                    catDisp = 'EARLY LEAVE';
                    catClass = 'lh-cat--early';
                } else if (catRaw.includes('wfh')) {
                    catDisp = 'WFH';
                    catClass = 'lh-cat--wfh';
                } else if (catRaw.includes('sick')) {
                    catDisp = 'SICK LEAVE';
                    catClass = 'lh-cat--sick';
                } else if (catRaw.includes('casual')) {
                    catDisp = 'CASUAL LEAVE';
                    catClass = 'lh-cat--casual';
                }

                const catBadge = document.getElementById('viewCategoryBadge');
                catBadge.textContent = catDisp;
                catBadge.className = `lh-cat ${catClass}`;

                const statusBadge = document.getElementById('viewStatusBadge');
                const status = data.status.toLowerCase().replace(/\s+/g, '_');
                statusBadge.textContent = status.replace(/_/g, ' ');
                statusBadge.className = `lh-status lh-status--${status}`;

                const cat = data.leave_category.toLowerCase();
                const isGatepass = cat.includes('gatepass');
                const isHalfDay = cat.includes('half');
                const isWFH = cat.includes('wfh');

                if (isGatepass) {
                    document.getElementById('viewTotalDays').textContent = '1 Hour (Early Leave)';
                } else if (isHalfDay) {
                    document.getElementById('viewTotalDays').textContent = '0.5 Days (Half Day)';
                } else if (isWFH) {
                    document.getElementById('viewTotalDays').textContent = `${data.total_days} Days (WFH)`;
                } else {
                    document.getElementById('viewTotalDays').textContent = `${data.total_days} Days`;
                }

                if (isGatepass) {
                    document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    document.getElementById('viewStartTimeText').textContent = data.start_time ? data.start_time.substring(0, 5) : 'N/A';
                    document.getElementById('viewEndDateText').textContent = 'Same Day';
                    document.getElementById('viewEndTimeText').textContent = data.end_time ? data.end_time.substring(0, 5) : 'N/A';
                } else if (isHalfDay) {
                    document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

                    let whichHalf = 'Half Day';
                    if (cat.includes('first')) whichHalf = 'First Half';
                    else if (cat.includes('second')) whichHalf = 'Second Half';

                    document.getElementById('viewStartTimeText').textContent = whichHalf;
                    document.getElementById('viewEndDateText').textContent = 'Same Day';
                    document.getElementById('viewEndTimeText').textContent = '0.5 Day';
                } else if (isWFH) {
                    document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    document.getElementById('viewStartTimeText').textContent = 'WFH Mode';
                    document.getElementById('viewEndDateText').textContent = data.end_date ? getReturnDateText(data.end_date) : '-';
                    document.getElementById('viewEndTimeText').textContent = 'Return Date';
                } else {
                    document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    document.getElementById('viewStartTimeText').textContent = 'Full Day';
                    document.getElementById('viewEndDateText').textContent = data.end_date ? getReturnDateText(data.end_date) : '-';
                    document.getElementById('viewEndTimeText').textContent = 'Return Date';
                }

                document.getElementById('viewReason').textContent = data.reason;
                document.getElementById('viewMessage').textContent = data.message || 'No extra message.';
                renderApprovalTrail(document.getElementById('viewApprovalTrail'), data.approval);

                fetchHistory(data.employee_id);
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('viewLeaveModal')).show();

                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });
    }

    function fetchHistory(empId) {
        const tbody = document.getElementById('historyTableBody');
        const badge = document.getElementById('historyCountBadge');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted small">Loading records...</td></tr>';

        fetch(`/api/leave/employee/${empId}`)
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';
                badge.textContent = `${data.length} Total`;

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted small">No history available for this employee.</td></tr>';
                    return;
                }

                const today = new Date();
                data.forEach(item => {
                    const sDate = new Date(item.start_date);
                    const isFuture = sDate > today;
                    const dateFormatted = sDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                    const statusKey = item.status.toLowerCase().replace(/\s+/g, '_');

                    const catRaw = item.leave_category.toLowerCase();
                    let catClass = 'lh-cat--paid';
                    let catDisp = item.leave_category;

                    if (catRaw.includes('sick')) {
                        catClass = 'lh-cat--sick';
                        catDisp = 'Sick Leave';
                    } else if (catRaw.includes('casual')) {
                        catClass = 'lh-cat--casual';
                        catDisp = 'Casual Leave';
                    } else if (catRaw.includes('gatepass')) {
                        catClass = 'lh-cat--early';
                        catDisp = 'Early Leave';
                    } else if (catRaw.includes('wfh')) {
                        catClass = 'lh-cat--wfh';
                        catDisp = 'WFH';
                    } else if (catRaw.includes('paid')) {
                        catDisp = 'Paid Leave';
                    }

                    tbody.innerHTML += `
                        <tr class="${isFuture ? 'lh-history-row--future' : ''}">
                            <td class="ps-3 py-3">
                                <div class="fw-bold text-dark small">${dateFormatted}</div>
                                ${isFuture ? '<div class="lh-upcoming-tag">Upcoming</div>' : ''}
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small">${item.leave_type}</div>
                                <span class="lh-cat ${catClass}">${catDisp}</span>
                            </td>
                            <td class="text-center">
                                <span class="lh-status lh-status--${statusKey}">${item.status.replace(/_/g, ' ')}</span>
                            </td>
                            <td class="pe-3 text-end fw-bold text-primary small">${catRaw.includes('gatepass') ? '1 Hr' : item.total_days}</td>
                        </tr>
                    `;
                });
            });
    }

    function deleteApplication(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3858f9',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-primary px-4',
                cancelButton: 'btn btn-light-brand px-4 me-3'
            },
            buttonsStyling: false
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch(`/leave/application/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
                .then(res => res.json())
                .then(() => {
                    if (typeof Toast !== 'undefined') {
                        Toast.fire({ icon: 'success', title: 'Application deleted' });
                    }
                    setTimeout(() => window.location.reload(), 1000);
                });
        });
    }

    /**
     * Turn any server response into {ok, data, message}: JSON or not (an HTML error page,
     * expired session, server error), so the user always gets a readable message.
     */
    async function readServerResponse(res) {
        const type = res.headers.get('content-type') || '';
        const data = type.includes('application/json') ? await res.json().catch(() => ({})) : {};
        let message = data.message || '';
        if (data.errors) {
            message = Object.values(data.errors).flat().join(' ');
        }
        if (!res.ok && !message) {
            message = res.status === 419 ? 'Your session has expired. Please refresh the page and try again.'
                : res.status === 403 ? 'You do not have permission to do this.'
                : res.status === 404 ? 'This record no longer exists. Please refresh the page.'
                : res.status === 429 ? 'Please wait a moment — a request is already being processed.'
                : res.status >= 500 ? 'Server error — please try again. If it keeps happening, contact the administrator.'
                : 'Something went wrong. Please try again.';
        }
        return { ok: res.ok && data.success !== false, status: res.status, data, message };
    }

    function showToast(message, type) {
        const toast = document.getElementById('customToast');
        const toastMsg = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        if (!toast || !toastMsg || !toastIcon) return;

        toastMsg.textContent = message;
        toast.className = 'custom-toast';
        toast.classList.add(type === 'success' ? 'toast-success' : 'toast-error');
        toastIcon.innerHTML = type === 'success' ? '✓' : '✗';
        toast.classList.add('toast-show');
        clearTimeout(window.__lhToastTimer);
        window.__lhToastTimer = setTimeout(() => toast.classList.remove('toast-show'), type === 'success' ? 2500 : 5000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        toggleCategoryFields();

        initLeaveCategoryPicker();
        document.getElementById('leaveCategory')?.addEventListener('change', toggleCategoryFields);

        document.getElementById('applyEmployeeId')?.addEventListener('change', function () {
            document.querySelector('#applyEmployeeDropdown .wghrm-dropdown-trigger')?.classList.remove('is-invalid');
            fetchApplyLeaveBalance(this.value);
        });

        const applyEmpDropdown = document.getElementById('applyEmployeeDropdown');
        const applyEmpItems = applyEmpDropdown?.querySelectorAll('.wghrm-items-list .wghrm-item');
        if (applyEmpItems && applyEmpItems.length === 1) {
            applyEmpItems[0].click();
        }

        document.getElementById('leaveHistoryQuickSearch')?.addEventListener('input', function (e) {
            const term = e.target.value.toLowerCase().trim();
            document.querySelectorAll('#leaveHistoryTable tbody tr.leave-history-row').forEach(row => {
                row.style.display = !term || row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });

        document.getElementById('applyLeaveModal')?.addEventListener('shown.bs.offcanvas', function () {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
            initLeaveCategoryPicker();
            toggleCategoryFields();

            const employeeIdInput = document.getElementById('applyEmployeeId');
            if (employeeIdInput?.value) {
                fetchApplyLeaveBalance(employeeIdInput.value);
            }
        });

        document.getElementById('applyLeaveModal')?.addEventListener('hidden.bs.offcanvas', resetApplyLeaveDrawer);

        document.getElementById('applyLeaveForm')?.addEventListener('submit', function (e) {
            e.preventDefault();

            // Block double clicks / repeated Enter while a request is in flight
            if (this.dataset.submitting === '1') {
                return;
            }

            // Check the form before sending, with one clear message.
            const employeeField = document.getElementById('applyEmployeeId');
            const category = document.getElementById('leaveCategory')?.value || '';
            const startDate = document.getElementById('startDate')?.value || '';
            const endDate = document.getElementById('endDate')?.value || '';
            const reason = (document.getElementById('applyReason')?.value || '').trim();
            const startTime = document.getElementById('startTime')?.value || '';
            let problem = '';
            if (employeeField && !employeeField.value) {
                problem = 'Please select the employee.';
                document.querySelector('#applyEmployeeDropdown .wghrm-dropdown-trigger')?.classList.add('is-invalid');
            } else if (!category) {
                problem = 'Please select a leave category.';
            } else if (!startDate) {
                problem = 'Please choose the start date.';
            } else if (endDate && endDate < startDate) {
                problem = 'End date cannot be before the start date.';
            } else if (category === 'Gatepass Leave' && !startTime) {
                problem = 'Please choose the time for early leave.';
            } else if (!reason) {
                problem = 'Please enter the reason for leave.';
            }
            if (problem) {
                showToast(problem, 'error');
                return;
            }
            document.querySelector('#applyEmployeeDropdown .wghrm-dropdown-trigger')?.classList.remove('is-invalid');

            const form = this;
            const submitBtn = document.querySelector('button[type="submit"][form="applyLeaveForm"]');
            const submitBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            const setSubmitting = function (busy) {
                form.dataset.submitting = busy ? '1' : '';
                if (submitBtn) {
                    submitBtn.disabled = busy;
                    submitBtn.innerHTML = busy ? 'Submitting...' : submitBtnHtml;
                }
            };
            setSubmitting(true);

            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            data['total_days'] = parseFloat(data['total_days'] || '0') || 0;

            if (data['leave_type'] === 'Half Day' && data['half_day_type']) {
                data['leave_type'] = `Half Day (${data['half_day_type']})`;
            }

            fetch('{{ route("leave.apply") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
                .then(readServerResponse)
                .then(result => {
                    if (result.ok) {
                        // Keep the button disabled until the page reloads
                        showToast(result.data.message || 'Leave applied successfully! Status: Pending', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                        return;
                    }
                    setSubmitting(false);
                    showToast(result.message, 'error');
                    if (result.data.duplicate) {
                        setTimeout(() => window.location.reload(), 2500); // show the one that was already saved
                    }
                })
                .catch(err => {
                    setSubmitting(false);
                    console.error('Leave Application Error:', err);
                    showToast('Could not reach the server. Please check your connection and try again.', 'error');
                });
        });

        document.getElementById('actionForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            const selectedStatus = data['status'];
            data['remarks'] = (data['remarks'] || '').trim();
            if (data['remarks'].length < 3) {
                showToast('Please enter remarks / reason for this decision.', 'error');
                document.getElementById('actionRemarks').focus();
                return;
            }

            fetch('{{ route("leave.updateAction") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
                .then(async res => ({ ok: res.ok, body: await res.json().catch(() => ({})) }))
                .then(({ ok, body }) => {
                    if (ok && body.success) {
                        const statusLabel = selectedStatus.charAt(0).toUpperCase() + selectedStatus.slice(1).replace('_', ' ');
                        showToast('Leave status updated to: ' + statusLabel, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        const msg = body.errors ? Object.values(body.errors).flat().join(', ') : (body.message || 'Could not update the application.');
                        showToast(msg, 'error');
                    }
                })
                .catch(() => showToast('Could not update the application.', 'error'));
        });

        // Level 1: Team Leader approve / reject
        document.querySelectorAll('#tlForm [data-tl-decision]').forEach(btn => {
            btn.addEventListener('click', function () {
                const decision = this.dataset.tlDecision;
                const remarks = document.getElementById('tlRemarks').value.trim();
                if (remarks.length < 3) {
                    showToast('Please enter remarks / reason for your decision.', 'error');
                    document.getElementById('tlRemarks').focus();
                    return;
                }
                const buttons = document.querySelectorAll('#tlForm [data-tl-decision]');
                buttons.forEach(b => b.disabled = true);

                fetch('{{ route("leave.tlAction") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ leave_id: document.getElementById('tlLeaveId').value, decision, remarks })
                })
                    .then(async res => ({ ok: res.ok, body: await res.json().catch(() => ({})) }))
                    .then(({ ok, body }) => {
                        if (ok && body.success) {
                            showToast(body.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            const msg = body.errors ? Object.values(body.errors).flat().join(', ') : (body.message || 'Could not save your decision.');
                            showToast(msg, 'error');
                            buttons.forEach(b => b.disabled = false);
                        }
                    })
                    .catch(() => { showToast('Could not save your decision.', 'error'); buttons.forEach(b => b.disabled = false); });
            });
        });
    });
</script>
@endpush
