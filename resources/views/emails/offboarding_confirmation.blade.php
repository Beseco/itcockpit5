<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bestätigung Ausscheiden</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    <tr>
                        <td style="background:#dc2626;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:#fecaca;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Mitarbeiter-Offboarding</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">
                                Bestätigung zum Ausscheiden aus dem Dienstverhältnis
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#1f2937;">
                                Guten Tag <strong>{{ $record->voller_name }}</strong>,
                            </p>
                            <p style="margin:0 0 16px;line-height:1.7;color:#374151;">
                                Im Zusammenhang mit Ihrem Ausscheiden aus dem Dienstverhältnis am
                                <strong>{{ $record->datum_ausscheiden->format('d.m.Y') }}</strong>
                                wird Ihre digitale Bestätigung benötigt.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:16px;margin:0 0 24px;">
                                <tr><td style="padding:4px 0;font-size:13px;color:#6b7280;">Mitarbeiter/in</td>
                                    <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ $record->voller_name }}</td></tr>
                                @if($record->personalnummer)
                                <tr><td style="padding:4px 0;font-size:13px;color:#6b7280;">Personalnummer</td>
                                    <td style="padding:4px 0;font-size:13px;color:#111827;">{{ $record->personalnummer }}</td></tr>
                                @endif
                                <tr><td style="padding:4px 0;font-size:13px;color:#6b7280;">Abteilung</td>
                                    <td style="padding:4px 0;font-size:13px;color:#111827;">{{ $record->abteilung ?? '—' }}</td></tr>
                                <tr><td style="padding:4px 0;font-size:13px;color:#6b7280;">Ausscheiden am</td>
                                    <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ $record->datum_ausscheiden->format('d.m.Y') }}</td></tr>
                            </table>

                            <p style="margin:0 0 24px;line-height:1.7;color:#374151;">
                                Bitte klicken Sie auf den folgenden Link um die Bestätigung digital zu unterzeichnen:
                            </p>

                            <table cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td style="background:#dc2626;border-radius:6px;padding:14px 28px;">
                                        <a href="{{ $confirmUrl }}"
                                           style="color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;">
                                            Jetzt digital bestätigen →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;font-size:12px;color:#9ca3af;">
                                Falls der Button nicht funktioniert, kopieren Sie diesen Link in Ihren Browser:
                            </p>
                            <p style="margin:0 0 24px;font-size:12px;color:#6b7280;word-break:break-all;">
                                <a href="{{ $confirmUrl }}" style="color:#dc2626;">{{ $confirmUrl }}</a>
                            </p>

                            <p style="margin:0;font-size:12px;color:#9ca3af;border-top:1px solid #f3f4f6;padding-top:16px;">
                                Dieser Link ist personalisiert und nach Abschluss der Bestätigung nicht mehr gültig.
                                Bei Fragen wenden Sie sich an Ihre IT-Abteilung.
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:0;border-radius:0 0 8px 8px;padding:16px 32px;text-align:center;">
                            <span style="font-size:12px;color:#9ca3af;">IT Cockpit · Landratsamt Freising</span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
