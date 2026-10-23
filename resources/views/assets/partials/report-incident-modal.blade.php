{{--
    Report damage / loss. Params:
      $action      form URL (admin: assets.incidents.store, employee: assets.incidents.report)
      $assetChoices  collection of assets to pick from (id, asset_tag, name) - or null when opened per asset
      $adminFields bool: show cost + "send for repair now"
    Open with amOpenReport('damage'|'lost', assetId?, assetLabel?)
--}}
@push('modals')
<div class="modal fade" id="amReportModal" tabindex="-1" aria-labelledby="amReportTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="amReportTitle">Report damage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" id="amReportType" value="damage">
                <div class="modal-body p-4">
                    <div class="btn-group w-100 mb-3" role="group" aria-label="Incident type">
                        <button type="button" class="btn btn-outline-warning" data-am-type="damage" onclick="amSetReportType('damage')"><i class="feather-alert-triangle me-1"></i> Damaged</button>
                        <button type="button" class="btn btn-outline-danger" data-am-type="lost" onclick="amSetReportType('lost')"><i class="feather-x-octagon me-1"></i> Lost / stolen</button>
                    </div>

                    @if($assetChoices)
                        <div class="mb-3">
                            <label class="fw-semibold small mb-1" for="amReportAsset">Asset <span class="text-danger">*</span></label>
                            <select class="form-select" name="asset_id" id="amReportAsset" required>
                                <option value="">-- Choose asset --</option>
                                @foreach($assetChoices as $choice)
                                    <option value="{{ $choice->id }}">{{ $choice->asset_tag }} · {{ $choice->name }}{{ $choice->activeAllocation?->user ? ' (' . $choice->activeAllocation->user->name . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="asset_id" id="amReportAsset">
                        <p class="small mb-3">Asset: <b id="amReportAssetLabel"></b></p>
                    @endif

                    <div class="mb-3">
                        <label class="fw-semibold small mb-1" for="amReportDate">When did it happen? <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="incident_date" id="amReportDate" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-semibold small mb-1" for="amReportDesc">What happened? <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="description" id="amReportDesc" rows="3" maxlength="2000" required placeholder="Describe the damage or how / where it was lost"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="fw-semibold small mb-1" for="amReportFile">Photo or document <span class="am-muted fw-normal">(optional - JPG/PNG/PDF, max 4 MB)</span></label>
                        <input type="file" class="form-control" name="attachment" id="amReportFile" accept="image/jpeg,image/png,image/webp,application/pdf">
                    </div>

                    @if(!empty($adminFields))
                        <div class="mb-3">
                            <label class="fw-semibold small mb-1" for="amReportCost">Estimated cost (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="cost" id="amReportCost">
                        </div>
                        <div class="form-check" id="amReportRepairWrap">
                            <input class="form-check-input" type="checkbox" name="send_to_repair" value="1" id="amReportRepair">
                            <label class="form-check-label small" for="amReportRepair">Take it back and send for repair now (moves to Maintenance)</label>
                        </div>
                    @endif

                    <div class="alert alert-danger small mt-3 mb-0 d-none" id="amReportLostNote">
                        The asset will be marked <b>Lost</b> and removed from its holder. If it turns up, close the case as <b>Found</b>.
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="background:#3858f9;border:none;">Submit report</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    window.amSetReportType = function (type) {
        document.getElementById('amReportType').value = type;
        document.getElementById('amReportTitle').textContent = type === 'lost' ? 'Report lost / stolen' : 'Report damage';
        document.querySelectorAll('[data-am-type]').forEach(b => b.classList.toggle('active', b.dataset.amType === type));
        document.getElementById('amReportLostNote').classList.toggle('d-none', type !== 'lost');
        const repair = document.getElementById('amReportRepairWrap');
        if (repair) repair.classList.toggle('d-none', type === 'lost');
    };
    window.amOpenReport = function (type, assetId, assetLabel) {
        amSetReportType(type || 'damage');
        const field = document.getElementById('amReportAsset');
        if (assetId) field.value = assetId;
        const label = document.getElementById('amReportAssetLabel');
        if (label) label.textContent = assetLabel || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('amReportModal')).show();
    };
</script>
@endpush
