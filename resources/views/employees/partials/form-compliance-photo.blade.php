@php
    $emp = $employee ?? null;
    $pfChecked = old('pf', $emp?->pf ?? false);
    $esiChecked = old('esi', $emp?->esi ?? false);
    $insuranceChecked = old('insurance', $emp?->insurance ?? false);
@endphp

<div class="zoho-form-section-block">
    <h3 class="zoho-form-section-title">Benefits & Compliance</h3>
    <div class="zoho-compliance-grid">

        <div class="zoho-compliance-row">
            <div class="zoho-compliance-cell">
                <label class="zoho-compliance-label" for="pfToggle">PF Applicable</label>
                <div class="zoho-switch-wrap">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="pf" id="pfToggle" {{ $pfChecked ? 'checked' : '' }}>
                        <label class="form-check-label" for="pfToggle">{{ $pfChecked ? 'Yes' : 'No' }}</label>
                    </div>
                </div>
            </div>
            <div class="zoho-compliance-cell">
                <label class="zoho-compliance-label">PF No.</label>
                <div class="zoho-compliance-value">
                    <div id="pfField" class="zoho-compliance-field" style="display: {{ $pfChecked ? 'block' : 'none' }};">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                            <input type="text" name="pf_number" class="form-control" placeholder="Enter PF number"
                                value="{{ old('pf_number', $emp?->pf_number) }}">
                        </div>
                    </div>
                    <span id="pfFieldOff" class="zoho-compliance-off-hint" style="display: {{ $pfChecked ? 'none' : 'block' }};">—</span>
                </div>
            </div>
        </div>

        <div class="zoho-compliance-row">
            <div class="zoho-compliance-cell">
                <label class="zoho-compliance-label" for="esiToggle">ESI Applicable</label>
                <div class="zoho-switch-wrap">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="esi" id="esiToggle" {{ $esiChecked ? 'checked' : '' }}>
                        <label class="form-check-label" for="esiToggle">{{ $esiChecked ? 'Yes' : 'No' }}</label>
                    </div>
                </div>
            </div>
            <div class="zoho-compliance-cell">
                <label class="zoho-compliance-label">ESI No.</label>
                <div class="zoho-compliance-value">
                    <div id="esiField" class="zoho-compliance-field" style="display: {{ $esiChecked ? 'block' : 'none' }};">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield"></i></span>
                            <input type="text" name="esi_number" class="form-control" placeholder="Enter ESI number"
                                value="{{ old('esi_number', $emp?->esi_number) }}">
                        </div>
                    </div>
                    <span id="esiFieldOff" class="zoho-compliance-off-hint" style="display: {{ $esiChecked ? 'none' : 'block' }};">—</span>
                </div>
            </div>
        </div>

        <div class="zoho-compliance-row">
            <div class="zoho-compliance-cell">
                <label class="zoho-compliance-label" for="insuranceToggle">Insurance Applicable</label>
                <div class="zoho-switch-wrap">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="insurance" id="insuranceToggle" {{ $insuranceChecked ? 'checked' : '' }}>
                        <label class="form-check-label" for="insuranceToggle">{{ $insuranceChecked ? 'Yes' : 'No' }}</label>
                    </div>
                </div>
            </div>
            <div class="zoho-compliance-cell">
                <label class="zoho-compliance-label">Provider</label>
                <div class="zoho-compliance-value">
                    <div id="insuranceProviderField" class="zoho-compliance-field" style="display: {{ $insuranceChecked ? 'block' : 'none' }};">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                            <input type="text" name="insurance_provider" class="form-control" placeholder="Insurance company"
                                value="{{ old('insurance_provider', $emp?->insurance_provider) }}">
                        </div>
                    </div>
                    <span id="insuranceProviderOff" class="zoho-compliance-off-hint" style="display: {{ $insuranceChecked ? 'none' : 'block' }};">—</span>
                </div>
            </div>
        </div>

        <div class="zoho-compliance-row zoho-compliance-row--last">
            <div class="zoho-compliance-cell">
                <label class="zoho-compliance-label">Policy Number</label>
                <div class="zoho-compliance-value">
                    <div id="insurancePolicyField" class="zoho-compliance-field" style="display: {{ $insuranceChecked ? 'block' : 'none' }};">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-hash"></i></span>
                            <input type="text" name="insurance_policy_number" class="form-control" placeholder="Policy number"
                                value="{{ old('insurance_policy_number', $emp?->insurance_policy_number) }}">
                        </div>
                    </div>
                    <span id="insurancePolicyOff" class="zoho-compliance-off-hint" style="display: {{ $insuranceChecked ? 'none' : 'block' }};">—</span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function zohoToggleCompliance(toggleId, fieldId, offHintId, labelFor) {
        const toggle = document.getElementById(toggleId);
        if (!toggle) return;
        toggle.addEventListener('change', function () {
            const on = this.checked;
            const field = document.getElementById(fieldId);
            const offHint = offHintId ? document.getElementById(offHintId) : null;
            const label = labelFor ? document.querySelector('label[for="' + labelFor + '"]') : null;
            if (field) field.style.display = on ? 'block' : 'none';
            if (offHint) offHint.style.display = on ? 'none' : 'block';
            if (label) label.textContent = on ? 'Yes' : 'No';
        });
    }

    zohoToggleCompliance('pfToggle', 'pfField', 'pfFieldOff', 'pfToggle');
    zohoToggleCompliance('esiToggle', 'esiField', 'esiFieldOff', 'esiToggle');
    zohoToggleCompliance('insuranceToggle', 'insuranceProviderField', 'insuranceProviderOff', 'insuranceToggle');

    document.getElementById('insuranceToggle')?.addEventListener('change', function () {
        const on = this.checked;
        const policy = document.getElementById('insurancePolicyField');
        const policyOff = document.getElementById('insurancePolicyOff');
        if (policy) policy.style.display = on ? 'block' : 'none';
        if (policyOff) policyOff.style.display = on ? 'none' : 'block';
    });
</script>
