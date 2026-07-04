<div class="erp-form-sticky-bar" id="employeeFormFooter">
    <div class="erp-form-sticky-left">
        <button type="button" class="erp-btn erp-btn-outline d-none" id="empFormPrevBtn">
            <i class="bi bi-arrow-left"></i> <span class="erp-btn-text">Previous</span>
        </button>
        <a href="{{ $cancelUrl ?? route('employees.index') }}" class="erp-btn erp-btn-outline">
            <span class="erp-btn-text">Cancel</span>
        </a>
    </div>
    <div class="erp-form-sticky-right">
        <button type="button" class="erp-btn erp-btn-ghost" id="empFormDraftBtn">
            <i class="bi bi-save"></i> <span class="erp-btn-text">Save Draft</span>
        </button>
        <button type="button" class="erp-btn erp-btn-primary" id="empFormNextBtn">
            <span class="erp-btn-text">Next</span> <i class="bi bi-arrow-right"></i>
        </button>
        <button type="submit" class="erp-btn erp-btn-primary d-none" id="empFormSubmitBtn" form="employeeForm">
            <i class="bi bi-check-lg"></i> <span class="erp-btn-text">{{ $submitLabel ?? 'Submit' }}</span>
        </button>
    </div>
</div>
<div class="erp-draft-toast" id="erpDraftToast">Draft saved locally</div>
