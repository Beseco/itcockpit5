<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vertrags-Erinnerung</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#b45309;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Vertragsmanagement</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">
                                ⏰ Vertrag läuft bald aus
                            </h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 20px;line-height:1.7;">
                                Der folgende Vertrag erreicht in Kürze sein Vertragsende. Bitte prüfen Sie, ob
                                eine Verlängerung oder Kündigung erforderlich ist – beachten Sie die Kündigungsfrist.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#92400e;font-weight:600;margin-bottom:8px;">Vertrag</div>
                                        <div style="font-size:20px;font-weight:700;color:#92400e;margin-bottom:16px;">{{ $vertrag->name }}</div>

                                        <table cellpadding="0" cellspacing="0" style="font-size:13px;color:#92400e;width:100%;">
                                            @if($vertrag->dienstleister)
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.75;white-space:nowrap;vertical-align:top;">Dienstleister</td>
                                                <td style="font-weight:600;">{{ $vertrag->dienstleister->firmenname }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.75;white-space:nowrap;vertical-align:top;">Vertragsbeginn</td>
                                                <td>{{ $vertrag->vertragsbeginn?->format('d.m.Y') ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.75;white-space:nowrap;vertical-align:top;">Vertragsende</td>
                                                <td style="font-weight:700;">{{ $vertrag->vertragsende?->format('d.m.Y') ?? 'unbefristet' }}</td>
                                            </tr>
                                            @if($vertrag->kuendigungsfrist_monate)
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.75;white-space:nowrap;vertical-align:top;">Kündigungsfrist</td>
                                                <td>{{ $vertrag->kuendigungsfrist_monate }} Monat(e)</td>
                                            </tr>
                                            @if($vertrag->getKuendigungsstichtag())
                                            <tr>
                                                <td style="padding:4px 16px 4px 0;opacity:0.75;white-space:nowrap;vertical-align:top;">Spätester Kündigungstermin</td>
                                                <td style="font-weight:700;color:#b91c1c;">{{ $vertrag->getKuendigungsstichtag()->format('d.m.Y') }}</td>
                                            </tr>
                                            @endif
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($vertrag->notizen)
                            <div style="margin-bottom:20px;padding:12px 16px;background:#f9fafb;border-left:3px solid #d1d5db;border-radius:0 4px 4px 0;">
                                <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;margin-bottom:4px;">Notizen</div>
                                <div style="font-size:13px;color:#4b5563;white-space:pre-line;">{{ $vertrag->notizen }}</div>
                            </div>
                            @endif

                            <a href="{{ route('vertragsmanagement.show', $vertrag) }}"
                               style="display:inline-block;background:#b45309;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:10px 20px;border-radius:6px;">
                                Vertrag im IT Cockpit öffnen →
                            </a>

                            <p style="margin:20px 0 0;line-height:1.6;color:#6b7280;font-size:12px;">
                                Diese Erinnerung wird wöchentlich versendet, solange der Vertrag in der
                                Erinnerungsphase ({{ $vertrag->erinnerung_vorlauf_wochen }} Wochen vor Vertragsende) liegt.
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;padding:16px 32px;text-align:center;">
                            <span style="font-size:12px;color:#9ca3af;">IT Cockpit · Vertragsmanagement · Automatische Benachrichtigung · {{ now()->format('d.m.Y') }}</span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
