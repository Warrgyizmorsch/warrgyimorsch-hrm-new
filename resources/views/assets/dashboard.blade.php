@extends('layouts.app')

@section('content')
    @php
        $s = fn ($k) => (int) ($statusCounts[$k] ?? 0);
        $inService = $statusCounts->except(['Lost', 'Retired'])->sum();
        $money = fn ($v) => '₹' . number_format((float) $v, 0);
        $statusOrder = ['Available', 'Allocated', 'Maintenance', 'Faulty', 'Lost', 'Retired'];
    @endphp
    @include('assets.partials.styles')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10" style="color:#3858f9;font-weight:700;">Asset Dashboard</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Assets</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="d-flex gap-2">
                <a href="{{ route('assets.export') }}" class="btn btn-light-brand" style="border-radius:10px;"><i class="feather-download me-1"></i> Export register</a>
                <a href="{{ route('assets.index') }}#add" class="btn btn-primary" style="border-radius:10px;background:#3858f9;border:none;"><i class="feather-plus me-1"></i> Add asset</a>
            </div>
        </div>
    </div>

    <div class="main-content pt-4 am-wrap">
        @include('assets.partials.subnav', ['current' => 'assets.dashboard'])

        {{-- KPIs --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ route('assets.index') }}" class="am-card am-kpi">
                    <span class="am-kpi-label"><i class="feather-package"></i> In service</span>
                    <span class="am-kpi-value">{{ $inService }}</span>
                    <span class="am-kpi-sub">{{ $s('Retired') }} retired</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ route('assets.index') }}?status=Available" class="am-card am-kpi">
                    <span class="am-kpi-label text-success"><i class="feather-check-circle"></i> Available</span>
                    <span class="am-kpi-value">{{ $s('Available') }}</span>
                    <span class="am-kpi-sub">ready to allocate</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ route('assets.index') }}?status=Allocated" class="am-card am-kpi">
                    <span class="am-kpi-label text-primary"><i class="feather-users"></i> Allocated</span>
                    <span class="am-kpi-value">{{ $s('Allocated') }}</span>
                    <span class="am-kpi-sub">{{ $awaitingAck->count() }} awaiting confirmation</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ route('assets.index') }}?status=Maintenance" class="am-card am-kpi">
                    <span class="am-kpi-label text-warning"><i class="feather-tool"></i> Repair / faulty</span>
                    <span class="am-kpi-value">{{ $s('Maintenance') + $s('Faulty') }}</span>
                    <span class="am-kpi-sub">{{ $s('Maintenance') }} in repair · {{ $s('Faulty') }} faulty</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ route('assets.index') }}?status=Lost" class="am-card am-kpi">
                    <span class="am-kpi-label text-danger"><i class="feather-x-octagon"></i> Lost</span>
                    <span class="am-kpi-value">{{ $s('Lost') }}</span>
                    <span class="am-kpi-sub">{{ $money($value['lost_or_retired']) }} lost / retired value</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ route('assets.incidents.index') }}" class="am-card am-kpi">
                    <span class="am-kpi-label text-danger"><i class="feather-alert-triangle"></i> Open cases</span>
                    <span class="am-kpi-value">{{ $openIncidents->count() }}</span>
                    <span class="am-kpi-sub">{{ $pendingRequests }} device request(s) pending</span>
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- Needs attention --}}
            <div class="col-lg-7">
                <div class="am-card h-100">
                    <div class="am-card-header"><h6>Needs attention</h6></div>
                    <div class="table-responsive">
                        <table class="am-table">
                            <tbody>
                            @foreach($disputed as $a)
                                <tr>
                                    <td style="width:36px;" class="pe-0"><span class="badge am-badge bg-soft-danger text-danger">Disputed</span></td>
                                    <td><a class="am-link" href="{{ route('assets.show', $a->asset_id) }}">{{ $a->asset->asset_tag ?? '' }} {{ $a->asset->name ?? '' }}</a><div class="small am-muted">{{ $a->user->name ?? '' }}: "{{ $a->acknowledgement_note }}"</div></td>
                                    <td class="small am-muted text-nowrap d-none d-sm-table-cell">{{ $a->acknowledged_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                            @foreach($openIncidents->take(6) as $i)
                                <tr>
                                    <td><span class="badge am-badge {{ $i->type === 'lost' ? 'bg-soft-danger text-danger' : 'bg-soft-warning text-warning' }}">{{ $i->type === 'lost' ? 'Lost' : ($i->status === 'in_repair' ? 'In repair' : 'Damaged') }}</span></td>
                                    <td><a class="am-link" href="{{ route('assets.show', $i->asset_id) }}">{{ $i->asset->asset_tag ?? '' }} {{ $i->asset->name ?? '' }}</a><div class="small am-muted">{{ $i->reference }} · {{ $i->holder_name ?: 'no holder' }} · {{ \Illuminate\Support\Str::limit($i->description, 70) }}</div></td>
                                    <td class="small am-muted text-nowrap d-none d-sm-table-cell">{{ $i->incident_date->isToday() ? 'Today' : $i->incident_date->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                            @foreach($awaitingAck->filter(fn ($a) => $a->allocated_at && $a->allocated_at->lt(now()->subDays(3)))->take(6) as $a)
                                <tr>
                                    <td><span class="badge am-badge bg-soft-warning text-warning">Unconfirmed</span></td>
                                    <td><a class="am-link" href="{{ route('assets.show', $a->asset_id) }}">{{ $a->asset->asset_tag ?? '' }} {{ $a->asset->name ?? '' }}</a><div class="small am-muted">{{ $a->user->name ?? '' }} hasn't confirmed receipt</div></td>
                                    <td class="small am-muted text-nowrap d-none d-sm-table-cell">{{ $a->allocated_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                            @foreach($warranty->take(6) as $w)
                                <tr>
                                    <td><span class="badge am-badge {{ $w->warranty_expiry->isPast() ? 'bg-soft-danger text-danger' : 'bg-soft-info text-info' }}">Warranty</span></td>
                                    <td><a class="am-link" href="{{ route('assets.show', $w->id) }}">{{ $w->asset_tag }} {{ $w->name }}</a><div class="small am-muted">{{ $w->warranty_expiry->isPast() ? 'Expired' : 'Expires' }} {{ $w->warranty_expiry->format('d M Y') }}{{ $w->activeAllocation?->user ? ' · with ' . $w->activeAllocation->user->name : '' }}</div></td>
                                    <td class="small am-muted text-nowrap d-none d-sm-table-cell">{{ $w->warranty_expiry->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                            @foreach($inRepair->take(4) as $r)
                                <tr>
                                    <td><span class="badge am-badge bg-soft-warning text-warning">Maintenance</span></td>
                                    <td><a class="am-link" href="{{ route('assets.show', $r->id) }}">{{ $r->asset_tag }} {{ $r->name }}</a><div class="small am-muted">In maintenance{{ $r->since ? ' since ' . \Carbon\Carbon::parse($r->since)->format('d M Y') : '' }}</div></td>
                                    <td class="small am-muted text-nowrap d-none d-sm-table-cell">{{ $r->since ? \Carbon\Carbon::parse($r->since)->diffForHumans() : '' }}</td>
                                </tr>
                            @endforeach
                            @if($disputed->isEmpty() && $openIncidents->isEmpty() && $warranty->isEmpty() && $inRepair->isEmpty() && $awaitingAck->filter(fn ($a) => $a->allocated_at && $a->allocated_at->lt(now()->subDays(3)))->isEmpty())
                                <tr><td colspan="3" class="am-empty">Nothing needs attention right now.</td></tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Money --}}
            <div class="col-lg-5">
                <div class="am-card h-100">
                    <div class="am-card-header"><h6>Value &amp; recovery</h6></div>
                    <div class="am-card-body">
                        <dl class="am-dl" style="grid-template-columns: 1fr auto;">
                            <dt>Purchase value in service</dt><dd class="text-end">{{ $money($value['in_service']) }}</dd>
                            <dt class="small">Assets with a recorded cost</dt><dd class="text-end small am-muted fw-normal">{{ $value['with_cost'] }} of {{ $value['in_service_count'] }}</dd>
                            <dt>Value lost / retired</dt><dd class="text-end">{{ $money($value['lost_or_retired']) }}</dd>
                            <dt>Damage &amp; loss cost</dt><dd class="text-end">{{ $money($value['incident_cost']) }}</dd>
                            <dt>Recovered from employees</dt><dd class="text-end text-success">{{ $money($value['recovered']) }}</dd>
                            <dt>Still to recover</dt><dd class="text-end {{ $value['outstanding'] > 0 ? 'text-danger' : '' }}">{{ $money($value['outstanding']) }}</dd>
                        </dl>
                        <hr>
                        <div class="small am-muted mb-1 fw-semibold text-uppercase" style="font-size:11px;">Last audit</div>
                        @if($lastAudit)
                            <a class="am-link" href="{{ route('assets.audits.show', $lastAudit) }}">{{ $lastAudit->title }}</a>
                            <div class="small am-muted">{{ ucfirst(str_replace('_', ' ', $lastAudit->status)) }} · {{ $lastAudit->created_at->format('d M Y') }} · {{ $lastAudit->checked_count }}/{{ $lastAudit->items_count }} checked · {{ $lastAudit->missing_count }} missing</div>
                        @else
                            <div class="small">No audit yet. <a href="{{ route('assets.audits.index') }}">Start one</a></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- By type --}}
            <div class="col-lg-7">
                <div class="am-card">
                    <div class="am-card-header"><h6>Stock by type</h6></div>
                    <div class="table-responsive">
                        <table class="am-table">
                            <thead><tr><th>Type</th>@foreach($statusOrder as $st)<th class="num">{{ $st }}</th>@endforeach<th class="num">Total</th></tr></thead>
                            <tbody>
                            @forelse($byType as $type => $counts)
                                <tr>
                                    <td class="fw-semibold">{{ $type }}</td>
                                    @foreach($statusOrder as $st)
                                        <td class="num {{ ($counts[$st] ?? 0) ? '' : 'am-muted' }}">{{ $counts[$st] ?? 0 }}</td>
                                    @endforeach
                                    <td class="num fw-bold">{{ $counts->sum() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="am-empty">No assets yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Activity --}}
            <div class="col-lg-5">
                <div class="am-card">
                    <div class="am-card-header"><h6>Recent activity</h6></div>
                    <div class="am-card-body">
                        @if($recentEvents->isEmpty())
                            <div class="am-empty">No activity yet.</div>
                        @else
                            <ul class="am-timeline">
                                @foreach($recentEvents as $e)
                                    <li style="padding-bottom:14px;">
                                        <span class="am-dot bg-soft-{{ $e->color }} text-{{ $e->color }}"><i class="{{ $e->icon }}"></i></span>
                                        <div class="small fw-semibold" style="color:#0f172a;">
                                            {{ $e->label }} ·
                                            @if($e->asset)<a class="am-link" href="{{ route('assets.show', $e->asset_id) }}">{{ $e->asset->asset_tag }}</a>@endif
                                            @if($e->to_name)<span class="am-muted fw-normal">→ {{ $e->to_name }}</span>@elseif($e->from_name)<span class="am-muted fw-normal">from {{ $e->from_name }}</span>@endif
                                        </div>
                                        <div class="small am-muted">{{ $e->created_at->diffForHumans() }} · {{ $e->performer->name ?? 'System' }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
