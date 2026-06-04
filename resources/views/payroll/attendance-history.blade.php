@extends('layouts.app')
@section('content')
    <!-- [ page-header ] start -->
    <div class="page-header d-flex align-items-center justify-content-between mb-4">
        <div class="page-header-left">
            <div class="page-header-title">
                <h5 class="m-b-10" style="color: #3858f9; font-weight: 700;">Attendance History</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Attendance History</li>
            </ul>
        </div>
    </div>
    <!-- [ page-header ] end -->

    <!-- [ Main Content ] start -->
    <div class="container-fluid" style="margin-bottom: 100px; padding: 0 10px;">

        <!-- Header Section -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-md-7 col-12">
                <h4 class="fw-bold text-dark mb-1" style="font-size: 20px;">Attendance Records</h4>
                <p class="text-muted small mb-0">Monthly log for <span class="fw-bold text-primary">{{ $employee->name }}</span></p>
            </div>
            <div class="col-md-5 col-12">
                <form method="GET" class="w-100 wghrm-month-form">
                    <div class="position-relative">
                        <input type="month" name="month" value="{{ $selectedMonth }}" 
                            class="form-control shadow-sm border-0 w-100" onchange="this.form.submit()"
                            style="border-radius: 12px; height: 48px; font-weight: 600; padding-right: 45px; background: #fff;">
                     </div>
                </form>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="row">
            <div class="col-12">
                <!-- Desktop Table View -->
                <div class="card border-0 shadow-sm d-none d-md-block" style="border-radius: 16px; overflow: hidden; background: white;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <tr class="text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                        <th class="ps-4 py-3">#</th>
                                        <th class="py-3">Date</th>
                                        <th class="py-3 text-center">Check In</th>
                                        <th class="py-3 text-center">Check Out</th>
                                        <th class="py-3 text-center">Working Hours</th>
                                        <th class="pe-4 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($history as $index => $item)
                                        @php
                                            $badgeClass = 'bg-soft-success text-success';
                                            if (str_contains($item['status'], 'Absent')) $badgeClass = 'bg-soft-danger text-danger';
                                            elseif (str_contains($item['status'], 'Leave')) $badgeClass = 'bg-soft-info text-info';
                                            elseif (str_contains($item['status'], 'Half')) $badgeClass = 'bg-soft-warning text-warning';
                                            elseif (str_contains($item['status'], 'Sunday') || str_contains($item['status'], 'Holiday')) $badgeClass = 'bg-soft-secondary text-secondary';
                                        @endphp
                                        <tr style="height: 70px; border-bottom: 1px solid #f1f5f9;">
                                            <td class="ps-4 fw-bold text-muted" style="font-size: 13px;">{{ $index + 1 }}</td>
                                            <td class="fw-bold text-dark" style="font-size: 14px;">
                                                {{ $item['date'] }}
                                                @if(str_contains($item['date'], '(Sat)'))
                                                    <span class="badge bg-soft-primary text-primary ms-2">
                                                        Activity
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center text-muted fw-semibold" style="font-size: 13px;">{{ $item['punch_in'] }}</td>
                                            <td class="text-center text-muted fw-semibold" style="font-size: 13px;">{{ $item['punch_out'] }}</td>
                                            <td class="text-center fw-bold text-primary" style="font-size: 14px;">{{ $item['total_hours'] }}</td>
                                            <td class="pe-4 text-center">
                                                <span class="badge px-3 py-2 rounded-pill fw-bold {{ $badgeClass }}" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">
                                                    {{ $item['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="py-4">
                                                    <i class="feather-info text-muted mb-3" style="font-size: 40px;"></i>
                                                    <h5 class="fw-bold text-dark">No Records Found</h5>
                                                    <p class="text-muted small">No attendance records for the selected month.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Mobile Card View -->
                <div class="d-md-none">
                    @forelse($history as $index => $item)
                        @php
                            $badgeClass = 'bg-soft-success text-success';
                            $statusIcon = 'feather-check-circle';
                            if (str_contains($item['status'], 'Absent')) {
                                $badgeClass = 'bg-soft-danger text-danger';
                                $statusIcon = 'feather-x-circle';
                            } elseif (str_contains($item['status'], 'Leave')) {
                                $badgeClass = 'bg-soft-info text-info';
                                $statusIcon = 'feather-info';
                            } elseif (str_contains($item['status'], 'Half')) {
                                $badgeClass = 'bg-soft-warning text-warning';
                                $statusIcon = 'feather-clock';
                            } elseif (str_contains($item['status'], 'Sunday') || str_contains($item['status'], 'Holiday')) {
                                $badgeClass = 'bg-soft-secondary text-secondary';
                                $statusIcon = 'feather-calendar';
                            }
                        @endphp
                        <div class="card border-0 shadow-sm mb-3" style="border-radius: 16px; background: white;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div style="flex: 1;">
                                        <h6 class="fw-bold text-dark mb-2" style="font-size: 15px;">{{ $item['date'] }}</h6>
                                        <span class="badge {{ $badgeClass }} rounded-pill px-3 py-1 fw-bold" style="font-size: 10px; text-transform: uppercase;">
                                            <i class="{{ $statusIcon }} me-1"></i> {{ $item['status'] }}
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block mb-1" style="font-size: 11px;">Work Time</small>
                                        <span class="fw-bold text-primary" style="font-size: 16px;">{{ $item['total_hours'] }}</span>
                                    </div>
                                </div>
                                <div class="row g-0 bg-light rounded-3 p-3 mt-2">
                                    <div class="col-6 border-end text-center">
                                        <small class="text-muted d-block mb-1" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Check In</small>
                                        <span class="fw-bold text-dark" style="font-size: 14px;">{{ $item['punch_in'] }}</span>
                                    </div>
                                    <div class="col-6 text-center">
                                        <small class="text-muted d-block mb-1" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Check Out</small>
                                        <span class="fw-bold text-dark" style="font-size: 14px;">{{ $item['punch_out'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="card border-0 shadow-sm text-center py-5" style="border-radius: 16px; background: white;">
                            <div class="card-body">
                                <i class="feather-info text-muted mb-3" style="font-size: 40px;"></i>
                                <h6 class="fw-bold">No Records Found</h6>
                                <p class="text-muted small">No attendance records for this month.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-soft-success { background: rgba(34, 197, 94, 0.08) !important; color: #22c55e !important; }
        .bg-soft-danger { background: rgba(239, 68, 68, 0.08) !important; color: #ef4444 !important; }
        .bg-soft-warning { background: rgba(245, 158, 11, 0.08) !important; color: #f59e0b !important; }
        .bg-soft-info { background: rgba(6, 182, 212, 0.08) !important; color: #06b6d4 !important; }
        .bg-soft-secondary { background: rgba(100, 116, 139, 0.08) !important; color: #64748b !important; }
        .bg-soft-primary { background: rgba(56, 88, 249, 0.08) !important; color: #3858f9 !important; }

        input[type="month"] {
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            height: 48px !important;
            padding: 10px 15px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #334155 !important;
            background-color: #ffffff !important;
            transition: all 0.3s ease;
        }

        input[type="month"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.6;
        }
        input[type="month"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        @media (min-width: 768px) {
            .wghrm-month-form {
                max-width: 220px !important;
                margin-left: auto;
            }
        }

        @media (max-width: 767.98px) {
            .page-header { 
                flex-direction: column !important; 
                align-items: flex-start !important;
                gap: 10px;
                margin-bottom: 20px !important;
            }
            .breadcrumb {
                margin-top: 5px;
            }
            .container-fluid {
                padding: 0 15px !important;
            }
        }
    </style>
@endsection