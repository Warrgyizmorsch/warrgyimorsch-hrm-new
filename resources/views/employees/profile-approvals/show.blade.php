@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
<style>
    .pa-table td, .pa-table th { vertical-align: middle; }
    .pa-old { color: #64748b; font-size: 13px; }
    .pa-changed { background: rgba(245, 158, 11, .07); }
    .pa-changed .pa-tag { font-size: 10px; font-weight: 700; color: #b45309; text-transform: uppercase; }
</style>
@endpush

@section('content')
@php
    $isPending = $profileRequest->status === 'pending';
    $isOnboarding = $profileRequest->type === 'onboarding';
    $docs = $employee->documents->keyBy('type');
@endphp
<div class="zoho-page-shell master-page attendance-page m-2 p-2">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Profile Approvals',
        'viewLabel' => $employee->name,
        'scopeLinks' => [
            ['label' => '← Back to approvals', 'url' => route('profile-approvals.index'), 'active' => false],
        ],
    ])

    <div class="main-content zoho-module-content" style="max-width: 1000px;">
        <div class="mst-offcanvas-card mb-3">
            <div class="mst-offcanvas-card-body">
                <div class="row g-2 small">
                    <div class="col-md-3"><strong>Code:</strong> {{ $employee->employee_code }}</div>
                    <div class="col-md-3"><strong>Department:</strong> {{ $employee->departmentRef->name ?? '—' }}</div>
                    <div class="col-md-3"><strong>Designation:</strong> {{ $employee->designation }}</div>
                    <div class="col-md-3"><strong>Type:</strong> {{ $isOnboarding ? 'New joiner profile' : 'Change request' }}</div>
                    <div class="col-md-12 text-muted">Submitted {{ $profileRequest->submitted_at?->format('d M Y, h:i A') }}
                        @if($profileRequest->reviewer) · last reviewed by {{ $profileRequest->reviewer->name }} {{ $profileRequest->reviewed_at?->diffForHumans() }} @endif
                    </div>
                </div>
            </div>
        </div>

        @if(!$isPending)
            <div class="alert alert-secondary py-2 small">Sent back to the employee: “{{ $profileRequest->review_note }}”. It will come back here when they resubmit.</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('profile-approvals.approve', $profileRequest) }}" id="approveForm">
            @csrf
            <div class="zoho-people-table-card mb-3">
                <div class="table-responsive">
                    <table class="table pa-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 170px;">Field</th>
                                <th>Submitted by employee {{ $isPending ? '(you can correct it)' : '' }}</th>
                                @unless($isOnboarding)<th style="width: 30%;">Current value</th>@endunless
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\App\Models\EmployeeProfileRequest::FIELDS as $field => $label)
                                @php $value = old($field, $profileRequest->data[$field] ?? ''); @endphp
                                <tr class="{{ !$isOnboarding && in_array($field, $changed, true) ? 'pa-changed' : '' }}">
                                    <td class="fw-semibold">{{ $label }}
                                        @if(!$isOnboarding && in_array($field, $changed, true))<div class="pa-tag">changed</div>@endif
                                    </td>
                                    <td>
                                        @if($field === 'gender')
                                            <select name="gender" class="form-select form-select-sm" @disabled(!$isPending)>
                                                @foreach(['male' => 'Male', 'female' => 'Female'] as $g => $gl)
                                                    <option value="{{ $g }}" @selected($value === $g)>{{ $gl }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($field === 'address')
                                            <textarea name="address" class="form-control form-control-sm" rows="2" @disabled(!$isPending)>{{ $value }}</textarea>
                                        @else
                                            <input type="{{ $field === 'date_of_birth' ? 'date' : 'text' }}" name="{{ $field }}" value="{{ $value }}"
                                                class="form-control form-control-sm @error($field) is-invalid @enderror" @disabled(!$isPending)>
                                        @endif
                                        @error($field)<div class="text-danger small">{{ $message }}</div>@enderror
                                    </td>
                                    @unless($isOnboarding)
                                        <td class="pa-old">{{ $field === 'gender' ? ucfirst((string) $current[$field]) : ($current[$field] ?: '—') }}</td>
                                    @endunless
                                </tr>
                            @endforeach
                            <tr>
                                <td class="fw-semibold">Documents</td>
                                <td colspan="2">
                                    @foreach(\App\Models\EmployeeProfileRequest::DOCUMENT_TYPES as $type)
                                        @if($docs->has($type))
                                            <a href="{{ route('employee-documents.download', $docs[$type]->id) }}" target="_blank" class="me-3">
                                                <i class="feather-paperclip"></i> {{ \App\Models\EmployeeDocument::TYPES[$type]['label'] }}
                                            </a>
                                        @else
                                            <span class="text-muted me-3">{{ \App\Models\EmployeeDocument::TYPES[$type]['label'] }}: not uploaded</span>
                                        @endif
                                    @endforeach
                                    @if($employee->photo)
                                        <a href="{{ asset('storage/' . $employee->photo) }}" target="_blank"><i class="feather-image"></i> Photo</a>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        @if($isPending)
            <div class="d-flex flex-wrap gap-3 align-items-start justify-content-between">
                <form method="POST" action="{{ route('profile-approvals.send-back', $profileRequest) }}" class="flex-grow-1" style="max-width: 520px;">
                    @csrf
                    <label class="form-label small fw-bold">Send back with a note</label>
                    <div class="d-flex gap-2">
                        <input type="text" name="review_note" class="form-control form-control-sm @error('review_note') is-invalid @enderror"
                            placeholder="e.g. IFSC looks wrong — please check your cheque book" value="{{ old('review_note') }}" maxlength="1000">
                        <button type="submit" class="zoho-btn-outline btn-sm text-nowrap">Send back</button>
                    </div>
                    @error('review_note')<div class="text-danger small">{{ $message }}</div>@enderror
                </form>
                <button type="submit" form="approveForm" class="zoho-btn-primary">
                    <i class="feather-check"></i> {{ $isOnboarding ? 'Approve & activate account' : 'Approve changes' }}
                </button>
            </div>
        @endif
    </div>
</div>
@endsection
