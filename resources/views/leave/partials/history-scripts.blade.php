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

    function setTotalDuration(displayText, numericValue) {
        const display = document.getElementById('totalDaysDisplay');
        const hidden = document.getElementById('totalDays');
        if (display) {
            display.textContent = displayText;
        }
        if (hidden) {
            hidden.value = numericValue ?? (parseFloat(String(displayText).replace(/[^0-9.]/g, '')) || 0);
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

        form.reset();
        document.getElementById('leaveCategoryPicker')?.querySelectorAll('.lv-cat-chip').forEach(c => c.classList.remove('is-selected'));
        document.getElementById('leaveCategory').value = '';
        document.getElementById('catFull').checked = true;
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

    function openActionModal(id) {
        fetch(`/api/leave/details/${id}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('actionLeaveId').value = data.id;
                document.getElementById('displayAppCode').textContent = `LA-${String(data.id).padStart(4, '0')}`;
                updateBalanceBadge(data.balance);
                document.querySelector('#actionForm select[name="status"]').value = data.status;
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('leaveActionModal')).show();
            });
    }

    function updateBalanceBadge(balance) {
        const badge = document.getElementById('displayBalanceBadge');
        if (!badge) return;

        const numericBalance = Math.max(Number(balance ?? 0), 0);
        badge.textContent = Number.isInteger(numericBalance) ? numericBalance : numericBalance.toFixed(1);
        badge.className = `lh-balance-badge ${numericBalance == 0 ? 'lh-balance-badge--zero' : 'lh-balance-badge--ok'}`;
    }

    function openViewModal(id) {
        fetch(`/api/leave/details/${id}`)
            .then(res => res.json())
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
                    document.getElementById('viewEndDateText').textContent = data.end_date ? new Date(data.end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
                    document.getElementById('viewEndTimeText').textContent = 'Return Date';
                } else {
                    document.getElementById('viewStartDateText').textContent = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    document.getElementById('viewStartTimeText').textContent = 'Full Day';
                    document.getElementById('viewEndDateText').textContent = data.end_date ? new Date(data.end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
                    document.getElementById('viewEndTimeText').textContent = 'Return Date';
                }

                document.getElementById('viewReason').textContent = data.reason;
                document.getElementById('viewMessage').textContent = data.message || 'No extra message.';

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
        setTimeout(() => toast.classList.remove('toast-show'), 2000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        toggleCategoryFields();

        initLeaveCategoryPicker();
        document.getElementById('leaveCategory')?.addEventListener('change', toggleCategoryFields);

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
        });

        document.getElementById('applyLeaveModal')?.addEventListener('hidden.bs.offcanvas', resetApplyLeaveDrawer);

        document.getElementById('applyLeaveForm')?.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!document.getElementById('leaveCategory')?.value) {
                showToast('Please select a leave category.', 'error');
                return;
            }

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
                .then(res => {
                    if (!res.ok) {
                        return res.json().then(err => { throw err; });
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('Leave applied successfully! Status: Pending', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    }
                })
                .catch(err => {
                    console.error('Leave Application Error:', err);
                    let msg = 'Something went wrong! Please check the form and try again.';
                    if (err && err.errors) {
                        msg = Object.values(err.errors).flat().join(', ');
                    } else if (err && err.message) {
                        msg = err.message;
                    } else if (typeof err === 'string') {
                        msg = err;
                    }
                    showToast(msg, 'error');
                });
        });

        document.getElementById('actionForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            const selectedStatus = data['status'];

            fetch('{{ route("leave.updateAction") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const statusLabel = selectedStatus.charAt(0).toUpperCase() + selectedStatus.slice(1).replace('_', ' ');
                        showToast('Leave status updated to: ' + statusLabel, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    }
                });
        });
    });
</script>
@endpush
