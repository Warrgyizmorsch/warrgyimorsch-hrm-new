@extends('layouts.app')

@section('content')
    <style>
        /* Premium Plus Employee Details Styles */
        :root {
            --profile-primary: #4e73df;
            --profile-secondary: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        .employee-details-container {
            background: #f8fafc;
            min-height: 100vh;
            border-radius: 0;
            overflow: hidden;
        }

        .premium-banner {
            height: 220px;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            position: relative;
            overflow: hidden;
        }

        .premium-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(78, 115, 223, 0.1) 0%, transparent 70%);
            animation: pulse 15s infinite alternate;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.3;
            }

            100% {
                transform: scale(1.2);
                opacity: 0.5;
            }
        }

        .profile-card-overlap {
            margin-top: -100px;
            position: relative;
            z-index: 10;
            padding: 0 30px;
        }

        .glass-profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            padding: 40px;
            transition: transform 0.3s ease;
        }

        .header-photo-wrapper {
            position: relative;
            margin-top: -80px;
            margin-bottom: 20px;
            display: inline-block;
        }

        .header-photo-wrapper img,
        .header-photo-wrapper .employee-photo-premium {
            width: 140px;
            height: 140px;
            border-radius: 30px;
            object-fit: cover;
            border: 6px solid #fff;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            background: #fff;
        }

        .badge-premium {
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 11px;
        }

        .nav-premium-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            background: rgba(241, 245, 249, 0.5);
            padding: 8px;
            border-radius: 18px;
            width: fit-content;
        }

        .premium-tab {
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .premium-tab.active {
            background: #fff;
            color: var(--profile-primary);
            box-shadow: 0 10px 20px rgba(78, 115, 223, 0.1);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .info-item-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: all 0.3s ease;
        }

        .info-item-card:hover {
            transform: translateY(-5px);
            border-color: var(--profile-primary);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.05);
        }

        .card-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            background: rgba(78, 115, 223, 0.08);
            color: var(--profile-primary);
            transition: all 0.3s ease;
        }

        .info-item-card:hover .card-icon-circle {
            background: var(--profile-primary);
            color: #fff;
        }

        .card-label {
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
            display: block;
        }

        .card-value {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
            margin: 0;
        }

        /* Modal Styling */
        .premium-modal .modal-content {
            border-radius: 28px;
            border: none;
            overflow: hidden;
        }

        .premium-modal .modal-header {
            background: #f8fafc;
            padding: 30px 40px;
            border: none;
        }

        .premium-modal .modal-body {
            padding: 20px 40px 40px 40px;
        }

        .premium-input-group {
            margin-bottom: 24px;
        }

        .premium-input-group label {
            font-weight: 800;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: block;
        }

        .premium-control {
            background: #f1f5f9 !important;
            border: 2px solid transparent !important;
            border-radius: 16px !important;
            padding: 14px 20px !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            transition: all 0.3s ease !important;
        }

        .premium-control:focus {
            background: #fff !important;
            border-color: var(--profile-primary) !important;
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1) !important;
        }

        .tab-pane-fade {
            display: none;
            animation: fadeInSlide 0.5s ease-out forwards;
        }

        @keyframes fadeInSlide {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tab-pane-fade.active {
            display: block;
        }
    </style>

    <div class="employee-details-container">
        <!-- Top Banner -->
        <div class="premium-banner"></div>

        <!-- Main Profile Card Overlap -->
        <div class="profile-card-overlap">
            <div class="glass-profile-card">
                <div class="row">
                    <div class="col-lg-3 text-center text-lg-start">
                        <div class="header-photo-wrapper">
                            @if($employee && $employee->photo)
                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="" id="profileImageDisplay">
                            @else
                                <div class="employee-photo-premium d-flex align-items-center justify-content-center bg-soft-primary text-primary"
                                    style="font-size: 54px; font-weight: 800;">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                            @endif

                            <div class="position-absolute bottom-0 end-0 mb-2 me-2">
                                <label for="profile_photo_input" class="btn btn-primary btn-icon rounded-circle shadow-lg"
                                    style="width: 38px; height: 38px; cursor: pointer; border: 3px solid #fff;">
                                    <i class="feather-camera" style="font-size: 14px;"></i>
                                </label>
                            </div>

                            <form id="photoUploadForm" action="{{ route('profile.update') }}" method="POST"
                                enctype="multipart/form-data" class="d-none">
                                @csrf @method('PATCH')
                                <input type="hidden" name="name" value="{{ $user->name }}">
                                <input type="hidden" name="mobile_number" value="{{ $employee?->mobile_number ?? '' }}">
                                <input type="file" id="profile_photo_input" name="photo" accept="image/*"
                                    onchange="document.getElementById('photoUploadForm').submit();">
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-9 pt-lg-2">
                        <div
                            class="d-flex flex-column flex-lg-row justify-content-between align-items-center align-items-lg-start gap-4">
                            <div class="text-center text-lg-start">
                                <h1 class="fw-bolder text-dark mb-2" style="font-size: 32px;">{{ $user->name }}</h1>
                                <div
                                    class="d-flex flex-wrap justify-content-center justify-content-lg-start align-items-center gap-3">
                                    <span
                                        class="badge badge-premium bg-soft-primary text-primary border border-primary border-opacity-10">{{ $employee?->designation ?? 'Team Member' }}</span>
                                    <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                        <i class="feather-box me-1"></i> {{ $employee?->departmentRef?->name ?? 'General' }}
                                    </span>
                                    <span class="text-muted small fw-bold">|</span>
                                    <span class="text-muted small fw-bold text-uppercase">ID: <span
                                            class="text-primary">{{ $employee?->employee_code ?? 'N/A' }}</span></span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-soft-primary rounded-pill px-4 fw-bold"
                                    data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="feather-edit-3 me-2"></i> Edit Account
                                </button>
                                <button type="button" class="btn btn-soft-danger rounded-pill px-4 fw-bold"
                                    data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="feather-shield me-2"></i> Security
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-5 border-light">

                <!-- Premium Tabs Navigation -->
                <div class="nav-premium-tabs">
                    <button class="premium-tab active" onclick="switchPremiumTab('personal', this)">
                        <i class="feather-user"></i> Personal Data
                    </button>
                    <button class="premium-tab" onclick="switchPremiumTab('bank', this)">
                        <i class="feather-credit-card"></i> Finance
                    </button>
                    <button class="premium-tab" onclick="switchPremiumTab('salary', this)">
                        <i class="feather-activity"></i> Payroll Structure
                    </button>
                </div>

                <!-- Tab Panels -->
                <div class="tab-panels mt-4">
                    <!-- PERSONAL -->
                    <div id="pane-personal" class="tab-pane-fade active">
                        <div class="info-grid">
                            <div class="info-item-card">
                                <div class="card-icon-circle"><i class="feather-user"></i></div>
                                <div class="card-content"><span class="card-label">Official Name</span>
                                    <p class="card-value">{{ $user->name }}</p>
                                </div>
                            </div>
                            <div class="info-item-card">
                                <div class="card-icon-circle"><i class="feather-phone"></i></div>
                                <div class="card-content"><span class="card-label">Primary Contact</span>
                                    <p class="card-value">{{ $employee?->mobile_number ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="info-item-card">
                                <div class="card-icon-circle"><i class="feather-mail"></i></div>
                                <div class="card-content"><span class="card-label">Email Address</span>
                                    <p class="card-value text-break">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="info-item-card">
                                <div class="card-icon-circle"><i class="feather-calendar"></i></div>
                                <div class="card-content"><span class="card-label">Birth Date</span>
                                    <p class="card-value">{{ $employee?->date_of_birth ?? 'Not Specified' }}</p>
                                </div>
                            </div>
                            <div class="info-item-card">
                                <div class="card-icon-circle"><i class="feather-hash"></i></div>
                                <div class="card-content"><span class="card-label">Aadhaar ID</span>
                                    <p class="card-value">{{ $employee?->aadhaar_number ?? 'Not Linked' }}</p>
                                </div>
                            </div>
                            <div class="info-item-card">
                                <div class="card-icon-circle"><i class="feather-file-text"></i></div>
                                <div class="card-content"><span class="card-label">PAN Identifier</span>
                                    <p class="card-value text-uppercase">{{ $employee?->pan_number ?? 'Not Linked' }}</p>
                                </div>
                            </div>
                            <div class="info-item-card">
                                <div class="card-icon-circle"><i class="feather-clock"></i></div>
                                <div class="card-content"><span class="card-label">Official Timing</span>
                                    <p class="card-value">
                                        {{ ($employee?->time_in) ? \Carbon\Carbon::parse($employee->time_in)->format('h:i A') : '--:--' }}
                                        -
                                        {{ ($employee?->time_out) ? \Carbon\Carbon::parse($employee->time_out)->format('h:i A') : '--:--' }}
                                    </p>
                                </div>
                            </div>
                            <div class="info-item-card">
                                <div class="card-icon-circle"><i class="feather-briefcase"></i></div>
                                <div class="card-content"><span class="card-label">Onboarding Date</span>
                                    <p class="card-value">{{ $employee?->date_of_joining ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-4 border rounded-4 bg-light bg-opacity-50">
                            <h6 class="fw-bold mb-4 text-dark"><i class="feather-shield me-2 text-primary"></i>Statutory
                                Compliance</h6>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="p-3 bg-white rounded-4 shadow-sm border">
                                        <span class="card-label">PF ENROLLMENT</span>
                                        <h6
                                            class="fw-bold mt-1 {{ ($employee && $employee->pf) ? 'text-success' : 'text-muted' }}">
                                            {{ ($employee && $employee->pf) ? ($employee->pf_number ?: 'Active') : 'Disabled' }}
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-white rounded-4 shadow-sm border">
                                        <span class="card-label">ESI COMPLIANCE</span>
                                        <h6
                                            class="fw-bold mt-1 {{ ($employee && $employee->esi) ? 'text-success' : 'text-muted' }}">
                                            {{ ($employee && $employee->esi) ? ($employee->esi_number ?: 'Active') : 'Disabled' }}
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-white rounded-4 shadow-sm border">
                                        <span class="card-label">INSURANCE POLICY</span>
                                        <h6
                                            class="fw-bold mt-1 {{ ($employee && $employee->insurance) ? 'text-success' : 'text-muted' }}">
                                            {{ ($employee && $employee->insurance) ? ($employee->insurance_policy_number ?: 'Protected') : 'No Policy' }}
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BANK -->
                    <div id="pane-bank" class="tab-pane-fade">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="info-item-card h-100">
                                    <div class="card-icon-circle"><i class="feather-home"></i></div>
                                    <div class="card-content">
                                        <span class="card-label">FINANCIAL INSTITUTION</span>
                                        <p class="card-value fs-5">{{ $employee?->bank_name ?? 'NOT REGISTERED' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <div class="info-item-card">
                                            <div class="card-icon-circle"><i class="feather-hash"></i></div>
                                            <div class="card-content"><span class="card-label">ACCOUNT NUMBER</span>
                                                <p class="card-value">{{ $employee?->account_number ?? 'XXXX XXXX XXXX' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-item-card">
                                            <div class="card-icon-circle"><i class="feather-activity"></i></div>
                                            <div class="card-content"><span class="card-label">IFSC CODE</span>
                                                <p class="card-value text-primary">{{ $employee?->ifsc_code ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SALARY -->
                    <div id="pane-salary" class="tab-pane-fade">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-body p-5">
                                <h4 class="fw-bold mb-4 text-dark text-center">Payroll Breakdown Estimate</h4>
                                <div class="mx-auto" style="max-width: 500px;">
                                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2"><span
                                            class="text-muted fw-bold small">BASIC PORTION</span><span class="fw-bold">₹
                                            {{ number_format($employee?->basic_salary ?? 0, 2) }}</span></div>
                                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2"><span
                                            class="text-muted fw-bold small">HRA ALLOWANCE</span><span class="fw-bold">₹
                                            {{ number_format($employee?->hra ?? 0, 2) }}</span></div>
                                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2"><span
                                            class="text-muted fw-bold small">CONVEYANCE</span><span class="fw-bold">₹
                                            {{ number_format($employee?->conveyance_allowance ?? 0, 2) }}</span></div>
                                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2"><span
                                            class="text-muted fw-bold small">MEDICAL COVER</span><span class="fw-bold">₹
                                            {{ number_format($employee?->medical_allowance ?? 0, 2) }}</span></div>
                                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2"><span
                                            class="text-muted fw-bold small">GENERAL ALLOWANCE</span><span class="fw-bold">₹
                                            {{ number_format($employee?->other_allowance ?? 0, 2) }}</span></div>

                                    @php $gt = ($employee?->basic_salary ?? 0) + ($employee?->hra ?? 0) + ($employee?->conveyance_allowance ?? 0) + ($employee?->medical_allowance ?? 0) + ($employee?->other_allowance ?? 0); @endphp
                                    <div class="mt-5 p-4 bg-soft-primary rounded-4 text-center">
                                        <span class="card-label text-primary">GROSS MONTHLY PACKAGE</span>
                                        <h2 class="fw-bolder text-dark mb-0 mt-2">₹ {{ number_format($gt, 2) }}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Edit Modal -->
    <div class="modal fade premium-modal mt-5" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Edit Master Details</h4>
                        <p class="text-muted small mb-0">Update your primary information records</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <div class="premium-input-group">
                            <label>Display Name</label>
                            <input type="text" name="name" class="premium-control form-control" value="{{ $user->name }}"
                                required>
                        </div>
                        <div class="premium-input-group mb-0">
                            <label>Mobile Number</label>
                            <input type="text" name="mobile_number" class="premium-control form-control"
                                value="{{ $employee?->mobile_number ?? '' }}">
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4">
                        <button type="button" class="btn btn-soft-secondary rounded-pill px-5 fw-bold"
                            data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Apply Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Premium Password Modal -->
    <div class="modal fade premium-modal mt-5" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <div>
                        <h4 class="fw-bold text-danger mb-1"><i class="feather-lock me-2"></i>Security Shield</h4>
                        <p class="text-muted small mb-0">Protect your account with a strong password</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('profile.password.update') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if(count($all_employees) > 0)
                            <div class="premium-input-group">
                                <label>Select Account to Secure</label>

                                <div class="wghrm-search-dropdown" id="targetUserDropdown">
                                    <!-- Trigger -->
                                    <div class="wghrm-dropdown-trigger"
                                        style="height: 52px; border-radius: 16px; background: #f1f5f9 !important; border: 2px solid transparent !important;">
                                        <span class="wghrm-trigger-text fw-bold text-dark">
                                            My Own Account ({{ $user->name }})
                                        </span>
                                        <i class="feather-chevron-down" style="width: 18px; height: 18px;"></i>
                                    </div>

                                    <!-- Dropdown Menu -->
                                    <div class="wghrm-dropdown-menu">
                                        <div class="wghrm-search-container">
                                            <i class="feather-search wghrm-search-icon"></i>
                                            <input type="text" class="wghrm-search-input" placeholder="Search employee...">
                                        </div>

                                        <div class="wghrm-items-list">
                                            <!-- My Own Account Option -->
                                            <div class="wghrm-item selected" data-value="{{ $user->id }}"
                                                data-text="My Own Account ({{ $user->name }})">
                                                <span class="wghrm-item-text">My Own Account ({{ $user->name }})</span>
                                                <i class="feather-check wghrm-item-check"
                                                    style="width: 14px; height: 14px;"></i>
                                            </div>

                                            <div class="border-top my-2 opacity-50"></div>

                                            @foreach($all_employees as $emp)
                                                @if($emp->id != $user->id)
                                                    <div class="wghrm-item" data-value="{{ $emp->id }}"
                                                        data-text="Employee: {{ $emp->name }} ({{ $emp->employee->employee_code ?? 'N/A' }})">
                                                        <span class="wghrm-item-text">
                                                            <div class="fw-bold">{{ $emp->name }}</div>
                                                            <div class="small text-muted">
                                                                {{ $emp->employee->employee_code ?? 'ID: ' . $emp->id }}</div>
                                                        </span>
                                                        <i class="feather-check wghrm-item-check"
                                                            style="width: 14px; height: 14px;"></i>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Hidden Input -->
                                    <input type="hidden" name="target_user_id" value="{{ $user->id }}">
                                </div>
                            </div>
                        @endif

                        <div class="premium-input-group">
                            <label>New Password</label>
                            <div class="position-relative">
                                <input type="password" id="new_password" name="password" class="premium-control form-control"
                                    placeholder="Minimum 8 characters" required style="padding-right: 50px !important;">
                                <button type="button" class="btn border-0 position-absolute end-0 top-50 translate-middle-y me-1" 
                                    onclick="togglePasswordVisibility('new_password', this)" style="z-index: 10;">
                                    <i class="feather-eye text-muted" style="width: 18px; height: 18px;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="premium-input-group mb-0">
                            <label>Repeat New Password</label>
                            <div class="position-relative">
                                <input type="password" id="confirm_password" name="password_confirmation" class="premium-control form-control"
                                    placeholder="Match new password" required style="padding-right: 50px !important;">
                                <button type="button" class="btn border-0 position-absolute end-0 top-50 translate-middle-y me-1" 
                                    onclick="togglePasswordVisibility('confirm_password', this)" style="z-index: 10;">
                                    <i class="feather-eye text-muted" style="width: 18px; height: 18px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4">
                        <button type="button" class="btn btn-soft-secondary rounded-pill px-5 fw-bold"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-5 fw-bold shadow-sm">Enable
                            Security</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchPremiumTab(paneId, btn) {
            document.querySelectorAll('.premium-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.tab-pane-fade').forEach(p => p.classList.remove('active'));
            document.getElementById('pane-' + paneId).classList.add('active');
        }

        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('feather-eye');
                icon.classList.add('feather-eye-off');
            } else {
                input.type = 'password';
                icon.classList.remove('feather-eye-off');
                icon.classList.add('feather-eye');
            }
        }
    </script>
@endsection