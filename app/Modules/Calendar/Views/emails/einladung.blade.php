<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Einladung: {{ $event->titel }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
    <tr><td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

            <tr>
                <td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:24px 32px;">
                    <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Kalender</span>
                    <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;">📅 Einladung: {{ $event->titel }}</h1>
                </td>
            </tr>

            <tr>
                <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">
                    <p style="margin:0 0 16px;color:#6b7280;font-size:14px;">
                        <strong>{{ $eingeladenVon->name }}</strong> hat dich zu folgendem Termin eingeladen:
                    </p>

                    <table width="100%" cellpadding="0" cellspacing="0"
                           style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:16px;margin-bottom:20px;">
                        <tr>
                            <td style="padding:6px 0;">
                                <span style="font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Termin</span><br>
                                <strong style="font-size:16px;color:#111827;">{{ $event->titel }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;border-top:1px solid #e5e7eb;margin-top:8px;">
                                <span style="font-size:12px;color:#9ca3af;">Beginn</span><br>
                                <span style="color:#374151;">
                                    {{ $event->ganztag
                                        ? $event->start_at->format('d.m.Y') . ' (ganztägig)'
                                        : $event->start_at->format('d.m.Y \u\m H:i \U\h\r') }}
                                </span>
                            </td>
                        </tr>
                        @if($event->end_at)
                        <tr>
                            <td style="padding:6px 0;border-top:1px solid #e5e7eb;">
                                <span style="font-size:12px;color:#9ca3af;">Ende</span><br>
                                <span style="color:#374151;">
                                    {{ $event->ganztag
                                        ? $event->end_at->format('d.m.Y')
                                        : $event->end_at->format('d.m.Y \u\m H:i \U\h\r') }}
                                </span>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td style="padding:6px 0;border-top:1px solid #e5e7eb;">
                                <span style="font-size:12px;color:#9ca3af;">Typ</span><br>
                                <span style="color:#374151;">{{ \App\Modules\Calendar\Models\CalendarEvent::TYPEN[$event->typ] ?? $event->typ }}</span>
                            </td>
                        </tr>
                    </table>

                    @if($event->beschreibung)
                    <div style="font-size:14px;line-height:1.6;color:#374151;border-left:3px solid #4f46e5;padding-left:12px;margin-bottom:20px;">
                        {!! \Illuminate\Support\Str::markdown($event->beschreibung, ['html_input' => 'strip']) !!}
                    </div>
                    @endif
                </td>
            </tr>

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
    </td></tr>
</table>
</body>
</html>
