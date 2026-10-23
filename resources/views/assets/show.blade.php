@extends('layouts.app')

@section('content')
    @php
        $alloc = $asset->activeAllocation;
        $holder = $alloc?->user;
        $openIncidents = $asset->incidents->filter->isOpen();
        $money = fn ($v) => $v === null ? '-' : '₹' . number_format((float) $v, 2);
        $date = fn ($d) => $d ? $d->format('d M Y') : '-';
        $isTrashed = $asset->trashed();
        $canAct = $isAdmin && !$isTrashed;
        $warrantyColors = ['active' => 'success', 'expiring' => 'warning', 'expired' => 'danger'];
    @endphp
    @include('assets.partials.styles')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10" style="color:#3858f9;font-weight:700;">{{ $asset->asset_tag ?: 'Asset' }}</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                <li class="breadcrumb-item active">{{ $asset->name }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(!$isTrashed && $asset->status === 'Allocated')
                    <button type="button" class="btn btn-primary" style="background:#3858f9;border:none;border-radius:10px;" data-bs-toggle="modal" data-bs-target="#amTransferModal">
                        <i class="feather-repeat me-1"></i> Transfer
                    </button>
                @endif
                @if($canAct)
                    @if($asset->status === 'Available')
                        <button type="button" class="btn btn-success" style="border-radius:10px;" data-bs-toggle="modal" data-bs-target="#amAllocateModal">
                            <i class="feather-user-plus me-1"></i> Allocate
                        </button>
                    @endif
                    @if($asset->status === 'Allocated')
                        <button type="button" class="btn btn-soft-secondary" style="border-radius:10px;" data-bs-toggle="modal" data-bs-target="#amReturnModal">
                            <i class="feather-corner-down-left me-1"></i> Return
                        </button>
                    @endif
                    @if(in_array($asset->status, ['Maintenance', 'Faulty'], true))
                        <form method="POST" action="{{ route('assets.return', $asset->id) }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="condition" value="Good">
                            <input type="hidden" name="note" value="Back in stock">
                            <button type="submit" class="btn btn-success" style="border-radius:10px;"><i class="feather-check me-1"></i> Mark Available</button>
                        </form>
                    @endif
                    @if($asset->isClosed() && $openIncidents->isEmpty())
                        <button type="button" class="btn btn-success" style="border-radius:10px;" data-bs-toggle="modal" data-bs-target="#amReactivateModal">
                            <i class="feather-rotate-ccw me-1"></i> Back in service
                        </button>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-light-brand dropdown-toggle" style="border-radius:10px;" type="button" data-bs-toggle="dropdown" aria-expanded="false">More</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#amEditModal"><i class="feather-edit-3 me-2"></i>Edit details</a></li>
                            @unless($asset->isClosed())
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="amOpenReport('damage', {{ $asset->id }}, @js($asset->display_name))"><i class="feather-alert-triangle me-2"></i>Report damage</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="amOpenReport('lost', {{ $asset->id }}, @js($asset->display_name))"><i class="feather-x-octagon me-2"></i>Report lost / stolen</a></li>
                            @endunless
                            @if(!$alloc && $asset->status !== 'Retired')
                                <li><a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#amRetireModal"><i class="feather-archive me-2"></i>Retire / dispose</a></li>
                            @endif
                            @unless($alloc)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('assets.destroy', $asset->id) }}" onsubmit="return deleteData(event)">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="feather-trash-2 me-2"></i>Delete record</button>
                                    </form>
                                </li>
                            @endunless
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="main-content pt-4 am-wrap">
        @if($isAdmin)
            @include('assets.partials.subnav', ['current' => 'assets.index'])
        @endif

        @if($isTrashed)
            <div class="alert alert-secondary">This asset record was deleted on {{ $asset->deleted_at->format('d M Y') }}. History is kept for reference.</div>
        @endif
        @if($alloc && $alloc->isDisputed())
            <div class="alert alert-warning d-flex gap-2 align-items-start">
                <i class="feather-alert-circle mt-1"></i>
                <div><b>{{ $holder->name ?? 'The holder' }} disputed this handover</b> on {{ $alloc->acknowledged_at?->format('d M Y') }}: "{{ $alloc->acknowledgement_note }}". Check with them, then return or transfer the asset if needed.</div>
            </div>
        @endif

        <div class="row g-4">
            {{-- Left: identity, holder, register details --}}
            <div class="col-lg-4">
                <div class="am-card mb-4">
                    <div class="am-card-body">
                        @if($asset->image_url)
                            <a href="{{ $asset->image_url }}" target="_blank" rel="noopener">
                                <img src="{{ $asset->image_url }}" alt="{{ $asset->name }}" style="width:100%;height:190px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;" class="mb-3">
                            </a>
                        @endif
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                            <h4 class="fw-bold mb-0" style="font-size:20px;color:#0f172a;">{{ $asset->name }}</h4>
                            @include('assets.partials.status-badge', ['status' => $asset->status])
                        </div>
                        <div class="am-muted small mb-3">{{ $asset->type }}{{ $asset->brand ? ' · ' . $asset->brand : '' }}{{ $asset->model ? ' ' . $asset->model : '' }}</div>

                        <div class="p-3 rounded-3 mb-0" style="background:#f8fafc;border:1px solid #eef2f7;">
                            <div class="small am-muted fw-semibold text-uppercase mb-2" style="font-size:11px;">Current holder</div>
                            @if($holder)
                                <div class="fw-bold">{{ $holder->name }}</div>
                                <div class="small am-muted">{{ $holder->employee->departmentRef->name ?? '' }}</div>
                                <div class="small mt-2">Since {{ $date($alloc->allocated_at) }}@if($alloc->allocatedBy) · by {{ $alloc->allocatedBy->name }}@endif</div>
                                <div class="mt-2">
                                    @if($alloc->isAwaitingAcknowledgement())
                                        <span class="badge am-badge bg-soft-warning text-warning">Awaiting confirmation</span>
                                    @elseif($alloc->isDisputed())
                                        <span class="badge am-badge bg-soft-danger text-danger">Disputed</span>
                                    @else
                                        <span class="badge am-badge bg-soft-success text-success">Receipt confirmed</span>
                                    @endif
                                </div>
                            @else
                                <div class="am-muted small">{{ $asset->status === 'Lost' ? 'Lost - not with anyone' : 'In stock - not allocated' }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="am-card mb-4">
                    <div class="am-card-header"><h6>Details</h6></div>
                    <div class="am-card-body">
                        <dl class="am-dl">
                            <dt>Asset tag</dt><dd><span class="am-tag">{{ $asset->asset_tag ?: '-' }}</span></dd>
                            <dt>Serial no.</dt><dd>{{ $asset->serial_number ?: '-' }}</dd>
                            <dt>Condition</dt><dd>{{ $asset->condition ?: '-' }}</dd>
                            <dt>Location</dt><dd>{{ $asset->location ?: '-' }}</dd>
                            <dt>Added</dt><dd>{{ $date($asset->created_at) }}</dd>
                        </dl>
                        @if($asset->system_configuration)
                            <div class="small am-muted fw-semibold text-uppercase mt-3 mb-1" style="font-size:11px;">Configuration</div>
                            <div class="small p-2 rounded-2" style="background:#f8fafc;white-space:pre-line;line-height:1.5;">{{ $asset->system_configuration }}</div>
                        @endif
                        @if($asset->notes)
                            <div class="small am-muted fw-semibold text-uppercase mt-3 mb-1" style="font-size:11px;">Notes</div>
                            <div class="small" style="white-space:pre-line;">{{ $asset->notes }}</div>
                        @endif
                    </div>
                </div>

                <div class="am-card">
                    <div class="am-card-header">
                        <h6>Purchase &amp; warranty</h6>
                        @if($asset->warranty_state !== 'none')
                            @php $wc = $warrantyColors[$asset->warranty_state]; @endphp
                            <span class="badge am-badge bg-soft-{{ $wc }} text-{{ $wc }}">{{ ['active' => 'Under warranty', 'expiring' => 'Expiring soon', 'expired' => 'Warranty expired'][$asset->warranty_state] }}</span>
                        @endif
                    </div>
                    <div class="am-card-body">
                        <dl class="am-dl">
                            <dt>Purchase date</dt><dd>{{ $date($asset->purchase_date) }}</dd>
                            <dt>Cost</dt><dd>{{ $money($asset->purchase_cost) }}</dd>
                            <dt>Vendor</dt><dd>{{ $asset->vendor ?: '-' }}</dd>
                            <dt>Invoice no.</dt><dd>{{ $asset->invoice_number ?: '-' }}</dd>
                            <dt>Warranty until</dt><dd>{{ $date($asset->warranty_expiry) }}</dd>
                        </dl>
                        @if($canAct && !$asset->purchase_date && !$asset->purchase_cost)
                            <a href="javascript:void(0)" class="small" data-bs-toggle="modal" data-bs-target="#amEditModal">+ Add purchase details</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: open cases + history --}}
            <div class="col-lg-8">
                @foreach($openIncidents as $incident)
                    <div class="am-card mb-4" style="border-color:{{ $incident->type === 'lost' ? '#fecaca' : '#fde68a' }};">
                        <div class="am-card-body d-flex flex-wrap gap-3 align-items-start justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="am-tag">{{ $incident->reference }}</span>
                                    <span class="badge am-badge {{ $incident->type === 'lost' ? 'bg-soft-danger text-danger' : 'bg-soft-warning text-warning' }}">{{ \App\Models\AssetIncident::TYPES[$incident->type] }}</span>
                                    <span class="badge am-badge bg-soft-secondary text-secondary">{{ \App\Models\AssetIncident::STATUSES[$incident->status] }}</span>
                                </div>
                                <div class="fw-semibold">{{ $incident->description }}</div>
                                <div class="small am-muted mt-1">
                                    {{ $date($incident->incident_date) }}@if($incident->holder_name) · with {{ $incident->holder_name }}@endif · reported by {{ $incident->reporter->name ?? 'system' }}
                                    @if($incident->attachment_url) · <a href="{{ $incident->attachment_url }}" target="_blank" rel="noopener">attachment</a>@endif
                                </div>
                            </div>
                            @if($canAct)
                                @php $incBtn = ['data-ref' => $incident->reference, 'data-type' => $incident->type, 'data-cost' => $incident->cost, 'data-recovery-status' => $incident->recovery_status, 'data-recovery-amount' => $incident->recovery_amount, 'data-recovered-amount' => $incident->recovered_amount]; @endphp
                                <div class="d-flex flex-wrap gap-2">
                                    @if($incident->type === 'damage' && $incident->status === 'open')
                                        <button type="button" class="btn btn-sm btn-soft-warning" data-url="{{ route('assets.incidents.repair', $incident) }}" data-ref="{{ $incident->reference }}" onclick="amOpenRepair(this)"><i class="feather-tool me-1"></i>Send for repair</button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-primary" style="background:#3858f9;border:none;" data-url="{{ route('assets.incidents.resolve', $incident) }}" @foreach($incBtn as $k => $v) {{ $k }}="{{ $v }}" @endforeach onclick="amOpenResolve(this)"><i class="feather-check me-1"></i>Close case</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="am-card">
                    <div class="am-card-header">
                        <ul class="nav nav-pills gap-1" role="tablist">
                            <li class="nav-item"><button class="nav-link active btn-sm" data-bs-toggle="pill" data-bs-target="#amTabTimeline" type="button" role="tab">Timeline</button></li>
                            <li class="nav-item"><button class="nav-link btn-sm" data-bs-toggle="pill" data-bs-target="#amTabHolders" type="button" role="tab">Holders ({{ $asset->allocations->count() }})</button></li>
                            <li class="nav-item"><button class="nav-link btn-sm" data-bs-toggle="pill" data-bs-target="#amTabIncidents" type="button" role="tab">Damage &amp; loss ({{ $asset->incidents->count() }})</button></li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane fade show active am-card-body" id="amTabTimeline" role="tabpanel">
                            @if($events->isEmpty())
                                <div class="am-empty">No history recorded yet.</div>
                            @else
                                <ul class="am-timeline">
                                    @foreach($events as $event)
                                        <li>
                                            <span class="am-dot bg-soft-{{ $event->color }} text-{{ $event->color }}"><i class="{{ $event->icon }}"></i></span>
                                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                                <div class="fw-semibold" style="color:#0f172a;">
                                                    {{ $event->label }}
                                                    @if($event->from_name && $event->to_name)
                                                        <span class="am-muted fw-normal">- {{ $event->from_name }} → {{ $event->to_name }}</span>
                                                    @elseif($event->to_name)
                                                        <span class="am-muted fw-normal">- to {{ $event->to_name }}</span>
                                                    @elseif($event->from_name)
                                                        <span class="am-muted fw-normal">- from {{ $event->from_name }}</span>
                                                    @endif
                                                    @if($event->from_status && $event->to_status && $event->from_status !== $event->to_status)
                                                        <span class="am-muted fw-normal small">({{ $event->from_status }} → {{ $event->to_status }})</span>
                                                    @endif
                                                </div>
                                                <div class="small am-muted" title="{{ $event->created_at->format('d M Y, h:i A') }}">{{ $event->created_at->format('d M Y, h:i A') }}</div>
                                            </div>
                                            @if($event->notes)
                                                <div class="small mt-1" style="color:#334155;">{{ $event->notes }}</div>
                                            @endif
                                            @if($event->event === 'details_updated' && !empty($event->meta['changes']))
                                                <div class="small am-muted mt-1">
                                                    @foreach($event->meta['changes'] as $field => $change)
                                                        @continue($field === 'image_path' || $field === 'system_configuration' || $field === 'notes')
                                                        <div>{{ ucfirst(str_replace('_', ' ', $field)) }}: <s>{{ $change['from'] ?? '-' }}</s> → {{ $change['to'] ?? '-' }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="small am-muted mt-1">by {{ $event->performer->name ?? 'System' }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="amTabHolders" role="tabpanel">
                            <div class="table-responsive">
                                <table class="am-table">
                                    <thead><tr><th>Employee</th><th>From</th><th>To</th><th>Handed over</th><th>Returned</th><th>Receipt</th></tr></thead>
                                    <tbody>
                                    @forelse($asset->allocations as $row)
                                        <tr>
                                            <td><div class="fw-semibold">{{ $row->user->name ?? 'Deleted user' }}</div><div class="small am-muted text-truncate" style="max-width:220px;" title="{{ $row->reason }}">{{ $row->reason }}</div></td>
                                            <td class="text-nowrap">{{ $date($row->allocated_at) }}</td>
                                            <td class="text-nowrap">
                                                @if($row->status === 'Allocated')<span class="badge am-badge bg-soft-primary text-primary">Current</span>
                                                @elseif($row->status === 'Lost')<span class="badge am-badge bg-soft-danger text-danger">Lost {{ $date($row->returned_at) }}</span>
                                                @else{{ $date($row->returned_at) }}@endif
                                            </td>
                                            <td class="small">{{ $row->allocatedBy->name ?? '-' }}@if($row->condition_on_allocation)<div class="am-muted">{{ $row->condition_on_allocation }}</div>@endif</td>
                                            <td class="small">{{ $row->returnedBy->name ?? '-' }}@if($row->condition_on_return)<div class="am-muted">{{ $row->condition_on_return }}</div>@endif @if($row->return_note)<div class="am-muted">{{ $row->return_note }}</div>@endif</td>
                                            <td class="small">
                                                @switch($row->acknowledgement_status)
                                                    @case('pending') <span class="text-warning fw-semibold">Pending</span> @break
                                                    @case('disputed') <span class="text-danger fw-semibold">Disputed</span><div class="am-muted">{{ $row->acknowledgement_note }}</div> @break
                                                    @case('accepted') <span class="text-success fw-semibold">Confirmed</span><div class="am-muted">{{ $row->acknowledged_at?->format('d M Y') }}</div> @break
                                                    @default <span class="am-muted">-</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="am-empty">Never allocated.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="amTabIncidents" role="tabpanel">
                            <div class="table-responsive">
                                <table class="am-table">
                                    <thead><tr><th>Case</th><th>What happened</th><th>Holder</th><th class="num">Cost</th><th>Recovery</th><th>Outcome</th>@if($canAct)<th></th>@endif</tr></thead>
                                    <tbody>
                                    @forelse($asset->incidents as $incident)
                                        <tr>
                                            <td class="text-nowrap"><span class="am-tag">{{ $incident->reference }}</span><div class="small am-muted mt-1">{{ $date($incident->incident_date) }}</div></td>
                                            <td style="min-width:200px;"><span class="badge am-badge {{ $incident->type === 'lost' ? 'bg-soft-danger text-danger' : 'bg-soft-warning text-warning' }} mb-1">{{ \App\Models\AssetIncident::TYPES[$incident->type] }}</span><div class="small">{{ $incident->description }}</div></td>
                                            <td class="small">{{ $incident->holder_name ?: '-' }}</td>
                                            <td class="num">{{ $money($incident->cost) }}</td>
                                            <td class="small text-nowrap">
                                                {{ \App\Models\AssetIncident::RECOVERY_STATUSES[$incident->recovery_status] ?? '-' }}
                                                @if($incident->recovery_amount > 0)<div class="am-muted">{{ $money($incident->recovered_amount) }} / {{ $money($incident->recovery_amount) }}</div>@endif
                                            </td>
                                            <td class="small">{{ $incident->isOpen() ? \App\Models\AssetIncident::STATUSES[$incident->status] : $incident->resolution_label }}@if($incident->resolution_notes)<div class="am-muted">{{ $incident->resolution_notes }}</div>@endif</td>
                                            @if($canAct)
                                                <td><button type="button" class="btn btn-sm btn-light-brand" data-url="{{ route('assets.incidents.recovery', $incident) }}" data-ref="{{ $incident->reference }}" data-cost="{{ $incident->cost }}" data-recovery-status="{{ $incident->recovery_status }}" data-recovery-amount="{{ $incident->recovery_amount }}" data-recovered-amount="{{ $incident->recovered_amount }}" onclick="amOpenRecovery(this)" title="Cost & recovery">₹</button></td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="am-empty">No damage or loss recorded.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php $otherUsers = $users->where('id', '!=', $holder?->id); @endphp

    @push('modals')
    {{-- Transfer --}}
    @if(!$isTrashed && $asset->status === 'Allocated')
    <div class="modal fade" id="amTransferModal" tabindex="-1" aria-labelledby="amTransferTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header"><h5 class="modal-title" id="amTransferTitle">Transfer to another employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <form method="POST" action="{{ route('assets.transfer', $asset->id) }}">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="small am-muted">From <b>{{ $holder->name ?? '-' }}</b>. Their handover closes now; the new holder is asked to confirm receipt.</p>
                        <div class="mb-3">
                            <label class="fw-semibold small mb-1" for="amTransferTo">New holder <span class="text-danger">*</span></label>
                            <select class="form-select" name="to_user_id" id="amTransferTo" required>
                                <option value="">-- Choose employee --</option>
                                @foreach($otherUsers as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold small mb-1" for="amTransferCond">Condition at handover</label>
                            <select class="form-select" name="condition" id="amTransferCond">
                                @foreach(\App\Models\Asset::CONDITIONS as $c)<option value="{{ $c }}" @selected($c === $asset->condition)>{{ $c }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="fw-semibold small mb-1" for="amTransferReason">Reason <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="reason" id="amTransferReason" maxlength="500" required placeholder="e.g. Moved to design team">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0"><button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-4" style="background:#3858f9;border:none;">Transfer</button></div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($canAct)
    {{-- Allocate --}}
    <div class="modal fade" id="amAllocateModal" tabindex="-1" aria-labelledby="amAllocateTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header"><h5 class="modal-title" id="amAllocateTitle">Allocate {{ $asset->asset_tag }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <form method="POST" action="{{ route('assets.allocateManual', $asset->id) }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="fw-semibold small mb-1" for="amAllocUser">Employee <span class="text-danger">*</span></label>
                            <select class="form-select" name="user_id" id="amAllocUser" required>
                                <option value="">-- Choose employee --</option>
                                @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold small mb-1" for="amAllocCond">Condition at handover</label>
                            <select class="form-select" name="condition" id="amAllocCond">
                                @foreach(\App\Models\Asset::CONDITIONS as $c)<option value="{{ $c }}" @selected($c === $asset->condition)>{{ $c }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="fw-semibold small mb-1" for="amAllocNote">Note</label>
                            <input type="text" class="form-control" name="note" id="amAllocNote" maxlength="500" placeholder="e.g. New joiner kit">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0"><button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success px-4">Allocate</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Return --}}
    <div class="modal fade" id="amReturnModal" tabindex="-1" aria-labelledby="amReturnTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header"><h5 class="modal-title" id="amReturnTitle">Take back from {{ $holder->name ?? 'holder' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <form method="POST" action="{{ route('assets.return', $asset->id) }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="fw-semibold small mb-1" for="amReturnCond">Condition on return <span class="text-danger">*</span></label>
                            <select class="form-select" name="condition" id="amReturnCond" required>
                                <option value="Good">Good / working - back to Available</option>
                                <option value="Maintenance">Needs repair - to Maintenance</option>
                                <option value="Faulty">Broken - to Faulty</option>
                            </select>
                        </div>
                        <div>
                            <label class="fw-semibold small mb-1" for="amReturnNote">Note</label>
                            <input type="text" class="form-control" name="note" id="amReturnNote" maxlength="500" placeholder="e.g. Resigned, last day 30 Oct">
                        </div>
                        <p class="small am-muted mt-3 mb-0">If it's damaged by the employee and you want to track cost, use <b>More → Report damage</b> instead.</p>
                    </div>
                    <div class="modal-footer border-top-0"><button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-4" style="background:#3858f9;border:none;">Confirm return</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Retire --}}
    <div class="modal fade" id="amRetireModal" tabindex="-1" aria-labelledby="amRetireTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header"><h5 class="modal-title" id="amRetireTitle">Retire {{ $asset->asset_tag }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <form method="POST" action="{{ route('assets.retire', $asset->id) }}">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="small am-muted">Retired assets leave active stock but stay in the register with their history.</p>
                        <label class="fw-semibold small mb-1" for="amRetireReason">Reason <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="reason" id="amRetireReason" maxlength="500" required placeholder="e.g. End of life, sold, e-waste">
                    </div>
                    <div class="modal-footer border-top-0"><button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark px-4">Retire</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reactivate --}}
    <div class="modal fade" id="amReactivateModal" tabindex="-1" aria-labelledby="amReactivateTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header"><h5 class="modal-title" id="amReactivateTitle">Put back in service</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <form method="POST" action="{{ route('assets.reactivate', $asset->id) }}">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="small am-muted">The asset returns to stock as <b>Available</b>.</p>
                        <label class="fw-semibold small mb-1" for="amReactNote">Note</label>
                        <input type="text" class="form-control" name="note" id="amReactNote" maxlength="500">
                    </div>
                    <div class="modal-footer border-top-0"><button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success px-4">Confirm</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit details --}}
    <div class="modal fade" id="amEditModal" tabindex="-1" aria-labelledby="amEditTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content premium-modal">
                <div class="modal-header"><h5 class="modal-title" id="amEditTitle">Edit {{ $asset->asset_tag }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <form method="POST" action="{{ route('assets.details.update', $asset->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4" style="max-height:65vh;overflow-y:auto;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-semibold small mb-1" for="amEdName">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="amEdName" value="{{ old('name', $asset->name) }}" required maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold small mb-1" for="amEdType">Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" id="amEdType" required>
                                    @foreach(collect(\App\Models\Asset::TYPES)->push($asset->type)->unique() as $t)<option value="{{ $t }}" @selected($t === $asset->type)>{{ $t }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold small mb-1" for="amEdSerial">Serial number</label>
                                <input type="text" class="form-control" name="serial_number" id="amEdSerial" value="{{ old('serial_number', $asset->serial_number) }}" maxlength="150">
                            </div>
                            @include('assets.partials.register-fields', ['asset' => $asset, 'name' => fn ($f) => $f, 'idp' => 'amEd'])
                            <div class="col-12">
                                <label class="fw-semibold small mb-1" for="amEdConfig">Configuration</label>
                                <textarea class="form-control" name="system_configuration" id="amEdConfig" rows="3">{{ old('system_configuration', $asset->system_configuration) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="fw-semibold small mb-1" for="amEdNotes">Notes</label>
                                <textarea class="form-control" name="notes" id="amEdNotes" rows="2" maxlength="2000">{{ old('notes', $asset->notes) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="fw-semibold small mb-1" for="amEdImage">Photo <span class="am-muted fw-normal">(JPG/PNG/WebP, max 2 MB)</span></label>
                                <input type="file" class="form-control" name="image" id="amEdImage" accept="image/jpeg,image/png,image/webp">
                                @if($asset->image_path)
                                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="amEdRemoveImg"><label class="form-check-label small" for="amEdRemoveImg">Remove current photo</label></div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0"><button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-4" style="background:#3858f9;border:none;">Save</button></div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endpush

    @if($canAct)
        @include('assets.partials.report-incident-modal', ['action' => route('assets.incidents.store'), 'assetChoices' => null, 'adminFields' => true])
        @include('assets.partials.incident-modals')
    @endif
@endsection
