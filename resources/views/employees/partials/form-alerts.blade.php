@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show zoho-form-alert" role="alert">
        <i class="feather-check-circle me-2"></i> <strong>{{ session('success') }}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div id="hrmDraftSavedNote" class="hrm-draft-saved-note d-none" role="status">
    <i class="feather-check-circle"></i> Draft saved locally
</div>
