<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihr Ticket-Score</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Ticket-Score</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">
                                Wöchentliche Auswertung – {{ now()->format('d.m.Y') }}
                            </h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#1f2937;">
                                Hallo <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;line-height:1.7;color:#374151;">
                                hier ist Ihre wöchentliche Ticket-Auswertung. Der Score basiert auf veralteten und kritischen Tickets,
                                die Ihnen zugewiesen sind.
                            </p>

                            {{-- Score-Box --}}
                            @php
                                $greenMax = (float) $settings->score_green_max;
                                $redMin   = (float) $settings->score_red_min;
                                if ($score <= $greenMax) {
                                    $scoreColor = '#15803d'; $scoreBg = '#f0fdf4'; $scoreBorder = '#86efac'; $scoreLabel = 'Gut';
                                } elseif ($score < $redMin) {
                                    $scoreColor = '#b45309'; $scoreBg = '#fffbeb'; $scoreBorder = '#fcd34d'; $scoreLabel = 'Erhöht';
                                } else {
                                    $scoreColor = '#b91c1c'; $scoreBg = '#fef2f2'; $scoreBorder = '#fca5a5'; $scoreLabel = 'Kritisch';
                                }
                            @endphp
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                                <tr>
                                    <td align="center"
                                        style="background:{{ $scoreBg }};border:2px solid {{ $scoreBorder }};border-radius:10px;padding:20px 32px;">
                                        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;">Ihr Score</div>
                                        <div style="font-size:48px;font-weight:800;color:{{ $scoreColor }};line-height:1;">
                                            {{ number_format($score, 1) }}
                                        </div>
                                        <div style="font-size:14px;font-weight:600;color:{{ $scoreColor }};margin-top:4px;">{{ $scoreLabel }}</div>
                                        <div style="font-size:12px;color:#9ca3af;margin-top:8px;">
                                            {{ $yellowTickets->count() }} gelbe Tickets × 0,5 Pkt &nbsp;+&nbsp;
                                            {{ $redTickets->count() }} rote Tickets × 1,0 Pkt
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            {{-- Rote Tickets --}}
                            @if($redTickets->isNotEmpty())
                            <p style="margin:0 0 10px;font-weight:700;color:#b91c1c;font-size:14px;">
                                Kritische Tickets (rot) – {{ $redTickets->count() }} Stück
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse;border:1px solid #fca5a5;border-radius:6px;overflow:hidden;margin:0 0 24px;">
                                <thead>
                                    <tr style="background:#fef2f2;">
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b91c1c;border-bottom:1px solid #fca5a5;">Nr.</th>
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b91c1c;border-bottom:1px solid #fca5a5;">Titel</th>
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b91c1c;border-bottom:1px solid #fca5a5;">Status</th>
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b91c1c;border-bottom:1px solid #fca5a5;">Erstellt</th>
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b91c1c;border-bottom:1px solid #fca5a5;">Letzte Änderung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($redTickets as $ticket)
                                    @php $rowBg = $loop->odd ? '#ffffff' : '#fff5f5'; @endphp
                                    <tr style="background:{{ $rowBg }};">
                                        <td style="padding:8px 12px;font-size:12px;font-family:monospace;color:#6b7280;border-bottom:1px solid #fee2e2;">#{{ $ticket['number'] }}</td>
                                        <td style="padding:8px 12px;font-size:12px;color:#1f2937;border-bottom:1px solid #fee2e2;">
                                            {{ \Illuminate\Support\Str::limit($ticket['title'], 45) }}
                                        </td>
                                        <td style="padding:8px 12px;font-size:12px;color:#374151;border-bottom:1px solid #fee2e2;">{{ $ticket['state'] }}</td>
                                        <td style="padding:8px 12px;font-size:12px;color:#6b7280;white-space:nowrap;border-bottom:1px solid #fee2e2;">
                                            {{ $ticket['created_at'] ? \Carbon\Carbon::parse($ticket['created_at'])->format('d.m.Y') : '—' }}
                                        </td>
                                        <td style="padding:8px 12px;font-size:12px;color:#6b7280;white-space:nowrap;border-bottom:1px solid #fee2e2;">
                                            {{ $ticket['updated_at'] ? \Carbon\Carbon::parse($ticket['updated_at'])->format('d.m.Y') : '—' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @endif

                            {{-- Gelbe Tickets --}}
                            @if($yellowTickets->isNotEmpty())
                            <p style="margin:0 0 10px;font-weight:700;color:#b45309;font-size:14px;">
                                Veraltete Tickets (gelb) – {{ $yellowTickets->count() }} Stück
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse;border:1px solid #fcd34d;border-radius:6px;overflow:hidden;margin:0 0 24px;">
                                <thead>
                                    <tr style="background:#fffbeb;">
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b45309;border-bottom:1px solid #fcd34d;">Nr.</th>
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b45309;border-bottom:1px solid #fcd34d;">Titel</th>
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b45309;border-bottom:1px solid #fcd34d;">Status</th>
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b45309;border-bottom:1px solid #fcd34d;">Erstellt</th>
                                        <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b45309;border-bottom:1px solid #fcd34d;">Letzte Änderung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($yellowTickets as $ticket)
                                    @php $rowBg = $loop->odd ? '#ffffff' : '#fefce8'; @endphp
                                    <tr style="background:{{ $rowBg }};">
                                        <td style="padding:8px 12px;font-size:12px;font-family:monospace;color:#6b7280;border-bottom:1px solid #fef08a;">#{{ $ticket['number'] }}</td>
                                        <td style="padding:8px 12px;font-size:12px;color:#1f2937;border-bottom:1px solid #fef08a;">
                                            {{ \Illuminate\Support\Str::limit($ticket['title'], 45) }}
                                        </td>
                                        <td style="padding:8px 12px;font-size:12px;color:#374151;border-bottom:1px solid #fef08a;">{{ $ticket['state'] }}</td>
                                        <td style="padding:8px 12px;font-size:12px;color:#6b7280;white-space:nowrap;border-bottom:1px solid #fef08a;">
                                            {{ $ticket['created_at'] ? \Carbon\Carbon::parse($ticket['created_at'])->format('d.m.Y') : '—' }}
                                        </td>
                                        <td style="padding:8px 12px;font-size:12px;color:#6b7280;white-space:nowrap;border-bottom:1px solid #fef08a;">
                                            {{ $ticket['updated_at'] ? \Carbon\Carbon::parse($ticket['updated_at'])->format('d.m.Y') : '—' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @endif

                            @if($redTickets->isEmpty() && $yellowTickets->isEmpty())
                            <p style="margin:0;color:#6b7280;font-size:13px;line-height:1.6;">
                                Keine veralteten oder kritischen Tickets. Weiter so!
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
