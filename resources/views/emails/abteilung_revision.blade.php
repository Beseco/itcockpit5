<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Softwareliste zur Prüfung: {{ $abteilung->anzeigename }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Abteilungsrevision</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">{{ $abteilung->anzeigename }}</h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#1f2937;">
                                @if($empfaengerName)
                                    Hallo <strong>{{ $empfaengerName }}</strong>,
                                @else
                                    Guten Tag,
                                @endif
                            </p>
                            @php $apps = $abteilung->applikationen()->orderBy('name')->get(); @endphp

                            @if($apps->count())
                            {{-- Abteilung hat Applikationen --}}
                            <p style="margin:0 0 16px;line-height:1.7;color:#374151;">
                                die IT-Abteilung bittet Sie, die aktuell erfasste Software-Liste
                                für <strong>{{ $abteilung->anzeigename }}</strong> zu überprüfen.
                                @if($abteilung->revision_date)
                                    Das Revisionsdatum ist der <strong>{{ $abteilung->revision_date->format('d.m.Y') }}</strong>.
                                @endif
                            </p>
                            <p style="margin:0 0 20px;line-height:1.7;color:#374151;">
                                Klicken Sie bitte auf den folgenden Button, um die erfassten Applikationen Ihrer Abteilung einzusehen und Rückmeldungen oder Änderungswünsche direkt an die IT zu übermitteln. Sie können auch bisher nicht erfasste Software vorschlagen.
                            </p>
                            @else
                            {{-- Abteilung hat keine Applikationen --}}
                            <p style="margin:0 0 16px;line-height:1.7;color:#374151;">
                                für den Bereich <strong>{{ $abteilung->anzeigename }}</strong> sind aktuell
                                <strong>keine Applikationen</strong> im Verzeichnis der IuK hinterlegt.
                                @if($abteilung->revision_date)
                                    Das Revisionsdatum ist der <strong>{{ $abteilung->revision_date->format('d.m.Y') }}</strong>.
                                @endif
                            </p>
                            <p style="margin:0 0 20px;line-height:1.7;color:#374151;">
                                Sollten in Ihrer Abteilung Applikationen oder Software eingesetzt werden,
                                bitten wir Sie, diese über den folgenden Link zu melden, damit wir diese eintragen können.
                                Andernfalls schließen Sie die Revision einfach ab, indem Sie bestätigen, dass keine neuen Apps hinzugekommen sind.
                            </p>
                            @endif

                            {{-- Button --}}
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
                                <tr>
                                    <td style="background:#4f46e5;border-radius:6px;text-align:center;">
                                        <a href="{{ $revisionUrl }}"
                                           style="display:inline-block;padding:14px 36px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">
                                            {{ $apps->count() ? 'Softwareliste prüfen →' : 'Software melden / Revision abschließen →' }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- App-Vorschau (nur wenn Apps vorhanden) --}}
                            @if($apps->count())
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:6px;margin:0 0 24px;overflow:hidden;">
                                <tr style="background:#f9fafb;">
                                    <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Name</th>
                                    <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Beschreibung</th>
                                    <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Verfahrensverantwortlicher</th>
                                </tr>
                                @foreach($apps as $app)
                                <tr>
                                    <td style="padding:7px 12px;font-size:13px;font-weight:600;color:#1f2937;border-bottom:1px solid #f3f4f6;">{{ $app->name }}</td>
                                    <td style="padding:7px 12px;font-size:12px;color:#374151;border-bottom:1px solid #f3f4f6;">{{ $app->einsatzzweck ?: '—' }}</td>
                                    <td style="padding:7px 12px;font-size:12px;color:#374151;border-bottom:1px solid #f3f4f6;">{{ $app->verantwortlich_name ?: '—' }}</td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;line-height:1.6;">
                                Dieser Link ist personalisiert und nur einmalig verwendbar.<br>
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
