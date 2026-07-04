@php
    $cancelUrl = $cancelUrl ?? route('employees.index');
    $submitLabel = $submitLabel ?? 'Submit';
    $isEdit = !empty($employee);
@endphp

<div class="zoho-people-list-header hrm-emp-toolbar">
    <div class="zoho-people-list-header-left">
        <div class="hrm-emp-toolbar-title-row">
            @if($isEdit)
                <div class="hrm-emp-toolbar-avatar" aria-hidden="true">
                    @if($employee->photo)
                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="" class="zoho-emp-avatar">
                    @else
                        <span class="hrm-emp-toolbar-initial">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
                    @endif
                </div>
            @endif
            <div>
                <h1 class="zoho-people-list-title">{{ $pageTitle ?? 'Add Employee' }}</h1>
                @if($isEdit)
                    <div class="zoho-people-view-meta">
                        <span class="zoho-people-view-name">{{ $employee->name }}</span>
                        @if($employee->employee_code)
                            <span class="hrm-emp-toolbar-code">({{ $employee->employee_code }})</span>
                        @endif
                    </div>
                @elseif(!empty($pageSubtitle))
                    <div class="zoho-people-view-meta">
                        <span class="zoho-people-view-name">{{ $pageSubtitle }}</span>
                    </div>
                @endif
                <div class="zoho-people-scope-links">
                    <a href="{{ $cancelUrl }}">Back to Employees</a>
                    @if($isEdit)
                        <span class="zoho-breadcrumb-sep">/</span>
                        <span class="zoho-breadcrumb-current">Edit profile</span>
                    @else
                        <span class="zoho-breadcrumb-sep">/</span>
                        <span class="zoho-breadcrumb-current">New record</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="zoho-people-list-header-right">
        <button type="button" class="zoho-btn-outline" id="empFormDraftBtnHeader" title="Save draft locally">
            <i class="feather-save"></i> Save Draft
        </button>
        <button type="button" class="zoho-btn-primary" id="empFormNextBtnHeader">
            Next Step <i class="feather-arrow-right"></i>
        </button>
        <button type="submit" class="zoho-btn-primary d-none" id="empFormSubmitBtnHeader" form="employeeForm">
            <i class="feather-check"></i> {{ $submitLabel }}
        </button>
        <a href="{{ $cancelUrl }}" class="zoho-icon-btn" title="Close">
            <i class="feather-x"></i>
        </a>
    </div>
</div>

<div class="hrm-emp-tabs-bar">
    <nav class="zoho-form-tabs hrm-emp-tabs" id="hrmStepper" role="tablist" aria-label="Employee form steps">
        <button type="button" class="nav-link active" data-step="1" role="tab" aria-selected="true">
            Personal Details
        </button>
        <button type="button" class="nav-link" data-step="2" role="tab" aria-selected="false">
            Job Details
        </button>
    </nav>
</div>
