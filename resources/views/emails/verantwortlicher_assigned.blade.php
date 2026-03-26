<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neue Zuordnung als Verfahrensverantwortliche/r</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Neue Zuordnung</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">Verfahrensverantwortliche/r</h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#1f2937;">
                                Guten Tag <strong>{{ $adUser->anzeigenameOrName }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;line-height:1.7;color:#374151;">
                                Sie wurden als <strong>Verfahrensverantwortliche/r</strong> für die folgende Applikation eingetragen:
                            </p>

                            {{-- App-Details --}}
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;margin:0 0 24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;width:160px;font-size:13px;">Applikation</td>
                                                <td style="padding:3px 0;font-size:13px;font-weight:700;color:#1f2937;">{{ $app->name }}</td>
                                            </tr>
                                            @if($app->einsatzzweck)
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;vertical-align:top;">Einsatzzweck</td>
                                                <td style="padding:3px 0;font-size:13px;color:#374151;">{{ $app->einsatzzweck }}</td>
                                            </tr>
                                            @endif
                                            @if($app->adminUser)
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;">IT-Administrator</td>
                                                <td style="padding:3px 0;font-size:13px;color:#374151;">{{ $app->adminUser->name }}</td>
                                            </tr>
                                            @endif
                                            @if($app->hersteller)
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;">Hersteller</td>
                                                <td style="padding:3px 0;font-size:13px;color:#374151;">{{ $app->hersteller }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px;line-height:1.7;color:#374151;">
                                Als Verfahrensverantwortliche/r sind Sie für die fachliche Zuständigkeit dieser Applikation verantwortlich
                                und werden zukünftig bei der jährlichen Revision kontaktiert.
                            </p>

                            {{-- Hinweis-Box --}}
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;margin:0;">
                                <tr>
                                    <td style="padding:14px 20px;">
                                        <p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#1e40af;">Fragen oder Einwände?</p>
                                        <p style="margin:0;font-size:13px;color:#1e40af;line-height:1.6;">
                                            Bitte wenden Sie sich an den <strong>ServiceDesk der IT</strong>.
                                            Sollte diese Zuordnung nicht korrekt sein, kann sie dort umgehend korrigiert werden.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;padding:16px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size:12px;color:#9ca3af;">
                                        Diese E-Mail wurde automatisch vom <strong style="color:#6b7280;">IT Cockpit</strong> versendet.
                                    </td>
                                    <td align="right" style="font-size:12px;color:#d1d5db;">
                                        {{ now()->format('d.m.Y H:i') }} Uhr
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
