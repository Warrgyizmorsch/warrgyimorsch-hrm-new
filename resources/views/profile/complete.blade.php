@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('assets/css/hrm-employee-dashboard.css') }}?v={{ filemtime(public_path('assets/css/hrm-employee-dashboard.css')) ?: time() }}">
<style>
    .selfp-banner { border-radius: 12px; padding: 14px 18px; margin-bottom: 16px; display: flex; gap: 12px; align-items: flex-start; }
    .selfp-banner i { font-size: 20px; line-height: 1.2; }
    .selfp-banner--info { background: rgba(56, 88, 249, .08); color: #2b44c7; }
    .selfp-banner--wait { background: rgba(245, 158, 11, .1); color: #a16207; }
    .selfp-banner--back { background: rgba(239, 68, 68, .08); color: #b91c1c; }
    .selfp-readonly { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px 20px; }
    .selfp-readonly div small { display: block; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; }
    .selfp-readonly div span { font-weight: 600; color: #0f172a; }
    .selfp-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 0 40px; }
</style>
@endpush

@section('content')
<div class="zoho-page-shell hrm-emp-page">
    <div class="main-content zoho-module-content" style="max-width: 1100px; margin: 0 auto;">
        <h4 class="fw-bold mb-1 mt-3">{{ $isOnboarding ? 'Complete your profile' : 'My personal & bank details' }}</h4>
        <p class="text-muted small mb-3">
            {{ $isOnboarding
                ? 'Fill in your details below. HR will check them and activate your account.'
                : 'Changes are sent to HR for approval. Your current details stay in use until they are approved.' }}
        </p>

        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif

        @if ($openRequest?->status === 'returned')
            <div class="selfp-banner selfp-banner--back">
                <i class="bi bi-arrow-return-left"></i>
                <div><strong>HR sent this back for changes:</strong><br>{{ $openRequest->review_note }}</div>
            </div>
        @elseif ($openRequest?->status === 'pending')
            <div class="selfp-banner selfp-banner--wait">
                <i class="bi bi-hourglass-split"></i>
                <div>
                    <strong>Waiting for HR approval</strong> — submitted {{ $openRequest->submitted_at?->format('d M Y, h:i A') }}.
                    You can still correct anything below and submit again.
                    @if($isOnboarding) The rest of the portal unlocks once HR approves. @endif
                </div>
            </div>
        @elseif ($isOnboarding)
            <div class="selfp-banner selfp-banner--info">
                <i class="bi bi-info-circle"></i>
                <div>Welcome, {{ $employee->name }}! Please fill in every field marked <span class="text-danger">*</span>. Have your Aadhaar, PAN and bank passbook/cheque handy.</div>
            </div>
        @endif

        {{-- Set by HR — shown for reference, not editable here. --}}
        <div class="hrm-card mb-3">
            <div class="hrm-card-head"><i class="bi bi-briefcase"></i><h3>Job details (set by HR)</h3></div>
            <div class="hrm-card-body">
                <div class="selfp-readonly">
                    <div><small>Employee Code</small><span>{{ $employee->employee_code ?: '—' }}</span></div>
                    <div><small>Name</small><span>{{ $employee->name }}</span></div>
                    <div><small>Email</small><span>{{ $employee->email ?: '—' }}</span></div>
                    <div><small>Mobile</small><span>{{ $employee->mobile_number ?: '—' }}</span></div>
                    <div><small>Department</small><span>{{ $employee->departmentRef->name ?? '—' }}</span></div>
                    <div><small>Designation</small><span>{{ $employee->designation ?: '—' }}</span></div>
                    <div><small>Date of Joining</small><span>{{ $employee->date_of_joining ? \Carbon\Carbon::parse($employee->date_of_joining)->format('d M Y') : '—' }}</span></div>
                </div>
                <p class="text-muted small mb-0 mt-2">Something wrong here? Tell HR — these can only be changed by them.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.complete.submit') }}" enctype="multipart/form-data" autocomplete="off">
            @csrf
            @php
                $val = fn ($field) => old($field, $values[$field] ?? '');
                $err = fn ($field) => $errors->first($field);
            @endphp

            <div class="hrm-cards-grid">
                <div class="hrm-card">
                    <div class="hrm-card-head"><i class="bi bi-person"></i><h3>Personal details</h3></div>
                    <div class="hrm-card-body">
                        <div class="hrm-form-grid cols-1">
                            <div class="hrm-field">
                                <label>Date of Birth <span class="req">*</span></label>
                                <div class="hrm-input-wrap"><i class="bi bi-calendar"></i>
                                    <input type="date" name="date_of_birth" class="form-control @if($err('date_of_birth')) is-invalid @endif" value="{{ $val('date_of_birth') }}" max="{{ now()->subYears(14)->toDateString() }}" required>
                                </div>
                                @if($err('date_of_birth'))<div class="text-danger small">{{ $err('date_of_birth') }}</div>@endif
                            </div>
                            <div class="hrm-field">
                                <label>Gender <span class="req">*</span></label>
                                <div class="hrm-radio-row">
                                    @foreach(['male' => 'Male', 'female' => 'Female'] as $g => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="gender_{{ $g }}" value="{{ $g }}" @checked($val('gender') === $g) required>
                                            <label class="form-check-label" for="gender_{{ $g }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @if($err('gender'))<div class="text-danger small">{{ $err('gender') }}</div>@endif
                            </div>
                            <div class="hrm-field">
                                <label>Address <span class="req">*</span></label>
                                <div class="hrm-input-wrap"><i class="bi bi-geo-alt"></i>
                                    <textarea name="address" class="form-control @if($err('address')) is-invalid @endif" rows="3" maxlength="500" placeholder="House no., street, city, PIN" required>{{ $val('address') }}</textarea>
                                </div>
                                @if($err('address'))<div class="text-danger small">{{ $err('address') }}</div>@endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hrm-card">
                    <div class="hrm-card-head"><i class="bi bi-person-vcard"></i><h3>ID details</h3></div>
                    <div class="hrm-card-body">
                        <div class="hrm-form-grid cols-1">
                            <div class="hrm-field">
                                <label>Aadhaar No. <span class="req">*</span></label>
                                <div class="hrm-input-wrap"><i class="bi bi-person-vcard"></i>
                                    <input type="text" name="aadhaar_number" class="form-control @if($err('aadhaar_number')) is-invalid @endif" value="{{ $val('aadhaar_number') }}"
                                        inputmode="numeric" placeholder="12 digits" pattern="[0-9 ]{12,14}" title="12 digits" required>
                                </div>
                                @if($err('aadhaar_number'))<div class="text-danger small">{{ $err('aadhaar_number') }}</div>@endif
                            </div>
                            <div class="hrm-field">
                                <label>PAN No. <span class="req">*</span></label>
                                <div class="hrm-input-wrap"><i class="bi bi-credit-card"></i>
                                    <input type="text" name="pan_number" class="form-control text-uppercase @if($err('pan_number')) is-invalid @endif" value="{{ $val('pan_number') }}"
                                        maxlength="10" placeholder="ABCDE1234F" pattern="[A-Za-z]{5}[0-9]{4}[A-Za-z]" title="5 letters, 4 digits, 1 letter" required>
                                </div>
                                @if($err('pan_number'))<div class="text-danger small">{{ $err('pan_number') }}</div>@endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hrm-card">
                    <div class="hrm-card-head"><i class="bi bi-bank"></i><h3>Bank details (salary account)</h3></div>
                    <div class="hrm-card-body">
                        <div class="hrm-form-grid cols-1">
                            <div class="hrm-field">
                                <label>Bank Name <span class="req">*</span></label>
                                <div class="hrm-input-wrap"><i class="bi bi-bank"></i>
                                    <input type="text" name="bank_name" class="form-control @if($err('bank_name')) is-invalid @endif" value="{{ $val('bank_name') }}" maxlength="100" placeholder="e.g. ICICI Bank" required>
                                </div>
                                @if($err('bank_name'))<div class="text-danger small">{{ $err('bank_name') }}</div>@endif
                            </div>
                            <div class="hrm-field">
                                <label>Account No. <span class="req">*</span></label>
                                <div class="hrm-input-wrap"><i class="bi bi-hash"></i>
                                    <input type="text" name="account_number" class="form-control @if($err('account_number')) is-invalid @endif" value="{{ $val('account_number') }}"
                                        inputmode="numeric" pattern="[0-9 ]{9,22}" title="9–18 digits" placeholder="Account number" required>
                                </div>
                                @if($err('account_number'))<div class="text-danger small">{{ $err('account_number') }}</div>@endif
                            </div>
                            <div class="hrm-field">
                                <label>Confirm Account No. <span class="req">*</span></label>
                                <div class="hrm-input-wrap"><i class="bi bi-hash"></i>
                                    <input type="text" name="account_number_confirmation" class="form-control" value="{{ old('account_number_confirmation') }}"
                                        inputmode="numeric" placeholder="Type it again" autocomplete="off" onpaste="return false" required>
                                </div>
                            </div>
                            <div class="hrm-field">
                                <label>IFSC Code <span class="req">*</span></label>
                                <div class="hrm-input-wrap"><i class="bi bi-key"></i>
                                    <input type="text" name="ifsc_code" class="form-control text-uppercase @if($err('ifsc_code')) is-invalid @endif" value="{{ $val('ifsc_code') }}"
                                        maxlength="11" placeholder="e.g. ICIC0006685" pattern="[A-Za-z]{4}0[A-Za-z0-9]{6}" title="4 letters, 0, then 6 letters/digits" required>
                                </div>
                                @if($err('ifsc_code'))<div class="text-danger small">{{ $err('ifsc_code') }}</div>
                                @else<div class="form-text">Printed on your cheque book / passbook. 5th character is always zero (0).</div>@endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hrm-card">
                    <div class="hrm-card-head"><i class="bi bi-folder2-open"></i><h3>Photo & documents</h3></div>
                    <div class="hrm-card-body">
                        @php $docs = $employee->documents->keyBy('type'); @endphp
                        <div class="hrm-form-grid cols-1">
                            <div class="hrm-field">
                                <label>Your photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/png,image/jpeg,image/webp">
                                <div class="form-text">{{ $employee->photo ? 'Uploaded — choose a file only to replace it.' : 'JPG/PNG, max 2 MB.' }}</div>
                                @if($err('photo'))<div class="text-danger small">{{ $err('photo') }}</div>@endif
                            </div>
                            @foreach(\App\Models\EmployeeProfileRequest::DOCUMENT_TYPES as $type)
                                <div class="hrm-field">
                                    <label>{{ \App\Models\EmployeeDocument::TYPES[$type]['label'] }} copy</label>
                                    <input type="file" name="documents[{{ $type }}]" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.webp">
                                    <div class="form-text">
                                        @if($docs->has($type))
                                            Uploaded: <a href="{{ route('employee-documents.download', $docs[$type]->id) }}" target="_blank">{{ $docs[$type]->original_name }}</a> — choose a file only to replace it.
                                        @else
                                            PDF or photo, max 5 MB.
                                        @endif
                                    </div>
                                    @if($err("documents.$type"))<div class="text-danger small">{{ $err("documents.$type") }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="selfp-actions">
                @unless($isOnboarding)
                    <a href="{{ route('profile.show') }}" class="btn btn-light">Cancel</a>
                @endunless
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-send"></i> {{ $openRequest ? 'Submit again for approval' : 'Submit for approval' }}
                </button>
            </div>
        </form>

        @if($isOnboarding)
            <form method="POST" action="{{ route('logout') }}" class="text-center pb-4">
                @csrf
                <button type="submit" class="btn btn-link text-muted small">Log out</button>
            </form>
        @endif
    </div>
</div>
@endsection
