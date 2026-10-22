@extends('layouts.app')

@section('content')
    @include('assets.partials.styles')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10" style="color:#3858f9;font-weight:700;">Asset Audits</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('assets.dashboard') }}">Assets</a></li>
                <li class="breadcrumb-item active">Audits</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <button type="button" class="btn btn-primary" style="border-radius:10px;background:#3858f9;border:none;" data-bs-toggle="modal" data-bs-target="#amStartAudit">
                <i class="feather-play me-1"></i> Start audit
            </button>
        </div>
    </div>

    <div class="main-content pt-4 am-wrap">
        @include('assets.partials.subnav', ['current' => 'assets.audits.index'])

        <div class="am-card">
            <div class="am-card-header">
                <h6>Physical verification</h6>
                <span class="small am-muted">An audit takes a snapshot of where every asset should be. Walk the office and mark each one.</span>
            </div>
            <div class="table-responsive">
                <table class="am-table">
                    <thead><tr><th>Audit</th><th>Status</th><th>Progress</th><th class="num">Missing</th><th class="num">Damaged</th><th class="num">Wrong holder</th><th>Started</th><th>Closed</th></tr></thead>
                    <tbody>
                    @forelse($audits as $audit)
                        @php $pct = $audit->items_count ? round($audit->checked_count / $audit->items_count * 100) : 0; @endphp
                        <tr>
                            <td><a class="am-link" href="{{ route('assets.audits.show', $audit) }}">{{ $audit->title }}</a></td>
                            <td>
                                @php $sc = ['in_progress' => 'primary', 'completed' => 'success', 'cancelled' => 'secondary'][$audit->status] ?? 'secondary'; @endphp
                                <span class="badge am-badge bg-soft-{{ $sc }} text-{{ $sc }}">{{ ucfirst(str_replace('_', ' ', $audit->status)) }}</span>
                            </td>
                            <td style="min-width:160px;">
                                <div class="progress" style="height:6px;" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Checked"><div class="progress-bar" style="width:{{ $pct }}%;background:#3858f9;"></div></div>
                                <div class="small am-muted mt-1">{{ $audit->checked_count }} / {{ $audit->items_count }} checked</div>
                            </td>
                            <td class="num {{ $audit->missing_count ? 'text-danger fw-bold' : 'am-muted' }}">{{ $audit->missing_count }}</td>
                            <td class="num {{ $audit->damaged_count ? 'text-warning fw-bold' : 'am-muted' }}">{{ $audit->damaged_count }}</td>
                            <td class="num {{ $audit->wrong_holder_count ? 'fw-bold' : 'am-muted' }}">{{ $audit->wrong_holder_count }}</td>
                            <td class="small">{{ $audit->created_at->format('d M Y') }}<div class="am-muted">{{ $audit->starter->name ?? '' }}</div></td>
                            <td class="small">{{ $audit->completed_at?->format('d M Y') ?? '-' }}<div class="am-muted">{{ $audit->completer->name ?? '' }}</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="am-empty">No audits yet. Start one to verify every asset is where the system says it is.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($audits->hasPages())
                <div class="am-card-body border-top">{{ $audits->links() }}</div>
            @endif
        </div>
    </div>

    @push('modals')
    <div class="modal fade" id="amStartAudit" tabindex="-1" aria-labelledby="amStartAuditTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header"><h5 class="modal-title" id="amStartAuditTitle">Start an audit</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <form method="POST" action="{{ route('assets.audits.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="fw-semibold small mb-1" for="amAuditTitle">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="amAuditTitle" required maxlength="150" value="Asset audit - {{ now()->format('M Y') }}">
                        </div>
                        <div class="mb-3">
                            <span class="fw-semibold small mb-1 d-block">Asset types <span class="am-muted fw-normal">(leave all unticked for everything)</span></span>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($types as $t)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="types[]" value="{{ $t }}" id="amAuditType{{ $loop->index }}">
                                        <label class="form-check-label small" for="amAuditType{{ $loop->index }}">{{ $t }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="fw-semibold small mb-1" for="amAuditNotes">Notes</label>
                            <textarea class="form-control" name="notes" id="amAuditNotes" rows="2" maxlength="2000"></textarea>
                        </div>
                        <p class="small am-muted mt-3 mb-0">Lost and retired assets are left out.</p>
                    </div>
                    <div class="modal-footer border-top-0"><button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-4" style="background:#3858f9;border:none;">Start</button></div>
                </form>
            </div>
        </div>
    </div>
    @endpush
@endsection
