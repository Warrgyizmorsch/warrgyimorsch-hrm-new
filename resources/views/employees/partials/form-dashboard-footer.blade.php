<div class="hrm-emp-footer" id="employeeFormFooter">
    <div class="hrm-emp-footer-left">
        <button type="button" class="hrm-btn hrm-btn-outline d-none" id="empFormPrevBtn">
            <i class="bi bi-arrow-left"></i> Previous
        </button>
        <a href="{{ $cancelUrl ?? route('employees.index') }}" class="hrm-btn hrm-btn-outline">Cancel</a>
    </div>
    <div class="hrm-emp-footer-right">
        <button type="button" class="hrm-btn hrm-btn-ghost" id="empFormDraftBtn">
            <i class="bi bi-save"></i> Save Draft
        </button>
        <button type="button" class="hrm-btn hrm-btn-primary" id="empFormNextBtn">
            Next Step <i class="bi bi-arrow-right"></i>
        </button>
        <button type="submit" class="hrm-btn hrm-btn-primary d-none" id="empFormSubmitBtn" form="employeeForm">
            <i class="bi bi-check-lg"></i> {{ $submitLabel ?? 'Submit' }}
        </button>
    </div>
</div>
