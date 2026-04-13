<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offene Revisionen – Erinnerung</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Revisions-Erinnerung</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">
                                {{ $apps->count() }} offene Revision{{ $apps->count() !== 1 ? 'en' : '' }}
                            </h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#1f2937;">
                                Hallo <strong>{{ $admin->name }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;line-height:1.7;color:#374151;">
                                die folgenden Applikationen haben ein fälliges Revisionsdatum und warten noch auf Ihre Überprüfung:
                            </p>

                            {{-- Tabelle der offenen Revisionen --}}
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;margin:0 0 28px;">
                                <thead>
                                    <tr style="background:#f9fafb;">
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;border-bottom:1px solid #e5e7eb;">Applikation</th>
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;border-bottom:1px solid #e5e7eb;">Revisionsdatum</th>
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;border-bottom:1px solid #e5e7eb;">Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($apps->sortBy('revision_date') as $app)
                                    @php
                                        $overdue = $app->revision_date && $app->revision_date->isPast();
                                        $dateColor = $overdue ? '#dc2626' : '#374151';
                                        $rowBg = $loop->odd ? '#ffffff' : '#fafafa';
                                    @endphp
                                    <tr style="background:{{ $rowBg }};">
                                        <td style="padding:10px 14px;font-size:13px;font-weight:600;color:#1f2937;border-bottom:1px solid #f3f4f6;">
                                            {{ $app->name }}
                                            @if($app->hersteller)
                                                <span style="display:block;font-size:11px;font-weight:400;color:#9ca3af;margin-top:1px;">{{ $app->hersteller }}</span>
                                            @endif
                                        </td>
                                        <td style="padding:10px 14px;font-size:13px;color:{{ $dateColor }};font-weight:{{ $overdue ? '700' : '400' }};border-bottom:1px solid #f3f4f6;">
                                            {{ $app->revision_date?->format('d.m.Y') ?? '–' }}
                                            @if($overdue)
                                                <span style="display:block;font-size:11px;font-weight:400;color:#dc2626;">überfällig</span>
                                            @endif
                                        </td>
                                        <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;">
                                            @if($app->revision_token)
                                                <a href="{{ route('revision.show', $app->revision_token) }}"
                                                   style="font-size:12px;color:#4f46e5;text-decoration:none;font-weight:600;white-space:nowrap;">
                                                    Revision starten →
                                                </a>
                                            @else
                                                <span style="font-size:12px;color:#9ca3af;">–</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;line-height:1.6;">
                                Bitte führen Sie die ausstehenden Revisionen zeitnah durch, um die Aktualität
                                der Applikationsdaten sicherzustellen.
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
