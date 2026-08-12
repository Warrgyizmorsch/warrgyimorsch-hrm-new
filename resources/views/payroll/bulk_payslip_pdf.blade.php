<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bulk Payslips</title>
    <style>
        @page {
            margin: 0.4in;
        }

        body {
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

        .page-break {
            page-break-after: always;
        }

        /* Top Header Layout */
        .header-top {
            width: 100%;
            margin-bottom: 30px;
        }

        .header-top td {
            border: none !important;
            padding: 0 !important;
        }

        .header-logo {
            width: 40%;
            vertical-align: middle;
        }

        .header-logo svg {
            display: block;
        }

        .company-address-box {
            text-align: right;
            font-size: 9px;
            color: #555;
            line-height: 1.3;
        }

        .company-name-header {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
        }

        /* Center Title */
        .title-block {
            text-align: center;
            margin-bottom: 25px;
        }

        .title-company {
            font-size: 15px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 5px;
        }

        .title-slip {
            font-size: 13px;
            font-weight: bold;
            color: #333;
        }

        /* Tables Grid */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 7px 10px;
        }

        .label {
            font-weight: bold;
            color: #475569;
            width: 15%;
            background-color: #fff;
        }

        .value {
            width: 35%;
            color: #000;
        }

        /* Earnings & Deductions Split Layout */
        .section-split {
            width: 100%;
            border: 1px solid #e2e8f0;
        }

        .section-split th {
            background-color: #f8fafc;
            color: #1e3a8a;
            font-size: 11px;
            text-align: center;
            padding: 8px;
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
            padding: 6px 10px;
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

        /* High-contrast Net Pay Callout */
        .net-payment-bar {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 12px;
            text-align: right;
            margin-top: 15px;
            border-radius: 4px;
        }

        .net-payment-text {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
        }

        /* Signature Space */
        .footer-note {
            margin-top: 40px;
            font-size: 8px;
            color: #94a3b8;
        }

        .signature-section {
            margin-top: 40px;
            text-align: right;
        }

        .sig-box {
            display: inline-block;
            text-align: center;
            width: 220px;
        }

        .sig-company {
            font-weight: bold;
            margin-bottom: 45px;
            font-size: 11px;
        }

        .sig-line {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    @foreach($payrolls as $payroll)
        <div class="container {{ !$loop->last ? 'page-break' : '' }}">
            <!-- Top Branding Header -->
            <table class="header-top">
                <tr>
                    <td style="width: 40%; vertical-align: middle;">
                        <!-- Real Logo Image (logo-blue.png) -->
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/Warr.png'))) }}"
                            style="width: 160px; height: auto; display: block;">
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

            <!-- Summary Title area -->
            <div class="title-block">
                <div class="title-company">Warrgyizmorsch Pvt. Ltd.</div>
                <div class="title-slip">Salary Slip for {{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}</div>
            </div>

            <!-- Employee Summary detail -->
            <table>
                <tr>
                    <td class="label">Employee ID:</td>
                    <td class="value">{{ $payroll->employee->id }}</td>
                    <td class="label">Name:</td>
                    <td class="value">{{ $payroll->employee->name }}</td>
                </tr>
                <tr>
                    <td class="label">Bank:</td>
                    <td class="value">{{ $payroll->employee->bank_name ?? 'SBI' }}</td>
                    <td class="label">Designation:</td>
                    <td class="value">{{ $payroll->employee->designation }}</td>
                </tr>
                <tr>
                    <td class="label">Gross Salary:</td>
                    <td class="value">₹{{ number_format($payroll->gross_salary, 2) }}</td>
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
                    <td class="label">PAN:</td>
                    <td class="value">{{ $payroll->employee->pan_number ?? 'ABCDE1234F' }}</td>
                    <td class="label">Leaves:</td>
                    <td class="value">{{ \Carbon\Carbon::parse($payroll->month)->daysInMonth - $payroll->payable_days }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Processing Month:</td>
                    <td class="value">{{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}</td>
                    <td class="label">Department:</td>
                    <td class="value">{{ $payroll->employee->department ?? '—' }}</td>
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
                                    <td>Basic</td>
                                    <td class="text-right">₹{{ number_format($payroll->basic_salary, 2) }}</td>
                                </tr>
                                @if(($payroll->dearness_allowance ?? 0) > 0)
                                    <tr>
                                        <td>Dearness Allowance</td>
                                        <td class="text-right">₹{{ number_format($payroll->dearness_allowance, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>HRA</td>
                                    <td class="text-right">₹{{ number_format($payroll->hra, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Medical Allowance</td>
                                    <td class="text-right">₹{{ number_format($payroll->medical_allowance, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Other Allowances</td>
                                    <td class="text-right">₹{{ number_format($payroll->other_allowance, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Conveyance Allowance</td>
                                    <td class="text-right">₹{{ number_format($payroll->conveyance_allowance, 2) }}</td>
                                </tr>
                                @if(($payroll->variable_earning ?? 0) > 0)
                                    <tr>
                                        <td>Variable Earning</td>
                                        <td class="text-right">₹{{ number_format($payroll->variable_earning, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="bold">
                                    <td style="border-top: 1px solid #e2e8f0;">Total Earnings</td>
                                    <td class="text-right" style="border-top: 1px solid #e2e8f0;">
                                        ₹{{ number_format($payroll->gross_salary, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                        <td class="col-50">
                            <table class="inner-table">
                                <tr>
                                    <td>TDS</td>
                                    <td class="text-right">₹0.00</td>
                                </tr>
                                <tr>
                                    <td>PF</td>
                                    <td class="text-right">₹{{ number_format($payroll->pf_deduction, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>ESI / ECS</td>
                                    <td class="text-right">₹{{ number_format($payroll->esi_deduction, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Other Cuts</td>
                                    <td class="text-right">₹{{ number_format($payroll->other_deduction, 2) }}</td>
                                </tr>
                                @if(($payroll->epf_deduction ?? 0) > 0)
                                    <tr>
                                        <td>EPF</td>
                                        <td class="text-right">₹{{ number_format($payroll->epf_deduction, 2) }}</td>
                                    </tr>
                                @endif
                                @if(($payroll->professional_tax_deduction ?? 0) > 0)
                                    <tr>
                                        <td>Professional Tax</td>
                                        <td class="text-right">₹{{ number_format($payroll->professional_tax_deduction, 2) }}</td>
                                    </tr>
                                @endif
                                @if(($payroll->loan_recovery_deduction ?? 0) > 0)
                                    <tr>
                                        <td>Loan Recovery</td>
                                        <td class="text-right">₹{{ number_format($payroll->loan_recovery_deduction, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>&nbsp;</td>
                                    <td class="text-right">&nbsp;</td>
                                </tr>
                                <tr class="bold">
                                    <td style="border-top: 1px solid #e2e8f0;">Total Deduction</td>
                                    <td class="text-right" style="border-top: 1px solid #e2e8f0;">
                                        ₹{{ number_format($payroll->deductions, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- High-contrast Net Pay Callout -->
            <div class="net-payment-bar">
                <span class="net-payment-text">Net Payment: ₹{{ number_format($payroll->net_salary, 2) }}</span>
            </div>

            <div class="footer-note">
                * Net salary payable is subject to deductions as per Income Tax Law.
            </div>

            <!-- Authorized Signature space -->
            <div class="signature-section">
                <div class="sig-box">
                    <div class="sig-company">Warrgyizmorsch Pvt. Ltd.</div>
                    <div class="sig-line">Authorized Signatory</div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>