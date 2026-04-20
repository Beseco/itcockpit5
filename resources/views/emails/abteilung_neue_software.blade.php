<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title>Software-Vorschlag: {{ $abteilung->anzeigename }}</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

    <tr><td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:22px 30px;">
        <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Software-Vorschlag</span>
        <h1 style="margin:6px 0 0;font-size:18px;font-weight:700;color:#ffffff;">{{ $abteilung->anzeigename }}</h1>
        <p style="margin:4px 0 0;font-size:12px;color:#c7d2fe;">{{ $apps->count() }} neue App(s) vorgeschlagen</p>
    </td></tr>

    <tr><td style="background:#ffffff;padding:28px 30px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

        <p style="margin:0 0 20px;line-height:1.7;color:#374151;">
            Im Rahmen der Abteilungsrevision für <strong>{{ $abteilung->anzeigename }}</strong>
            wurden folgende noch nicht erfasste Applikationen vorgeschlagen:
        </p>

        <table width="100%" cellpadding="0" cellspacing="0"
               style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;margin:0 0 20px;">
            <tr style="background:#f9fafb;">
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Name</th>
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Einsatzzweck</th>
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Hersteller</th>
            </tr>
            @foreach($apps as $app)
            <tr>
                <td style="padding:8px 12px;font-size:13px;font-weight:600;color:#1f2937;border-bottom:1px solid #f3f4f6;">{{ $app['name'] }}</td>
                <td style="padding:8px 12px;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;">{{ $app['einsatzzweck'] ?? '—' }}</td>
                <td style="padding:8px 12px;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;">{{ $app['hersteller'] ?? '—' }}</td>
            </tr>
            @endforeach
        </table>

        <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
            Bitte prüfen Sie, ob die genannte Software in das IT Cockpit aufgenommen werden soll.
        </p>

    </td></tr>

    <tr><td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;padding:14px 30px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="font-size:11px;color:#9ca3af;">Automatisch generiert vom <strong style="color:#6b7280;">IT Cockpit</strong></td>
                <td align="right" style="font-size:11px;color:#d1d5db;">{{ now()->format('d.m.Y H:i') }} Uhr</td>
            </tr>
        </table>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
