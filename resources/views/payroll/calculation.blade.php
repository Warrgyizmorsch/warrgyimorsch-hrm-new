@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0" style="background: #f8fafc; min-height: 100vh; font-family: 'Inter', sans-serif;">
        <!-- Top Header -->
        <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-white border-bottom shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <h5 class="fw-bold mb-0 me-3" style="color: #334155;">Payroll Module</h5>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted small">Home</a></li>
                        <li class="breadcrumb-item active small fw-bold" style="color: #3858f9;" aria-current="page">Payroll
                            Calculation</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="px-4">
            <!-- Calculation Filter Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: white;">
                <div class="card-header bg-white border-0 px-4 py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">Calculation Settings</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted mb-2">Employee Name</label>
                            <select id="employeeSelect" class="form-select border-0 bg-light py-2 px-3 shadow-none fw-bold"
                                style="border-radius: 8px; height: 45px;">
                                <option value="">Select Employee</option>
                                @foreach (\App\Models\Employee::all() as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted mb-2">Month</label>
                            <input type="month" id="monthSelect"
                                class="form-control border-0 bg-light py-2 px-3 shadow-none fw-bold"
                                value="{{ date('Y-m') }}" style="border-radius: 8px; height: 45px;">
                        </div>
                        <div class="col-md-2">
                            <button
                                class="btn btn-primary w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm"
                                onclick="calculatePayroll()"
                                style="background: #3858f9; border: none; height: 45px; border-radius: 8px;">
                                <i class="bi bi-calculator"></i> CALCULATE
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calculation Result -->
            <div id="calculationResult" style="display: none;">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-4"
                            style="border-radius: 12px; background: white; overflow: hidden;">
                            <div class="card-header bg-white border-bottom px-4 py-3">
                                <h6 class="fw-bold mb-0 text-dark">Salary Components</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h6 class="small fw-bold text-primary text-uppercase mb-3"
                                            style="letter-spacing: 0.5px;">Earnings</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless align-middle mb-0">
                                                    <tbody class="text-dark fw-bold">
                                                        <!-- <select id="calcType" class="form-control mb-2">
                                                            <option value="per_day">Per Day</option>
                                                            <option value="monthly">Monthly</option>
                                                            <option value="hourly">Hourly</option>
                                                        </select> -->

                                                           <tr>
                                                                <td class="ps-0 py-2 text-muted fw-normal">Payable Days</td>
                                                                <td><input type="number" step="0.01" id="inputPayableDays" class="form-control">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-0 py-2 text-muted fw-normal">Basic Salary</td>
                                                            <td><input type="number" id="inputBasic" class="form-control"></td>
                                                        </tr>

                                                        <tr>
                                                            <td class="ps-0 py-2 text-muted fw-normal">HRA</td>
                                                            <td><input type="number" id="inputHRA" class="form-control"></td>
                                                        </tr>

                                                        <tr>
                                                            <td class="ps-0 py-2 text-muted fw-normal">Conveyance</td>
                                                            <td><input type="number" id="inputConveyance" class="form-control"></td>
                                                        </tr>

                                                        <tr>
                                                            <td class="ps-0 py-2 text-muted fw-normal">Medical</td>
                                                            <td><input type="number" id="inputMedical" class="form-control"></td>
                                                        </tr>

                                                     

                                                        <tr>
                                                            <td class="ps-0 py-2 text-muted fw-normal">Override Salary</td>
                                                            <td>
                                                                <input type="checkbox" id="overrideCheck"> Enable
                                                                <input type="number" id="overrideAmount" class="form-control mt-1" disabled>
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
                                    <div class="col-md-6">
                                        <h6 class="small fw-bold text-danger text-uppercase mb-3"
                                            style="letter-spacing: 0.5px;">Deductions</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless align-middle mb-0">
                                                <tbody class="text-dark fw-bold">
                                                    <tr>
                                                        <td>PF</td>
                                                        <td><input type="number" id="inputPF" class="form-control"></td>
                                                    </tr>

                                                    <tr>
                                                        <td>ESI</td>
                                                        <td><input type="number" id="inputESI" class="form-control"></td>
                                                    </tr>

                                                    <tr>
                                                        <td>Other</td>
                                                        <td><input type="number" id="inputOther" class="form-control"></td>
                                                    </tr>
                                                   
                                                    <tr class="border-top">
                                                        <td class="ps-0 py-3 text-danger">Total Deductions</td>
                                                        <td class="text-end py-3 text-danger fs-5"
                                                            id="tableTotalDeductions">₹ 0.00</td>
                                                    </tr>
                                                  
                                                </tbody>
                                            </table>
                                        </div>
                                        <div id="overtime_box" style="color: #3554ea;font-weight: 600;"></div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: white;">
                            <div class="card-header bg-white border-bottom px-4 py-3">
                                <h6 class="fw-bold mb-0 text-dark">Net Salary</h6>
                            </div>
                            <div class="card-body p-4 text-center">
                                <div class="p-4 rounded-4 mb-4"
                                    style="background: linear-gradient(135deg, #3858f9 0%, #1e3a8a 100%);">
                                    <div class="text-white opacity-75 small mb-1">Take Home Pay</div>
                                    <div class="display-6 fw-bold text-white" id="tableNetSalary">₹ 0.00</div>
                                </div>

                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="text-muted small mb-1">Payable Days</div>
                                            <div class="fw-bold fs-5" id="resultPayableDays">0</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="text-muted small mb-1">Unpaid Days</div>
                                            <div class="fw-bold fs-5 text-danger" id="resultUnpaidDays">0</div>
                                        </div>
                                    </div>
                                    <div class="col-4 mt-2">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="text-muted small mb-1">Month</div>
                                            <div class="fw-bold fs-6" id="resultMonth">--</div>
                                        </div>
                                    </div>
                                    <div class="col-8 mt-2">
                                        <div class="p-3 bg-soft-danger rounded-3 border border-danger border-opacity-10">
                                            <div class="text-danger small fw-bold text-uppercase mb-1"
                                                style="font-size: 10px;">Total Salary Cut</div>
                                            <div class="fw-bold fs-5 text-danger" id="resultSalaryLoss">₹ 0.00</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary py-3 fw-bold shadow-sm"
                                        style="background: #3858f9; border: none; border-radius: 12px;" onclick="savePayroll(this)">
                                        <i class="bi bi-check2-circle me-2 fs-5"></i> SUBMIT PAYROLL
                                    </button>
                                    <button class="btn btn-soft-danger py-2 fw-bold d-none" id="downloadAfterSave"
                                        style="border-radius: 10px;" onclick="downloadCurrentPdf()">
                                        <i class="bi bi-file-earmark-pdf me-2"></i> DOWNLOAD PAYSLIP (PDF)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="noCalculation" class="text-center py-5">
                <div class="py-5" style="border: 2px dashed #e2e8f0; border-radius: 16px; background: #f8fafc;">
                    <i class="bi bi-calculator text-primary" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-dark mt-3 fw-bold fs-5 mb-1">Payroll Ready to Generate</p>
                    <p class="text-muted small">Select an employee and month above to start the calculation.</p>
                </div>
            </div>
        </div>
    </div>

<script>
    let isManualDays = false;
    let currentPayrollData = null;
    let currentPayrollTotalDays = 0;
    let isManualPF = false;
    let isManualESI = false;
    document.addEventListener('input', recalculate);
    // document.getElementById('calcType').addEventListener('change', recalculate);

    document.getElementById('inputPayableDays').addEventListener('input', function () {
        isManualDays = true;
    });
    document.getElementById('inputPF').addEventListener('input', function () {
        isManualPF = true;
    });
    document.getElementById('inputESI').addEventListener('input', function () {
        isManualESI = true;
    });

    document.getElementById('overrideCheck').addEventListener('change', function () {
        document.getElementById('overrideAmount').disabled = !this.checked;
    });

function recalculate(event) {
    const sourceId = event?.target?.id || '';

    let basic = parseFloat(document.getElementById('inputBasic').value) || 0;
    let hra = parseFloat(document.getElementById('inputHRA').value) || 0;
    let conv = parseFloat(document.getElementById('inputConveyance').value) || 0;
    let med = parseFloat(document.getElementById('inputMedical').value) || 0;
    let otherAllowance = parseFloat(currentPayrollData?.other_allowance) || 0;

    let fullSalary = basic + hra + conv + med + otherAllowance;
    let backendPerDaySalary = Number(currentPayrollData?.perdaysalary || 0);
    let totalDays = Number(currentPayrollTotalDays || currentPayrollData?.total_days || 0);
    if (!totalDays && backendPerDaySalary > 0 && fullSalary > 0) {
        totalDays = Math.round(fullSalary / backendPerDaySalary);
    }
    totalDays = totalDays || 30;

    let payableDays = parseFloat(document.getElementById('inputPayableDays').value) || 0;
    payableDays = Math.min(payableDays, totalDays); // ✅ safety

    // let calcType = document.getElementById('calcType').value;

    let gross = 0;

    // ✅ PER DAY VALUES
    let perDayBasic = basic / totalDays;
    let perDayHRA = hra / totalDays;
    let perDayConv = conv / totalDays;
    let perDayMed = med / totalDays;

    gross = (fullSalary / totalDays) * payableDays; 

    // if (calcType === 'monthly') {
    //     gross = (fullSalary / totalDays) * payableDays; 
    // } 
    // else if (calcType === 'per_day') {
    //     gross = (perDayBasic * payableDays) +
    //             (perDayHRA * payableDays) +
    //             (perDayConv * payableDays) +
    //             (perDayMed * payableDays);
    // } 
    // else if (calcType === 'hourly') {
    //     let totalHours = payableDays * 8;
    //     let perHour = fullSalary / (totalDays * 8);
    //     gross = perHour * totalHours;
    // }

    // ✅ Override
    if (document.getElementById('overrideCheck').checked) {
        let override = parseFloat(document.getElementById('overrideAmount').value);
        if (!isNaN(override) && override > 0) {
            gross = override;
        }
    }

    // ✅ UI Update
    document.getElementById('tableGrossSalary').innerText = '₹ ' + gross.toFixed(2);

    let earnedBasic = totalDays > 0 ? (basic / totalDays) * payableDays : 0;
    let autoPF = currentPayrollData?.pf_enabled ? earnedBasic * 0.12 : 0;
    let autoESI = (currentPayrollData?.esi_enabled && gross <= 21000) ? gross * 0.0075 : 0;

    if (sourceId !== 'inputPF') {
        document.getElementById('inputPF').value = autoPF.toFixed(2);
    }

    if (sourceId !== 'inputESI') {
        document.getElementById('inputESI').value = autoESI.toFixed(2);
    }

    let pf = parseFloat(document.getElementById('inputPF').value) || 0;
    let esi = parseFloat(document.getElementById('inputESI').value) || 0;
    let other = parseFloat(document.getElementById('inputOther').value) || 0;

    let totalDeduction = pf + esi + other;
    let net = gross - totalDeduction;

    document.getElementById('tableTotalDeductions').innerText = '₹ ' + totalDeduction.toFixed(2);
    document.getElementById('tableNetSalary').innerText = '₹ ' + net.toFixed(2);

    // ✅ RIGHT PANEL SYNC
    document.getElementById('resultPayableDays').innerText = payableDays;

    let unpaidDays = totalDays - payableDays;
    document.getElementById('resultUnpaidDays').innerText = unpaidDays.toFixed(2);

    let salaryLoss = fullSalary - gross;
    document.getElementById('resultSalaryLoss').innerText = '₹ ' + salaryLoss.toFixed(2);
}

    // =========================
    // CALCULATE API
    // =========================
    function calculatePayroll() {
        isManualDays = false;
        isManualPF = false;
        isManualESI = false;
        const month = document.getElementById('monthSelect').value;
        const employeeId = document.getElementById('employeeSelect').value;

        if (!month || !employeeId) {
            alert('Please select both month and employee');
            return;
        }

        const noCalc = document.getElementById('noCalculation');
        noCalc.innerHTML = `
            <div class="py-5 text-center">
                <div class="spinner-border text-primary"></div>
                <p class="mt-3 fw-bold text-primary">Calculating...</p>
            </div>
        `;

        fetch('{{ route("payroll.calculate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ month, employee_id: employeeId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentPayrollData = data.payroll;
                currentPayrollTotalDays = Number(data.payroll.total_days || 0);
                // console.log(data.payroll);
                displayPayrollData(data.payroll);

                noCalc.style.display = 'none';
                document.getElementById('calculationResult').style.display = 'block';
            } else {
                throw new Error(data.message || 'Something went wrong');
            }
        })
        .catch(err => {
            noCalc.innerHTML = `
                <div class="py-5 text-center text-danger">
                    <i class="bi bi-exclamation-circle fs-1"></i>
                    <p class="mt-3 fw-bold">${err.message}</p>
                </div>
            `;
        });
    }

    // =========================
    // DISPLAY DATA
    // =========================
    function displayPayrollData(p) {
        const formattedMonth = new Date(p.month + '-01').toLocaleString('en-IN', {
            month: 'short',
            year: 'numeric'
        });

        console.log('PAYROLL RESPONSE:', p);
        console.log('OT Hours:', p.overtime_hours);
        console.log('OT Days:', p.overtime_days);
        currentPayrollTotalDays = Number(p.total_days || currentPayrollTotalDays || 0);
        
        document.getElementById('resultMonth').textContent = formattedMonth;

        // ✅ Only set if NOT manually edited
        if (!isManualDays) {
            document.getElementById('inputPayableDays').value = p.payable_days;
        }

        // ALWAYS show backend initially
       if (!isManualDays) {
            document.getElementById('resultPayableDays').textContent = p.payable_days;
        }
        document.getElementById('resultUnpaidDays').textContent = p.unpaid_days;

        document.getElementById('resultSalaryLoss').textContent = '₹ ' + f(p.salary_loss);

        document.getElementById('inputBasic').value = p.basic_salary;
        document.getElementById('inputHRA').value = p.hra;
        document.getElementById('inputConveyance').value = p.conveyance_allowance;
        document.getElementById('inputMedical').value = p.medical_allowance;

        isManualPF = false;
        isManualESI = false;
        document.getElementById('inputPF').value = p.pf_deduction;
        document.getElementById('inputESI').value = p.esi_deduction;
        document.getElementById('inputOther').value = p.other_deduction || 0;
        
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
           

    // =========================
    // SAVE PAYROLL
    // =========================
    function savePayroll(btn) {

        if (!currentPayrollData) return;

        const originalBtnText = btn.innerHTML;
        btn.innerHTML = 'Saving...';
        btn.disabled = true;

        fetch('{{ route("payroll.store") }}', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
            },
            body: JSON.stringify({
                ...currentPayrollData,

                basic_salary: document.getElementById('inputBasic').value,
                hra: document.getElementById('inputHRA').value,
                conveyance_allowance: document.getElementById('inputConveyance').value,
                medical_allowance: document.getElementById('inputMedical').value,

                payable_days: document.getElementById('inputPayableDays').value,

                pf_deduction: document.getElementById('inputPF').value,
                esi_deduction: document.getElementById('inputESI').value,
                other_deduction: document.getElementById('inputOther').value,
                deductions: (
                    (parseFloat(document.getElementById('inputPF').value) || 0) +
                    (parseFloat(document.getElementById('inputESI').value) || 0) +
                    (parseFloat(document.getElementById('inputOther').value) || 0)
                ).toFixed(2),

                net_salary: document.getElementById('tableNetSalary').innerText.replace(/[₹,]/g,''),
                gross_salary: document.getElementById('tableGrossSalary').innerText.replace(/[₹,]/g,'')
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Payroll saved successfully!');
                window.location.href = '{{ route("payroll.index") }}';
            } else {
                alert(data.message || 'Error saving payroll');
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
            }
        })
        .catch(() => {
            alert('Something went wrong');
            btn.innerHTML = originalBtnText;
            btn.disabled = false;
        });
    }

    function f(n) {
        return parseFloat(n || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
</script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        .breadcrumb-item+.breadcrumb-item::before {
            content: ">";
            color: #94a3b8;
        }

        .display-6 {
            font-size: 2.25rem;
        }
    </style>
@endsection
