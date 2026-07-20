@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10" style="color: #3858f9; font-weight: 700;">My Assets</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">My Assets</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm"
                        style="border-radius: 10px; background: #3858f9; border: none; height: 44px;" 
                        data-bs-toggle="modal" data-bs-target="#requestAssetModal">
                        <i class="feather-plus-circle"></i> REQUEST DEVICE
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content pt-4" style="font-family: 'Inter', sans-serif;">
        <!-- Allocated Assets Section -->
        <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
            🖥️ Currently Allocated Devices
        </h5>
        
        <div class="row g-4 mb-5">
            @forelse($allocatedAssets as $alloc)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm position-relative overflow-hidden" 
                        style="border-radius: 20px; background: white; border: 1px solid #e2e8f0 !important; transition: all 0.3s ease;">
                        <div class="card-body p-4">
                            <!-- Type Badge -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-soft-success text-success" 
                                    style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 700; text-transform: uppercase;">
                                    {{ $alloc->asset->type }}
                                </span>
                                <span class="text-muted small">Assigned: {{ $alloc->allocated_at ? $alloc->allocated_at->format('d M, Y') : $alloc->updated_at->format('d M, Y') }}</span>
                            </div>

                            <!-- Name -->
                            <h4 class="fw-bold text-dark mb-1 fs-5">{{ $alloc->asset->name }}</h4>
                            <p class="text-muted small mb-3">Serial Number: <b class="text-dark">{{ $alloc->asset->serial_number ?: 'N/A' }}</b></p>

                            <!-- Specifications -->
                            <div class="mb-3">
                                <span class="small fw-semibold text-muted text-uppercase d-block mb-1">Specifications:</span>
                                <div class="bg-light p-3 rounded-3 text-muted small" style="min-height: 80px; line-height: 1.5; font-weight: 500;">
                                    {!! nl2br(e($alloc->asset->system_configuration)) ?: 'No configuration specified.' !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-center py-5" style="border-radius: 20px; background: white; border: 2px dashed #e2e8f0 !important;">
                        <div class="py-3">
                            <div style="font-size: 44px;">🛡️</div>
                            <h5 class="text-dark fw-bold mt-3">No Devices Allocated</h5>
                            <p class="text-muted small">You currently do not have any company devices assigned to your profile.</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        @if(isset($teamAllocatedAssets) && $teamAllocatedAssets->isNotEmpty())
            <!-- Team Devices Section (Team Leaders only) -->
            <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                👥 Team Devices
            </h5>

            <div class="row g-4 mb-5">
                @foreach($teamAllocatedAssets as $alloc)
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm position-relative overflow-hidden"
                            style="border-radius: 20px; background: white; border: 1px solid #e2e8f0 !important;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge bg-soft-primary text-primary"
                                        style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 700; text-transform: uppercase;">
                                        {{ $alloc->asset->type }}
                                    </span>
                                    <span class="text-muted small">Assigned: {{ $alloc->allocated_at ? $alloc->allocated_at->format('d M, Y') : $alloc->updated_at->format('d M, Y') }}</span>
                                </div>

                                <h4 class="fw-bold text-dark mb-1 fs-5">{{ $alloc->asset->name }}</h4>
                                <p class="text-muted small mb-1">Assigned to: <b class="text-dark">{{ $alloc->user->name ?? 'N/A' }}</b></p>
                                <p class="text-muted small mb-3">Serial Number: <b class="text-dark">{{ $alloc->asset->serial_number ?: 'N/A' }}</b></p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Requests History Section -->
        <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
            📝 Request History & Status
        </h5>

        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: white; overflow: hidden; border: 1px solid #e2e8f0 !important;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background: #3858f9; color: white;">
                            <tr style="height: 60px; vertical-align: middle;">
                                <th class="ps-4" style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Requested Device</th>
                                <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Reason</th>
                                <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white;">Request Date</th>
                                <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 180px; color: white;">Allocated Model</th>
                                <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white;">Return Date</th>
                                <th class="pe-4 text-center" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userRequests as $req)
                                @php
                                    $reqStatusClass = 'status-pending';
                                    if($req->status == 'Allocated' || $req->status == 'Approved') $reqStatusClass = 'status-completed';
                                    elseif($req->status == 'Rejected') $reqStatusClass = 'status-rework';
                                    elseif($req->status == 'Returned') $reqStatusClass = 'status-pending';
                                @endphp
                                <tr style="height: 70px; border-bottom: 1px solid #f1f5f9;">
                                    <td class="ps-4">
                                        <span class="badge bg-soft-primary text-primary" style="font-weight: 700; text-transform: uppercase;">
                                            {{ $req->asset_type }}
                                        </span>
                                    </td>
                                    <td style="max-width: 300px; white-space: normal; word-break: break-word;">
                                        <span class="text-muted small">{{ $req->reason }}</span>
                                    </td>
                                    <td class="text-muted small">{{ $req->created_at->format('d M, Y') }}</td>
                                    <td class="fw-semibold text-dark small">
                                        {{ $req->asset ? $req->asset->name : '-' }}
                                    </td>
                                    <td class="text-muted small">
                                        {{ $req->returned_at ? $req->returned_at->format('d M, Y') : '-' }}
                                    </td>
                                    <td class="pe-4 text-center">
                                        <span class="badge {{ $reqStatusClass }}" style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 600; text-transform: uppercase;">
                                            {{ $req->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="py-4">
                                            <div style="font-size: 36px;">📝</div>
                                            <h5 class="text-muted mt-3">No Requests Logged</h5>
                                            <p class="text-muted small">When you submit a device request form, it will list here.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Asset Modal -->
    <div class="modal fade" id="requestAssetModal" tabindex="-1" aria-labelledby="requestAssetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestAssetModalLabel">Request a Company Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('assets.request') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Select Device Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="asset_type" required style="height: 48px; border-radius: 10px;">
                                <option value="Laptop">Laptop</option>
                                <option value="Keyboard">Keyboard</option>
                                <option value="Mouse">Mouse</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Charger">Charger</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Reason for Request <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" rows="4" placeholder="Briefly explain the reason for this device request..." required style="border-radius: 10px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" style="background: #3858f9; border: none; border-radius: 8px;">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .premium-modal {
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        .premium-modal .modal-header {
            border-bottom: 1px solid #f1f5f9;
            padding: 20px 24px;
        }
        .premium-modal .modal-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 20px;
        }
    </style>
@endsection
