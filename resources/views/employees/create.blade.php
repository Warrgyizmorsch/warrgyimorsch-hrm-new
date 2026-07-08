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
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-building"></i>
                                        <div class="dropdown w-100">
                                            <button class="wghrm-custom-select-btn dropdown-toggle w-100 text-start" type="button"
                                                data-bs-toggle="dropdown" id="departmentDropdownBtn" data-bs-auto-close="outside">
                                                @php $oldDept = collect($departments)->firstWhere('name', old('department')); @endphp
                                                <span id="departmentDropdownText">{{ $oldDept ? $oldDept->name : 'Select department' }}</span>
                                            </button>
                                            <div class="dropdown-menu w-100 hrm-dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="hrmSelect(this,'department','','Select department')">Select department</a>
                                                @foreach($departments as $dept)
                                                    <a class="dropdown-item {{ old('department') == $dept->name ? 'active' : '' }}"
                                                        href="javascript:void(0)"
                                                        onclick="hrmSelect(this,'department','{{ $dept->name }}','{{ addslashes($dept->name) }}')">{{ $dept->name }}</a>
                                                @endforeach
                                            </div>
                                            <input type="hidden" name="department" id="departmentInput" value="{{ old('department') }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Designation <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-briefcase"></i>
                                        <div class="dropdown w-100">
                                            <button class="wghrm-custom-select-btn dropdown-toggle w-100 text-start" type="button"
                                                data-bs-toggle="dropdown" id="designationDropdownBtn" data-bs-auto-close="outside">
                                                @php $oldDesg = collect($designations)->firstWhere('name', old('designation')); @endphp
                                                <span id="designationDropdownText">{{ $oldDesg ? $oldDesg->name : 'Select designation' }}</span>
                                            </button>
                                            <div class="dropdown-menu w-100 hrm-dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="hrmSelect(this,'designation','','Select designation')">Select designation</a>
                                                @foreach($designations as $desg)
                                                    <a class="dropdown-item {{ old('designation') == $desg->name ? 'active' : '' }}"
                                                        href="javascript:void(0)"
                                                        onclick="hrmSelect(this,'designation','{{ $desg->name }}','{{ addslashes($desg->name) }}')">{{ $desg->name }}</a>
                                                @endforeach
                                            </div>
                                            <input type="hidden" name="designation" id="designationInput" value="{{ old('designation') }}" required>
                                        </div>
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
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-person-badge"></i>
                                        <div class="dropdown w-100">
                                            <button class="wghrm-custom-select-btn dropdown-toggle w-100 text-start" type="button"
                                                data-bs-toggle="dropdown" id="roleDropdownBtn" data-bs-auto-close="outside">
                                                @php $oldRole = collect($roles)->firstWhere('slug', old('role')); @endphp
                                                <span id="roleDropdownText">{{ $oldRole ? $oldRole->name : 'Select Role' }}</span>
                                            </button>
                                            <div class="dropdown-menu w-100 hrm-dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="hrmSelect(this,'role','','Select Role')">Select Role</a>
                                                @foreach($roles as $rl)
                                                    <a class="dropdown-item {{ old('role') == $rl->slug ? 'active' : '' }}"
                                                        href="javascript:void(0)"
                                                        onclick="hrmSelect(this,'role','{{ $rl->slug }}','{{ addslashes($rl->name) }}')">{{ $rl->name }}</a>
                                                @endforeach
                                            </div>
                                            <input type="hidden" name="role" id="roleInput" value="{{ old('role') }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Working Mode</label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-laptop"></i>
                                        <div class="dropdown w-100">
                                            <button class="wghrm-custom-select-btn dropdown-toggle w-100 text-start" type="button"
                                                id="working_modeDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                                <span id="working_modeDropdownText">{{ old('working_mode','Office') }}</span>
                                            </button>
                                            <div class="dropdown-menu w-100 hrm-dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="hrmSelect(this,'working_mode','Office','Office')">Office</a>
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="hrmSelect(this,'working_mode','Work from home','Work from home')">Work from home</a>
                                            </div>
                                            <input type="hidden" name="working_mode" id="working_modeInput" value="{{ old('working_mode','Office') }}">
                                        </div>
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
                            <div class="hrm-form-grid cols-1">
                                <div class="hrm-field">
                                    <label>Basic Salary <span class="req">*</span></label>
                                    <div class="hrm-input-wrap">
                                        <i class="bi bi-currency-dollar"></i>
                                        <input type="number" name="basic_salary" id="basic_salary" class="form-control salary-input"
                                            placeholder="Basic Salary" value="{{ old('basic_salary') }}" required>
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

function hrmSelect(el, field, value, text) {
    const input = document.getElementById(field + 'Input');
    input.value = value;
    document.getElementById(field + 'DropdownText').innerText = text;
    el.closest('.dropdown-menu').querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    input.dispatchEvent(new Event('change', { bubbles: true }));
    if (typeof window.hrmClearFieldError === 'function') window.hrmClearFieldError(input);
}

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
