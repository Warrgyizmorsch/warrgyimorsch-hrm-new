@extends('layouts.app')

@section('content')
    @php
        $open = $audit->isInProgress();
        $total = $counts->sum();
        $checked = $total - (int) ($counts['pending'] ?? 0);
        $resultColors = ['pending' => 'secondary', 'found' => 'success', 'wrong_holder' => 'info', 'damaged' => 'warning', 'missing' => 'danger'];
    @endphp
    @include('assets.partials.styles')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10" style="color:#3858f9;font-weight:700;">{{ $audit->title }}</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('assets.audits.index') }}">Audits</a></li>
                <li class="breadcrumb-item active">{{ $audit->title }}</li>
            </ul>
        </div>
        @if($open)
            <div class="page-header-right ms-auto d-flex gap-2">
                <form method="POST" action="{{ route('assets.audits.cancel', $audit) }}" onsubmit="return confirm('Cancel this audit? Results are kept but nothing is applied.');">
                    @csrf
                    <button type="submit" class="btn btn-light-brand" style="border-radius:10px;">Cancel audit</button>
                </form>
                <button type="button" class="btn btn-success" style="border-radius:10px;" data-bs-toggle="modal" data-bs-target="#amCompleteAudit"><i class="feather-check me-1"></i> Complete</button>
            </div>
        @endif
    </div>

    <div class="main-content pt-4 am-wrap">
        @include('assets.partials.subnav', ['current' => 'assets.audits.index'])

        <div class="am-card mb-4">
            <div class="am-card-body d-flex flex-wrap gap-4 align-items-center">
                <div>
                    <div class="small am-muted">Status</div>
                    <div class="fw-bold">{{ ucfirst(str_replace('_', ' ', $audit->status)) }}</div>
                </div>
                <div>
                    <div class="small am-muted">Started</div>
                    <div class="fw-bold">{{ $audit->created_at->format('d M Y') }} · {{ $audit->starter->name ?? '-' }}</div>
                </div>
                @if($audit->completed_at)
                    <div>
                        <div class="small am-muted">Closed</div>
                        <div class="fw-bold">{{ $audit->completed_at->format('d M Y') }} · {{ $audit->completer->name ?? '-' }}</div>
                    </div>
                @endif
                <div style="min-width:200px;flex:1;">
                    <div class="small am-muted">Progress · {{ $checked }} / {{ $total }}</div>
                    <div class="progress mt-1" style="height:8px;" role="progressbar" aria-label="Checked" aria-valuenow="{{ $checked }}" aria-valuemin="0" aria-valuemax="{{ $total }}"><div class="progress-bar" style="width:{{ $total ? round($checked / $total * 100) : 0 }}%;background:#3858f9;"></div></div>
                </div>
            </div>
            @if($audit->notes)
                <div class="am-card-body border-top small">{{ $audit->notes }}</div>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('assets.audits.show', $audit) }}" class="btn btn-sm {{ !$filter ? 'btn-primary' : 'btn-light-brand' }}" @if(!$filter) style="background:#3858f9;border:none;" @endif>All ({{ $total }})</a>
            @foreach(\App\Models\AssetAuditItem::RESULTS as $key => $label)
                <a href="{{ route('assets.audits.show', [$audit, 'result' => $key]) }}" class="btn btn-sm {{ $filter === $key ? 'btn-primary' : 'btn-light-brand' }}" @if($filter === $key) style="background:#3858f9;border:none;" @endif>{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
            @endforeach
        </div>

        <form method="POST" action="{{ route('assets.audits.record', $audit) }}" id="amAuditForm">
            @csrf
            <div class="am-card">
                <div class="am-card-header">
                    <h6>Checklist</h6>
                    @if($open)
                        <div class="d-flex gap-2 align-items-center">
                            <span class="small am-muted d-none d-md-inline">Tip: mark everything you can see as found first.</span>
                            <button type="button" class="btn btn-sm btn-light-brand" onclick="amMarkVisibleFound()">Mark unchecked as found</button>
                            <button type="submit" class="btn btn-sm btn-primary" style="background:#3858f9;border:none;">Save progress</button>
                        </div>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="am-table">
                        <thead><tr><th>Asset</th><th>Should be with</th><th>Result</th><th>Notes</th><th>Checked</th></tr></thead>
                        <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>
                                    <a class="am-link" href="{{ route('assets.show', $item->asset_id) }}" target="_blank" rel="noopener">{{ $item->asset->name ?? 'Deleted asset' }}</a>
                                    <div class="small am-muted"><span class="am-tag">{{ $item->asset->asset_tag ?? '' }}</span> {{ $item->asset->type ?? '' }}{{ $item->asset?->serial_number ? ' · SN ' . $item->asset->serial_number : '' }}</div>
                                </td>
                                <td class="small">
                                    {{ $item->expectedUser->name ?? $item->expected_user_name ?? 'In stock' }}
                                    <div class="am-muted">{{ $item->expected_status }}</div>
                                </td>
                                <td style="min-width:190px;">
                                    @if($open)
                                        <select class="form-select form-select-sm am-result" name="items[{{ $item->id }}][result]" aria-label="Result for {{ $item->asset->asset_tag ?? 'asset' }}">
                                            @foreach(\App\Models\AssetAuditItem::RESULTS as $key => $label)
                                                <option value="{{ $key }}" @selected($item->result === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        @php $rc = $resultColors[$item->result] ?? 'secondary'; @endphp
                                        <span class="badge am-badge bg-soft-{{ $rc }} text-{{ $rc }}">{{ \App\Models\AssetAuditItem::RESULTS[$item->result] }}</span>
                                        @if($item->incident)
                                            <div class="small mt-1"><a href="{{ route('assets.show', $item->asset_id) }}">{{ $item->incident->reference }}</a> opened</div>
                                        @endif
                                    @endif
                                </td>
                                <td style="min-width:200px;">
                                    @if($open)
                                        <input type="text" class="form-control form-control-sm" name="items[{{ $item->id }}][notes]" value="{{ $item->notes }}" maxlength="500" placeholder="e.g. Found with Rahul" aria-label="Notes">
                                    @else
                                        <span class="small">{{ $item->notes }}</span>
                                    @endif
                                </td>
                                <td class="small am-muted text-nowrap">{{ $item->checked_at?->format('d M, h:i A') }}<div>{{ $item->checker->name ?? '' }}</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="am-empty">No items for this filter.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($open && $items->isNotEmpty())
                    <div class="am-card-body border-top text-end">
                        <button type="submit" class="btn btn-primary" style="background:#3858f9;border:none;">Save progress</button>
                    </div>
                @endif
            </div>
        </form>
    </div>

    @if($open)
        @push('modals')
        <div class="modal fade" id="amCompleteAudit" tabindex="-1" aria-labelledby="amCompleteAuditTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content premium-modal">
                    <div class="modal-header"><h5 class="modal-title" id="amCompleteAuditTitle">Complete audit</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <form method="POST" action="{{ route('assets.audits.complete', $audit) }}">
                        @csrf
                        <div class="modal-body p-4">
                            <ul class="small mb-3">
                                <li>{{ $counts['found'] ?? 0 }} found, {{ $counts['wrong_holder'] ?? 0 }} with someone else</li>
                                <li class="text-danger">{{ $counts['missing'] ?? 0 }} missing</li>
                                <li class="text-warning">{{ $counts['damaged'] ?? 0 }} damaged</li>
                                @if($counts['pending'] ?? 0)<li>{{ $counts['pending'] }} not checked (left as is)</li>@endif
                            </ul>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="raise_incidents" value="1" id="amRaise" checked>
                                <label class="form-check-label small" for="amRaise">Open a <b>loss</b> case for each missing asset (it will be marked Lost) and a <b>damage</b> case for each damaged one</label>
                            </div>
                            <p class="small am-muted mt-3 mb-0">Save progress first - unsaved changes on the page are not included. Items "with someone else" are only noted; use Transfer on the asset page to correct the holder.</p>
                        </div>
                        <div class="modal-footer border-top-0"><button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Back</button><button type="submit" class="btn btn-success px-4">Complete audit</button></div>
                    </form>
                </div>
            </div>
        </div>
        @endpush
        @push('scripts')
        <script>
            function amMarkVisibleFound() {
                document.querySelectorAll('#amAuditForm .am-result').forEach(sel => { if (sel.value === 'pending') sel.value = 'found'; });
            }
        </script>
        @endpush
    @endif
@endsection
