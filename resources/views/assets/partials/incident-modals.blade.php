{{--
    Close / repair / cost-and-recovery dialogs for a damage or loss case.
    Buttons open them with data-* attributes, e.g.
      <button onclick="amOpenResolve(this)" data-url="..." data-type="damage" data-ref="INC-0001"
              data-cost="..." data-recovery-status="..." data-recovery-amount="..." data-recovered-amount="...">
--}}
@php
    $amResolutions = \App\Models\AssetIncident::RESOLUTIONS;
    $amRecovery = \App\Models\AssetIncident::RECOVERY_STATUSES;
    $amConditions = \App\Models\Asset::CONDITIONS;
@endphp

@push('modals')
<div class="modal fade" id="amResolveModal" tabindex="-1" aria-labelledby="amResolveTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="amResolveTitle">Close case <span class="am-tag ms-1" data-am-ref></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" data-am-form>
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="fw-semibold small mb-1" for="amResolution">Outcome <span class="text-danger">*</span></label>
                        <select class="form-select" name="resolution" id="amResolution" required></select>
                        <div class="form-text" id="amResolutionHelp"></div>
                    </div>
                    <div class="mb-3" id="amConditionWrap">
                        <label class="fw-semibold small mb-1" for="amCondition">Condition now</label>
                        <select class="form-select" name="condition" id="amCondition">
                            @foreach($amConditions as $c)
                                <option value="{{ $c }}" @selected($c === 'Good')>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('assets.partials.money-fields', ['prefix' => 'amR'])
                    <div class="mb-1">
                        <label class="fw-semibold small mb-1" for="amResolutionNotes">Notes</label>
                        <textarea class="form-control" name="resolution_notes" id="amResolutionNotes" rows="2" placeholder="Vendor, job card, what was replaced..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="background:#3858f9;border:none;">Close case</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="amRecoveryModal" tabindex="-1" aria-labelledby="amRecoveryTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="amRecoveryTitle">Cost &amp; recovery <span class="am-tag ms-1" data-am-ref></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" data-am-form>
                @csrf
                <div class="modal-body p-4">
                    @include('assets.partials.money-fields', ['prefix' => 'amC', 'withDate' => true])
                    <p class="small am-muted mb-0">Recorded for tracking only - nothing is deducted in payroll automatically.</p>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="background:#3858f9;border:none;">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="amRepairModal" tabindex="-1" aria-labelledby="amRepairTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="amRepairTitle">Send for repair <span class="am-tag ms-1" data-am-ref></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" data-am-form>
                @csrf
                <div class="modal-body p-4">
                    <p class="small am-muted">The device is taken back from its holder and moved to <b>Maintenance</b> until the case is closed.</p>
                    <label class="fw-semibold small mb-1" for="amRepairNote">Note</label>
                    <input type="text" class="form-control" name="note" id="amRepairNote" maxlength="500" placeholder="e.g. Given to Dell service centre, job #4471">
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4">Send for repair</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    (function () {
        const resolutions = @json($amResolutions);
        const helpText = {
            repaired: 'Condition is updated; a device in Maintenance goes back to Available stock.',
            no_action: 'Case closes; the device stays where it is.',
            found: 'The device goes back to Available stock.',
            written_off: 'The device is taken off its holder and marked Retired.',
        };

        function fill(modalId, btn) {
            const modal = document.getElementById(modalId);
            modal.querySelectorAll('[data-am-ref]').forEach(el => el.textContent = btn.dataset.ref || '');
            modal.querySelector('[data-am-form]').action = btn.dataset.url;
            return modal;
        }

        function fillMoney(modal, prefix, btn) {
            const set = (id, v) => { const el = modal.querySelector('#' + prefix + id); if (el) el.value = v ?? ''; };
            set('Cost', btn.dataset.cost);
            set('Status', btn.dataset.recoveryStatus || 'none');
            set('Amount', btn.dataset.recoveryAmount && parseFloat(btn.dataset.recoveryAmount) > 0 ? btn.dataset.recoveryAmount : '');
            set('Recovered', btn.dataset.recoveredAmount && parseFloat(btn.dataset.recoveredAmount) > 0 ? btn.dataset.recoveredAmount : '');
            amToggleRecovery(prefix);
        }

        window.amToggleRecovery = function (prefix) {
            const status = document.getElementById(prefix + 'Status');
            const box = document.getElementById(prefix + 'Box');
            if (!status || !box) return;
            const show = status.value !== 'none';
            box.classList.toggle('d-none', !show);
            document.getElementById(prefix + 'Amount').required = show;
        };

        function syncResolution() {
            const sel = document.getElementById('amResolution');
            document.getElementById('amResolutionHelp').textContent = helpText[sel.value] || '';
            document.getElementById('amConditionWrap').classList.toggle('d-none', sel.value === 'written_off');
        }

        window.amOpenResolve = function (btn) {
            const modal = fill('amResolveModal', btn);
            const sel = document.getElementById('amResolution');
            sel.innerHTML = '';
            Object.entries(resolutions[btn.dataset.type] || {}).forEach(([value, label]) => sel.add(new Option(label, value)));
            sel.onchange = syncResolution;
            syncResolution();
            fillMoney(modal, 'amR', btn);
            bootstrap.Modal.getOrCreateInstance(modal).show();
        };

        window.amOpenRecovery = function (btn) {
            const modal = fill('amRecoveryModal', btn);
            fillMoney(modal, 'amC', btn);
            bootstrap.Modal.getOrCreateInstance(modal).show();
        };

        window.amOpenRepair = function (btn) {
            const modal = fill('amRepairModal', btn);
            bootstrap.Modal.getOrCreateInstance(modal).show();
        };
    })();
</script>
@endpush
