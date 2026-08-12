    <div class="p-4">
        <!-- Hidden fields for edit mode -->
        <input type="hidden" id="payrollIdForUpdate" value="">
        <input type="hidden" id="isEditMode" value="false">
        
        <!-- Calculation Filter Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: white;">
            <div class="card-header bg-white border-0 px-4 py-3 border-bottom text-center">
                <h6 class="fw-bold mb-0 text-dark">Setup Calculation Parameters</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-2 align-items-end">
                    <div class="col-4">
                        <label class="form-label small fw-bold text-muted mb-2">Employee</label>
                        <div class="dropdown">
                            <button class="wghrm-custom-select-btn fw-bold dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="employeeSelectBtn"
                                style="border-radius: 8px; height: 45px !important; font-size: 13px; background: #f1f5f9; border: none; box-shadow: none;">
                                Select
                            </button>
                            <div class="dropdown-menu wghrm-custom-dropdown-menu" style="width: 100%;">
                                <div class="wghrm-custom-search-box">
                                    <input type="text" class="wghrm-custom-search-input"
                                        placeholder="Search employee..." onkeyup="wghrmFilterItems(this)"
                                        onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                </div>
                                @foreach(\App\Models\Employee::active()->get() as $emp)
                                    <a class="dropdown-item wghrm-custom-dropdown-item"
                                        href="javascript:void(0);"
                                        onclick="document.getElementById('employeeSelect').value='{{ $emp->id }}'; document.getElementById('employeeSelectBtn').innerText='{{ addslashes($emp->name) }}'; bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle')).hide();">
                                        {{ $emp->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" id="employeeSelect" value="">
                    </div>
                    <div class="col-4 text-center">
                        <label class="form-label small fw-bold text-muted mb-2">Month</label>
                        <input type="month" id="monthSelect"
                            class="form-control border-0 bg-light py-2 px-2 shadow-none fw-bold text-center"
                            value="{{ date('Y-m') }}" style="border-radius: 8px; height: 45px !important; font-size: 13px;">
                    </div>
                    <div class="col-4 ps-0">
                        <button
                            class="btn btn-primary w-100 fw-bold shadow-sm"
                            onclick="calculatePayroll()"
                            style="background: #3858f9; border: none; height: 45px !important; border-radius: 8px; font-size: 12px; letter-spacing: 0.5px;">
                            CALCULATE
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculation Result -->
        <div id="calculationResult" style="display: none;">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm mb-0"
                        style="border-radius: 12px; background: white; overflow: hidden;">
                        <div class="card-header bg-white border-bottom px-4 py-3">
                            <h6 class="fw-bold mb-0 text-dark">Salary Components</h6>
                                <div id="overtime_box" style="color: #3554ea;font-weight: 600;"></div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <h6 class="small fw-bold text-primary text-uppercase mb-3"
                                        style="letter-spacing: 0.5px;">Earnings</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless align-middle mb-0">
                                                <tbody class="text-dark fw-bold">
                                                       <tr>
                                                            <td class="ps-0 py-2 text-muted fw-normal">Payable Days</td>
                                                            <td><input type="number" step="0.01" id="inputPayableDays" class="form-control form-control-sm">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="ps-0 py-2 text-muted fw-normal">Basic Salary</td>
                                                        <td><input type="number" id="inputBasic" class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="ps-0 py-2 text-muted fw-normal">HRA</td>
                                                        <td><input type="number" id="inputHRA" class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="ps-0 py-2 text-muted fw-normal">Conveyance</td>
                                                        <td><input type="number" id="inputConveyance" class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="ps-0 py-2 text-muted fw-normal">Medical</td>
                                                        <td><input type="number" id="inputMedical" class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr id="rowDearnessAllowance" style="display:none;">
                                                        <td class="ps-0 py-2 text-muted fw-normal">Dearness Allowance</td>
                                                        <td><input type="number" id="inputDearnessAllowance" class="form-control form-control-sm" readonly></td>
                                                    </tr>
                                                    <tr id="rowVariableEarning" style="display:none;">
                                                        <td class="ps-0 py-2 text-muted fw-normal">Variable Earning</td>
                                                        <td><input type="number" id="inputVariableEarning" class="form-control form-control-sm" placeholder="0.00"></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="ps-0 py-2 text-muted fw-normal">Override</td>
                                                        <td>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <input type="checkbox" id="overrideCheck">
                                                                <input type="number" id="overrideAmount" class="form-control form-control-sm" placeholder="Amt" disabled>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr class="border-top">
                                                        <td class="ps-0 py-3">Gross Salary</td>
                                                        <td class="text-end py-3 fs-5" id="tableGrossSalary">₹ 0.00</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <div class="col-sm-6">
                                    <h6 class="small fw-bold text-danger text-uppercase mb-3"
                                        style="letter-spacing: 0.5px;">Deductions</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0">
                                            <tbody class="text-dark fw-bold">
                                                <tr>
                                                    <td>PF</td>
                                                    <td><input type="number" id="inputPF" class="form-control form-control-sm"></td>
                                                </tr>
                                                <tr>
                                                    <td>ESI</td>
                                                    <td><input type="number" id="inputESI" class="form-control form-control-sm"></td>
                                                </tr>
                                                <tr>
                                                    <td>Other</td>
                                                    <td><input type="number" id="inputOther" class="form-control form-control-sm"></td>
                                                </tr>
                                                <tr id="rowEpf" style="display:none;">
                                                    <td>EPF</td>
                                                    <td><input type="number" id="inputEpf" class="form-control form-control-sm" placeholder="0.00"></td>
                                                </tr>
                                                <tr id="rowProfessionalTax" style="display:none;">
                                                    <td>Professional Tax</td>
                                                    <td><input type="number" id="inputProfessionalTax" class="form-control form-control-sm" placeholder="0.00"></td>
                                                </tr>
                                                <tr id="rowLoanRecovery" style="display:none;">
                                                    <td>Loan Recovery</td>
                                                    <td><input type="number" id="inputLoanRecovery" class="form-control form-control-sm" placeholder="0.00"></td>
                                                </tr>
                                                <tr class="border-top">
                                                    <td class="ps-0 py-3 text-danger">Total Deductions</td>
                                                    <td class="text-end py-3 text-danger fs-5"
                                                        id="tableTotalDeductions">₹ 0.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-white border-bottom px-4 py-3">
                            <h6 class="fw-bold mb-0 text-dark">Net Salary & Summary</h6>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="p-4 rounded-4 mb-4 shadow"
                                style="background: linear-gradient(135deg, #3858f9 0%, #1e3a8a 100%);">
                                <div class="text-white opacity-75 small mb-1">Take Home Pay</div>
                                <div class="fs-2 fw-bold text-white" id="tableNetSalary">₹ 0.00</div>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-4">
                                    <div class="p-2 bg-light rounded-3">
                                        <div class="text-muted fs-10 mb-1">Payable</div>
                                        <div class="fw-bold" id="resultPayableDays">0</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 bg-light rounded-3">
                                        <div class="text-muted fs-10 mb-1">Unpaid</div>
                                        <div class="fw-bold text-danger" id="resultUnpaidDays">0</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 bg-light rounded-3">
                                        <div class="text-muted fs-10 mb-1">Loss</div>
                                        <div class="fw-bold text-danger" id="resultSalaryLoss">₹ 0.00</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-primary py-3 fw-bold shadow-sm"
                                    style="background: #3858f9; border: none; border-radius: 12px;" onclick="savePayroll(this)">
                                    <i class="bi bi-check2-circle me-2 fs-5"></i> SUBMIT & SAVE PAYROLL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="noCalculation" class="text-center py-5">
            <div class="py-5" style="border: 2px dashed #e2e8e0; border-radius: 16px; background: #f8fafc;">
                <i class="bi bi-calculator text-primary" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="text-dark mt-3 fw-bold fs-5 mb-1">Payroll Ready to Generate</p>
                <p class="text-muted small">Select an employee and month above to start the calculation.</p>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof isManualDays === 'undefined') {
        var isManualDays = false;
    }
    if (typeof currentPayrollData === 'undefined') {
        var currentPayrollData = null;
    }
    if (typeof currentPayrollTotalDays === 'undefined') {
        var currentPayrollTotalDays = 0;
    }
    if (typeof isManualPF === 'undefined') {
        var isManualPF = false;
    }
    if (typeof isManualESI === 'undefined') {
        var isManualESI = false;
    }

    // Use a unique scoped setup if possible, or just re-ensure listeners
    function initPayrollLogic() {
        document.addEventListener('input', recalculate);
        const daysInput = document.getElementById('inputPayableDays');
        if(daysInput) {
            daysInput.addEventListener('input', function () {
                isManualDays = true;
            });
        }

        const pfInput = document.getElementById('inputPF');
        if(pfInput) {
            pfInput.addEventListener('input', function () {
                isManualPF = true;
            });
        }

        const esiInput = document.getElementById('inputESI');
        if(esiInput) {
            esiInput.addEventListener('input', function () {
                isManualESI = true;
            });
        }

        const overrideCheck = document.getElementById('overrideCheck');
        if(overrideCheck) {
            overrideCheck.addEventListener('change', function () {
                document.getElementById('overrideAmount').disabled = !this.checked;
            });
        }
    }

    initPayrollLogic();

   function recalculate(event) {
        const sourceId = event?.target?.id || '';
        let basic = parseFloat(document.getElementById('inputBasic')?.value) || 0;
        let hra = parseFloat(document.getElementById('inputHRA')?.value) || 0;
        let conv = parseFloat(document.getElementById('inputConveyance')?.value) || 0;
        let med = parseFloat(document.getElementById('inputMedical')?.value) || 0;
        let otherAllowance = parseFloat(currentPayrollData?.other_allowance) || 0;
        let dearnessAllowance = parseFloat(document.getElementById('inputDearnessAllowance')?.value) || 0;
        let variableEarning = parseFloat(document.getElementById('inputVariableEarning')?.value) || 0;

        let fullSalary = basic + hra + conv + med + otherAllowance + dearnessAllowance;
        let backendPerDaySalary = Number(currentPayrollData?.perdaysalary || 0);
        let totalDays = Number(currentPayrollTotalDays || currentPayrollData?.total_days || 0);
        if (!totalDays && backendPerDaySalary > 0 && fullSalary > 0) {
            totalDays = Math.round(fullSalary / backendPerDaySalary);
        }
        totalDays = totalDays || 30;

        let payableDays = parseFloat(document.getElementById('inputPayableDays')?.value) || 0;
        payableDays = Math.min(payableDays, totalDays);
        let gross = (fullSalary / totalDays) * payableDays;

        if (document.getElementById('overrideCheck')?.checked) {
            let override = parseFloat(document.getElementById('overrideAmount').value);
            if (!isNaN(override) && override > 0) gross = override;
        }

        let baseGross = gross;
        gross += variableEarning;

        if(document.getElementById('tableGrossSalary')) document.getElementById('tableGrossSalary').innerText = '₹ ' + gross.toFixed(2);

        let earnedBasic = totalDays > 0 ? (basic / totalDays) * payableDays : 0;
        let autoPF = currentPayrollData?.pf_enabled ? earnedBasic * 0.12 : 0;
        let autoESI = (currentPayrollData?.esi_enabled && gross <= 21000) ? gross * 0.0075 : 0;

        if (sourceId !== 'inputPF' && document.getElementById('inputPF')) {
            document.getElementById('inputPF').value = autoPF.toFixed(2);
        }

        if (sourceId !== 'inputESI' && document.getElementById('inputESI')) {
            document.getElementById('inputESI').value = autoESI.toFixed(2);
        }

        let pf = parseFloat(document.getElementById('inputPF')?.value) || 0;
        let esi = parseFloat(document.getElementById('inputESI')?.value) || 0;
        let other = parseFloat(document.getElementById('inputOther')?.value) || 0;
        let epf = parseFloat(document.getElementById('inputEpf')?.value) || 0;
        let professionalTax = parseFloat(document.getElementById('inputProfessionalTax')?.value) || 0;
        let loanRecovery = parseFloat(document.getElementById('inputLoanRecovery')?.value) || 0;

        let totalDeduction = pf + esi + other + epf + professionalTax + loanRecovery;
        let net = gross - totalDeduction;

        if(document.getElementById('tableTotalDeductions')) document.getElementById('tableTotalDeductions').innerText = '₹ ' + totalDeduction.toFixed(2);
        if(document.getElementById('tableNetSalary')) document.getElementById('tableNetSalary').innerText = '₹ ' + net.toFixed(2);

        if(document.getElementById('resultPayableDays')) document.getElementById('resultPayableDays').innerText = payableDays;
        let unpaidDays = totalDays - payableDays;
        if(document.getElementById('resultUnpaidDays')) document.getElementById('resultUnpaidDays').innerText = unpaidDays.toFixed(2);

        let salaryLoss = fullSalary - baseGross;
        if(document.getElementById('resultSalaryLoss')) document.getElementById('resultSalaryLoss').innerText = '₹ ' + salaryLoss.toFixed(2);
    }

    function calculatePayroll() {
        isManualDays = false;
        isManualPF = false;
        isManualESI = false;
        const month = document.getElementById('monthSelect').value;
        const employeeId = document.getElementById('employeeSelect').value;

        if (!month || !employeeId) {
            alert('Please select person and month');
            return;
        }

        const noCalc = document.getElementById('noCalculation');
        noCalc.innerHTML = `<div class="py-5 text-center"><div class="spinner-border text-primary"></div></div>`;

        fetch('{{ url("/payroll/calculate") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ month, employee_id: employeeId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentPayrollData = data.payroll;
                currentPayrollTotalDays = Number(data.payroll.total_days || 0);
                displayPayrollData(data.payroll);
                noCalc.style.display = 'none';
                document.getElementById('calculationResult').style.display = 'block';
            } else alert(data.message);
        });
    }

    function displayPayrollData(p) {
        const formattedMonth = new Date(p.month + '-01').toLocaleString('en-IN', { month: 'short', year: 'numeric' });
        if(document.getElementById('resultMonth')) document.getElementById('resultMonth').textContent = formattedMonth;

        document.getElementById('inputPayableDays').value = p.payable_days;
        document.getElementById('inputBasic').value = p.basic_salary;
        document.getElementById('inputHRA').value = p.hra;
        document.getElementById('inputConveyance').value = p.conveyance_allowance;
        document.getElementById('inputMedical').value = p.medical_allowance;
        isManualPF = false;
        isManualESI = false;
        document.getElementById('inputPF').value = p.pf_deduction;
        document.getElementById('inputESI').value = p.esi_deduction;
        document.getElementById('inputOther').value = p.other_deduction || 0;

        const isBD = p.department === 'Business Development';
        document.getElementById('rowDearnessAllowance').style.display = isBD ? '' : 'none';
        document.getElementById('rowVariableEarning').style.display = isBD ? '' : 'none';
        document.getElementById('rowEpf').style.display = isBD ? '' : 'none';
        document.getElementById('rowProfessionalTax').style.display = isBD ? '' : 'none';
        document.getElementById('rowLoanRecovery').style.display = isBD ? '' : 'none';
        document.getElementById('inputDearnessAllowance').value = p.dearness_allowance || 0;
        document.getElementById('inputVariableEarning').value = p.variable_earning || 0;
        document.getElementById('inputEpf').value = p.epf_deduction || 0;
        document.getElementById('inputProfessionalTax').value = p.professional_tax_deduction || 0;
        document.getElementById('inputLoanRecovery').value = p.loan_recovery_deduction || 0;

        currentPayrollTotalDays = Number(p.total_days || currentPayrollTotalDays || 0);
        const overtimeBox = document.getElementById('overtime_box');

        if (overtimeBox) {
            const otHours = Number(p.overtime_hours || 0);
            const otDays = Number(p.overtime_days || 0);

            overtimeBox.style.display = 'block';

            if (otHours > 0) {
                overtimeBox.innerHTML = `
                    <div class="alert alert-info mb-3">
                        <strong>${p.emp_name}</strong> worked 
                        <strong>${otHours}</strong> hrs 
                        (<strong>${otDays}</strong> days) extra this month
                    </div>
                `;
            } else {
                overtimeBox.innerHTML = `
                    <div class="alert alert-secondary mb-3">
                        No overtime this month
                    </div>
                `;
            }
        }

        recalculate();
    }

    function savePayroll(btn) {
        if (!currentPayrollData) return;
        btn.disabled = true;
        
        const payrollId = document.getElementById('payrollIdForUpdate').value;
        const isEditMode = document.getElementById('isEditMode').value === 'true';
        
        const payloadData = {
            ...currentPayrollData,
            basic_salary: document.getElementById('inputBasic').value,
            hra: document.getElementById('inputHRA').value,
            conveyance_allowance: document.getElementById('inputConveyance').value,
            medical_allowance: document.getElementById('inputMedical').value,
            dearness_allowance: document.getElementById('inputDearnessAllowance').value || 0,
            variable_earning: document.getElementById('inputVariableEarning').value || 0,
            payable_days: document.getElementById('inputPayableDays').value,
            pf_deduction: document.getElementById('inputPF').value,
            esi_deduction: document.getElementById('inputESI').value,
            other_deduction: document.getElementById('inputOther').value,
            epf_deduction: document.getElementById('inputEpf').value || 0,
            professional_tax_deduction: document.getElementById('inputProfessionalTax').value || 0,
            loan_recovery_deduction: document.getElementById('inputLoanRecovery').value || 0,
            deductions: (
                (parseFloat(document.getElementById('inputPF').value) || 0) +
                (parseFloat(document.getElementById('inputESI').value) || 0) +
                (parseFloat(document.getElementById('inputOther').value) || 0) +
                (parseFloat(document.getElementById('inputEpf').value) || 0) +
                (parseFloat(document.getElementById('inputProfessionalTax').value) || 0) +
                (parseFloat(document.getElementById('inputLoanRecovery').value) || 0)
            ).toFixed(2),
            net_salary: document.getElementById('tableNetSalary').innerText.replace(/[₹,]/g,''),
            gross_salary: document.getElementById('tableGrossSalary').innerText.replace(/[₹,]/g,'')
        };
        
        const url = isEditMode ? `{{ url("/payroll") }}/${payrollId}` : '{{ url("/payroll/store") }}';
        const method = isEditMode ? 'PUT' : 'POST';
        
        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payloadData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof Toast !== 'undefined') {
                    Toast.fire({
                        icon: 'success',
                        title: isEditMode ? 'Payroll updated successfully!' : 'Payroll saved successfully!'
                    });
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert(isEditMode ? 'Payroll updated successfully!' : 'Payroll saved successfully!');
                    location.reload();
                }
            } else {
                if (typeof Toast !== 'undefined') {
                    Toast.fire({
                        icon: 'error',
                        title: data.message || 'Error saving payroll'
                    });
                } else {
                    alert(data.message || 'Error saving payroll');
                }
                btn.disabled = false;
            }
        });
    }

    // Reset form when offcanvas is hidden
    function resetPayrollForm() {
        document.getElementById('payrollIdForUpdate').value = '';
        document.getElementById('isEditMode').value = 'false';
        document.getElementById('employeeSelect').value = '';
        document.getElementById('employeeSelectBtn').innerText = 'Select';
        document.getElementById('monthSelect').value = new Date().toISOString().substring(0, 7);
        document.getElementById('inputPayableDays').value = '';
        document.getElementById('inputBasic').value = '';
        document.getElementById('inputHRA').value = '';
        document.getElementById('inputConveyance').value = '';
        document.getElementById('inputMedical').value = '';
        document.getElementById('inputPF').value = '';
        document.getElementById('inputESI').value = '';
        document.getElementById('inputOther').value = '';
        document.getElementById('inputDearnessAllowance').value = '';
        document.getElementById('inputVariableEarning').value = '';
        document.getElementById('inputEpf').value = '';
        document.getElementById('inputProfessionalTax').value = '';
        document.getElementById('inputLoanRecovery').value = '';
        document.getElementById('rowDearnessAllowance').style.display = 'none';
        document.getElementById('rowVariableEarning').style.display = 'none';
        document.getElementById('rowEpf').style.display = 'none';
        document.getElementById('rowProfessionalTax').style.display = 'none';
        document.getElementById('rowLoanRecovery').style.display = 'none';
        document.getElementById('overrideCheck').checked = false;
        document.getElementById('overrideAmount').disabled = true;
        document.getElementById('overrideAmount').value = '';

        currentPayrollData = null;
        isManualDays = false;
        isManualPF = false;
        isManualESI = false;
        currentPayrollTotalDays = 0;
        
        document.getElementById('calculationResult').style.display = 'none';
        document.getElementById('noCalculation').style.display = 'block';
    }

    // Add event listener to reset form when offcanvas is hidden
    document.addEventListener('DOMContentLoaded', function() {
        const offcanvasEl = document.getElementById('payrollCalculationOffcanvas');
        if (offcanvasEl) {
            offcanvasEl.addEventListener('hidden.bs.offcanvas', function() {
                resetPayrollForm();
            });
        }
    });
</script>
