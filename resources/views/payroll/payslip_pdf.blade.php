<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payroll->employee->name }}</title>
    <style>
        @page {
            margin: 0.4in;
        }

        body {
            /* Using DejaVu Sans for Rupee symbol support */
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
        }

        /* Top Header Layout */
        .header-top {
            width: 100%;
            margin-bottom: 25px;
        }

        .header-top td {
            border: none !important;
            padding: 0 !important;
        }

        .company-address-box {
            text-align: right;
            font-size: 9px;
            color: #555;
            line-height: 1.3;
        }

        .company-name-header {
            font-size: 11px;
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
        }

        /* Center Title */
        .title-block {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }

        .title-company {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 3px;
        }

        .title-slip {
            font-size: 12px;
            font-weight: bold;
            color: #444;
        }

        /* Tables Grid */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
        }

        .label {
            font-weight: bold;
            color: #475569;
            width: 18%;
            background-color: #fcfcfc;
        }

        .value {
            width: 32%;
            color: #000;
        }

        /* Earnings & Deductions Sections */
        .section-split {
            width: 100%;
            border: 1px solid #e2e8f0;
        }

        .section-split th {
            background-color: #f8fafc;
            color: #1e3a8a;
            font-size: 10px;
            text-align: center;
            padding: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .col-50 {
            width: 50%;
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .inner-table {
            width: 100%;
            margin-bottom: 0;
            border: none;
        }

        .inner-table td {
            border: none;
            border-bottom: 1px solid #f1f5f9;
            padding: 5px 10px;
        }

        .inner-table tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        /* Net Payment Bar */
        .net-payment-bar {
            background-color: #f0f4ff;
            border: 1.5px solid #3858f9;
            padding: 10px 15px;
            text-align: right;
            margin-top: 10px;
            border-radius: 6px;
        }

        .net-payment-text {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
        }

        /* Authorized Signature Table-based Layout */
        .signature-table {
            width: 100%;
            margin-top: 50px;
            border: none !important;
        }

        .signature-table td {
            border: none !important;
            padding: 0 !important;
        }

        .sig-container {
            text-align: center;
            width: 280px;
            float: right;
        }

        .sig-company {
            font-weight: bold;
            margin-bottom: 50px;
            font-size: 11px;
            color: #000;
        }

        .sig-line {
            border-top: 1.5px solid #000;
            padding-top: 6px;
            font-size: 10px;
            font-weight: bold;
            color: #000;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Top Branding Header -->
        <table class="header-top">
            <tr>
                <td style="width: 40%; vertical-align: middle;">
                    <!-- Real Logo Image Optimized -->
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/Warr.png'))) }}"
                        style="width: 140px; height: auto; display: block;">
                </td>
                <td style="width: 60%;">
                    <div class="company-address-box">
                        <div class="company-name-header">Warrgyizmorsch Pvt. Ltd.</div>
                        410, 4th floor, Ashoka palace, <br>
                        Shobhagpura, Udaipur, Rajasthan<br>
                        info@&#8203;warrgyizmorsch.com | https:/&#8203;/warrgyizmorsch.com/<br>
                        +91 9257874994
                    </div>
                </td>
            </tr>
        </table>

        <!-- Summary Title Area -->
        <div class="title-block">
            <div class="title-company">Warrgyizmorsch Pvt. Ltd.</div>
            <div class="title-slip">Salary Slip for {{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}</div>
        </div>

        <!-- Employee Summary Table -->
        <table>
            <tr>
                <td class="label">Employee ID:</td>
                <td class="value">{{ $payroll->employee->id }}</td>
                <td class="label">Name:</td>
                <td class="value">{{ $payroll->employee->name }}</td>
            </tr>
            <tr>
                <td class="label">Bank Name:</td>
                <td class="value">{{ $payroll->employee->bank_name ?? 'SBI' }}</td>
                <td class="label">Designation:</td>
                <td class="value">{{ $payroll->employee->designation }}</td>
            </tr>
            <tr>
                <td class="label">Gross Salary:</td>
                <td class="value">&#8377;{{ number_format($payroll->gross_salary, 2) }}</td>
                <td class="label">Account No.:</td>
                <td class="value">{{ $payroll->employee->account_number ?? '998877665544' }}</td>
            </tr>
            <tr>
                <td class="label">Total Days:</td>
                <td class="value">{{ \Carbon\Carbon::parse($payroll->month)->daysInMonth }}</td>
                <td class="label">Paid Days:</td>
                <td class="value">{{ $payroll->payable_days }}</td>
            </tr>
            <tr>
                <td class="label">PAN No:</td>
                <td class="value">{{ $payroll->employee->pan_number ?? 'ABCDE1234F' }}</td>
                <td class="label">Leaves:</td>
                <td class="value">{{ \Carbon\Carbon::parse($payroll->month)->daysInMonth - $payroll->payable_days }}
                </td>
            </tr>
            <tr>
                <td class="label">Month:</td>
                <td class="value">{{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}</td>
                <td class="label">Department:</td>
                <td class="value">{{ $payroll->employee->departmentRef->name ?? '—' }}</td>
            </tr>
        </table>

        <!-- Earnings & Deductions Split -->
        <table class="section-split">
            <thead>
                <tr>
                    <th style="border-right: 1px solid #e2e8f0;">Earnings</th>
                    <th>Deductions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-50" style="border-right: 1px solid #e2e8f0;">
                        <table class="inner-table">
                            <tr>
                                <td>Basic Pay</td>
                                <td class="text-right">&#8377;{{ number_format($payroll->basic_salary, 2) }}</td>
                            </tr>
                            @if(($payroll->dearness_allowance ?? 0) > 0)
                                <tr>
                                    <td>Dearness Allowance</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->dearness_allowance, 2) }}</td>
                                </tr>
                            @endif
                            @if($payroll->hra > 0)
                                <tr>
                                    <td>HRA</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->hra, 2) }}</td>
                                </tr>
                            @endif
                            @if($payroll->medical_allowance > 0)
                                <tr>
                                    <td>Medical Allowance</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->medical_allowance, 2) }}</td>
                                </tr>
                            @endif
                            @if($payroll->other_allowance > 0)
                                <tr>
                                    <td>Other Allowances</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->other_allowance, 2) }}</td>
                                </tr>
                            @endif
                            @if($payroll->conveyance_allowance > 0)
                                <tr>
                                    <td>Conveyance</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->conveyance_allowance, 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if(($payroll->variable_earning ?? 0) > 0)
                                <tr>
                                    <td>Variable Earning</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->variable_earning, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="bold">
                                <td style="border-top: 1px solid #e2e8f0;">Total Earnings</td>
                                <td class="text-right" style="border-top: 1px solid #e2e8f0;">
                                    &#8377;{{ number_format($payroll->gross_salary, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                    <td class="col-50">
                        <table class="inner-table">
                            @php $hasDeductions = false; @endphp

                            @if($payroll->pf_deduction > 0)
                                @php $hasDeductions = true; @endphp
                                <tr>
                                    <td>PF Deduction</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->pf_deduction, 2) }}</td>
                                </tr>
                            @endif

                            @if($payroll->esi_deduction > 0)
                                @php $hasDeductions = true; @endphp
                                <tr>
                                    <td>ESI / ECS</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->esi_deduction, 2) }}</td>
                                </tr>
                            @endif

                            @if(($payroll->epf_deduction ?? 0) > 0)
                                @php $hasDeductions = true; @endphp
                                <tr>
                                    <td>EPF</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->epf_deduction, 2) }}</td>
                                </tr>
                            @endif

                            @if(($payroll->professional_tax_deduction ?? 0) > 0)
                                @php $hasDeductions = true; @endphp
                                <tr>
                                    <td>Professional Tax</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->professional_tax_deduction, 2) }}</td>
                                </tr>
                            @endif

                            @if(($payroll->loan_recovery_deduction ?? 0) > 0)
                                @php $hasDeductions = true; @endphp
                                <tr>
                                    <td>Loan Recovery</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->loan_recovery_deduction, 2) }}</td>
                                </tr>
                            @endif

                            @if($payroll->other_deduction > 0)
                                @php $hasDeductions = true; @endphp
                                <tr>
                                    <td>Other Cuts</td>
                                    <td class="text-right">&#8377;{{ number_format($payroll->other_deduction, 2) }}</td>
                                </tr>
                            @endif

                            @if(!$hasDeductions)
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted"
                                        style="font-size: 9px; opacity: 0.5;">No deductions applicable</td>
                                </tr>
                            @endif

                            <tr class="bold">
                                <td style="border-top: 1px solid #e2e8f0;">Total Deduction</td>
                                <td class="text-right" style="border-top: 1px solid #e2e8f0;">
                                    &#8377;{{ number_format($payroll->deductions, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- High-contrast Net Pay Callout -->
        <div class="net-payment-bar">
            <span class="net-payment-text">Net Payable Amount:
                &#8377;{{ number_format($payroll->net_salary, 2) }}</span>
        </div>

        <div style="margin-top: 30px; font-size: 8px; color: #94a3b8; font-style: italic;">
            * This is a computer-generated document and does not require a physical seal. Net salary payable is subject
            to applicable deductions.
        </div>

        <!-- Authorized Signature Section -->
        <table class="signature-table">
            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%;">
                    <div class="sig-container">
                        <div class="sig-company">Warrgyizmorsch Pvt. Ltd.</div>
                        <div class="sig-line">Authorized Signatory</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>