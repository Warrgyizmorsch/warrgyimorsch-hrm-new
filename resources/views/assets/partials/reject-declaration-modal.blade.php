{{-- Reject-with-reason popup for asset declarations; action URL is set by openRejectDeclarationModal(). --}}
<div class="modal fade" id="rejectDeclarationModal" tabindex="-1" aria-labelledby="rejectDeclarationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectDeclarationModalLabel">Reject Declaration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectDeclarationForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Rejecting: <b class="text-dark" id="reject-declaration-name"></b></p>
                    <label class="fw-semibold mb-2">Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="rejection_reason" rows="3" required maxlength="255" placeholder="e.g. Serial number doesn't match the device" style="border-radius: 10px;"></textarea>
                    <div class="text-muted small mt-2">The employee will see this reason on their My Assets page.</div>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4" style="border-radius: 8px;">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function openRejectDeclarationModal(url, name) {
        document.getElementById('rejectDeclarationForm').action = url;
        document.getElementById('reject-declaration-name').textContent = name;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectDeclarationModal')).show();
    }
</script>
