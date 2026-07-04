@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('assets/css/hrm-employee-dashboard.css') }}?v={{ filemtime(public_path('assets/css/hrm-employee-dashboard.css')) ?: time() }}">
@endpush

@section('content')
@php
    $pfChecked = old('pf', $employee->pf ?? false);
    $esiChecked = old('esi', $employee->esi ?? false);
    $insChecked = old('insurance', $employee->insurance ?? false);
@endphp
<div class="zoho-page-shell hrm-emp-page">
    @include('employees.partials.form-dashboard-header', [
        'pageTitle' => 'Edit Employee',
        'employee' => $employee,
        'submitLabel' => 'Update Employee',
        'cancelUrl' => route('employees.index'),
    ])

    <div class="main-content zoho-module-content">
        @include('employees.partials.form-alerts')

        <form method="POST" action="{{ route('employees.update', $employee->id) }}" enctype="multipart/form-data" autocomplete="off" id="employeeForm" @if($errors->any()) data-server-errors='@json($errors->getMessages())' @endif>
            @csrf
            @method('PUT')

            <div class="hrm-step-panel active" data-step="1">
                <div class="hrm-cards-grid">

                    <div class="hrm-card">
                        <div class="hrm-card-head"><i class="bi bi-person"></i><h3>Employee Information</h3></div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid">
                                <div class="hrm-field">
                                    <label>Employee Code</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-hash"></i>
                                        <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code', $employee->employee_code) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Name <span class="req">*</span></label>
                                    <div class="hrm-input-wrap"><i class="bi bi-person"></i>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Email</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-envelope"></i>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Mobile Number <span class="req">*</span></label>
                                    <div class="hrm-input-wrap"><i class="bi bi-telephone"></i>
                                        <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $employee->mobile_number) }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Department</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-building"></i>
                                        <select name="department" class="form-select" required>
                                            <option value="">Select department</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->name }}" {{ $dept->name == old('department', $employee->department) ? 'selected' : '' }}>{{ $dept->name }}</option>
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
                                                <option value="{{ $desg->name }}" {{ $desg->name == old('designation', $employee->designation) ? 'selected' : '' }}>{{ $desg->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Date of Joining</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-calendar"></i>
                                        <input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining', $employee->date_of_joining) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Date of Birth</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-calendar"></i>
                                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $employee->date_of_birth) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Gender</label>
                                    <div class="hrm-radio-row">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male" {{ old('gender', $employee->gender) == 'male' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="genderMale">Male</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female" {{ old('gender', $employee->gender) == 'female' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="genderFemale">Female</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Role</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-person-badge"></i>
                                        <select name="role" class="form-select" required>
                                            <option value="">Select Role</option>
                                            @foreach($roles as $rl)
                                                <option value="{{ $rl->slug }}" {{ old('role', $employee->role) == $rl->slug ? 'selected' : '' }}>{{ $rl->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Working Mode</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-laptop"></i>
                                        <select name="working_mode" class="form-select">
                                            <option value="Office" {{ old('working_mode', $employee->working_mode) == 'Office' ? 'selected' : '' }}>Office</option>
                                            <option value="Work from home" {{ old('working_mode', $employee->working_mode) == 'Work from home' ? 'selected' : '' }}>Work from home</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hrm-card">
                        <div class="hrm-card-head"><i class="bi bi-person-vcard"></i><h3>Identity & Contact</h3></div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid cols-1">
                                <div class="hrm-field">
                                    <label>Aadhaar Number</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-person-vcard"></i>
                                        <input type="text" name="aadhaar_number" class="form-control" value="{{ old('aadhaar_number', $employee->aadhaar_number) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>PAN Number</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-credit-card"></i>
                                        <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number', $employee->pan_number) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Address</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-geo-alt"></i>
                                        <textarea name="address" class="form-control">{{ old('address', $employee->address) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hrm-card">
                        <div class="hrm-card-head"><i class="bi bi-clock"></i><h3>Work Schedule</h3></div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid cols-1">
                                <div class="hrm-field">
                                    <label>Time In</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-clock"></i>
                                        <input type="time" name="time_in" class="form-control" value="{{ old('time_in', $employee->time_in) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Time Out</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-clock"></i>
                                        <input type="time" name="time_out" class="form-control" value="{{ old('time_out', $employee->time_out) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Leave Allotment</label>
                                    <div class="hrm-input-wrap no-icon">
                                        <input type="text" name="leave" class="form-control" value="{{ old('leave', $employee->leave) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hrm-card">
                        <div class="hrm-card-head"><i class="bi bi-shield-check"></i><h3>Benefits & Compliance</h3></div>
                        <div class="hrm-card-body">
                            <div class="hrm-compliance-field">
                                <label>PF Applicable</label>
                                <div class="hrm-toggle-inline">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="pf" id="pfToggle" {{ $pfChecked ? 'checked' : '' }}>
                                    </div>
                                    <div id="pfField" class="hrm-input-wrap no-icon" style="display:{{ $pfChecked ? 'block' : 'none' }}">
                                        <input type="text" name="pf_number" class="form-control" value="{{ old('pf_number', $employee->pf_number) }}">
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
                                        <input type="text" name="esi_number" class="form-control" value="{{ old('esi_number', $employee->esi_number) }}">
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
                                        <input type="text" name="insurance_provider" class="form-control" value="{{ old('insurance_provider', $employee->insurance_provider) }}">
                                    </div>
                                    <span id="insuranceProviderOff" class="hrm-off" style="display:{{ $insChecked ? 'none' : 'flex' }}">—</span>
                                </div>
                            </div>
                            <div class="hrm-compliance-field">
                                <label>Policy Number</label>
                                <div id="insurancePolicyWrap" class="hrm-input-wrap" style="display:{{ $insChecked ? 'flex' : 'none' }}">
                                    <i class="bi bi-file-text"></i>
                                    <input type="text" name="insurance_policy_number" id="insurancePolicyField" class="form-control"
                                        value="{{ old('insurance_policy_number', $employee->insurance_policy_number) }}">
                                </div>
                                <span id="insurancePolicyOff" class="hrm-off" style="display:{{ $insChecked ? 'none' : 'flex' }};margin-top:0">—</span>
                            </div>
                        </div>
                    </div>

                    @include('employees.partials.form-dashboard-documents', ['employee' => $employee])

                </div>
            </div>

            <div class="hrm-step-panel" data-step="2">
                <div class="hrm-cards-grid">
                    <div class="hrm-card">
                        <div class="hrm-card-head"><i class="bi bi-bank"></i><h3>Bank Details</h3></div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid cols-1">
                                <div class="hrm-field">
                                    <label>Bank Name <span class="req">*</span></label>
                                    <div class="hrm-input-wrap"><i class="bi bi-bank"></i>
                                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $employee->bank_name) }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Account Number <span class="req">*</span></label>
                                    <div class="hrm-input-wrap"><i class="bi bi-hash"></i>
                                        <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $employee->account_number) }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>IFSC Code <span class="req">*</span></label>
                                    <div class="hrm-input-wrap"><i class="bi bi-key"></i>
                                        <input type="text" name="ifsc_code" class="form-control" value="{{ old('ifsc_code', $employee->ifsc_code) }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hrm-card">
                        <div class="hrm-card-head"><i class="bi bi-cash-coin"></i><h3>Salary Details</h3></div>
                        <div class="hrm-card-body">
                            <div class="hrm-form-grid cols-1">
                                <div class="hrm-field">
                                    <label>Basic Salary <span class="req">*</span></label>
                                    <div class="hrm-input-wrap"><i class="bi bi-currency-dollar"></i>
                                        <input type="number" name="basic_salary" id="basic_salary" class="form-control salary-input" value="{{ old('basic_salary', $employee->basic_salary) }}" required>
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>HRA</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-house"></i>
                                        <input type="number" name="hra" id="hra" class="form-control salary-input" value="{{ old('hra', $employee->hra) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Conveyance Allowance</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-truck"></i>
                                        <input type="number" name="conveyance_allowance" id="conveyance_allowance" class="form-control salary-input" value="{{ old('conveyance_allowance', $employee->conveyance_allowance) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Medical Allowance</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-heart-pulse"></i>
                                        <input type="number" name="medical_allowance" id="medical_allowance" class="form-control salary-input" value="{{ old('medical_allowance', $employee->medical_allowance) }}">
                                    </div>
                                </div>
                                <div class="hrm-field">
                                    <label>Other Allowance</label>
                                    <div class="hrm-input-wrap"><i class="bi bi-gift"></i>
                                        <input type="number" name="other_allowance" id="other_allowance" class="form-control salary-input" value="{{ old('other_allowance', $employee->other_allowance) }}">
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

        @include('employees.partials.form-dashboard-footer', ['submitLabel' => 'Update Employee'])
    </div>
</div>

<script>
function hrmToggle(id, fieldId, offId) {
    document.getElementById(id)?.addEventListener('change', function () {
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
    ['insuranceProviderField','insurancePolicyWrap'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = on ? (id.includes('Wrap') ? 'flex' : 'block') : 'none'; });
    ['insuranceProviderOff','insurancePolicyOff'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = on ? 'none' : 'flex'; });
});
document.querySelectorAll('.salary-input').forEach(i => i.addEventListener('input', function () {
    const t = ['basic_salary','hra','conveyance_allowance','medical_allowance','other_allowance'].reduce((s, id) => s + (parseFloat(document.getElementById(id).value) || 0), 0);
    document.getElementById('total_salary').value = t.toFixed(2);
}));
window.addEventListener('load', function () { document.querySelectorAll('.salary-input').forEach(i => i.dispatchEvent(new Event('input'))); });
</script>
@include('employees.partials.form-dashboard-scripts')

@endsection
