<script>
(function () {
    const tabOrder = ['personal', 'job', 'compliance', 'documents'];
    const prevBtn = document.getElementById('empFormPrevBtn');
    const nextBtn = document.getElementById('empFormNextBtn');
    const nextBtnHeader = document.getElementById('empFormNextBtnHeader');
    const submitBtn = document.getElementById('empFormSubmitBtn');
    const draftBtn = document.getElementById('empFormDraftBtn');
    const draftBtnHeader = document.getElementById('empFormDraftBtnHeader');
    const tabEl = document.getElementById('employeeTab');
    const form = document.getElementById('employeeForm');
    const draftKey = 'erp_employee_form_draft_' + (form?.action || 'create');

    function activeTabId() {
        const pane = document.querySelector('#employeeTabContent .tab-pane.active, #employeeTabContent .tab-pane.show');
        return pane ? pane.id : 'personal';
    }

    function showTab(tabId) {
        const trigger = document.getElementById(tabId + '-tab');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
        updateFooter();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function fieldLabel(field) {
        const container = field.closest('.form-field, [class*="col-"]');
        if (container) {
            const label = container.querySelector('label');
            if (label) return label.innerText.replace('*', '').trim();
        }
        return 'a required field';
    }

    function validateTabPane(tabPane) {
        if (!tabPane) return { valid: true, missing: [] };

        const missing = [];
        tabPane.querySelectorAll('[required]').forEach(function (field) {
            if (!String(field.value || '').trim()) {
                missing.push(fieldLabel(field));
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        return { valid: !missing.length, missing: missing };
    }

    function validateCurrentTab() {
        const currentTab = document.querySelector('#employeeTabContent .tab-pane.active');
        const result = validateTabPane(currentTab);
        if (!result.valid) {
            alert('Please fill required fields: ' + result.missing.join(', '));
        }
        return result.valid;
    }

    function validateAllTabs() {
        let firstInvalidTab = null;
        const allMissing = [];

        tabOrder.forEach(function (tabId) {
            const tabPane = document.getElementById(tabId);
            const result = validateTabPane(tabPane);
            if (!result.valid) {
                allMissing.push.apply(allMissing, result.missing);
                if (!firstInvalidTab) firstInvalidTab = tabId;
            }
        });

        if (allMissing.length) {
            alert('Please fill required fields: ' + allMissing.join(', '));
            if (firstInvalidTab) showTab(firstInvalidTab);
            return false;
        }
        return true;
    }

    function updateFooter() {
        const id = activeTabId();
        const idx = tabOrder.indexOf(id);

        if (prevBtn) prevBtn.classList.toggle('d-none', idx <= 0);
        if (nextBtn) nextBtn.classList.toggle('d-none', idx >= tabOrder.length - 1);
        if (nextBtnHeader) nextBtnHeader.classList.toggle('d-none', idx >= tabOrder.length - 1);
        if (submitBtn) submitBtn.classList.toggle('d-none', idx < tabOrder.length - 1);
    }

    function goNext() {
        if (!validateCurrentTab()) return;
        const idx = tabOrder.indexOf(activeTabId());
        if (idx >= 0 && idx < tabOrder.length - 1) showTab(tabOrder[idx + 1]);
    }

    if (nextBtn) nextBtn.addEventListener('click', goNext);
    if (nextBtnHeader) nextBtnHeader.addEventListener('click', goNext);

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            const idx = tabOrder.indexOf(activeTabId());
            if (idx > 0) showTab(tabOrder[idx - 1]);
        });
    }

    if (tabEl) {
        tabEl.addEventListener('shown.bs.tab', updateFooter);
    }

    form?.addEventListener('submit', function (e) {
        if (!validateAllTabs()) {
            e.preventDefault();
        }
    });

    function showDraftToast() {
        const toast = document.getElementById('erpDraftToast');
        if (!toast) return;
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 2200);
    }

    function saveDraft() {
        if (!form) return;
        const data = {};
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.name || el.type === 'file' || el.type === 'password') return;
            if (el.type === 'checkbox') {
                data[el.name] = el.checked;
            } else if (el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else {
                data[el.name] = el.value;
            }
        });
        try {
            localStorage.setItem(draftKey, JSON.stringify(data));
            showDraftToast();
        } catch (e) {}
    }

    function restoreDraft() {
        if (!form) return;
        let data;
        try {
            data = JSON.parse(localStorage.getItem(draftKey) || 'null');
        } catch (e) {
            return;
        }
        if (!data || typeof data !== 'object') return;

        Object.keys(data).forEach(function (name) {
            const fields = form.querySelectorAll('[name="' + name + '"]');
            if (!fields.length) return;
            const val = data[name];
            fields.forEach(function (field) {
                if (field.type === 'checkbox') {
                    field.checked = !!val;
                    field.dispatchEvent(new Event('change'));
                } else if (field.type === 'radio') {
                    field.checked = field.value === val;
                } else if (field.type !== 'file') {
                    field.value = val;
                    field.dispatchEvent(new Event('change'));
                }
            });
            const textEl = document.getElementById(name + 'DropdownText');
            if (textEl && typeof val === 'string' && val) textEl.textContent = val;
        });
    }

    if (draftBtn) draftBtn.addEventListener('click', saveDraft);
    if (draftBtnHeader) draftBtnHeader.addEventListener('click', saveDraft);

    restoreDraft();
    updateFooter();
})();
</script>
