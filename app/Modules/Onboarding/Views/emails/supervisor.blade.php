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
            <td style="background-color:#1e3a5f;padding:36px 40px;border-radius:10px 10px 0 0;">
                <p style="margin:0 0 6px 0;color:#a5c8f0;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;font-weight:600;">IT-Abteilung · Kreis Freising</p>
                <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;line-height:1.4;">{{ $mailSubject }}</h1>
            </td>
        </tr>

        {{-- Erklärungsbox --}}
        <tr>
            <td style="background-color:#ffffff;padding:24px 40px 20px;border-left:4px solid #2563eb;">
                <p style="margin:0;color:#111827;font-size:14px;line-height:1.7;">
                    <strong style="color:#1e3a5f;">Sie erhalten diese E-Mail, da Sie als Vorgesetzter von
                    {{ $record->vorname }} {{ $record->nachname }} hinterlegt sind.</strong><br>
                    Die IT-Abteilung hat soeben ein neues AD-Benutzerkonto angelegt.
                    Bitte prüfen Sie die nachfolgenden Angaben auf Richtigkeit und melden Sie
                    Fehler oder Korrekturen umgehend an die IT-Abteilung.
                </p>
            </td>
        </tr>

        {{-- Body-Text aus Einstellungen --}}
        @if(trim($mailBody))
        <tr>
            <td style="background:#ffffff;padding:28px 40px 8px;color:#374151;font-size:15px;line-height:1.8;">
                {!! nl2br(e($mailBody)) !!}
            </td>
        </tr>
        @endif

        {{-- Kontodaten-Tabelle --}}
        <tr>
            <td style="background:#ffffff;padding:24px 40px 36px;">
                <table width="100%" cellpadding="0" cellspacing="0"
                       style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;font-size:14px;">
                    <tr>
                        <td colspan="2" style="background:#f8fafc;padding:12px 16px;border-bottom:1px solid #e5e7eb;">
                            <strong style="color:#374151;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">
                                Kontodaten zur Prüfung
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 16px;color:#6b7280;width:40%;border-bottom:1px solid #f3f4f6;">Name</td>
                        <td style="padding:10px 16px;color:#111827;font-weight:600;border-bottom:1px solid #f3f4f6;">{{ $record->vorname }} {{ $record->nachname }}</td>
                    </tr>
                    <tr style="background:#f9fafb;">
                        <td style="padding:10px 16px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Benutzername</td>
                        <td style="padding:10px 16px;color:#111827;font-family:monospace;border-bottom:1px solid #f3f4f6;">{{ $record->samaccountname }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 16px;color:#6b7280;border-bottom:1px solid #f3f4f6;">E-Mail / UPN</td>
                        <td style="padding:10px 16px;color:#111827;font-family:monospace;border-bottom:1px solid #f3f4f6;">{{ $record->upn }}</td>
                    </tr>
                    @if($record->rufnummer)
                    <tr style="background:#f9fafb;">
                        <td style="padding:10px 16px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Rufnummer</td>
                        <td style="padding:10px 16px;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $record->rufnummer }}</td>
                    </tr>
                    @endif
                    @php $snap = $record->ad_attributes_snapshot ?? []; @endphp
                    @if(!empty($snap['mobile']))
                    <tr>
                        <td style="padding:10px 16px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Mobilnummer</td>
                        <td style="padding:10px 16px;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $snap['mobile'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($snap['buero']))
                    <tr style="background:#f9fafb;">
                        <td style="padding:10px 16px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Büro</td>
                        <td style="padding:10px 16px;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $snap['buero'] }}</td>
                    </tr>
                    @endif
                    @if($record->vorlage?->abteilung)
                    <tr>
                        <td style="padding:10px 16px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Organisationseinheit</td>
                        <td style="padding:10px 16px;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $record->vorlage->abteilung->name }}</td>
                    </tr>
                    @endif
                    <tr style="background:#f9fafb;">
                        <td style="padding:10px 16px;color:#6b7280;">Angelegt am</td>
                        <td style="padding:10px 16px;color:#111827;">{{ $record->created_at->format('d.m.Y \u\m H:i \U\h\r') }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Call to Action --}}
        <tr>
            <td style="background-color:#f8fafc;padding:20px 40px;border-top:2px solid #1e3a5f;">
                <p style="margin:0;color:#111827;font-size:13px;line-height:1.6;">
                    <strong style="color:#1e3a5f;">Bitte prüfen Sie die Angaben</strong> und wenden Sie sich bei Unstimmigkeiten
                    umgehend an die IT-Abteilung. Das Konto ist ab sofort aktiv.
                </p>
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="background:#f9fafb;padding:20px 40px;border-radius:0 0 10px 10px;border-top:1px solid #e5e7eb;">
                <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">
                    IT Cockpit · Landratsamt Freising · {{ now()->format('d.m.Y') }} ·
                    Diese E-Mail wurde automatisch generiert. Bitte nicht antworten.
                </p>
            </td>
        </tr>

    </table>

</td></tr>
</table>

</body>
</html>
