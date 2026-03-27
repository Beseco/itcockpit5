<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title>Offboarding bestätigt</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                <tr>
                    <td style="background:#16a34a;border-radius:8px 8px 0 0;padding:24px 32px;">
                        <span style="font-size:11px;color:#bbf7d0;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Offboarding</span>
                        <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;">Bestätigung eingegangen</h1>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">
                        <p style="margin:0 0 16px;font-size:15px;">Hallo <strong>{{ $record->anleger_name }}</strong>,</p>
                        <p style="margin:0 0 16px;line-height:1.7;">
                            <strong>{{ $record->voller_name }}</strong> hat die Offboarding-Bestätigung digital unterzeichnet.
                        </p>
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:16px;margin:0 0 24px;">
                            <tr><td style="padding:4px 0;font-size:13px;color:#6b7280;">Name</td>
                                <td style="font-size:13px;font-weight:600;">{{ $record->voller_name }}</td></tr>
                            <tr><td style="padding:4px 0;font-size:13px;color:#6b7280;">Unterzeichnet als</td>
                                <td style="font-size:13px;">{{ $record->bestaetigung_name }}</td></tr>
                            <tr><td style="padding:4px 0;font-size:13px;color:#6b7280;">Zeitpunkt</td>
                                <td style="font-size:13px;">{{ $record->bestaetigung_erhalten_at->format('d.m.Y H:i') }} Uhr</td></tr>
                            <tr><td style="padding:4px 0;font-size:13px;color:#6b7280;">IP-Adresse</td>
                                <td style="font-size:13px;">{{ $record->bestaetigung_ip }}</td></tr>
                        </table>
                        <p style="margin:0;font-size:12px;color:#9ca3af;border-top:1px solid #f3f4f6;padding-top:16px;">
                            Der nächste Schritt ist die Löschung der Benutzerkonten. Bitte markieren Sie den Vorgang im IT Cockpit als abgeschlossen.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:0;border-radius:0 0 8px 8px;padding:16px 32px;text-align:center;">
                        <span style="font-size:12px;color:#9ca3af;">IT Cockpit · Landratsamt Freising</span>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
