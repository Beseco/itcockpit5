<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f2f5;padding:40px 16px;">
<tr><td align="center">

    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        {{-- Header --}}
        <tr>
            <td style="background:linear-gradient(135deg,#4338ca 0%,#6366f1 100%);padding:36px 40px;border-radius:10px 10px 0 0;">
                <p style="margin:0 0 4px 0;color:#c7d2fe;font-size:13px;letter-spacing:1px;text-transform:uppercase;">IT-Abteilung · Kreis Freising</p>
                <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;line-height:1.3;">{{ $mailSubject }}</h1>
            </td>
        </tr>

        {{-- Body --}}
        <tr>
            <td style="background:#ffffff;padding:36px 40px;color:#374151;font-size:15px;line-height:1.8;">
                {!! nl2br(e($mailBody)) !!}
            </td>
        </tr>

        {{-- Hinweis-Box --}}
        <tr>
            <td style="background:#fffbeb;padding:20px 40px;border-top:1px solid #fde68a;border-bottom:1px solid #fde68a;">
                <p style="margin:0;color:#92400e;font-size:13px;line-height:1.6;">
                    <strong>⚠ Sicherheitshinweis:</strong> Bitte ändern Sie Ihr Passwort beim ersten Anmelden.
                    Dieses E-Mail wurde automatisch erstellt – bitte antworten Sie nicht darauf.
                    Bei Fragen wenden Sie sich direkt an die IT-Abteilung.
                </p>
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="background:#f9fafb;padding:20px 40px;border-radius:0 0 10px 10px;border-top:1px solid #e5e7eb;">
                <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">
                    IT Cockpit · Landratsamt Freising · {{ now()->format('d.m.Y') }}
                </p>
            </td>
        </tr>

    </table>

</td></tr>
</table>

</body>
</html>
