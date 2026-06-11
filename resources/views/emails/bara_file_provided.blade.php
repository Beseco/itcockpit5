<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installationsdatei bereitgestellt</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#16a34a;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:rgba(255,255,255,0.75);text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Baramundi · Versionsüberwachung</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">
                                ✓ Installationsdatei bereitgestellt
                            </h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 20px;line-height:1.7;color:#374151;">
                                Die Installationsdatei für das Paket <strong>{{ $package->name }}</strong> ist jetzt
                                im Versionsordner vorhanden und kann von Baramundi verteilt werden.
                            </p>

                            {{-- Versions-Box --}}
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#166534;font-weight:600;margin-bottom:8px;">Paket</div>
                                        <div style="font-size:20px;font-weight:700;color:#166534;margin-bottom:16px;">{{ $package->name }}</div>

                                        <table cellpadding="0" cellspacing="0" style="font-size:13px;color:#166534;width:100%;">
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.7;white-space:nowrap;vertical-align:top;">Version</td>
                                                <td style="font-family:monospace;font-weight:700;font-size:15px;">{{ $version }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.7;white-space:nowrap;vertical-align:top;">Server</td>
                                                <td style="font-family:monospace;">{{ $package->server_name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.7;white-space:nowrap;vertical-align:top;">Pfad</td>
                                                <td style="font-family:monospace;font-size:12px;word-break:break-all;">{{ $package->getUncPath() }}\{{ $version }}\</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.7;white-space:nowrap;vertical-align:top;">Erkannt am</td>
                                                <td>{{ now()->format('d.m.Y H:i') }} Uhr</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;line-height:1.7;color:#374151;font-weight:600;">
                                Die Software-Verteilung kann jetzt greifen.
                            </p>
                            <p style="margin:0;line-height:1.7;color:#6b7280;font-size:13px;">
                                Der Paketüberwachungs-Scan hat festgestellt, dass im Versionsordner mindestens eine
                                Installationsdatei (größer 0 Byte) vorhanden ist. Der Status wurde auf
                                <strong>OK</strong> gesetzt.
                            </p>

                            @if($package->notes)
                            <div style="margin-top:20px;padding:12px 16px;background:#f9fafb;border-left:3px solid #d1d5db;border-radius:0 4px 4px 0;">
                                <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;margin-bottom:4px;">Notizen</div>
                                <div style="font-size:13px;color:#4b5563;">{{ $package->notes }}</div>
                            </div>
                            @endif

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;padding:16px 32px;text-align:center;">
                            <span style="font-size:12px;color:#9ca3af;">IT Cockpit · Baramundi Paketüberwachung · Automatische Benachrichtigung · {{ now()->format('d.m.Y') }}</span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
