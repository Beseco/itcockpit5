<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title>Revisionsvorschlag: {{ $app->name }}</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

    <tr><td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:22px 30px;">
        <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Revisionsvorschlag</span>
        <h1 style="margin:6px 0 0;font-size:18px;font-weight:700;color:#ffffff;">{{ $app->name }}</h1>
        <p style="margin:4px 0 0;font-size:12px;color:#c7d2fe;">Abteilung: {{ $abteilung->anzeigename }}</p>
    </td></tr>

    <tr><td style="background:#ffffff;padding:28px 30px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

        <p style="margin:0 0 16px;font-size:15px;color:#1f2937;">
            Hallo <strong>{{ $app->adminUser->name }}</strong>,
        </p>
        <p style="margin:0 0 20px;line-height:1.7;color:#374151;">
            im Rahmen der Abteilungsrevision für <strong>{{ $abteilung->anzeigename }}</strong>
            wurden für die Applikation <strong>{{ $app->name }}</strong> Änderungsvorschläge eingereicht.
            Sie können diese mit einem Klick übernehmen oder ignorieren.
        </p>

        {{-- Vergleichstabelle --}}
        @php
            $schutzbedarf = ['A' => 'Normal', 'B' => 'Hoch', 'C' => 'Sehr hoch'];
            $labels = [
                'einsatzzweck'        => 'Beschreibung',
                'ansprechpartner'     => 'Ansprechpartner',
                'verantwortlich_name' => 'Verfahrensverantwortlicher',
                'confidentiality'     => 'Vertraulichkeit',
                'integrity'           => 'Integrität',
                'availability'        => 'Verfügbarkeit',
            ];
            $orig = $proposal->original_data;
            $prop = $proposal->proposed_data ?? [];
        @endphp

        <table width="100%" cellpadding="0" cellspacing="0"
               style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:6px;margin:0 0 24px;overflow:hidden;">
            <tr style="background:#f9fafb;">
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;width:30%;">Feld</th>
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Bisher</th>
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Vorgeschlagen</th>
            </tr>
            @foreach($labels as $key => $label)
                @php
                    $o = $orig[$key] ?? null;
                    $p = $prop[$key] ?? null;
                    if (in_array($key, ['confidentiality','integrity','availability'])) {
                        $o = $o ? ($schutzbedarf[$o] ?? $o) . ' (' . $o . ')' : null;
                        $p = $p ? ($schutzbedarf[$p] ?? $p) . ' (' . $p . ')' : null;
                    }
                    $changed = $o !== $p;
                @endphp
                <tr style="background:{{ $changed ? '#fefce8' : '#ffffff' }};">
                    <td style="padding:7px 12px;font-size:12px;font-weight:600;color:#374151;border-bottom:1px solid #f3f4f6;">{{ $label }}</td>
                    <td style="padding:7px 12px;font-size:12px;color:{{ $changed ? '#991b1b' : '#6b7280' }};border-bottom:1px solid #f3f4f6;{{ $changed ? 'text-decoration:line-through;' : '' }}">{{ $o ?: '—' }}</td>
                    <td style="padding:7px 12px;font-size:12px;color:{{ $changed ? '#065f46' : '#6b7280' }};font-weight:{{ $changed ? '700' : '400' }};border-bottom:1px solid #f3f4f6;">{{ $p ?: '—' }}</td>
                </tr>
            @endforeach
        </table>

        @if($proposal->reason)
        <table width="100%" cellpadding="0" cellspacing="0"
               style="background:#fef3c7;border:1px solid #fde68a;border-radius:6px;margin:0 0 24px;">
            <tr><td style="padding:12px 16px;">
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;text-transform:uppercase;color:#92400e;">Begründung Schutzbedarf-Änderung</p>
                <p style="margin:0;font-size:13px;color:#78350f;">{{ $proposal->reason }}</p>
            </td></tr>
        </table>
        @endif

        {{-- Approve-Button --}}
        <table cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
            <tr><td style="background:#059669;border-radius:6px;text-align:center;">
                <a href="{{ $approveUrl }}"
                   style="display:inline-block;padding:13px 32px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">
                    ✓ Änderungen übernehmen
                </a>
            </td></tr>
        </table>

        <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;line-height:1.6;">
            Wenn Sie nichts unternehmen, werden keine Änderungen vorgenommen.<br>
            <a href="{{ $approveUrl }}" style="color:#6b7280;word-break:break-all;">{{ $approveUrl }}</a>
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
