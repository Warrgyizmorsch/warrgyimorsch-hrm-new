<div class="p-4 bg-white" style="font-family: 'Inter', sans-serif;">
    <!-- Header Summary -->
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <div class="text-uppercase text-muted small fw-bold mb-1" style="font-size: 11px; letter-spacing: 1px;">Employee Name</div>
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-person-circle text-primary"></i> {{ $payroll->employee->name }}
            </h5>
        </div>
        <div class="text-end">
            <div class="text-uppercase text-muted small fw-bold mb-1" style="font-size: 11px; letter-spacing: 1px;">Payroll Month</div>
            <span class="badge px-3 py-2 fs-6 fw-bold shadow-sm" style="border-radius: 8px; background: #3858f9;">
                <i class="bi bi-calendar3 me-1"></i> {{ $payroll->month }}
            </span>
        </div>
    </div>

    <!-- Value Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- Payable Days -->
        <div class="col-6 col-md-4">
            <div class="p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">Payable Days</div>
                    <i class="bi bi-calendar-check text-muted fs-6"></i>
                </div>
                <div class="fs-4 fw-bold mb-0 text-dark">{{ $payroll->payable_days }}</div>
            </div>
        </div>

        <!-- TDS -->
        <div class="col-6 col-md-4">
            <div class="p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">TDS (0%)</div>
                    <i class="bi bi-receipt text-muted fs-6"></i>
                </div>
                <div class="fs-4 fw-bold mb-0 text-dark">0.00</div>
            </div>
        </div>

        <!-- PF Deduction -->
        <div class="col-6 col-md-4">
            <div class="p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">PF Deduction</div>
                    <i class="bi bi-shield-lock text-muted fs-6"></i>
                </div>
                <div class="fs-4 fw-bold mb-0 text-danger">{{ number_format($payroll->pf_deduction ?? 0, 2) }}</div>
            </div>
        </div>

        <!-- Other Cuts -->
        <div class="col-6 col-md-4">
            <div class="p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">Other Cuts</div>
                    <i class="bi bi-scissors text-muted fs-6"></i>
                </div>
                <div class="fs-4 fw-bold mb-0 text-danger">{{ number_format($payroll->other_deduction ?? 0, 2) }}</div>
            </div>
        </div>

        <!-- ECS -->
        <div class="col-6 col-md-4">
            <div class="p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">ECS</div>
                    <i class="bi bi-credit-card-2-front text-muted fs-6"></i>
                </div>
                <div class="fs-4 fw-bold mb-0 text-dark">0.00</div>
            </div>
        </div>

        <!-- Total Net Salary -->
        <div class="col-6 col-md-4">
            <div class="p-4 rounded-3 text-white shadow-sm" style="background: linear-gradient(135deg, #3858f9 0%, #1e3a8a 100%);">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="text-white opacity-75 small fw-bold text-uppercase" style="font-size: 10px;">Total Net Pay</div>
                    <i class="bi bi-wallet2 fs-6"></i>
                </div>
                <div class="fs-4 fw-bold mb-0">₹ {{ number_format($payroll->net_salary, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Additional Breakdown -->
    <div class="p-4 rounded-4 border bg-white shadow-sm mb-3">
        <h6 class="text-uppercase fw-bold text-dark mb-4 d-flex align-items-center gap-2" style="font-size: 12px; letter-spacing: 0.5px;">
            <i class="bi bi-bar-chart-steps text-primary"></i> Earnings Summary
        </h6>
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Basic Salary</div>
                <div class="fw-bold fs-6">₹ {{ number_format($payroll->basic_salary, 2) }}</div>
            </div>
            @if(($payroll->dearness_allowance ?? 0) > 0)
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Dearness Allowance</div>
                <div class="fw-bold fs-6">₹ {{ number_format($payroll->dearness_allowance, 2) }}</div>
            </div>
            @endif
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">HRA</div>
                <div class="fw-bold fs-6">₹ {{ number_format($payroll->hra, 2) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Conveyance</div>
                <div class="fw-bold fs-6">₹ {{ number_format($payroll->conveyance_allowance, 2) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Medical Allowance</div>
                <div class="fw-bold fs-6">₹ {{ number_format($payroll->medical_allowance, 2) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Other Allowance</div>
                <div class="fw-bold fs-6">₹ {{ number_format($payroll->other_allowance, 2) }}</div>
            </div>
            @if(($payroll->variable_earning ?? 0) > 0)
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Variable Earning</div>
                <div class="fw-bold fs-6">₹ {{ number_format($payroll->variable_earning, 2) }}</div>
            </div>
            @endif
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Gross Total</div>
                <div class="fw-bold fs-6 text-primary">₹ {{ number_format($payroll->gross_salary, 2) }}</div>
            </div>
        </div>
    </div>

    @if((($payroll->epf_deduction ?? 0) > 0) || (($payroll->professional_tax_deduction ?? 0) > 0) || (($payroll->loan_recovery_deduction ?? 0) > 0))
    <div class="p-4 rounded-4 border bg-white shadow-sm">
        <h6 class="text-uppercase fw-bold text-dark mb-4 d-flex align-items-center gap-2" style="font-size: 12px; letter-spacing: 0.5px;">
            <i class="bi bi-dash-circle text-danger"></i> Additional Deductions
        </h6>
        <div class="row g-4">
            @if(($payroll->epf_deduction ?? 0) > 0)
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">EPF</div>
                <div class="fw-bold fs-6 text-danger">₹ {{ number_format($payroll->epf_deduction, 2) }}</div>
            </div>
            @endif
            @if(($payroll->professional_tax_deduction ?? 0) > 0)
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Professional Tax</div>
                <div class="fw-bold fs-6 text-danger">₹ {{ number_format($payroll->professional_tax_deduction, 2) }}</div>
            </div>
            @endif
            @if(($payroll->loan_recovery_deduction ?? 0) > 0)
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1 small">Loan Recovery</div>
                <div class="fw-bold fs-6 text-danger">₹ {{ number_format($payroll->loan_recovery_deduction, 2) }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

<style>
    .text-primary { color: #3858f9 !important; }
</style>