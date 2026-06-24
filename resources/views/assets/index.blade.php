@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10" style="color: #3858f9; font-weight: 700;">Asset Inventory</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Asset Management</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2">
                    <a href="javascript:void(0);" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm"
                        style="border-radius: 10px; background: #3858f9; border: none; height: 44px;" 
                        data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="feather-plus"></i> ADD ASSET
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content pt-4" style="font-family: 'Inter', sans-serif;">
        <!-- Switcher Tabs -->
        <div class="d-flex justify-content-start mb-4">
            <div class="tickets-switcher" style="display: inline-flex; background: #f1f5f9; padding: 5px; border-radius: 30px; gap: 4px; border: 1px solid #e2e8f0;">
                <button class="switcher-btn active" id="tab-inventory-btn" onclick="switchAssetTab('inventory')" style="border: none; background: transparent; padding: 8px 24px; border-radius: 25px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.25s;">
                    Asset Inventory
                </button>
                <button class="switcher-btn" id="tab-requests-btn" onclick="switchAssetTab('requests')" style="border: none; background: transparent; padding: 8px 24px; border-radius: 25px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.25s;">
                    Asset Requests 
                    @if($requests->where('status', 'Pending')->count() > 0)
                        <span class="badge bg-danger ms-1" style="font-size: 10px;">{{ $requests->where('status', 'Pending')->count() }}</span>
                    @endif
                </button>
            </div>
        </div>

        <!-- Inventory Tab Section -->
        <div id="section-inventory" class="asset-tab-section">
            <!-- Inventory Sub-tabs -->
            <div class="d-flex justify-content-start mb-3 gap-2" id="inventory-status-filter">
                <button class="status-filter-btn active" onclick="filterAssetStatus('all')" style="border: none; background: #f1f5f9; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #64748b; transition: all 0.2s;">
                    All Stock ({{ $assets->count() }})
                </button>
                <button class="status-filter-btn" onclick="filterAssetStatus('Available')" style="border: none; background: #f1f5f9; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #64748b; transition: all 0.2s;">
                    Available ({{ $assets->where('status', 'Available')->count() }})
                </button>
                <button class="status-filter-btn" onclick="filterAssetStatus('Allocated')" style="border: none; background: #f1f5f9; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #64748b; transition: all 0.2s;">
                    Allocated ({{ $assets->where('status', 'Allocated')->count() }})
                </button>
                <button class="status-filter-btn" onclick="filterAssetStatus('Maintenance')" style="border: none; background: #f1f5f9; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #64748b; transition: all 0.2s;">
                    Maintenance ({{ $assets->where('status', 'Maintenance')->count() }})
                </button>
                <button class="status-filter-btn" onclick="filterAssetStatus('Faulty')" style="border: none; background: #f1f5f9; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #64748b; transition: all 0.2s;">
                    Faulty / Broken ({{ $assets->where('status', 'Faulty')->count() }})
                </button>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px; background: white; overflow: hidden; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #3858f9; color: white;">
                                <tr style="height: 60px; vertical-align: middle;">
                                    <th class="ps-4" style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Asset Name / Type</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Serial Number</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Configuration</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Status</th>
                                    <th class="pe-4 text-center" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 180px; color: white;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $asset)
                                    @php
                                        $statusClass = 'status-pending';
                                        if($asset->status == 'Available') $statusClass = 'status-completed';
                                        elseif($asset->status == 'Allocated') $statusClass = 'status-in-process';
                                        elseif($asset->status == 'Maintenance') $statusClass = 'status-on-hold';
                                        elseif($asset->status == 'Faulty') $statusClass = 'status-rework';
                                    @endphp
                                    <tr class="asset-row-item" data-status="{{ $asset->status }}" style="height: 75px; border-bottom: 1px solid #f1f5f9;">
                                        <td class="ps-4">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-dark fs-6">{{ $asset->name }}</span>
                                                <span class="badge bg-soft-primary text-primary mt-1 align-self-start" style="font-size: 10px; font-weight: 700; text-transform: uppercase;">{{ $asset->type }}</span>
                                            </div>
                                        </td>
                                        <td class="fw-semibold text-muted">{{ $asset->serial_number ?: 'N/A' }}</td>
                                        <td style="max-width: 300px; white-space: normal; word-break: break-word;">
                                            <div class="text-muted small" style="line-height: 1.4;">
                                                {!! nl2br(e($asset->system_configuration)) ?: '<span class="text-light">-</span>' !!}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusClass }}" style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 600; text-transform: uppercase;">
                                                {{ $asset->status }}
                                            </span>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                @if($asset->status == 'Available')
                                                    <a href="javascript:void(0);" 
                                                        class="avatar-text avatar-md bg-soft-success text-success rounded" 
                                                        title="Allocate Manually"
                                                        onclick="openManualAllocateModal({{ json_encode($asset) }})">
                                                        <i class="feather-user-plus"></i>
                                                    </a>
                                                @endif
                                                <a href="javascript:void(0);" 
                                                    class="avatar-text avatar-md bg-soft-primary text-primary rounded" 
                                                    title="Edit"
                                                    onclick="editAsset({{ json_encode($asset) }})">
                                                    <i class="feather-edit-3"></i>
                                                </a>
                                                <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return deleteData(event)">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="avatar-text avatar-md bg-soft-danger text-danger rounded border-0" title="Delete">
                                                        <i class="feather-trash-2"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="inventory-empty-row-initial">
                                        <td colspan="5" class="text-center py-5">
                                            <div class="py-4">
                                                <div style="font-size: 40px;">📦</div>
                                                <h4 class="text-muted mt-3">No Assets Registered</h4>
                                                <p class="text-muted small">Add your first asset details using the button above.</p>
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

        <!-- Requests Tab Section -->
        <div id="section-requests" class="asset-tab-section d-none">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; background: white; overflow: hidden; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #3858f9; color: white;">
                                <tr style="height: 60px; vertical-align: middle;">
                                    <th class="ps-4" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 220px; color: white">Requested By</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 150px; color: white">Asset Type</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white">Reason</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white">Request Date</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 150px; color: white">Allocated Asset</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white">Status</th>
                                    <th class="pe-4 text-center" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 220px; color: white">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    @php
                                        $reqStatusClass = 'status-pending';
                                        if($req->status == 'Allocated' || $req->status == 'Approved') $reqStatusClass = 'status-completed';
                                        elseif($req->status == 'Rejected') $reqStatusClass = 'status-rework';
                                        elseif($req->status == 'Returned') $reqStatusClass = 'status-pending';
                                    @endphp
                                    <tr style="height: 75px; border-bottom: 1px solid #f1f5f9;">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar-wrapper" style="width: 32px; height: 32px; flex-shrink: 0; display: inline-block;">
                                                    @if($req->user && $req->user->photo)
                                                        <img src="{{ asset('storage/' . $req->user->photo) }}" alt="{{ $req->user->name }}" class="user-avatar-img" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                                    @else
                                                        <div class="user-avatar-initials" style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #3858f9 0%, #8b5cf6 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                                            {{ substr($req->user->name ?? 'U', 0, 1) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark fs-6">{{ $req->user->name ?? 'Unknown User' }}</span>
                                                    <span class="text-muted small" style="font-size: 11px;">{{ $req->user->role ?? '' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-soft-primary text-primary" style="font-weight: 700; text-transform: uppercase;">{{ $req->asset_type }}</span>
                                        </td>
                                        <td style="max-width: 250px; white-space: normal; word-break: break-word;">
                                            <div class="text-muted small">{{ $req->reason }}</div>
                                        </td>
                                        <td class="text-muted small">{{ $req->created_at->format('d M, Y') }}</td>
                                        <td>
                                            @if($req->asset)
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark small">{{ $req->asset->name }}</span>
                                                    <span class="text-muted small" style="font-size: 10px;">SN: {{ $req->asset->serial_number ?: 'N/A' }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $reqStatusClass }}" style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 600; text-transform: uppercase;">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                        <td class="pe-4 text-center">
                                            @if($req->status == 'Pending')
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button class="btn btn-sm btn-soft-success fw-bold d-flex align-items-center gap-1" style="border-radius: 8px;" onclick="openAllocateModal({{ $req->id }}, '{{ $req->asset_type }}')">
                                                        <i class="feather-check"></i> Allocate
                                                    </button>
                                                    <form action="{{ route('assets.reject', $req->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-soft-danger fw-bold d-flex align-items-center gap-1" style="border-radius: 8px;">
                                                            <i class="feather-x"></i> Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif($req->status == 'Allocated')
                                                <button type="button" class="btn btn-sm btn-soft-secondary fw-bold d-flex align-items-center gap-1 mx-auto" style="border-radius: 8px;" onclick="openReturnModal({{ $req->id }}, '{{ $req->asset ? $req->asset->name : 'Device' }}')">
                                                    <i class="feather-corner-down-left"></i> Mark Returned
                                                </button>
                                            @else
                                                <span class="text-muted small">No action required</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="py-4">
                                                <div style="font-size: 40px;">📝</div>
                                                <h4 class="text-muted mt-3">No Asset Requests</h4>
                                                <p class="text-muted small">Employees' device request letters will show up here.</p>
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
    </div>

    <!-- Add Asset Modal -->
    <div class="modal fade" id="addAssetModal" tabindex="-1" aria-labelledby="addAssetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAssetModalLabel">Add New Inventory Asset(s)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('assets.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4" style="max-height: 60vh; overflow-y: auto;">
                        <div id="assets-container">
                            <div class="asset-entry-row p-3 mb-3 border rounded-3 position-relative" style="background: #f8fafc; border-color: #e2e8f0 !important;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-primary asset-heading">Asset #1</h6>
                                    <button type="button" class="btn-close remove-asset-row-btn d-none" aria-label="Remove" onclick="removeAssetRow(this)"></button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-semibold small mb-1">Asset Name / Model <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="assets[0][name]" placeholder="e.g. HP EliteBook 840 G3" required style="border-radius: 8px;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold small mb-1">Asset Type <span class="text-danger">*</span></label>
                                        <select class="form-select" name="assets[0][type]" required style="border-radius: 8px;">
                                         <option value="PC">PC</option>    
                                        <option value="Laptop">Laptop</option>
                                            <option value="Keyboard">Keyboard</option>
                                            <option value="Mouse">Mouse</option>
                                            <option value="Monitor">Monitor</option>
                                            <option value="Charger">Charger</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold small mb-1">Serial Number / Unique ID</label>
                                        <input type="text" class="form-control" name="assets[0][serial_number]" placeholder="e.g. SN-92B4F6F5" style="border-radius: 8px;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold small mb-1">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" name="assets[0][status]" required style="border-radius: 8px;">
                                            <option value="Available" selected>Available</option>
                                            <option value="Allocated">Allocated</option>
                                            <option value="Maintenance">Maintenance</option>
                                            <option value="Faulty">Faulty</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="fw-semibold small mb-1">System Configuration</label>
                                        <textarea class="form-control" name="assets[0][system_configuration]" rows="2" placeholder="Processor, RAM, Storage details..." style="border-radius: 8px;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-soft-primary btn-sm mb-2 w-100 fw-bold d-flex align-items-center justify-content-center gap-1" id="add-asset-row-btn" style="border-radius: 8px; height: 40px;">
                            <i class="feather-plus"></i> ADD ANOTHER ASSET ROW
                        </button>
                    </div>
                    <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" style="background: #3858f9; border: none; border-radius: 8px;">Save Assets</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Asset Modal -->
    <div class="modal fade" id="editAssetModal" tabindex="-1" aria-labelledby="editAssetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAssetModalLabel">Edit Asset Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editAssetForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Asset Name / Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit-name" required>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Asset Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="edit-type" required>
                                  <option value="PC">PC</option>    
                                <option value="Laptop">Laptop</option>
                                <option value="Keyboard">Keyboard</option>
                                <option value="Mouse">Mouse</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Charger">Charger</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Serial Number / Unique ID</label>
                            <input type="text" class="form-control" name="serial_number" id="edit-serial_number">
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">System Configuration</label>
                            <textarea class="form-control" name="system_configuration" id="edit-system_configuration" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="edit-status" required>
                                <option value="Available">Available</option>
                                <option value="Allocated">Allocated</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Faulty">Faulty</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" style="background: #3858f9; border: none; border-radius: 8px;">Update Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manual Allocate Asset Modal -->
    <div class="modal fade" id="manualAllocateModal" tabindex="-1" aria-labelledby="manualAllocateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualAllocateModalLabel">Manual Device Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="manualAllocateForm" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Asset Name / Model</label>
                            <input type="text" class="form-control bg-light text-dark fw-semibold" id="manual-alloc-asset-name" readonly style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Select Employee <span class="text-danger">*</span></label>
                            <select class="form-select" name="user_id" required style="height: 48px; border-radius: 10px;">
                                <option value="">-- Choose Employee --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ str_replace('_', ' ', ucwords($u->role)) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-success px-4 text-white" style="border-radius: 8px;">Allocate Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Allocate Asset Modal -->
    <div class="modal fade" id="allocateModal" tabindex="-1" aria-labelledby="allocateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="allocateModalLabel">Allocate Inventory Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="allocateForm" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">Choose from available stock assets matching the requested type: <b id="req-type-display" class="text-primary"></b></p>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Select Available Asset</label>
                            <select class="form-select" name="asset_id" id="allocate-asset-select" required>
                                <!-- Populated dynamically by JS -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-success px-4 text-white" style="border-radius: 8px;">Allocate Device</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Return Asset Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnModalLabel">Return Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="returnForm" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">Please specify the return condition for device: <b id="return-asset-name-display" class="text-primary"></b></p>
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Asset Condition on Return <span class="text-danger">*</span></label>
                            <select class="form-select" name="condition" id="return-condition-select" required style="height: 48px; border-radius: 10px;">
                                <option value="Good" selected>Good / Working (Asset status set to Available)</option>
                                <option value="Maintenance">Needs Repair (Asset status set to Maintenance)</option>
                                <option value="Faulty">Broken / Scrapped (Asset status set to Faulty)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" style="background: #3858f9; border: none; border-radius: 8px;">Confirm Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .switcher-btn.active {
            background: #ffffff !important;
            color: #3858f9 !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.02);
        }
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
        .status-filter-btn.active {
            background: #3858f9 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 6px -1px rgba(56, 88, 249, 0.2), 0 2px 4px -1px rgba(56, 88, 249, 0.1);
        }
    </style>

    <script>
        let editAssetModal;
        let allocateModal;
        let manualAllocateModal;
        let returnModal;
        let assetRowIndex = 1;
        const allAvailableAssets = @json($availableAssets);

        document.addEventListener('DOMContentLoaded', function() {
            editAssetModal = new bootstrap.Modal(document.getElementById('editAssetModal'));
            allocateModal = new bootstrap.Modal(document.getElementById('allocateModal'));
            manualAllocateModal = new bootstrap.Modal(document.getElementById('manualAllocateModal'));
            returnModal = new bootstrap.Modal(document.getElementById('returnModal'));

            // Add Asset Row cloning listener
            const addBtn = document.getElementById('add-asset-row-btn');
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    const container = document.getElementById('assets-container');
                    
                    // Get values from the last row to clone them
                    const rows = container.querySelectorAll('.asset-entry-row');
                    const lastRow = rows[rows.length - 1];
                    
                    let nameVal = '';
                    let typeVal = 'Laptop';
                    let statusVal = 'Available';
                    let configVal = '';
                    
                    if (lastRow) {
                        nameVal = lastRow.querySelector('[name*="[name]"]').value || '';
                        typeVal = lastRow.querySelector('[name*="[type]"]').value || 'Laptop';
                        statusVal = lastRow.querySelector('[name*="[status]"]').value || 'Available';
                        configVal = lastRow.querySelector('[name*="[system_configuration]"]').value || '';
                    }

                    const nextIndex = rows.length;

                    const rowTemplate = `
<div class="asset-entry-row p-3 mb-3 border rounded-3 position-relative" style="background: #f8fafc; border-color: #e2e8f0 !important;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-primary asset-heading">Asset #${nextIndex + 1}</h6>
        <button type="button" class="btn-close remove-asset-row-btn" aria-label="Remove" onclick="removeAssetRow(this)"></button>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="fw-semibold small mb-1">Asset Name / Model <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="assets[${nextIndex}][name]" value="${escapeHtml(nameVal)}" placeholder="e.g. HP EliteBook 840 G3" required style="border-radius: 8px;">
        </div>
        <div class="col-md-6">
            <label class="fw-semibold small mb-1">Asset Type <span class="text-danger">*</span></label>
            <select class="form-select" name="assets[${nextIndex}][type]" required style="border-radius: 8px;">
                <option value="Laptop" ${typeVal === 'Laptop' ? 'selected' : ''}>Laptop</option>
                <option value="Keyboard" ${typeVal === 'Keyboard' ? 'selected' : ''}>Keyboard</option>
                <option value="Mouse" ${typeVal === 'Mouse' ? 'selected' : ''}>Mouse</option>
                <option value="Monitor" ${typeVal === 'Monitor' ? 'selected' : ''}>Monitor</option>
                <option value="Charger" ${typeVal === 'Charger' ? 'selected' : ''}>Charger</option>
                <option value="Other" ${typeVal === 'Other' ? 'selected' : ''}>Other</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="fw-semibold small mb-1">Serial Number / Unique ID</label>
            <input type="text" class="form-control" name="assets[${nextIndex}][serial_number]" placeholder="e.g. SN-92B4F6F5" style="border-radius: 8px;">
        </div>
        <div class="col-md-6">
            <label class="fw-semibold small mb-1">Status <span class="text-danger">*</span></label>
            <select class="form-select" name="assets[${nextIndex}][status]" required style="border-radius: 8px;">
                <option value="Available" ${statusVal === 'Available' ? 'selected' : ''}>Available</option>
                <option value="Allocated" ${statusVal === 'Allocated' ? 'selected' : ''}>Allocated</option>
                <option value="Maintenance" ${statusVal === 'Maintenance' ? 'selected' : ''}>Maintenance</option>
                <option value="Faulty" ${statusVal === 'Faulty' ? 'selected' : ''}>Faulty</option>
            </select>
        </div>
        <div class="col-12">
            <label class="fw-semibold small mb-1">System Configuration</label>
            <textarea class="form-control" name="assets[${nextIndex}][system_configuration]" rows="2" placeholder="Processor, RAM, Storage details..." style="border-radius: 8px;">${escapeHtml(configVal)}</textarea>
        </div>
    </div>
</div>`;
                    container.insertAdjacentHTML('beforeend', rowTemplate);
                    renumberAssetRows();
                });
            }
        });

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function renumberAssetRows() {
            const container = document.getElementById('assets-container');
            const rows = container.querySelectorAll('.asset-entry-row');
            rows.forEach((row, index) => {
                // Update heading
                row.querySelector('.asset-heading').innerText = `Asset #${index + 1}`;
                
                // Update names of all inputs in this row
                row.querySelectorAll('[name^="assets["]').forEach(input => {
                    const nameAttr = input.getAttribute('name');
                    const newName = nameAttr.replace(/assets\[\d+\]/, `assets[${index}]`);
                    input.setAttribute('name', newName);
                });

                // Toggle close button visibility: hide for first row if it's the only one left
                const removeBtn = row.querySelector('.remove-asset-row-btn');
                if (removeBtn) {
                    if (rows.length === 1) {
                        removeBtn.classList.add('d-none');
                    } else {
                        removeBtn.classList.remove('d-none');
                    }
                }
            });
            // Update global index counter
            assetRowIndex = rows.length;
        }

        function removeAssetRow(button) {
            const row = button.closest('.asset-entry-row');
            row.remove();
            renumberAssetRows();
        }

        function switchAssetTab(tab) {
            document.querySelectorAll('.switcher-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.asset-tab-section').forEach(sec => sec.classList.add('d-none'));

            if (tab === 'inventory') {
                document.getElementById('tab-inventory-btn').classList.add('active');
                document.getElementById('section-inventory').classList.remove('d-none');
            } else {
                document.getElementById('tab-requests-btn').classList.add('active');
                document.getElementById('section-requests').classList.remove('d-none');
            }
        }

        function editAsset(asset) {
            document.getElementById('edit-name').value = asset.name;
            document.getElementById('edit-type').value = asset.type;
            document.getElementById('edit-serial_number').value = asset.serial_number || '';
            document.getElementById('edit-system_configuration').value = asset.system_configuration || '';
            document.getElementById('edit-status').value = asset.status;
            
            document.getElementById('editAssetForm').action = "{{ url('asset-management') }}/" + asset.id;
            editAssetModal.show();
        }

        function openAllocateModal(requestId, requestedType) {
            document.getElementById('req-type-display').innerText = requestedType;
            
            // Filter available assets that match the requested type
            const filtered = allAvailableAssets.filter(asset => asset.type.toLowerCase() === requestedType.toLowerCase());
            
            let options = '';
            if (filtered.length === 0) {
                options = '<option value="">No available assets found of this type</option>';
            } else {
                filtered.forEach(asset => {
                    const sn = asset.serial_number ? ` (SN: ${asset.serial_number})` : '';
                    options += `<option value="${asset.id}">${asset.name}${sn}</option>`;
                });
            }
            
            document.getElementById('allocate-asset-select').innerHTML = options;
            document.getElementById('allocateForm').action = "{{ url('asset-requests') }}/" + requestId + "/allocate";
            
            allocateModal.show();
        }

        function openManualAllocateModal(asset) {
            document.getElementById('manual-alloc-asset-name').value = asset.name;
            document.getElementById('manualAllocateForm').action = "{{ url('asset-management') }}/" + asset.id + "/allocate-manual";
            manualAllocateModal.show();
        }

        function openReturnModal(requestId, assetName) {
            document.getElementById('return-asset-name-display').innerText = assetName;
            document.getElementById('returnForm').action = "{{ url('asset-requests') }}/" + requestId + "/return";
            returnModal.show();
        }

        function filterAssetStatus(status) {
            // Get all filter buttons inside #inventory-status-filter
            const filterContainer = document.getElementById('inventory-status-filter');
            if (filterContainer) {
                const buttons = filterContainer.querySelectorAll('.status-filter-btn');
                buttons.forEach(btn => {
                    btn.classList.remove('active');
                });
            }
            
            // Add active class to clicked button
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }

            // Filter rows
            const rows = document.querySelectorAll('.asset-row-item');
            let visibleCount = 0;
            rows.forEach(row => {
                if (status === 'all' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Hide initial empty state if it exists
            const initialEmptyRow = document.getElementById('inventory-empty-row-initial');
            if (initialEmptyRow) {
                if (rows.length === 0) {
                    initialEmptyRow.style.display = '';
                } else {
                    initialEmptyRow.style.display = 'none';
                }
            }

            // Handle dynamic empty state row if zero rows match during filtering
            const dynamicEmptyRow = document.getElementById('inventory-empty-row');
            if (visibleCount === 0 && rows.length > 0) {
                if (!dynamicEmptyRow) {
                    const tbody = document.querySelector('#section-inventory table tbody');
                    if (tbody) {
                        tbody.insertAdjacentHTML('beforeend', `
                            <tr id="inventory-empty-row">
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-4">
                                        <div style="font-size: 40px;">📦</div>
                                        <h4 class="text-muted mt-3">No Assets Found</h4>
                                        <p class="text-muted small">No assets with status "${status}" were found in stock.</p>
                                    </div>
                                </td>
                            </tr>
                        `);
                    }
                } else {
                    dynamicEmptyRow.style.display = '';
                    dynamicEmptyRow.querySelector('p').innerText = `No assets with status "${status}" were found in stock.`;
                }
            } else {
                if (dynamicEmptyRow) {
                    dynamicEmptyRow.style.display = 'none';
                }
            }
        }
    </script>
@endsection
