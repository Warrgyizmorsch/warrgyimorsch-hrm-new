@extends('layouts.app')

@section('content')
    @include('assets.partials.styles')
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
                    <a href="{{ route('assets.guide') }}" class="btn btn-light-brand d-flex align-items-center gap-2" style="border-radius: 10px; height: 44px;">
                        <i class="feather-help-circle"></i> HOW TO USE
                    </a>
                    <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm"
                        style="border-radius: 10px; background: #3858f9; border: none; height: 44px;" 
                        data-bs-toggle="modal" data-bs-target="#requestAssetModal">
                        <i class="feather-plus-circle"></i> REQUEST DEVICE
                    </button>
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
                        style="border-radius: 10px; height: 44px;"
                        data-bs-toggle="modal" data-bs-target="#declareAssetModal">
                        <i class="feather-clipboard"></i> DECLARE AN ASSET I HAVE
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content pt-4" style="font-family: 'Inter', sans-serif;">
        @php $awaiting = $allocatedAssets->filter->isAwaitingAcknowledgement(); @endphp
        @if($awaiting->isNotEmpty())
            <div class="card border-0 mb-4" style="border-radius: 16px; background: #fffbeb; border: 1px solid #fde68a !important;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1 text-dark"><i class="feather-inbox me-1"></i> Please confirm you received {{ $awaiting->count() === 1 ? 'this device' : 'these devices' }}</h6>
                    <p class="text-muted small mb-3">HR has recorded the following as handed over to you. Check the serial number, then confirm - or tell HR if something is wrong.</p>
                    @foreach($awaiting as $alloc)
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:#fde68a !important;">
                            <div>
                                <div class="fw-bold text-dark">{{ $alloc->asset->name }} <span class="badge bg-soft-primary text-primary ms-1" style="font-size:10px;">{{ $alloc->asset->asset_tag }}</span></div>
                                <div class="small text-muted">{{ $alloc->asset->type }} · SN: {{ $alloc->asset->serial_number ?: 'N/A' }} · handed over {{ $alloc->allocated_at?->format('d M Y') }}{{ $alloc->allocatedBy ? ' by ' . $alloc->allocatedBy->name : '' }}{{ $alloc->condition_on_allocation ? ' · condition: ' . $alloc->condition_on_allocation : '' }}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('assets.acknowledge', $alloc->id) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="accept">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="feather-check me-1"></i> Confirm receipt</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="openDisputeModal({{ $alloc->id }}, @js($alloc->asset->name))">Report a problem</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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

                            @if($alloc->asset->image_url)
                            <a href="{{ $alloc->asset->image_url }}" target="_blank" rel="noopener" class="d-block mb-3">
                                <img src="{{ $alloc->asset->image_url }}" alt="{{ $alloc->asset->name }}" loading="lazy" style="width: 100%; height: 160px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0;">
                            </a>
                            @endif

                            <!-- Name -->
                            <h4 class="fw-bold text-dark mb-1 fs-5">{{ $alloc->asset->name }}</h4>
                            <p class="text-muted small mb-1">Asset Tag: <b class="text-dark">{{ $alloc->asset->asset_tag ?: 'N/A' }}</b></p>
                            <p class="text-muted small mb-2">Serial Number: <b class="text-dark">{{ $alloc->asset->serial_number ?: 'N/A' }}</b></p>
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                @if($alloc->isAwaitingAcknowledgement())
                                    <span class="badge bg-soft-warning text-warning" style="font-size:10px;">AWAITING YOUR CONFIRMATION</span>
                                @elseif($alloc->isDisputed())
                                    <span class="badge bg-soft-danger text-danger" style="font-size:10px;">PROBLEM REPORTED</span>
                                @endif
                                @foreach($alloc->asset->openIncidents as $openIncident)
                                    <span class="badge {{ $openIncident->type === 'lost' ? 'bg-soft-danger text-danger' : 'bg-soft-warning text-warning' }}" style="font-size:10px;">{{ strtoupper($openIncident->type) }} REPORTED</span>
                                @endforeach
                            </div>

                            <!-- Specifications -->
                            <div class="mb-3">
                                <span class="small fw-semibold text-muted text-uppercase d-block mb-1">Specifications:</span>
                                <div class="bg-light p-3 rounded-3 text-muted small" style="min-height: 80px; line-height: 1.5; font-weight: 500;">
                                    {!! nl2br(e($alloc->asset->system_configuration)) ?: 'No configuration specified.' !!}
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-soft-warning flex-fill" onclick="amOpenReport('damage', {{ $alloc->asset->id }}, @js($alloc->asset->display_name))"><i class="feather-alert-triangle me-1"></i> Report damage</button>
                                <button type="button" class="btn btn-sm btn-soft-danger flex-fill" onclick="amOpenReport('lost', {{ $alloc->asset->id }}, @js($alloc->asset->display_name))"><i class="feather-x-octagon me-1"></i> Report lost</button>
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

        @if($myIncidents->isNotEmpty())
            <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">⚠️ Damage &amp; Loss Reports</h5>
            <div class="card border-0 shadow-sm mb-5" style="border-radius: 16px; background: white; overflow: hidden; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead style="background:#f8fafc;">
                                <tr>
                                    <th class="ps-4" style="font-size:11px;">Case</th>
                                    <th style="font-size:11px;">Device</th>
                                    <th style="font-size:11px;">What happened</th>
                                    <th style="font-size:11px;">Status</th>
                                    <th class="pe-4" style="font-size:11px;">Recovery</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myIncidents as $inc)
                                    <tr>
                                        <td class="ps-4 small fw-bold">{{ $inc->reference }}<div class="text-muted fw-normal">{{ $inc->incident_date->format('d M Y') }}</div></td>
                                        <td class="small">{{ $inc->asset->name ?? '-' }}<div class="text-muted">{{ $inc->asset->asset_tag ?? '' }}</div></td>
                                        <td class="small" style="max-width:320px;white-space:normal;"><b>{{ \App\Models\AssetIncident::TYPES[$inc->type] }}</b> - {{ $inc->description }}</td>
                                        <td class="small">{{ $inc->isOpen() ? \App\Models\AssetIncident::STATUSES[$inc->status] : ($inc->resolution_label ?? 'Closed') }}</td>
                                        <td class="pe-4 small">
                                            @if($inc->recovery_status === 'none')
                                                -
                                            @else
                                                {{ \App\Models\AssetIncident::RECOVERY_STATUSES[$inc->recovery_status] }}
                                                <div class="text-muted">₹{{ number_format((float) $inc->recovered_amount, 2) }} of ₹{{ number_format((float) $inc->recovery_amount, 2) }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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
                                        @if($req->isDeclaration())
                                            <span class="badge bg-soft-warning text-warning d-block mt-1" style="font-size: 10px; font-weight: 700; text-transform: uppercase; width: fit-content;">Declaration</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 300px; white-space: normal; word-break: break-word;">
                                        @if($req->isDeclaration())
                                            <div class="fw-semibold text-dark small">{{ $req->declared_name }}{{ $req->declared_serial_number ? ' · SN: ' . $req->declared_serial_number : '' }}</div>
                                        @endif
                                        <span class="text-muted small">{{ $req->reason }}</span>
                                        @if($req->created_by && (int) $req->created_by !== (int) $req->user_id)
                                            <div class="text-muted" style="font-size: 11px;">Added by {{ $req->creator->name ?? 'your Team Leader' }}</div>
                                        @endif
                                        @if($req->status === 'Rejected' && $req->rejection_reason)
                                            <div class="text-danger" style="font-size: 11px;">Reason: {{ $req->rejection_reason }}</div>
                                        @endif
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
                                            {{ $req->isDeclaration() && $req->status === 'Pending' ? 'Pending verification' : $req->status }}
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

    @include('assets.partials.declare-modal')

    <!-- Report a problem with a handover -->
    <div class="modal fade" id="disputeModal" tabindex="-1" aria-labelledby="disputeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="disputeModalLabel">Report a problem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="disputeForm" method="POST">
                    @csrf
                    <input type="hidden" name="decision" value="dispute">
                    <div class="modal-body p-4">
                        <p class="small text-muted">Device: <b id="disputeAssetName"></b></p>
                        <label class="fw-semibold mb-2" for="disputeNote">What's wrong? <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="note" id="disputeNote" rows="3" maxlength="500" required placeholder="e.g. I never received it / the serial number is different / it was already damaged" style="border-radius: 10px;"></textarea>
                    </div>
                    <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4" style="border-radius: 8px;">Send to HR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('assets.partials.report-incident-modal', ['action' => route('assets.incidents.report'), 'assetChoices' => null, 'adminFields' => false])

    <script>
        function openDisputeModal(allocationId, assetName) {
            document.getElementById('disputeAssetName').textContent = assetName;
            document.getElementById('disputeForm').action = "{{ url('my-assets') }}/" + allocationId + "/acknowledge";
            bootstrap.Modal.getOrCreateInstance(document.getElementById('disputeModal')).show();
        }
    </script>

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
