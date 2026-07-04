<script>
(function () {
    const panels = document.querySelectorAll('.hrm-step-panel');
    const steps = document.querySelectorAll('#hrmStepper [data-step]');
    const prevBtn = document.getElementById('empFormPrevBtn');
    const nextBtn = document.getElementById('empFormNextBtn');
    const nextBtnHeader = document.getElementById('empFormNextBtnHeader');
    const submitBtn = document.getElementById('empFormSubmitBtn');
    const submitBtnHeader = document.getElementById('empFormSubmitBtnHeader');
    const draftBtn = document.getElementById('empFormDraftBtn');
    const draftBtnHeader = document.getElementById('empFormDraftBtnHeader');
    const form = document.getElementById('employeeForm');
    const totalSteps = panels.length;
    let current = 1;

    function fieldLabel(field) {
        const wrap = field.closest('.hrm-field');
        if (wrap) {
            const label = wrap.querySelector('label');
            if (label) return label.innerText.replace(/\*/g, '').trim();
        }
        return 'This field';
    }

    function showFieldError(field, message) {
        const wrap = field.closest('.hrm-field');
        if (!wrap) return;

        field.classList.add('is-invalid');
        const inputWrap = field.closest('.hrm-input-wrap');
        if (inputWrap) inputWrap.classList.add('is-invalid');

        let fb = wrap.querySelector('.hrm-field-feedback');
        if (!fb) {
            fb = document.createElement('div');
            fb.className = 'hrm-field-feedback';
            fb.setAttribute('role', 'alert');
            wrap.appendChild(fb);
        }
        fb.textContent = message;
    }

    function clearFieldError(field) {
        const wrap = field.closest('.hrm-field');
        if (!wrap) return;

        field.classList.remove('is-invalid');
        const inputWrap = field.closest('.hrm-input-wrap');
        if (inputWrap) inputWrap.classList.remove('is-invalid');

        const fb = wrap.querySelector('.hrm-field-feedback');
        if (fb) fb.remove();
    }

    function clearPanelErrors(panel) {
        if (!panel) return;
        panel.querySelectorAll('.hrm-field-feedback').forEach(function (el) { el.remove(); });
        panel.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    }

    function validatePanel(panel) {
        if (!panel) return { valid: true, invalidFields: [] };

        clearPanelErrors(panel);
        const invalidFields = [];

        panel.querySelectorAll('[required]').forEach(function (field) {
            if (field.type === 'radio') {
                const group = panel.querySelectorAll('[name="' + field.name + '"]');
                const checked = Array.from(group).some(function (r) { return r.checked; });
                if (!checked && !invalidFields.some(function (f) { return f.name === field.name; })) {
                    showFieldError(field, fieldLabel(field) + ' is required');
                    invalidFields.push(field);
                }
                return;
            }

            if (!String(field.value || '').trim()) {
                showFieldError(field, fieldLabel(field) + ' is required');
                invalidFields.push(field);
            }
        });

        return { valid: !invalidFields.length, invalidFields: invalidFields };
    }

    function focusFirstInvalid(fields) {
        if (!fields.length) return;
        const field = fields[0];
        const target = field.closest('.hrm-input-wrap') || field;
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (typeof field.focus === 'function' && field.type !== 'hidden') {
            field.focus({ preventScroll: true });
        }
    }

    function updateFooter() {
        if (prevBtn) prevBtn.classList.toggle('d-none', current <= 1);
        if (nextBtn) nextBtn.classList.toggle('d-none', current >= totalSteps);
        if (nextBtnHeader) nextBtnHeader.classList.toggle('d-none', current >= totalSteps);
        if (submitBtn) submitBtn.classList.toggle('d-none', current < totalSteps);
        if (submitBtnHeader) submitBtnHeader.classList.toggle('d-none', current < totalSteps);
    }

    function goToStep(n, options) {
        options = options || {};
        current = Math.max(1, Math.min(n, totalSteps));

        panels.forEach(function (p) {
            p.classList.toggle('active', parseInt(p.dataset.step, 10) === current);
        });

        steps.forEach(function (s) {
            const sn = parseInt(s.dataset.step, 10);
            const isActive = sn === current;
            s.classList.toggle('active', isActive);
            s.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        updateFooter();
        if (options.scrollTop !== false) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function tryGoForward(target) {
        if (target > current) {
            const panel = document.querySelector('.hrm-step-panel[data-step="' + current + '"]');
            const result = validatePanel(panel);
            if (!result.valid) {
                focusFirstInvalid(result.invalidFields);
                return;
            }
        }
        goToStep(target);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            tryGoForward(current + 1);
        });
    }

    if (nextBtnHeader) {
        nextBtnHeader.addEventListener('click', function () {
            tryGoForward(current + 1);
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            goToStep(current - 1);
        });
    }

    steps.forEach(function (step) {
        step.addEventListener('click', function () {
            tryGoForward(parseInt(this.dataset.step, 10));
        });
    });

    form?.addEventListener('input', function (e) {
        if (e.target.matches('input, select, textarea')) clearFieldError(e.target);
    });

    form?.addEventListener('change', function (e) {
        if (e.target.matches('input, select, textarea')) clearFieldError(e.target);
    });

    form?.addEventListener('submit', function (e) {
        let firstBad = null;
        let firstInvalid = null;

        panels.forEach(function (panel) {
            const result = validatePanel(panel);
            if (!result.valid) {
                if (!firstBad) {
                    firstBad = parseInt(panel.dataset.step, 10);
                    firstInvalid = result.invalidFields[0];
                }
            }
        });

        if (firstBad) {
            e.preventDefault();
            goToStep(firstBad, { scrollTop: false });
            if (firstInvalid) focusFirstInvalid([firstInvalid]);
        }
    });

    function applyServerErrors() {
        const raw = form?.dataset.serverErrors;
        if (!raw) return;

        let messages = {};
        try { messages = JSON.parse(raw); } catch (err) { return; }

        let firstField = null;
        Object.keys(messages).forEach(function (name) {
            const msg = messages[name][0];
            const field = form.querySelector('[name="' + name + '"]');
            if (field) {
                showFieldError(field, msg);
                if (!firstField) firstField = field;
            }
        });

        if (firstField) {
            const panel = firstField.closest('.hrm-step-panel');
            if (panel) goToStep(parseInt(panel.dataset.step, 10), { scrollTop: false });
            focusFirstInvalid([firstField]);
        }
    }

    function saveDraft() {
        if (!form) return;
        const data = {};
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.name || el.type === 'file' || el.type === 'password') return;
            if (el.type === 'checkbox') data[el.name] = el.checked;
            else if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; }
            else data[el.name] = el.value;
        });
        try {
            localStorage.setItem('hrm_employee_draft', JSON.stringify(data));
            const note = document.getElementById('hrmDraftSavedNote');
            if (note) {
                note.classList.remove('d-none');
                setTimeout(function () { note.classList.add('d-none'); }, 2500);
            }
        } catch (err) {}
    }

    window.hrmClearFieldError = clearFieldError;

    if (draftBtn) draftBtn.addEventListener('click', saveDraft);
    if (draftBtnHeader) draftBtnHeader.addEventListener('click', saveDraft);

    goToStep(1);
    applyServerErrors();
})();
</script>
