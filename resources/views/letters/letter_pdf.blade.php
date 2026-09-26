<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 0.5in;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #222;
            margin: 0;
            padding: 0;
        }

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

        .title-block {
            text-align: center;
            margin-bottom: 22px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }

        .title-slip {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
        }

        .letter-body {
            text-align: justify;
        }

        .letter-body p {
            margin: 0 0 12px 0;
        }

        .signature-table {
            width: 100%;
            margin-top: 60px;
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
    <table class="header-top">
        <tr>
            <td style="width: 40%; vertical-align: middle;">
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

    <div class="title-block">
        <div class="title-slip">{{ $title }}</div>
    </div>

    <div class="letter-body">
        {!! $content !!}
    </div>

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
</body>

</html>
