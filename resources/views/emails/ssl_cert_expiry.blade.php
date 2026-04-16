<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL-Zertifikat läuft ab</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;">

                    {{-- Header --}}
                    @php
                        $headerBg = $daysRemaining <= 7 ? '#dc2626' : ($daysRemaining <= 14 ? '#d97706' : '#4f46e5');
                        $label    = $daysRemaining <= 7 ? 'Dringende Warnung' : 'Ablaufwarnung';
                    @endphp
                    <tr>
                        <td style="background:{{ $headerBg }};border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:rgba(255,255,255,0.75);text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · SSL-Zertifikate · {{ $label }}</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">
                                @if($daysRemaining <= 1)
                                    Zertifikat läuft morgen ab!
                                @else
                                    Zertifikat läuft in {{ $daysRemaining }} Tagen ab
                                @endif
                            </h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 20px;line-height:1.7;color:#374151;">
                                Das folgende SSL-Zertifikat läuft
                                @if($daysRemaining <= 1)
                                    <strong>morgen</strong>
                                @else
                                    in <strong>{{ $daysRemaining }} Tagen</strong> (am <strong>{{ $cert->valid_to->format('d.m.Y') }}</strong>)
                                @endif
                                ab. Bitte rechtzeitig erneuern.
                            </p>

                            {{-- Zertifikats-Box --}}
                            @php
                                $boxBg     = $daysRemaining <= 7 ? '#fef2f2' : ($daysRemaining <= 14 ? '#fffbeb' : '#eef2ff');
                                $boxBorder = $daysRemaining <= 7 ? '#fca5a5' : ($daysRemaining <= 14 ? '#fcd34d' : '#a5b4fc');
                                $boxText   = $daysRemaining <= 7 ? '#991b1b' : ($daysRemaining <= 14 ? '#92400e' : '#3730a3');
                            @endphp
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:{{ $boxBg }};border:1px solid {{ $boxBorder }};border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:{{ $boxText }};font-weight:600;margin-bottom:8px;">Zertifikat</div>
                                        <div style="font-size:18px;font-weight:700;color:{{ $boxText }};margin-bottom:12px;">{{ $cert->name }}</div>

                                        <table cellpadding="0" cellspacing="0" style="font-size:13px;color:{{ $boxText }};">
                                            @if($cert->subject_cn)
                                            <tr>
                                                <td style="padding:2px 16px 2px 0;opacity:0.7;white-space:nowrap;">Common Name</td>
                                                <td style="font-family:monospace;font-weight:600;">{{ $cert->subject_cn }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:2px 16px 2px 0;opacity:0.7;white-space:nowrap;">Gültig bis</td>
                                                <td style="font-weight:600;">{{ $cert->valid_to?->format('d.m.Y') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:2px 16px 2px 0;opacity:0.7;white-space:nowrap;">Aussteller</td>
                                                <td>{{ $cert->issuer_cn ?? '—' }}</td>
                                            </tr>
                                            @if($cert->responsibleUser)
                                            <tr>
                                                <td style="padding:2px 16px 2px 0;opacity:0.7;white-space:nowrap;">Verantwortlicher</td>
                                                <td>{{ $cert->responsibleUser->name }}</td>
                                            </tr>
                                            @endif
                                            @if($cert->servers->isNotEmpty())
                                            <tr>
                                                <td style="padding:2px 16px 2px 0;opacity:0.7;white-space:nowrap;vertical-align:top;">Server</td>
                                                <td>{{ $cert->servers->pluck('name')->join(', ') }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($cert->doc_url)
                            <p style="margin:0 0 20px;line-height:1.7;">
                                📄 <a href="{{ $cert->doc_url }}" style="color:#4f46e5;">Zur Dokumentation</a>
                            </p>
                            @endif

                            @if($cert->description)
                            <p style="margin:0 0 20px;line-height:1.7;color:#6b7280;font-size:13px;font-style:italic;">
                                {{ $cert->description }}
                            </p>
                            @endif

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;padding:16px 32px;text-align:center;">
                            <span style="font-size:12px;color:#9ca3af;">IT Cockpit · Automatische Benachrichtigung · {{ now()->format('d.m.Y') }}</span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
