@extends('layouts.app')

@section('content')
    @php
        $money = fn ($v) => $v === null ? '-' : '₹' . number_format((float) $v, 2);
        $tabs = ['open_any' => 'Open', 'in_repair' => 'In repair', 'closed' => 'Closed', 'all' => 'All'];
    @endphp
    @include('assets.partials.styles')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10" style="color:#3858f9;font-weight:700;">Damage &amp; Loss</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('assets.dashboard') }}">Assets</a></li>
                <li class="breadcrumb-item active">Damage &amp; Loss</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <button type="button" class="btn btn-primary" style="border-radius:10px;background:#3858f9;border:none;" onclick="amOpenReport('damage')">
                <i class="feather-plus me-1"></i> Report damage / loss
            </button>
        </div>
    </div>

    <div class="main-content pt-4 am-wrap">
        @include('assets.partials.subnav', ['current' => 'assets.incidents.index'])

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg"><div class="am-card am-kpi"><span class="am-kpi-label">Open</span><span class="am-kpi-value">{{ $summary['open'] }}</span></div></div>
            <div class="col-6 col-lg"><div class="am-card am-kpi"><span class="am-kpi-label">In repair</span><span class="am-kpi-value">{{ $summary['in_repair'] }}</span></div></div>
            <div class="col-6 col-lg"><div class="am-card am-kpi"><span class="am-kpi-label">Total cost</span><span class="am-kpi-value" style="font-size:22px;">{{ $money($summary['cost']) }}</span></div></div>
            <div class="col-6 col-lg"><div class="am-card am-kpi"><span class="am-kpi-label text-success">Recovered</span><span class="am-kpi-value" style="font-size:22px;">{{ $money($summary['recovered']) }}</span></div></div>
            <div class="col-12 col-lg"><a href="{{ route('assets.incidents.index', ['status' => 'all', 'recovery' => 'pending']) }}" class="am-card am-kpi"><span class="am-kpi-label text-danger">Still to recover</span><span class="am-kpi-value" style="font-size:22px;">{{ $money($summary['outstanding']) }}</span></a></div>
        </div>

        <div class="am-card">
            <div class="am-card-header">
                <ul class="nav nav-pills gap-1">
                    @foreach($tabs as $key => $label)
                        <li class="nav-item"><a class="nav-link btn-sm {{ $status === $key ? 'active' : '' }}" href="{{ route('assets.incidents.index', array_filter(['status' => $key === 'open_any' ? null : $key, 'type' => $filters['type'] ?? null])) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
                <form method="GET" class="d-flex flex-wrap gap-2" role="search">
                    @if($status !== 'open_any')<input type="hidden" name="status" value="{{ $status }}">@endif
                    <select name="type" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()" aria-label="Type">
                        <option value="">All types</option>
                        @foreach(\App\Models\AssetIncident::TYPES as $k => $l)<option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $l }}</option>@endforeach
                    </select>
                    <select name="recovery" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()" aria-label="Recovery">
                        <option value="">Any recovery</option>
                        @foreach(\App\Models\AssetIncident::RECOVERY_STATUSES as $k => $l)<option value="{{ $k }}" @selected(($filters['recovery'] ?? '') === $k)>{{ $l }}</option>@endforeach
                    </select>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" style="width:200px;" placeholder="Tag, asset or employee">
                </form>
            </div>
            <div class="table-responsive">
                <table class="am-table">
                    <thead>
                        <tr><th>Case</th><th>Asset</th><th>What happened</th><th>Employee</th><th class="num">Cost</th><th>Recovery</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($incidents as $incident)
                        @php
                            $btn = 'data-ref="' . e($incident->reference) . '" data-type="' . e($incident->type) . '" data-cost="' . e($incident->cost) . '" data-recovery-status="' . e($incident->recovery_status) . '" data-recovery-amount="' . e($incident->recovery_amount) . '" data-recovered-amount="' . e($incident->recovered_amount) . '"';
                        @endphp
                        <tr>
                            <td class="text-nowrap"><span class="am-tag">{{ $incident->reference }}</span><div class="small am-muted mt-1">{{ $incident->incident_date->format('d M Y') }}</div></td>
                            <td>
                                @if($incident->asset)
                                    <a class="am-link" href="{{ route('assets.show', $incident->asset_id) }}">{{ $incident->asset->name }}</a>
                                    <div class="small am-muted">{{ $incident->asset->asset_tag }} · @include('assets.partials.status-badge', ['status' => $incident->asset->status])</div>
                                @endif
                            </td>
                            <td style="min-width:220px;">
                                <span class="badge am-badge {{ $incident->type === 'lost' ? 'bg-soft-danger text-danger' : 'bg-soft-warning text-warning' }} mb-1">{{ \App\Models\AssetIncident::TYPES[$incident->type] }}</span>
                                <div class="small">{{ \Illuminate\Support\Str::limit($incident->description, 120) }}</div>
                                <div class="small am-muted">by {{ $incident->reporter->name ?? 'system' }}@if($incident->attachment_url) · <a href="{{ $incident->attachment_url }}" target="_blank" rel="noopener">attachment</a>@endif</div>
                            </td>
                            <td class="small">{{ $incident->holder_name ?: '-' }}</td>
                            <td class="num">{{ $money($incident->cost) }}</td>
                            <td class="small text-nowrap">
                                {{ \App\Models\AssetIncident::RECOVERY_STATUSES[$incident->recovery_status] ?? '-' }}
                                @if($incident->recovery_amount > 0)<div class="am-muted">{{ $money($incident->recovered_amount) }} of {{ $money($incident->recovery_amount) }}</div>@endif
                            </td>
                            <td class="small">
                                @if($incident->isOpen())
                                    <span class="badge am-badge {{ $incident->status === 'in_repair' ? 'bg-soft-warning text-warning' : 'bg-soft-danger text-danger' }}">{{ \App\Models\AssetIncident::STATUSES[$incident->status] }}</span>
                                @else
                                    <span class="badge am-badge bg-soft-success text-success">Closed</span>
                                    <div class="am-muted mt-1">{{ $incident->resolution_label }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-1">
                                @if($incident->type === 'damage' && $incident->status === 'open')
                                    <button type="button" class="btn btn-sm btn-soft-warning" data-url="{{ route('assets.incidents.repair', $incident) }}" data-ref="{{ $incident->reference }}" onclick="amOpenRepair(this)" title="Send for repair"><i class="feather-tool"></i></button>
                                @endif
                                @if($incident->isOpen())
                                    <button type="button" class="btn btn-sm btn-primary" style="background:#3858f9;border:none;" data-url="{{ route('assets.incidents.resolve', $incident) }}" {!! $btn !!} onclick="amOpenResolve(this)">Close</button>
                                @endif
                                <button type="button" class="btn btn-sm btn-light-brand" data-url="{{ route('assets.incidents.recovery', $incident) }}" {!! $btn !!} onclick="amOpenRecovery(this)" title="Cost &amp; recovery">₹</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="am-empty">No cases here.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($incidents->hasPages())
                <div class="am-card-body border-top">{{ $incidents->links() }}</div>
            @endif
        </div>
    </div>

    @include('assets.partials.report-incident-modal', ['action' => route('assets.incidents.store'), 'assetChoices' => $reportableAssets, 'adminFields' => true])
    @include('assets.partials.incident-modals')
@endsection
