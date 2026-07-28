@extends('layouts.app')

@section('content')
    @php
        $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);
    @endphp
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
        @if($isAdmin)
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
        @endif
    </div>

    <div class="main-content pt-4" style="font-family: 'Inter', sans-serif;">
        <!-- Switcher Tabs -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div class="tickets-switcher" style="display: inline-flex; background: #f1f5f9; padding: 5px; border-radius: 30px; gap: 4px; border: 1px solid #e2e8f0; overflow-x: auto; max-width: 100%;">
                <button class="switcher-btn active" id="tab-inventory-btn" onclick="switchAssetTab('inventory')" style="border: none; background: transparent; padding: 8px 24px; border-radius: 25px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.25s; white-space: nowrap;">
                    Asset Inventory
                </button>
                <button class="switcher-btn" id="tab-requests-btn" onclick="switchAssetTab('requests')" style="border: none; background: transparent; padding: 8px 24px; border-radius: 25px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.25s; white-space: nowrap;">
                    Asset Requests 
                    @if($requests->where('status', 'Pending')->count() > 0)
                        <span class="badge bg-danger ms-1" style="font-size: 10px;">{{ $requests->where('status', 'Pending')->count() }}</span>
                    @endif
                </button>
                <button class="switcher-btn" id="tab-allocated-btn" onclick="switchAssetTab('allocated')" style="border: none; background: transparent; padding: 8px 24px; border-radius: 25px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.25s; white-space: nowrap;">
                    Allocated Assets
                    @if($requests->where('status', 'Allocated')->count() > 0)
                        <span class="badge bg-success ms-1" style="font-size: 10px; background: #22c55e !important;">{{ $requests->where('status', 'Allocated')->count() }}</span>
                    @endif
                </button>
                <button class="switcher-btn" id="tab-returned-btn" onclick="switchAssetTab('returned')" style="border: none; background: transparent; padding: 8px 24px; border-radius: 25px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.25s; white-space: nowrap;">
                    Returned Assets
                </button>
                @if($isAdmin)
                <button class="switcher-btn" id="tab-deactivated-btn" onclick="switchAssetTab('deactivated')" style="border: none; background: transparent; padding: 8px 24px; border-radius: 25px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.25s; white-space: nowrap;">
                    Deactivated Employees
                    @if($deactivatedAllocations->groupBy('user_id')->count() > 0)
                        <span class="badge bg-danger ms-1" style="font-size: 10px;">{{ $deactivatedAllocations->groupBy('user_id')->count() }}</span>
                    @endif
                </button>
                @endif
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
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Assigned Employee</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Configuration</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white;">Status</th>
                                    <th class="pe-4 text-center" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 180px; color: white;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $inventoryRows = $assets
                                        ->groupBy(fn($asset) => $asset->activeAllocation ? 'user_' . $asset->activeAllocation->user_id : 'asset_' . $asset->id)
                                        ->sortBy(function($group) {
                                            $asset = $group->first();
                                            return strtolower($asset->activeAllocation->user->name ?? $asset->name ?? '');
                                        });
                                @endphp
                                @forelse($inventoryRows as $inventoryGroup)
                                    @php
                                        $asset = $inventoryGroup->first();
                                        $isAssignedGroup = $asset->activeAllocation && $inventoryGroup->count() > 1;
                                        $statusClass = 'status-pending';
                                        if($asset->status == 'Available') $statusClass = 'status-completed';
                                        elseif($asset->status == 'Allocated') $statusClass = 'status-in-process';
                                        elseif($asset->status == 'Maintenance') $statusClass = 'status-on-hold';
                                        elseif($asset->status == 'Faulty') $statusClass = 'status-rework';

                                        $assignedUser = $asset->activeAllocation->user ?? null;
                                        $assignedDept = $assignedUser->employee->department ?? $assignedUser->role ?? '';
                                        $assetSummary = $inventoryGroup->map(function($item) {
                                            $serial = $item->serial_number ? ' - SN: ' . $item->serial_number : '';
                                            return $item->name . $serial;
                                        })->implode(', ');
                                        $jsAllocations = $inventoryGroup->filter(fn($item) => $item->activeAllocation)->map(function($item) {
                                            return [
                                                'id' => $item->activeAllocation->id,
                                                'name' => $item->name ?? 'Unknown Asset',
                                                'type' => $item->type ?? 'Other',
                                                'serial_number' => $item->serial_number ?? 'N/A',
                                                'allocated_at' => $item->activeAllocation->allocated_at ? $item->activeAllocation->allocated_at->format('d M, Y') : $item->activeAllocation->updated_at->format('d M, Y'),
                                                'system_configuration' => $item->system_configuration ?? '-',
                                            ];
                                        })->values()->all();
                                    @endphp
                                    <tr class="asset-row-item" data-status="{{ $asset->status }}" style="height: 75px; border-bottom: 1px solid #f1f5f9;">
                                        <td class="ps-4">
                                            @php
                                                $typeLower = strtolower($asset->type ?? '');
                                                $iconClass = 'feather-package';
                                                if ($typeLower === 'pc') $iconClass = 'feather-monitor';
                                                elseif ($typeLower === 'laptop') $iconClass = 'bi bi-laptop';
                                                elseif ($typeLower === 'keyboard') $iconClass = 'feather-type';
                                                elseif ($typeLower === 'mouse') $iconClass = 'feather-mouse-pointer';
                                                elseif ($typeLower === 'monitor') $iconClass = 'feather-tv';
                                                elseif ($typeLower === 'charger') $iconClass = 'feather-zap';
                                            @endphp
                                            <div class="d-flex align-items-center">
                                                <div style="width: 32px; height: 32px; background: rgba(56, 88, 249, 0.08); color: #3858f9; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;" class="me-2">
                                                    <i class="{{ $iconClass }}" style="font-size: 15px;"></i>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark fs-6">
                                                        {{ $isAssignedGroup ? ($assignedUser->name ?? 'Assigned Employee') : $asset->name }}
                                                    </span>
                                                    <span class="badge bg-soft-primary text-primary mt-1 align-self-start" style="font-size: 10px; font-weight: 700; text-transform: uppercase;">
                                                        {{ $isAssignedGroup ? $inventoryGroup->count() . ' ASSETS' : $asset->type }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold text-muted">
                                            {{ $isAssignedGroup ? $inventoryGroup->count() . ' assigned' : ($asset->serial_number ?: 'N/A') }}
                                        </td>
                                        <td>
                                            @if($assignedUser)
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="user-avatar-wrapper" style="width: 30px; height: 30px; flex-shrink: 0;">
                                                        @if($assignedUser->photo)
                                                            <img src="{{ asset('storage/' . $assignedUser->photo) }}" alt="{{ $assignedUser->name }}" class="user-avatar-img" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                                        @else
                                                            <div class="user-avatar-initials" style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #3858f9 0%, #8b5cf6 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                                                {{ substr($assignedUser->name ?? 'U', 0, 1) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold text-dark small">{{ $assignedUser->name }}</span>
                                                        <span class="text-muted" style="font-size: 10px;">{{ $assignedDept }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="badge bg-soft-secondary text-secondary" style="font-size: 10px; padding: 6px 10px; border-radius: 30px; font-weight: 700;">Unassigned</span>
                                            @endif
                                        </td>
                                        <td style="max-width: 300px; white-space: normal; word-break: break-word;">
                                            @if($isAssignedGroup)
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 420px;" title="{{ $assetSummary }}">
                                                    {{ $assetSummary }}
                                                </div>
                                                <div class="text-muted small mt-1" style="font-size: 11px;">
                                                    {{ $inventoryGroup->pluck('type')->filter()->unique()->implode(', ') ?: 'Asset details' }}
                                                </div>
                                            @else
                                                <div class="text-muted small" style="line-height: 1.4;">
                                                    {!! nl2br(e($asset->system_configuration)) ?: '<span class="text-light">-</span>' !!}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusClass }}" style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 600; text-transform: uppercase;">
                                                {{ $asset->status }}
                                            </span>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                @if($isAssignedGroup)
                                                    <button class="btn btn-sm btn-soft-primary fw-bold"
                                                            style="border-radius: 8px;"
                                                            data-assets='@json($jsAllocations)'
                                                            data-employee="{{ $assignedUser->name ?? 'Employee' }}"
                                                            onclick="openAllocatedAssetsOffcanvas(this)">
                                                        <i class="feather-eye"></i> Manage Assets
                                                    </button>
                                                @else
                                                @if($isAdmin)
                                                @if($asset->status == 'Available')
                                                    <a href="javascript:void(0);"
                                                        class="avatar-text avatar-md bg-soft-success text-success rounded"
                                                        title="Allocate Manually"
                                                        onclick="openManualAllocateModal({{ json_encode($asset) }})">
                                                        <i class="feather-user-plus"></i>
                                                    </a>
                                                @endif
                                                @endif
                                                @if($isAdmin || $isTeamLeader)
                                                <a href="javascript:void(0);"
                                                    class="avatar-text avatar-md bg-soft-primary text-primary rounded"
                                                    title="Edit"
                                                    onclick="editAsset({{ json_encode($asset) }}, {{ $asset->activeAllocation->user_id ?? 'null' }})">
                                                    <i class="feather-edit-3"></i>
                                                </a>
                                                @endif
                                                @if($isAdmin)
                                                <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return deleteData(event)">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="avatar-text avatar-md bg-soft-danger text-danger rounded border-0" title="Delete">
                                                        <i class="feather-trash-2"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                @if(!$isAdmin && !$isTeamLeader)
                                                    <span class="text-muted small">View only</span>
                                                @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="inventory-empty-row-initial">
                                        <td colspan="6" class="text-center py-5">
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
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white">Status</th>
                                    <th class="pe-4 text-center" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 220px; color: white">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $activeRequests = $requests->whereIn('status', ['Pending', 'Rejected']);
                                @endphp
                                @forelse($activeRequests as $req)
                                    @php
                                        $reqStatusClass = 'status-pending';
                                        if($req->status == 'Rejected') $reqStatusClass = 'status-rework';
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
                                                    <span class="text-muted small" style="font-size: 11px;">{{ $req->user->employee->department ?? $req->user->role ?? '' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $typeLower = strtolower($req->asset_type ?? '');
                                                $iconClass = 'feather-package';
                                                if ($typeLower === 'pc') $iconClass = 'feather-monitor';
                                                elseif ($typeLower === 'laptop') $iconClass = 'feather-tablet';
                                                elseif ($typeLower === 'keyboard') $iconClass = 'feather-type';
                                                elseif ($typeLower === 'mouse') $iconClass = 'feather-mouse-pointer';
                                                elseif ($typeLower === 'monitor') $iconClass = 'feather-tv';
                                                elseif ($typeLower === 'charger') $iconClass = 'feather-zap';
                                            @endphp
                                            <span class="badge bg-soft-primary text-primary d-inline-flex align-items-center gap-1" style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 700; text-transform: uppercase;">
                                                <i class="{{ $iconClass }}" style="font-size: 11px;"></i>
                                                {{ $req->asset_type }}
                                            </span>
                                        </td>
                                        <td style="max-width: 250px; white-space: normal; word-break: break-word;">
                                            <div class="text-muted small">{{ $req->reason }}</div>
                                        </td>
                                        <td class="text-muted small">{{ $req->created_at->format('d M, Y') }}</td>
                                        <td>
                                            <span class="badge {{ $reqStatusClass }}" style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 600; text-transform: uppercase;">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                        <td class="pe-4 text-center">
                                            @if($req->status == 'Pending' && $isAdmin)
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

        <!-- Allocated Assets Tab Section -->
        <div id="section-allocated" class="asset-tab-section d-none">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; background: white; overflow: hidden; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #3858f9; color: white;">
                                <tr style="height: 60px; vertical-align: middle;">
                                    <th class="ps-4" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 220px; color: white">Employee</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 120px; color: white">Assets Count</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white">Asset Details</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white">Latest Assigned</th>
                                    <th class="pe-4 text-center" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 200px; color: white">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $allocatedRequests = $requests->where('status', 'Allocated');
                                    $allocatedByUser = $allocatedRequests
                                        ->groupBy('user_id')
                                        ->sortBy(fn($userAllocations) => strtolower($userAllocations->first()->user->name ?? ''));
                                @endphp
                                @forelse($allocatedByUser as $userId => $userAllocations)
                                    @php
                                        $firstAlloc = $userAllocations->first();
                                        $user = $firstAlloc->user;
                                        $dept = $user->employee->department ?? $user->role ?? 'N/A';
                                        $latestAlloc = $userAllocations->sortByDesc(fn($a) => $a->allocated_at ?? $a->updated_at)->first();
                                        $assignedDate = $latestAlloc->allocated_at ? $latestAlloc->allocated_at->format('d M, Y') : $latestAlloc->updated_at->format('d M, Y');
                                        $assetSummary = $userAllocations->map(function($a) {
                                            $assetName = $a->asset->name ?? 'Unknown Asset';
                                            $serial = $a->asset && $a->asset->serial_number ? ' - SN: ' . $a->asset->serial_number : '';
                                            return $assetName . $serial;
                                        })->implode(', ');
                                        
                                        $jsAllocations = $userAllocations->map(function($a) {
                                            return [
                                                'id' => $a->id,
                                                'name' => $a->asset->name ?? 'Unknown Asset',
                                                'type' => $a->asset->type ?? 'Other',
                                                'serial_number' => $a->asset->serial_number ?? 'N/A',
                                                'allocated_at' => $a->allocated_at ? $a->allocated_at->format('d M, Y') : $a->updated_at->format('d M, Y'),
                                                'system_configuration' => $a->asset->system_configuration ?? '-'
                                            ];
                                        })->values()->all();
                                    @endphp
                                    <tr style="height: 75px; border-bottom: 1px solid #f1f5f9;">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar-wrapper" style="width: 32px; height: 32px; flex-shrink: 0; display: inline-block;">
                                                    @if($user && $user->photo)
                                                        <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}" class="user-avatar-img" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                                    @else
                                                        <div class="user-avatar-initials" style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #3858f9 0%, #8b5cf6 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                                            {{ substr($user->name ?? 'U', 0, 1) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark fs-6">{{ $user->name ?? 'Unknown User' }}</span>
                                                    <span class="text-muted small" style="font-size: 11px;">{{ $dept }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-soft-success text-success" style="font-size: 12px; padding: 8px 12px; border-radius: 30px; font-weight: 700;">
                                                {{ $userAllocations->count() }} {{ \Illuminate\Support\Str::plural('asset', $userAllocations->count()) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark text-truncate" style="max-width: 520px;" title="{{ $assetSummary }}">
                                                {{ $assetSummary }}
                                            </div>
                                            <div class="text-muted small mt-1" style="font-size: 11px;">
                                                {{ $userAllocations->pluck('asset.type')->filter()->unique()->implode(', ') ?: 'Asset details' }}
                                            </div>
                                        </td>
                                        <td class="text-muted small">{{ $assignedDate }}</td>
                                        <td class="pe-4 text-center">
                                            <button class="btn btn-sm btn-soft-primary fw-bold" 
                                                    style="border-radius: 8px;" 
                                                    data-assets='@json($jsAllocations)' 
                                                    data-employee="{{ $user->name ?? 'Employee' }}"
                                                    onclick="openAllocatedAssetsOffcanvas(this)">
                                                <i class="feather-eye"></i> Manage Assets
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="py-4">
                                                <div style="font-size: 40px;">🖥️</div>
                                                <h4 class="text-muted mt-3">No Allocated Assets</h4>
                                                <p class="text-muted small">Devices currently assigned to employees will be listed here.</p>
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

        <!-- Returned Assets Tab Section -->
        <div id="section-returned" class="asset-tab-section d-none">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; background: white; overflow: hidden; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #3858f9; color: white;">
                                <tr style="height: 60px; vertical-align: middle;">
                                    <th class="ps-4" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 220px; color: white">Employee</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 200px; color: white">Returned Asset</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white">Asset Type</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white">Assigned Date</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white">Return Date</th>
                                    <th class="pe-4 text-center" style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $returnedRequests = $requests->where('status', 'Returned');
                                @endphp
                                @forelse($returnedRequests as $req)
                                    @php
                                        $dept = $req->user->employee->department ?? $req->user->role ?? 'N/A';
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
                                                    <span class="text-muted small" style="font-size: 11px;">{{ $dept }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($req->asset)
                                                @php
                                                    $typeLower = strtolower($req->asset->type ?? '');
                                                    $iconClass = 'feather-package';
                                                    if ($typeLower === 'pc') $iconClass = 'feather-monitor';
                                                    elseif ($typeLower === 'laptop') $iconClass = 'feather-tablet';
                                                    elseif ($typeLower === 'keyboard') $iconClass = 'feather-type';
                                                    elseif ($typeLower === 'mouse') $iconClass = 'feather-mouse-pointer';
                                                    elseif ($typeLower === 'monitor') $iconClass = 'feather-tv';
                                                    elseif ($typeLower === 'charger') $iconClass = 'feather-zap';
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 32px; height: 32px; background: rgba(56, 88, 249, 0.08); color: #3858f9; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;" class="me-2">
                                                        <i class="{{ $iconClass }}" style="font-size: 15px;"></i>
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold text-dark small" style="font-size: 13px;">{{ $req->asset->name }}</span>
                                                        <span class="text-muted small" style="font-size: 10px;">SN: {{ $req->asset->serial_number ?: 'N/A' }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $typeLower = strtolower($req->asset_type ?? '');
                                                $iconClass = 'feather-package';
                                                if ($typeLower === 'pc') $iconClass = 'feather-monitor';
                                                elseif ($typeLower === 'laptop') $iconClass = 'feather-tablet';
                                                elseif ($typeLower === 'keyboard') $iconClass = 'feather-type';
                                                elseif ($typeLower === 'mouse') $iconClass = 'feather-mouse-pointer';
                                                elseif ($typeLower === 'monitor') $iconClass = 'feather-tv';
                                                elseif ($typeLower === 'charger') $iconClass = 'feather-zap';
                                            @endphp
                                            <span class="badge bg-soft-primary text-primary d-inline-flex align-items-center gap-1" style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 700; text-transform: uppercase;">
                                                <i class="{{ $iconClass }}" style="font-size: 11px;"></i>
                                                {{ $req->asset_type }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">{{ $req->allocated_at ? $req->allocated_at->format('d M, Y') : $req->updated_at->format('d M, Y') }}</td>
                                        <td class="text-muted small">{{ $req->returned_at ? $req->returned_at->format('d M, Y') : '-' }}</td>
                                        <td class="pe-4 text-center">
                                            <span class="badge bg-soft-secondary text-secondary" style="font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 600; text-transform: uppercase;">
                                                Returned
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="py-4">
                                                <div style="font-size: 40px;">🔄</div>
                                                <h4 class="text-muted mt-3">No Returned Assets</h4>
                                                <p class="text-muted small">Historically returned company devices will appear here.</p>
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

        @if($isAdmin)
        <!-- Deactivated Employees Tab Section -->
        <div id="section-deactivated" class="asset-tab-section d-none">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; background: white; overflow: hidden; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #3858f9; color: white;">
                                <tr style="height: 60px; vertical-align: middle;">
                                    <th class="ps-4" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 220px; color: white">Deactivated Employee</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 120px; color: white">Assets Count</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: white">Asset Details</th>
                                    <th style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 140px; color: white">Latest Assigned</th>
                                    <th class="pe-4 text-center" style="font-size: 12px; font-weight: 700; text-transform: uppercase; width: 200px; color: white">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $deactivatedByUser = $deactivatedAllocations
                                        ->groupBy('user_id')
                                        ->sortBy(fn($userAllocations) => strtolower($userAllocations->first()->user->name ?? ''));
                                @endphp
                                @forelse($deactivatedByUser as $userId => $userAllocations)
                                    @php
                                        $firstAlloc = $userAllocations->first();
                                        $user = $firstAlloc->user;
                                        $dept = $user->employee->department ?? $user->role ?? 'N/A';
                                        $latestAlloc = $userAllocations->sortByDesc(fn($a) => $a->allocated_at ?? $a->updated_at)->first();
                                        $assignedDate = $latestAlloc->allocated_at ? $latestAlloc->allocated_at->format('d M, Y') : $latestAlloc->updated_at->format('d M, Y');
                                        $assetSummary = $userAllocations->map(function($a) {
                                            $assetName = $a->asset->name ?? 'Unknown Asset';
                                            $serial = $a->asset && $a->asset->serial_number ? ' - SN: ' . $a->asset->serial_number : '';
                                            return $assetName . $serial;
                                        })->implode(', ');

                                        $jsAllocations = $userAllocations->map(function($a) {
                                            return [
                                                'id' => $a->id,
                                                'name' => $a->asset->name ?? 'Unknown Asset',
                                                'type' => $a->asset->type ?? 'Other',
                                                'serial_number' => $a->asset->serial_number ?? 'N/A',
                                                'allocated_at' => $a->allocated_at ? $a->allocated_at->format('d M, Y') : $a->updated_at->format('d M, Y'),
                                                'system_configuration' => $a->asset->system_configuration ?? '-'
                                            ];
                                        })->values()->all();
                                    @endphp
                                    <tr style="height: 75px; border-bottom: 1px solid #f1f5f9;">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar-wrapper" style="width: 32px; height: 32px; flex-shrink: 0; display: inline-block;">
                                                    @if($user && $user->photo)
                                                        <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}" class="user-avatar-img" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; filter: grayscale(1);">
                                                    @else
                                                        <div class="user-avatar-initials" style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                                            {{ substr($user->name ?? 'U', 0, 1) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark fs-6">{{ $user->name ?? 'Unknown User' }}</span>
                                                    <span class="text-muted small" style="font-size: 11px;">{{ $dept }}</span>
                                                    <span class="badge bg-soft-danger text-danger mt-1 align-self-start" style="font-size: 9px; padding: 2px 8px; border-radius: 20px; font-weight: 700; text-transform: uppercase;">Deactivated</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-soft-danger text-danger" style="font-size: 12px; padding: 8px 12px; border-radius: 30px; font-weight: 700;">
                                                {{ $userAllocations->count() }} {{ \Illuminate\Support\Str::plural('asset', $userAllocations->count()) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark text-truncate" style="max-width: 520px;" title="{{ $assetSummary }}">
                                                {{ $assetSummary }}
                                            </div>
                                            <div class="text-muted small mt-1" style="font-size: 11px;">
                                                {{ $userAllocations->pluck('asset.type')->filter()->unique()->implode(', ') ?: 'Asset details' }}
                                            </div>
                                        </td>
                                        <td class="text-muted small">{{ $assignedDate }}</td>
                                        <td class="pe-4 text-center">
                                            <button class="btn btn-sm btn-soft-danger fw-bold"
                                                    style="border-radius: 8px;"
                                                    data-assets='@json($jsAllocations)'
                                                    data-employee="{{ $user->name ?? 'Employee' }}"
                                                    onclick="openAllocatedAssetsOffcanvas(this)">
                                                <i class="feather-corner-down-left"></i> Reclaim Assets
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="py-4">
                                                <div style="font-size: 40px;">🚫</div>
                                                <h4 class="text-muted mt-3">No Assets Held by Deactivated Employees</h4>
                                                <p class="text-muted small">Devices still allocated to a deactivated employee will be listed here so they can be reclaimed.</p>
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
        @endif
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
                        <div class="p-3 mb-3 border rounded-3" style="background: #eef4ff; border-color: #dbeafe !important;">
                            <label class="fw-semibold small mb-1">Assign all new assets to employee</label>
                            <select class="form-select" name="assign_user_id" id="batch-assign-user" style="height: 44px; border-radius: 8px;">
                                <option value="">Keep as stock inventory</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ str_replace('_', ' ', ucwords($u->role)) }})</option>
                                @endforeach
                            </select>
                            <div class="text-muted small mt-2" style="font-size: 11px;">When an employee is selected here, every asset in this popup is assigned to that employee.</div>
                        </div>
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
                                            <option value="Headphone">Headphone</option>
                                            <option value="Earphone">Earphone</option>
                                            <option value="Pendrive/Hard disk">Pendrive/Hard disk</option>
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
                        @if($isAdmin || $isTeamLeader)
                        <div class="mb-3">
                            <label class="fw-semibold mb-2">Assign Employee @if($isTeamLeader)<span class="text-danger">*</span>@endif</label>
                            <select class="form-select" name="assign_user_id" id="edit-assign-user" @if($isTeamLeader) required @endif>
                                @if($isAdmin)
                                    <option value="">Unassigned (keep in stock)</option>
                                @endif
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ str_replace('_', ' ', ucwords($u->role)) }})</option>
                                @endforeach
                            </select>
                            @if($isTeamLeader)
                                <div class="text-muted small mt-2" style="font-size: 11px;">You can reassign this asset to another member of your team.</div>
                            @endif
                        </div>
                        @endif
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
@push('modals')
    <!-- Allocated Assets Offcanvas -->
    <div class="offcanvas offcanvas-end premium-offcanvas" tabindex="-1" id="allocatedAssetsOffcanvas" aria-labelledby="allocatedAssetsOffcanvasLabel" style="width: 460px; border-left: 1px solid #e2e8f0; background: #f8fafc; z-index: 1055;">
        <div class="offcanvas-header border-bottom bg-white p-4" style="height: 70px;">
            <div class="d-flex flex-column">
                <h5 class="offcanvas-title fw-bold text-dark fs-5" id="allocatedAssetsOffcanvasLabel">Allocated Devices</h5>
                <span class="text-muted small" id="offcanvas-employee-name" style="font-size: 12px; font-weight: 500;">Employee Name</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4" id="offcanvas-body-content" style="overflow-y: auto;">
            <!-- Dynamically populated via JS -->
        </div>
    </div>
@endpush

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
        .table tr td.asset-type-col {
            padding-left: 15px !important;
        }
    </style>

    <script>
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
        let editAssetModal;
        let allocateModal;
        let manualAllocateModal;
        let returnModal;
        let allocatedAssetsOffcanvas;
        let assetRowIndex = 1;
        const allAvailableAssets = @json($availableAssets);

        document.addEventListener('DOMContentLoaded', function() {
            editAssetModal = new bootstrap.Modal(document.getElementById('editAssetModal'));
            allocateModal = new bootstrap.Modal(document.getElementById('allocateModal'));
            manualAllocateModal = new bootstrap.Modal(document.getElementById('manualAllocateModal'));
            returnModal = new bootstrap.Modal(document.getElementById('returnModal'));
            allocatedAssetsOffcanvas = new bootstrap.Offcanvas(document.getElementById('allocatedAssetsOffcanvas'));

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
                <option value="PC" ${typeVal === 'PC' ? 'selected' : ''}>PC</option>
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
                    syncBatchAssignStatus();
                });
            }

            const batchAssignSelect = document.getElementById('batch-assign-user');
            if (batchAssignSelect) {
                batchAssignSelect.addEventListener('change', syncBatchAssignStatus);
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

        function syncBatchAssignStatus() {
            const batchAssignSelect = document.getElementById('batch-assign-user');
            const shouldAllocate = batchAssignSelect && batchAssignSelect.value;

            document.querySelectorAll('#assets-container select[name*="[status]"]').forEach(select => {
                if (shouldAllocate) {
                    select.value = 'Allocated';
                } else if (select.value === 'Allocated') {
                    select.value = 'Available';
                }
            });
        }

        function switchAssetTab(tab) {
            document.querySelectorAll('.switcher-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.asset-tab-section').forEach(sec => sec.classList.add('d-none'));

            if (tab === 'inventory') {
                document.getElementById('tab-inventory-btn').classList.add('active');
                document.getElementById('section-inventory').classList.remove('d-none');
            } else if (tab === 'requests') {
                document.getElementById('tab-requests-btn').classList.add('active');
                document.getElementById('section-requests').classList.remove('d-none');
            } else if (tab === 'allocated') {
                document.getElementById('tab-allocated-btn').classList.add('active');
                document.getElementById('section-allocated').classList.remove('d-none');
            } else if (tab === 'returned') {
                document.getElementById('tab-returned-btn').classList.add('active');
                document.getElementById('section-returned').classList.remove('d-none');
            } else if (tab === 'deactivated') {
                document.getElementById('tab-deactivated-btn').classList.add('active');
                document.getElementById('section-deactivated').classList.remove('d-none');
            }
        }

        function openAllocatedAssetsOffcanvas(element, employeeName) {
            let assets = [];
            let name = employeeName || 'Employee';
            
            if (element && element.getAttribute) {
                const rawAssets = element.getAttribute('data-assets');
                assets = JSON.parse(rawAssets || '[]');
                name = element.getAttribute('data-employee') || employeeName || 'Employee';
            } else if (typeof element === 'string') {
                assets = JSON.parse(element);
            } else {
                assets = element || [];
            }
            
            document.getElementById('offcanvas-employee-name').innerText = name;
            
            let html = '';
            if (!assets || assets.length === 0) {
                html = `
                    <div class="text-center py-5">
                        <p class="text-muted small">No active allocations found for this employee.</p>
                    </div>
                `;
            } else {
                assets.forEach(asset => {
                    let iconClass = 'feather-package';
                    const typeLower = (asset.type || '').toLowerCase();
                    if (typeLower === 'pc') iconClass = 'feather-monitor';
                    else if (typeLower === 'laptop') iconClass = 'feather-tablet';
                    else if (typeLower === 'keyboard') iconClass = 'feather-type';
                    else if (typeLower === 'mouse') iconClass = 'feather-mouse-pointer';
                    else if (typeLower === 'monitor') iconClass = 'feather-tv';
                    else if (typeLower === 'charger') iconClass = 'feather-zap';

                    html += `
                        <div class="card border-0 shadow-sm mb-3 rounded-3" style="border: 1px solid #e2e8f0 !important; background: white;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-soft-primary text-primary" style="font-size: 10px; padding: 4px 8px; border-radius: 6px; font-weight: 700; text-transform: uppercase;">
                                        ${asset.type}
                                    </span>
                                    <span class="text-muted small" style="font-size: 11px;">Assigned: ${asset.allocated_at}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 32px; height: 32px; background: rgba(56, 88, 249, 0.08); color: #3858f9; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;" class="me-2">
                                        <i class="${iconClass}" style="font-size: 15px;"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-0 fs-6">${asset.name}</h6>
                                </div>
                                <p class="text-muted small mb-2" style="font-size: 11px;">SN: <b class="text-dark d-inline-block" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;">${asset.serial_number}</b></p>                                
                                <div class="mb-3">
                                    <span class="small fw-semibold text-muted text-uppercase d-block mb-1" style="font-size: 10px;">Configuration:</span>
                                    <div class="bg-light p-2 rounded-2 text-muted small" style="font-size: 11px; min-height: 40px; font-weight: 500;">
                                        ${asset.system_configuration || 'No configuration details.'}
                                    </div>
                                </div>
                                ${isAdmin ? `
                                <button type="button" class="btn btn-sm btn-soft-secondary fw-bold w-100 d-flex align-items-center justify-content-center gap-1" style="border-radius: 8px; height: 36px;" data-id="${asset.id}" data-name="${escapeHtml(asset.name)}" onclick="triggerReturnFromOffcanvas(this)">
                                    <i class="feather-corner-down-left" style="font-size: 12px;"></i> Mark Returned
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
            }
            
            document.getElementById('offcanvas-body-content').innerHTML = html;
            allocatedAssetsOffcanvas.show();
        }

        function triggerReturnFromOffcanvas(button) {
            const requestId = button.getAttribute('data-id');
            const assetName = button.getAttribute('data-name');
            allocatedAssetsOffcanvas.hide();
            setTimeout(() => {
                openReturnModal(requestId, assetName);
            }, 300);
        }

        function editAsset(asset, assignedUserId) {
            document.getElementById('edit-name').value = asset.name;
            document.getElementById('edit-type').value = asset.type;
            document.getElementById('edit-serial_number').value = asset.serial_number || '';
            document.getElementById('edit-system_configuration').value = asset.system_configuration || '';
            document.getElementById('edit-status').value = asset.status;

            const assignSelect = document.getElementById('edit-assign-user');
            if (assignSelect) {
                assignSelect.value = assignedUserId ? String(assignedUserId) : '';
            }

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
                                <td colspan="6" class="text-center py-5">
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
