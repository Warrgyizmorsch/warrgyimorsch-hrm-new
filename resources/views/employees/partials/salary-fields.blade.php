{{--
    Salary entry for the employee create / edit forms: HR types the monthly Gross only, and the
    backend (SalaryStructureService) splits it into Basic / HRA / Other. The table is a live preview;
    PF / ESIC rows appear only when the PF / ESI toggles on this form are on.

    @param float|null $currentGross  existing gross (edit form), null on create
--}}
@php
    $salaryCfg = \App\Services\SalaryStructureService::jsConfig();
    $grossValue = old('gross_salary', isset($currentGross) ? round($currentGross, 2) : null);
    $grossRequired = !isset($currentGross);
@endphp
<div class="hrm-form-grid cols-1">
    <div class="hrm-field">
        <label>Gross Salary (monthly) @if($grossRequired)<span class="req">*</span>@endif</label>
        <div class="hrm-input-wrap"><i class="bi bi-currency-rupee"></i>
            <input type="number" step="0.01" min="0" name="gross_salary" id="gross_salary" class="form-control"
                placeholder="Gross Salary" value="{{ $grossValue }}" {{ $grossRequired ? 'required' : '' }}>
        </div>
        <p class="text-muted small mt-1 mb-0">
            Split automatically: Basic {{ $salaryCfg['basic_of_gross'] * 100 }}% of Gross,
            HRA {{ $salaryCfg['hra_of_basic'] * 100 }}% of Basic, Medical ₹{{ number_format($salaryCfg['medical_allowance']) }},
            Travel ₹{{ number_format($salaryCfg['conveyance_allowance']) }}, Other allowance = the rest.
        </p>
    </div>
    <div class="hrm-field">
        <table class="table table-sm mb-0" id="grossSalaryPreview">
            <tbody>
                <tr><td class="text-muted">Basic Salary</td><td class="text-end" data-preview="basic">₹0</td></tr>
                <tr><td class="text-muted">HRA</td><td class="text-end" data-preview="hra">₹0</td></tr>
                <tr><td class="text-muted">Medical Allowance</td><td class="text-end" data-preview="medical">₹0</td></tr>
                <tr><td class="text-muted">Travel Allowance</td><td class="text-end" data-preview="conveyance">₹0</td></tr>
                <tr><td class="text-muted">Other Allowance</td><td class="text-end" data-preview="other">₹0</td></tr>
                <tr class="fw-semibold"><td>Gross Salary</td><td class="text-end" data-preview="gross">₹0</td></tr>
                <tr data-preview-row="pf"><td class="text-muted">Employee PF ({{ $salaryCfg['employee_pf_rate'] * 100 }}% of Basic)</td><td class="text-end" data-preview="pf">₹0</td></tr>
                <tr data-preview-row="esic"><td class="text-muted">Employee ESIC ({{ $salaryCfg['employee_esic_rate'] * 100 }}% of Gross)</td><td class="text-end" data-preview="esic">₹0</td></tr>
                <tr class="fw-semibold"><td>Monthly Take-home</td><td class="text-end" data-preview="net">₹0</td></tr>
            </tbody>
        </table>
        <p class="text-muted small mt-1 mb-0" id="esicLimitNote" style="display:none;">
            ESIC not deducted: gross is above ₹{{ number_format($salaryCfg['esic_gross_limit']) }}.
        </p>
    </div>
</div>

<script>
(function () {
    const CFG = @json($salaryCfg);
    const input = document.getElementById('gross_salary');
    const table = document.getElementById('grossSalaryPreview');
    if (!input || !table) return;

    const inr = v => '₹' + Math.round(v).toLocaleString('en-IN');
    const set = (key, v) => { const c = table.querySelector('[data-preview="' + key + '"]'); if (c) c.textContent = inr(v); };
    const show = (key, on) => { const r = table.querySelector('[data-preview-row="' + key + '"]'); if (r) r.style.display = on ? '' : 'none'; };

    function refresh() {
        const gross = Math.max(0, parseFloat(input.value) || 0);
        const basic = Math.round(gross * CFG.basic_of_gross);
        const hra = Math.round(basic * CFG.hra_of_basic);
        const pfOn = !!document.getElementById('pfToggle')?.checked;
        const esiOn = !!document.getElementById('esiToggle')?.checked;
        const overLimit = gross > CFG.esic_gross_limit;
        const pf = pfOn ? Math.round(basic * CFG.employee_pf_rate) : 0;
        const esic = esiOn && !overLimit ? Math.round(gross * CFG.employee_esic_rate) : 0;

        const remaining = Math.max(0, gross - basic - hra);
        const medical = Math.min(CFG.medical_allowance, remaining);
        const conveyance = Math.min(CFG.conveyance_allowance, remaining - medical);

        set('basic', basic); set('hra', hra); set('medical', medical); set('conveyance', conveyance);
        set('other', remaining - medical - conveyance); set('gross', gross);
        set('pf', pf); set('esic', esic); set('net', gross - pf - esic);
        show('pf', pfOn); show('esic', esiOn && !overLimit);
        const note = document.getElementById('esicLimitNote');
        if (note) note.style.display = esiOn && overLimit ? '' : 'none';
    }

    input.addEventListener('input', refresh);
    ['pfToggle', 'esiToggle'].forEach(id => document.getElementById(id)?.addEventListener('change', refresh));
    refresh();
})();
</script>
