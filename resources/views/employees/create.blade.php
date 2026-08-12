@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('assets/css/hrm-employee-dashboard.css') }}?v={{ filemtime(public_path('assets/css/hrm-employee-dashboard.css')) ?: time() }}">
@endpush

@section('content')
<div class="zoho-page-shell hrm-emp-page">
    @include('employees.partials.form-dashboard-header', [
        'pageTitle' => 'Add Employee',
        'pageSubtitle' => 'Create a new employee profile with job, payroll, compliance and document details.',
        'submitLabel' => 'Submit',
        'cancelUrl' => route('employees.index'),
    ])

    <div class="main-content zoho-module-content">
        @include('employees.partials.form-alerts')

        <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data" autocomplete="off"
            id="employeeForm" @if($errors->any()) data-server-errors='@json($errors->getMessages())' @endif>
            @csrf

            {{-- Step 1: Personal (matches reference image) --}}
            <div class="hrm-step-panel active" data-step="1">
                <div class="hrm-cards-grid">

                    {{-- Employee Information --}}
                    <div class="hrm-card">
                        <div class="hrm-card-head">
                            <i class="bi bi-person"></i>
                            <h3>Employee Information</h3>
                        </div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid">
                                <div class="hrm-field">
                                    <label>Employee Code</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-hash"></i>
                                        <input type="text" name="employee_code" class="form-control" placeholder="Enter employee code"
                                            value="{{ old('employee_code') }}">
                                        <input type="hidden" name="from_job_application_id" value="{{ old('from_job_application_id') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Name <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-person"></i>
                                        <input type="text" name="name" class="form-control" placeholder="Enter employee name"
                                            value="{{ old('name') }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Email</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-envelope"></i>
                                        <input type="email" name="email" class="form-control" placeholder="Enter email"
                                            value="{{ old('email') }}" autocomplete="off">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Mobile Number <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-telephone"></i>
                                        <input type="text" name="mobile_number" class="form-control" placeholder="Enter mobile number"
                                            value="{{ old('mobile_number') }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Department <span class="req">*</span></label>
                                    <div class="hrm-input-wrap"><i class="bi bi-building"></i>
                                        <select name="department" id="employeeDepartmentSelect" class="form-select" required onchange="toggleGrossSalaryMode(this.value)">
                                            <option value="">Select department</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->name }}" {{ old('department') == $dept->name ? 'selected' : '' }}>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Designation <span class="req">*</span></label>
                                    <div class="hrm-input-wrap"><i class="bi bi-briefcase"></i>
                                        <select name="designation" class="form-select" required>
                                            <option value="">Select designation</option>
                                            @foreach($designations as $desg)
                                                <option value="{{ $desg->name }}" {{ old('designation') == $desg->name ? 'selected' : '' }}>{{ $desg->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Date of Joining</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-calendar"></i>
                                        <input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Date of Birth</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-calendar"></i>
                                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Gender</label>
                                    <div class="hrm-radio-row">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male"
                                                {{ old('gender', 'male') == 'male' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="genderMale">Male</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female"
                                                {{ old('gender') == 'female' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="genderFemale">Female</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Role</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-person-badge"></i>
                                        <select name="role" id="employeeRoleSelect" class="form-select" required onchange="toggleLedDepartments(this.value)">
                                            <option value="">Select Role</option>
                                            @foreach($roles as $rl)
                                                <option value="{{ $rl->slug }}" {{ old('role') == $rl->slug ? 'selected' : '' }}>{{ $rl->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="hrm-field" id="ledDepartmentsField" style="display: none;">
                                    <label>Additional Departments (as Team Leader)</label>
                                    <div class="hrm-input-wrap" style="align-items: flex-start;"><i class="bi bi-diagram-3"></i>
                                        <select name="additional_led_departments[]" class="form-select" multiple size="4">
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->name }}" {{ in_array($dept->name, old('additional_led_departments', [])) ? 'selected' : '' }}>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p class="text-muted small mt-1 mb-0">Departments this Team Leader can also view and manage projects/tasks for, besides their own department.</p>
                                </div>
                                <div class="hrm-field">
                                    <label>Working Mode</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-laptop"></i>
                                        <select name="working_mode" class="form-select">
                                            <option value="Office" {{ old('working_mode', 'Office') == 'Office' ? 'selected' : '' }}>Office</option>
                                            <option value="Work from home" {{ old('working_mode') == 'Work from home' ? 'selected' : '' }}>Work from home</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Password</label>
                                    <div class="hrm-input-wrap hrm-input-wrap--pw">
                                        <i class="bi bi-lock"></i>
                                        <input type="password" name="password" id="createEmpPassword" class="form-control"
                                            placeholder="Password" autocomplete="new-password">
                                        <button type="button" class="hrm-pw-toggle" onclick="togglePassword('createEmpPassword', this)">
                                            <i class="bi bi-eye-slash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Identity & Contact --}}
                    <div class="hrm-card">
                        <div class="hrm-card-head">
                            <i class="bi bi-person-vcard"></i>
                            <h3>Identity & Contact</h3>
                        </div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid cols-1">
                                <div class="hrm-field">
                                    <label>Aadhaar Number</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-person-vcard"></i>
                                        <input type="text" name="aadhaar_number" class="form-control" placeholder="Enter Aadhaar number"
                                            value="{{ old('aadhaar_number') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>PAN Number</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-credit-card"></i>
                                        <input type="text" name="pan_number" class="form-control" placeholder="E.G. ABCDE2548K"
                                            value="{{ old('pan_number') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Address</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-geo-alt"></i>
                                        <textarea name="address" class="form-control" placeholder="Enter address">{{ old('address') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Work Schedule --}}
                    <div class="hrm-card">
                        <div class="hrm-card-head">
                            <i class="bi bi-clock"></i>
                            <h3>Work Schedule</h3>
                        </div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid cols-1">
                                <div class="hrm-field">
                                    <label>Time In</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-clock"></i>
                                        <input type="time" name="time_in" class="form-control" value="{{ old('time_in', '09:30') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Time Out</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-clock"></i>
                                        <input type="time" name="time_out" class="form-control" value="{{ old('time_out', '18:00') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Leave Allotment</label>
                                    <div class="hrm-input-wrap no-icon">
                                        <input type="text" name="leave" class="form-control" placeholder="Enter leave allotment"
                                            value="{{ old('leave') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Benefits & Compliance --}}
                    @php
                        $pfChecked = old('pf', false);
                        $esiChecked = old('esi', false);
                        $insChecked = old('insurance', false);
                    @endphp
                    <div class="hrm-card">
                        <div class="hrm-card-head">
                            <i class="bi bi-shield-check"></i>
                            <h3>Benefits & Compliance</h3>
                        </div>
                        <div class="hrm-card-body">
                            <div class="hrm-compliance-field">
                                <label>PF Applicable</label>
                                <div class="hrm-toggle-inline">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="pf" id="pfToggle" {{ $pfChecked ? 'checked' : '' }}>
                                    </div>
                                    <div id="pfField" class="hrm-input-wrap no-icon" style="display:{{ $pfChecked ? 'block' : 'none' }}">
                                        <input type="text" name="pf_number" class="form-control" placeholder="PF Number"
                                            value="{{ old('pf_number') }}">
                                    </div>
                                    <span id="pfFieldOff" class="hrm-off" style="display:{{ $pfChecked ? 'none' : 'flex' }}">—</span>
                                </div>
                            </div>
                            <div class="hrm-compliance-field">
                                <label>ESI Applicable</label>
                                <div class="hrm-toggle-inline">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="esi" id="esiToggle" {{ $esiChecked ? 'checked' : '' }}>
                                    </div>
                                    <div id="esiField" class="hrm-input-wrap no-icon" style="display:{{ $esiChecked ? 'block' : 'none' }}">
                                        <input type="text" name="esi_number" class="form-control" placeholder="ESI Number"
                                            value="{{ old('esi_number') }}">
                                    </div>
                                    <span id="esiFieldOff" class="hrm-off" style="display:{{ $esiChecked ? 'none' : 'flex' }}">—</span>
                                </div>
                            </div>
                            <div class="hrm-compliance-field">
                                <label>Insurance Applicable</label>
                                <div class="hrm-toggle-inline">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="insurance" id="insuranceToggle" {{ $insChecked ? 'checked' : '' }}>
                                    </div>
                                    <div id="insuranceProviderField" class="hrm-input-wrap no-icon" style="display:{{ $insChecked ? 'block' : 'none' }}">
                                        <input type="text" name="insurance_provider" class="form-control" placeholder="Insurance Provider"
                                            value="{{ old('insurance_provider') }}">
                                    </div>
                                    <span id="insuranceProviderOff" class="hrm-off" style="display:{{ $insChecked ? 'none' : 'flex' }}">—</span>
                                </div>
                            </div>
                            <div class="hrm-compliance-field">
                                <label>Policy Number</label>
                                <div id="insurancePolicyWrap" class="hrm-input-wrap" style="display:{{ $insChecked ? 'flex' : 'none' }}">
                                    <i class="bi bi-file-text"></i>
                                    <input type="text" name="insurance_policy_number" id="insurancePolicyField" class="form-control"
                                        placeholder="Policy Number" value="{{ old('insurance_policy_number') }}">
                                </div>
                                <span id="insurancePolicyOff" class="hrm-off" style="display:{{ $insChecked ? 'none' : 'flex' }};margin-top:0">—</span>
                            </div>
                        </div>
                    </div>

                    @include('employees.partials.form-dashboard-documents', ['employee' => null])

                </div>
            </div>

            {{-- Step 2: Job Details --}}
            <div class="hrm-step-panel" data-step="2">
                <div class="hrm-cards-grid">
                    <div class="hrm-card">
                        <div class="hrm-card-head">
                            <i class="bi bi-bank"></i>
                            <h3>Bank Details</h3>
                        </div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid cols-1">
                                <div class="hrm-field">
                                    <label>Bank Name <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-bank"></i>
                                        <input type="text" name="bank_name" class="form-control" placeholder="Bank Name"
                                            value="{{ old('bank_name') }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Account Number <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-hash"></i>
                                        <input type="text" name="account_number" class="form-control" placeholder="Account Number"
                                            value="{{ old('account_number') }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>IFSC Code <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-key"></i>
                                        <input type="text" name="ifsc_code" class="form-control" placeholder="IFSC Code"
                                            value="{{ old('ifsc_code') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hrm-card">
                        <div class="hrm-card-head">
                            <i class="bi bi-cash-coin"></i>
                            <h3>Salary Details</h3>
                        </div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid cols-1" id="grossSalaryFields" style="display:none;">
                                <div class="hrm-field">
                                    <label>Gross Salary <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-currency-dollar"></i>
                                        <input type="number" name="gross_salary" id="gross_salary" class="form-control"
                                            placeholder="Gross Salary" value="{{ old('gross_salary') }}">
                                    </div>
                                    <p class="text-muted small mt-1 mb-0">Automatically split into Basic, DA, HRA, Conveyance &amp; Medical/Other allowances per the Business Development salary structure.</p>
                                </div>
                                <div class="hrm-field">
                                    <table class="table table-sm mb-0" id="grossSalaryPreview">
                                        <tbody>
                                            <tr><td class="text-muted">Basic Salary</td><td class="text-end" data-preview="basic_salary">₹0.00</td></tr>
                                            <tr><td class="text-muted">Dearness Allowance</td><td class="text-end" data-preview="dearness_allowance">₹0.00</td></tr>
                                            <tr><td class="text-muted">HRA</td><td class="text-end" data-preview="hra">₹0.00</td></tr>
                                            <tr><td class="text-muted">Conveyance</td><td class="text-end" data-preview="conveyance_allowance">₹0.00</td></tr>
                                            <tr><td class="text-muted">Medical Allowance</td><td class="text-end" data-preview="medical_allowance">₹0.00</td></tr>
                                            <tr><td class="text-muted">Other Allowance</td><td class="text-end" data-preview="other_allowance">₹0.00</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="hrm-form-grid cols-1" id="individualSalaryFields">
                                <div class="hrm-field">
                                    <label>Basic Salary <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-currency-dollar"></i>
                                        <input type="number" name="basic_salary" id="basic_salary" class="form-control salary-input"
                                            placeholder="Basic Salary" value="{{ old('basic_salary') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>HRA</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-house"></i>
                                        <input type="number" name="hra" id="hra" class="form-control salary-input"
                                            placeholder="HRA" value="{{ old('hra') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Conveyance Allowance</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-truck"></i>
                                        <input type="number" name="conveyance_allowance" id="conveyance_allowance"
                                            class="form-control salary-input" placeholder="Conveyance"
                                            value="{{ old('conveyance_allowance') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Medical Allowance</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-heart-pulse"></i>
                                        <input type="number" name="medical_allowance" id="medical_allowance"
                                            class="form-control salary-input" placeholder="Medical"
                                            value="{{ old('medical_allowance') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Other Allowance</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-gift"></i>
                                        <input type="number" name="other_allowance" id="other_allowance"
                                            class="form-control salary-input" placeholder="Other"
                                            value="{{ old('other_allowance') }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Total Salary</label>
                                    <div class="hrm-input-wrap no-icon">
                                        <input type="number" id="total_salary" class="form-control bg-light" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        @include('employees.partials.form-dashboard-footer', ['submitLabel' => 'Submit'])
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    }
}

function toggleLedDepartments(roleSlug) {
    const field = document.getElementById('ledDepartmentsField');
    if (field) field.style.display = roleSlug === 'team_leader' ? 'block' : 'none';
}
toggleLedDepartments(document.getElementById('employeeRoleSelect')?.value);

const SALARY_STRUCTURE_RATIOS = {
    'Business Development': {
        basic_salary: 0.59,
        dearness_allowance: 4705 / 35000,
        hra: 3345 / 35000,
        conveyance_allowance: 1600 / 35000,
        medical_allowance: 1200 / 35000,
        other_allowance: 0.10,
    },
};

function toggleGrossSalaryMode(department) {
    const grossFields = document.getElementById('grossSalaryFields');
    const individualFields = document.getElementById('individualSalaryFields');
    const useGross = !!SALARY_STRUCTURE_RATIOS[department];
    if (grossFields) grossFields.style.display = useGross ? 'block' : 'none';
    if (individualFields) individualFields.style.display = useGross ? 'none' : 'block';
    document.getElementById('gross_salary').required = useGross;
    document.getElementById('basic_salary').required = !useGross;
    if (useGross) updateGrossSalaryPreview(department);
}
toggleGrossSalaryMode(document.getElementById('employeeDepartmentSelect')?.value);

function updateGrossSalaryPreview(department) {
    const ratios = SALARY_STRUCTURE_RATIOS[department];
    if (!ratios) return;
    const gross = parseFloat(document.getElementById('gross_salary')?.value) || 0;
    document.querySelectorAll('#grossSalaryPreview [data-preview]').forEach(cell => {
        const field = cell.getAttribute('data-preview');
        const amount = gross * (ratios[field] || 0);
        cell.textContent = '₹' + amount.toFixed(2);
    });
}

document.getElementById('gross_salary')?.addEventListener('input', function () {
    updateGrossSalaryPreview(document.getElementById('employeeDepartmentSelect')?.value);
});

function hrmToggle(id, fieldId, offId) {
    const t = document.getElementById(id);
    if (!t) return;
    t.addEventListener('change', function () {
        const on = this.checked;
        const f = document.getElementById(fieldId);
        const o = document.getElementById(offId);
        if (f) f.style.display = on ? 'block' : 'none';
        if (o) o.style.display = on ? 'none' : 'flex';
    });
}

hrmToggle('pfToggle', 'pfField', 'pfFieldOff');
hrmToggle('esiToggle', 'esiField', 'esiFieldOff');
document.getElementById('insuranceToggle')?.addEventListener('change', function () {
    const on = this.checked;
    const provider = document.getElementById('insuranceProviderField');
    const providerOff = document.getElementById('insuranceProviderOff');
    const policyWrap = document.getElementById('insurancePolicyWrap');
    const policyOff = document.getElementById('insurancePolicyOff');
    if (provider) provider.style.display = on ? 'block' : 'none';
    if (providerOff) providerOff.style.display = on ? 'none' : 'flex';
    if (policyWrap) policyWrap.style.display = on ? 'flex' : 'none';
    if (policyOff) policyOff.style.display = on ? 'none' : 'flex';
});

document.querySelectorAll('.salary-input').forEach(i => i.addEventListener('input', function () {
    const t = ['basic_salary','hra','conveyance_allowance','medical_allowance','other_allowance']
        .reduce((s, id) => s + (parseFloat(document.getElementById(id).value) || 0), 0);
    document.getElementById('total_salary').value = t.toFixed(2);
}));
window.addEventListener('load', function () {
    document.querySelectorAll('.salary-input').forEach(i => i.dispatchEvent(new Event('input')));
});
</script>
@include('employees.partials.form-dashboard-scripts')

@endsection
