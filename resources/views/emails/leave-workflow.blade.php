@php
    $accent = $m['accent'];
    $stateColors = [
        'approved' => ['#dcfce7', '#15803d'],
        'rejected' => ['#fee2e2', '#b91c1c'],
        'unauthorised' => ['#fee2e2', '#b91c1c'],
        'unpaid' => ['#ffedd5', '#9a3412'],
        'on_hold' => ['#fef3c7', '#b45309'],
        'pending' => ['#fef3c7', '#b45309'],
        'bypassed' => ['#f1f5f9', '#475569'],
        'applied' => ['#dbeafe', '#1d4ed8'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $m['subject'] }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#1e293b;">
    <span style="display:none; max-height:0; overflow:hidden; opacity:0;">{{ \Illuminate\Support\Str::limit(strip_tags($m['intro']), 120) }}</span>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color:{{ $accent }}; padding:20px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-size:16px; font-weight:700; color:#ffffff; letter-spacing:.2px;">{{ $m['brand'] }}</td>
                                    <td align="right" style="font-size:12px; color:#ffffff; opacity:.9;">Leave Management</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Status + intro --}}
                    <tr>
                        <td style="padding:28px 28px 8px 28px;">
                            <span style="display:inline-block; padding:5px 12px; border-radius:999px; background-color:{{ $accent }}1a; color:{{ $accent }}; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">{{ $m['badge'] }}</span>
                            <p style="margin:18px 0 6px 0; font-size:15px; font-weight:600;">{{ $m['greeting'] }}</p>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#334155;">{{ $m['intro'] }}</p>
                        </td>
                    </tr>

                    {{-- Leave details --}}
                    <tr>
                        <td style="padding:20px 28px 4px 28px;">
                            <p style="margin:0 0 8px 0; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#64748b;">Leave details</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0; border-radius:8px; border-collapse:separate;">
                                @foreach($m['details'] as $label => $value)
                                    <tr>
                                        <td width="38%" style="padding:9px 14px; font-size:13px; color:#64748b; background-color:#f8fafc; {{ !$loop->last ? 'border-bottom:1px solid #eef2f7;' : '' }}">{{ $label }}</td>
                                        <td style="padding:9px 14px; font-size:13px; font-weight:600; color:#0f172a; {{ !$loop->last ? 'border-bottom:1px solid #eef2f7;' : '' }}">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Reason / message --}}
                    @if($m['reason'] || $m['message'])
                        <tr>
                            <td style="padding:16px 28px 4px 28px;">
                                @if($m['reason'])
                                    <p style="margin:0 0 6px 0; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#64748b;">Reason</p>
                                    <p style="margin:0 0 12px 0; padding:10px 14px; background-color:#f8fafc; border-left:3px solid #cbd5e1; font-size:13px; line-height:1.55; color:#334155; white-space:pre-line;">{{ $m['reason'] }}</p>
                                @endif
                                @if($m['message'])
                                    <p style="margin:0 0 6px 0; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#64748b;">Message from employee</p>
                                    <p style="margin:0; padding:10px 14px; background-color:#f8fafc; border-left:3px solid #cbd5e1; font-size:13px; line-height:1.55; color:#334155; white-space:pre-line;">{{ $m['message'] }}</p>
                                @endif
                            </td>
                        </tr>
                    @endif

                    {{-- Approval trail --}}
                    @if(!empty($m['trail']))
                        <tr>
                            <td style="padding:16px 28px 4px 28px;">
                                <p style="margin:0 0 8px 0; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#64748b;">Timeline</p>
                                @foreach($m['trail'] as $step)
                                    @php [$bg, $fg] = $stateColors[$step['state']] ?? ['#f1f5f9', '#475569']; @endphp
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:8px; border:1px solid #e2e8f0; border-radius:8px; border-collapse:separate;">
                                        <tr>
                                            <td style="padding:10px 14px;">
                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td style="font-size:13px; font-weight:700; color:#0f172a;">{{ $step['level'] }}</td>
                                                        <td align="right">@if($step['status'])<span style="display:inline-block; padding:3px 10px; border-radius:999px; background-color:{{ $bg }}; color:{{ $fg }}; font-size:11px; font-weight:700;">{{ $step['status'] }}</span>@endif</td>
                                                    </tr>
                                                </table>
                                                @if($step['by'] || $step['at'])
                                                    <p style="margin:4px 0 0 0; font-size:12px; color:#64748b;">{{ collect([$step['by'], $step['at']])->filter()->implode(' · ') }}</p>
                                                @endif
                                                @if($step['remarks'])
                                                    <p style="margin:8px 0 0 0; padding:8px 12px; background-color:#f8fafc; border-left:3px solid {{ $fg }}; font-size:13px; line-height:1.5; color:#334155; white-space:pre-line;"><strong>Remarks:</strong> {{ $step['remarks'] }}</p>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @endforeach
                            </td>
                        </tr>
                    @endif

                    {{-- Call to action --}}
                    @if(!empty($m['cta']))
                        <tr>
                            <td align="center" style="padding:20px 28px 28px 28px;">
                                <a href="{{ $m['cta']['url'] }}" style="display:inline-block; padding:12px 26px; background-color:{{ $accent }}; color:#ffffff; text-decoration:none; font-size:14px; font-weight:600; border-radius:8px;">{{ $m['cta']['label'] }}</a>
                                <p style="margin:12px 0 0 0; font-size:11px; color:#94a3b8;">Button not working? Open: <a href="{{ $m['cta']['url'] }}" style="color:#64748b;">{{ $m['cta']['url'] }}</a></p>
                            </td>
                        </tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:16px 28px; background-color:#f8fafc; border-top:1px solid #e2e8f0; font-size:11px; line-height:1.6; color:#94a3b8;">
                            This is an automated message from {{ $m['brand'] }}. Please do not reply to this e-mail — use the HRM portal for any action.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
