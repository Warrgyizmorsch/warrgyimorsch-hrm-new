<div class="erp-page-header">
    <div class="erp-page-header-text">
        <h1>{{ $pageTitle ?? 'Add Employee' }}</h1>
        <p>{{ $pageSubtitle ?? 'Create a new employee profile with job, payroll, compliance and document details.' }}</p>
    </div>
    <div class="erp-page-header-actions d-none d-xl-flex">
        <button type="button" class="erp-btn erp-btn-ghost" id="empFormDraftBtnHeader">
            <i class="bi bi-save"></i> Save Draft
        </button>
        <a href="{{ $cancelUrl ?? route('employees.index') }}" class="erp-btn erp-btn-outline">
            Cancel
        </a>
        <button type="button" class="erp-btn erp-btn-primary" id="empFormNextBtnHeader">
            Next Step <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</div>
