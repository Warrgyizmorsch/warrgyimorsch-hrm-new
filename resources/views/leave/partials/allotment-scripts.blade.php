@push('scripts')
<script>
(function () {
    const storeUrl = @json(route('leave.storeAllotment'));
    const allotmentUrl = @json(route('leave.allotment'));
    const csrfToken = @json(csrf_token());

    let historyPage = 1;
    let balancePage = 1;

    window.updateView = function () {
        const month = document.getElementById('monthSelect')?.value;
        if (!month) return;
        window.location.href = allotmentUrl + '?month=' + encodeURIComponent(month);
    };

    window.removeRow = function (btn) {
        const container = btn.closest('tr') || btn.closest('.la-mobile-card');
        const input = container?.querySelector('.allotment-input');
        const empId = input?.dataset.employeeId;

        if (!empId) {
            container?.remove();
            return;
        }

        document.querySelectorAll('.allotment-input[data-employee-id="' + empId + '"]').forEach(function (el) {
            el.closest('tr')?.remove();
            el.closest('.la-mobile-card')?.remove();
        });
    };

    function collectAllotments() {
        const allotments = {};
        const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
        const selector = isMobile
            ? '.leave-allotment-page .la-panel .la-mobile-list .allotment-input'
            : '#employeeTable .allotment-input';

        document.querySelectorAll(selector).forEach(function (input) {
            const id = input.dataset.employeeId;
            if (id) {
                allotments[id] = parseFloat(input.value) || 0;
            }
        });

        return allotments;
    }

    window.saveAllotments = function () {
        const month = document.getElementById('monthSelect')?.value;
        const allotments = collectAllotments();
        const btn = document.querySelector('.la-panel-foot .zoho-btn-primary');

        if (btn) {
            btn.disabled = true;
        }

        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ month: month, allotments: allotments })
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (result.ok && result.data.success) {
                    Toast.fire({ icon: 'success', title: result.data.message || 'Leaves allotted successfully' });
                    setTimeout(function () { window.location.reload(); }, 1200);
                    return;
                }
                Toast.fire({ icon: 'error', title: result.data.error || result.data.message || 'Could not save allotments' });
            })
            .catch(function () {
                Toast.fire({ icon: 'error', title: 'Something went wrong!' });
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                }
            });
    };

    function initTabSwitching() {
        const tabs = document.querySelectorAll('.la-page-tab[data-la-tab]');
        const panels = {
            history: document.getElementById('laTabHistory'),
            balance: document.getElementById('laTabBalance'),
        };

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const target = tab.dataset.laTab;
                if (!target || !panels[target]) return;

                tabs.forEach(function (t) {
                    const active = t === tab;
                    t.classList.toggle('is-active', active);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                Object.keys(panels).forEach(function (key) {
                    const panel = panels[key];
                    const active = key === target;
                    panel.classList.toggle('is-active', active);
                    panel.hidden = !active;
                });

                if (target === 'balance') {
                    renderBalancePagination();
                }
            });
        });
    }

    function getHistorySearchTerm() {
        return (document.getElementById('historySearch')?.value || '').trim().toLowerCase();
    }

    function getHistoryPerPage() {
        return parseInt(document.getElementById('entriesPerPage')?.value, 10) || 10;
    }

    function getFilteredHistoryRows() {
        const term = getHistorySearchTerm();
        return Array.from(document.querySelectorAll('#historyBody tr.history-row')).filter(function (row) {
            return row.textContent.toLowerCase().includes(term);
        });
    }

    function renderHistoryPagination() {
        const desktopRows = Array.from(document.querySelectorAll('#historyBody tr.history-row'));
        const mobileRows = Array.from(document.querySelectorAll('#historyMobileBody .history-row'));
        const filtered = getFilteredHistoryRows();
        const perPage = getHistoryPerPage();
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));

        if (historyPage > totalPages) {
            historyPage = totalPages;
        }

        const start = (historyPage - 1) * perPage;
        const end = start + perPage;
        const visible = new Set(filtered.slice(start, end));

        desktopRows.forEach(function (row, index) {
            const inFilter = filtered.includes(row);
            const show = inFilter && visible.has(row);
            row.style.display = show ? '' : 'none';
            row.classList.toggle('history-row-visible', show);
            if (mobileRows[index]) {
                mobileRows[index].style.display = show ? '' : 'none';
                mobileRows[index].classList.toggle('history-row-visible', show);
            }
        });

        const info = document.getElementById('paginationInfo');
        if (info) {
            info.textContent = total === 0
                ? 'No entries to show'
                : 'Showing ' + (start + 1) + ' to ' + Math.min(end, total) + ' of ' + total + ' entries';
        }

        renderPaginationControls('paginationControls', historyPage, totalPages, function (page) {
            historyPage = page;
            renderHistoryPagination();
        });
    }

    window.changeEntriesPerPage = function () {
        historyPage = 1;
        renderHistoryPagination();
    };

    function getBalanceSearchTerm() {
        return (document.getElementById('balanceSearch')?.value || '').trim().toLowerCase();
    }

    function getBalancePerPage() {
        return parseInt(document.getElementById('balanceEntriesPerPage')?.value, 10) || 10;
    }

    function getFilteredBalanceRows() {
        const term = getBalanceSearchTerm();
        return Array.from(document.querySelectorAll('#balanceTableBody tr.balance-row')).filter(function (row) {
            return row.textContent.toLowerCase().includes(term);
        });
    }

    function renderBalancePagination() {
        const desktopRows = Array.from(document.querySelectorAll('#balanceTableBody tr.balance-row'));
        const mobileRows = Array.from(document.querySelectorAll('#balanceMobileBody .balance-row'));
        const filtered = getFilteredBalanceRows();
        const perPage = getBalancePerPage();
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));

        if (balancePage > totalPages) {
            balancePage = totalPages;
        }

        const start = (balancePage - 1) * perPage;
        const end = start + perPage;
        const visible = new Set(filtered.slice(start, end));

        desktopRows.forEach(function (row, index) {
            const inFilter = filtered.includes(row);
            const show = inFilter && visible.has(row);
            row.style.display = show ? '' : 'none';
            if (mobileRows[index]) {
                mobileRows[index].style.display = show ? '' : 'none';
            }
        });

        const info = document.getElementById('balancePaginationInfo');
        if (info) {
            info.textContent = total === 0
                ? 'No entries to show'
                : 'Showing ' + (start + 1) + ' to ' + Math.min(end, total) + ' of ' + total + ' entries';
        }

        renderPaginationControls('balancePaginationControls', balancePage, totalPages, function (page) {
            balancePage = page;
            renderBalancePagination();
        });
    }

    function renderPaginationControls(containerId, currentPage, totalPages, onPage) {
        const controls = document.getElementById(containerId);
        if (!controls) return;

        controls.innerHTML = '';
        if (totalPages <= 1) return;

        const addBtn = function (label, page, disabled, active) {
            const li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = 'javascript:void(0)';
            a.textContent = label;
            if (!disabled && !active) {
                a.addEventListener('click', function () { onPage(page); });
            }
            li.appendChild(a);
            controls.appendChild(li);
        };

        addBtn('Prev', currentPage - 1, currentPage === 1, false);

        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1) {
                addBtn(String(p), p, false, p === currentPage);
            } else if (Math.abs(p - currentPage) === 2) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = '<span class="page-link">...</span>';
                controls.appendChild(dots);
            }
        }

        addBtn('Next', currentPage + 1, currentPage === totalPages, false);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTabSwitching();

        const historySearch = document.getElementById('historySearch');
        if (historySearch) {
            historySearch.addEventListener('input', function () {
                historyPage = 1;
                renderHistoryPagination();
            });
        }

        const balanceSearch = document.getElementById('balanceSearch');
        if (balanceSearch) {
            balanceSearch.addEventListener('input', function () {
                balancePage = 1;
                renderBalancePagination();
            });
        }

        const balanceEntries = document.getElementById('balanceEntriesPerPage');
        if (balanceEntries) {
            balanceEntries.addEventListener('change', function () {
                balancePage = 1;
                renderBalancePagination();
            });
        }

        if (document.getElementById('historyBody')) {
            renderHistoryPagination();
        }

        if (document.getElementById('balanceTableBody')) {
            renderBalancePagination();
        }
    });
})();
</script>
@endpush
