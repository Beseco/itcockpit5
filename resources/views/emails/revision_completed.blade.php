<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision abgeschlossen: {{ $app->name }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Revision abgeschlossen</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">{{ $app->name }}</h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            {{-- Meta --}}
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;margin:0 0 24px;">
                                <tr>
                                    <td style="padding:14px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;width:160px;font-size:13px;">Durchgeführt von</td>
                                                <td style="padding:3px 0;font-size:13px;font-weight:600;color:#1f2937;">{{ $app->adminUser?->name ?? '–' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;">Abgeschlossen am</td>
                                                <td style="padding:3px 0;font-size:13px;color:#374151;">{{ now()->format('d.m.Y H:i') }} Uhr</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:3px 0;color:#9ca3af;font-size:13px;">Nächste Revision</td>
                                                <td style="padding:3px 0;font-size:13px;color:#374151;">{{ $app->revision_date->format('d.m.Y') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Revisionsergebnis --}}
                            <p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">Revisionsergebnis</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                @php
                                    $rows = [
                                        ['Applikation noch aktiv',       $answers['app_aktiv'],              $answers['app_aktiv_notiz'] ?? null],
                                        ['Administrator korrekt',         $answers['admin_korrekt'],           null],
                                        ['Verfahrensverantw. korrekt',    $answers['verantwortlich_korrekt'],  null],
                                        ['Dokumentation aktuell',         $answers['doc_aktuell'],             null],
                                        ['Lieferantendaten korrekt',      $answers['lieferant_korrekt'],       null],
                                    ];
                                @endphp
                                @foreach($rows as [$label, $wert, $notiz])
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:7px 0;color:#6b7280;font-size:13px;width:200px;">{{ $label }}</td>
                                    <td style="padding:7px 0;">
                                        @if($wert === 'ja')
                                            <span style="display:inline-block;background:#d1fae5;color:#065f46;border-radius:4px;padding:2px 10px;font-size:12px;font-weight:600;">Ja</span>
                                        @else
                                            <span style="display:inline-block;background:#fee2e2;color:#991b1b;border-radius:4px;padding:2px 10px;font-size:12px;font-weight:600;">Nein</span>
                                        @endif
                                        @if($notiz)
                                            <br><span style="font-size:12px;color:#6b7280;">{{ $notiz }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </table>

                            {{-- Änderungen --}}
                            @if(!empty($changes))
                            <p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">Direkt vorgenommene Änderungen</p>
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;margin:0 0 24px;">
                                <tr>
                                    <td style="padding:14px 20px;">
                                        @if(isset($changes['admin_alt']))
                                        <p style="margin:0 0 6px;font-size:13px;color:#374151;">
                                            <strong>Administrator:</strong>
                                            <span style="color:#9ca3af;">{{ $changes['admin_alt'] ?? '(leer)' }}</span>
                                            &rarr; <strong>{{ $changes['admin_neu'] }}</strong>
                                        </p>
                                        @endif
                                        @if(isset($changes['verantwortlich_alt']))
                                        <p style="margin:0 0 6px;font-size:13px;color:#374151;">
                                            <strong>Verfahrensverantw.:</strong>
                                            <span style="color:#9ca3af;">{{ $changes['verantwortlich_alt'] ?? '(leer)' }}</span>
                                            &rarr; <strong>{{ $changes['verantwortlich_neu'] }}</strong>
                                        </p>
                                        @endif
                                        @if(isset($changes['doc_url_alt']))
                                        <p style="margin:0 0 6px;font-size:13px;color:#374151;">
                                            <strong>Dokumentations-URL:</strong><br>
                                            <span style="color:#9ca3af;font-size:12px;">{{ $changes['doc_url_alt'] ?? '(leer)' }}</span><br>
                                            &rarr; <a href="{{ $changes['doc_url_neu'] }}" style="color:#4f46e5;font-size:12px;">{{ $changes['doc_url_neu'] }}</a>
                                        </p>
                                        @endif
                                        @if(isset($changes['hersteller_alt']))
                                        <p style="margin:0 0 6px;font-size:13px;color:#374151;">
                                            <strong>Hersteller:</strong>
                                            <span style="color:#9ca3af;">{{ $changes['hersteller_alt'] ?? '(leer)' }}</span>
                                            &rarr; <strong>{{ $changes['hersteller_neu'] }}</strong>
                                        </p>
                                        @endif
                                        @if(isset($changes['ansprechpartner_alt']))
                                        <p style="margin:0 0 0;font-size:13px;color:#374151;">
                                            <strong>Ansprechpartner:</strong>
                                            <span style="color:#9ca3af;">{{ $changes['ansprechpartner_alt'] ?? '(leer)' }}</span>
                                            &rarr; <strong>{{ $changes['ansprechpartner_neu'] }}</strong>
                                        </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                            @endif

                            {{-- Anmerkungen --}}
                            @if(!empty($answers['anmerkungen']))
                            <p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">Allgemeine Anmerkungen</p>
                            <p style="margin:0;font-size:14px;line-height:1.7;color:#374151;padding:14px 20px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;">
                                {{ $answers['anmerkungen'] }}
                            </p>
                            @endif

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
