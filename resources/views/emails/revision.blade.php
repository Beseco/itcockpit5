<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisionsaufforderung: {{ $app->name }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Revisionsaufforderung</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">{{ $app->name }}</h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#1f2937;">
                                Hallo <strong>{{ $app->adminUser->name }}</strong>,
                            </p>
                            <p style="margin:0 0 16px;line-height:1.7;color:#374151;">
                                das Revisionsdatum für die Applikation <strong>{{ $app->name }}</strong>
                                ist am <strong>{{ $app->revision_date->format('d.m.Y') }}</strong> erreicht.
                                Bitte nehmen Sie sich einen Moment Zeit und überprüfen Sie die folgenden Punkte:
                            </p>

                            {{-- Checkliste --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                @foreach([
                                    'Wird die Applikation noch aktiv verwendet?',
                                    'Sind Sie noch der zuständige IT-Administrator?',
                                    'Ist der Verfahrensverantwortliche noch korrekt?',
                                    'Ist die Dokumentation noch aktuell?',
                                    'Stimmen die Hersteller- und Lieferanteninformationen?',
                                ] as $punkt)
                                <tr>
                                    <td style="padding:5px 0;vertical-align:top;width:24px;color:#4f46e5;font-size:16px;">&#10003;</td>
                                    <td style="padding:5px 0;line-height:1.5;color:#374151;">{{ $punkt }}</td>
                                </tr>
                                @endforeach
                            </table>

                            {{-- Stammdaten-Box --}}
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;margin:0 0 28px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">Aktuelle Stammdaten</p>
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;width:160px;font-size:13px;">Applikation</td>
                                                <td style="padding:3px 0;font-size:13px;font-weight:600;color:#1f2937;">{{ $app->name }}</td>
                                            </tr>
                                            @if($app->hersteller)
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;">Hersteller</td>
                                                <td style="padding:3px 0;font-size:13px;color:#374151;">{{ $app->hersteller }}</td>
                                            </tr>
                                            @endif
                                            @if($app->verantwortlichAdUser)
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;">Verfahrensverantw.</td>
                                                <td style="padding:3px 0;font-size:13px;color:#374151;">{{ $app->verantwortlichAdUser->anzeigenameOrName }}</td>
                                            </tr>
                                            @endif
                                            @if($app->doc_url)
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;">Dokumentation</td>
                                                <td style="padding:3px 0;font-size:13px;">
                                                    <a href="{{ $app->doc_url }}" style="color:#4f46e5;">{{ $app->doc_url }}</a>
                                                </td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;">Revisionsdatum</td>
                                                <td style="padding:3px 0;font-size:13px;color:#374151;">{{ $app->revision_date->format('d.m.Y') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Button --}}
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
                                <tr>
                                    <td style="background:#4f46e5;border-radius:6px;text-align:center;">
                                        <a href="{{ $revisionUrl }}"
                                           style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">
                                            Jetzt Revision durchführen &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;line-height:1.6;">
                                Dieser Link ist personalisiert und nach Abschluss der Revision nicht mehr gültig.<br>
                                <a href="{{ $revisionUrl }}" style="color:#6b7280;word-break:break-all;">{{ $revisionUrl }}</a>
                            </p>

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
