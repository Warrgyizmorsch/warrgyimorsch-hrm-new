@extends('layouts.app')

@section('content')
<div class="zoho-page-shell">
    <div class="main-content zoho-module-content">
        <div class="zoho-people-detail-card">
            <div class="zoho-people-detail-header">
                <div class="zoho-people-detail-profile">
                    <div class="zoho-people-detail-avatar">
                        @if($employee->photo)
                            <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}">
                        @else
                            {{ strtoupper(substr($employee->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="zoho-people-detail-meta">
                        <span class="zoho-badge zoho-badge-muted">EC-{{ str_pad($employee->id, 5, '0', STR_PAD_LEFT) }}</span>
                        <h2>{{ $employee->name }}</h2>
                        <div class="zoho-detail-sub">
                            @if($employee->designation)
                                <span><i class="feather-briefcase"></i> {{ $employee->designation }}</span>
                            @endif
                            @if($employee->department)
                                <span><i class="feather-layers"></i> {{ ucwords($employee->department) }}</span>
                            @endif
                            @if($employee->email)
                                <span><i class="feather-mail"></i> {{ $employee->email }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="zoho-people-detail-actions">
                    <a href="{{ route('employees.index') }}" class="zoho-btn-outline">
                        <i class="feather-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('employees.edit', $employee->id) }}" class="zoho-btn-primary">
                        <i class="feather-edit-2"></i> Edit
                    </a>
                </div>
            </div>

            @include('employees.partials.detail-tabs')

            <div class="tab-content zoho-people-detail-body" id="employeeDetailTabContent">
                <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                    <div class="zoho-detail-grid">
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Full Name</div>
                            <div class="zoho-detail-value">{{ $employee->name }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Email Address</div>
                            <div class="zoho-detail-value">{{ $employee->email ?? '—' }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Mobile Number</div>
                            <div class="zoho-detail-value">{{ $employee->mobile_number ?? '—' }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Employee Code</div>
                            <div class="zoho-detail-value is-highlight">{{ $employee->employee_code ?? ('EC-' . str_pad($employee->id, 5, '0', STR_PAD_LEFT)) }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Aadhaar Number</div>
                            <div class="zoho-detail-value">{{ $employee->aadhaar_number ?? '—' }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">PAN Number</div>
                            <div class="zoho-detail-value">{{ $employee->pan_number ?? '—' }}</div>
                        </div>
                        <div class="zoho-detail-row zoho-detail-span-full">
                            <div class="zoho-detail-label">Residential Address</div>
                            <div class="zoho-detail-value">{{ $employee->address ?? 'No address provided' }}</div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="bank" role="tabpanel" aria-labelledby="bank-tab">
                    <div class="zoho-detail-grid">
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Bank Name</div>
                            <div class="zoho-detail-value">{{ $employee->bank_name ?? '—' }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Account Number</div>
                            <div class="zoho-detail-value">{{ $employee->account_number ?? '—' }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">IFSC Code</div>
                            <div class="zoho-detail-value">{{ $employee->ifsc_code ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="salary" role="tabpanel" aria-labelledby="salary-tab">
                    <div class="zoho-detail-grid">
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Basic Salary</div>
                            <div class="zoho-detail-value is-highlight">₹ {{ number_format($employee->basic_salary ?? 0, 2) }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">HRA</div>
                            <div class="zoho-detail-value">₹ {{ number_format($employee->hra ?? 0, 2) }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Conveyance Allowance</div>
                            <div class="zoho-detail-value">₹ {{ number_format($employee->conveyance_allowance ?? 0, 2) }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Medical Allowance</div>
                            <div class="zoho-detail-value">₹ {{ number_format($employee->medical_allowance ?? 0, 2) }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">Other Allowance</div>
                            <div class="zoho-detail-value">₹ {{ number_format($employee->other_allowance ?? 0, 2) }}</div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">PF Status</div>
                            <div class="zoho-detail-value">
                                <span class="zoho-badge {{ $employee->pf ? 'zoho-badge-success' : 'zoho-badge-muted' }}">
                                    {{ $employee->pf ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        </div>
                        <div class="zoho-detail-row">
                            <div class="zoho-detail-label">ESI Status</div>
                            <div class="zoho-detail-value">
                                <span class="zoho-badge {{ $employee->esi ? 'zoho-badge-success' : 'zoho-badge-muted' }}">
                                    {{ $employee->esi ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                    <div class="zoho-detail-grid">
                        <div class="zoho-detail-row zoho-detail-span-full">
                            <div class="zoho-detail-label">Login Password</div>
                            <div class="zoho-detail-value">{{ $employee->password ? '••••••••' : '—' }}</div>
                            <p class="text-muted small mb-0 mt-2">Update credentials from the Edit Employee screen.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
@endsection
