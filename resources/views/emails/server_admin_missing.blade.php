<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server ohne Administrator</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:24px 32px;">
                            <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Server-Verwaltung</span>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">
                                {{ $servers->count() }} Server ohne Administrator
                            </h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">
                                Die folgenden Server haben keinen Administrator hinterlegt und sollten zeitnah einem Verantwortlichen zugewiesen werden:
                            </p>

                            {{-- Tabelle --}}
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;margin:0 0 28px;">
                                <thead>
                                    <tr style="background:#f9fafb;">
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;border-bottom:1px solid #e5e7eb;">Server</th>
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;border-bottom:1px solid #e5e7eb;">Hostname</th>
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;border-bottom:1px solid #e5e7eb;">Status</th>
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;border-bottom:1px solid #e5e7eb;">Abteilung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($servers->sortBy('name') as $server)
                                    @php $rowBg = $loop->odd ? '#ffffff' : '#fafafa'; @endphp
                                    <tr style="background:{{ $rowBg }};">
                                        <td style="padding:10px 14px;font-size:13px;font-weight:600;color:#1f2937;border-bottom:1px solid #f3f4f6;">
                                            <a href="{{ route('server.show', $server) }}"
                                               style="color:#4f46e5;text-decoration:none;">{{ $server->name }}</a>
                                        </td>
                                        <td style="padding:10px 14px;font-size:12px;color:#6b7280;font-family:monospace;border-bottom:1px solid #f3f4f6;">
                                            {{ $server->dns_hostname ?? '—' }}
                                        </td>
                                        <td style="padding:10px 14px;font-size:12px;border-bottom:1px solid #f3f4f6;">
                                            @php
                                                $statusColors = [
                                                    'produktiv'     => '#16a34a',
                                                    'testsystem'    => '#2563eb',
                                                    'ausgeschaltet' => '#6b7280',
                                                    'im_aufbau'     => '#ca8a04',
                                                    'ausgemustert'  => '#dc2626',
                                                ];
                                                $statusLabels = \App\Modules\Server\Models\Server::STATUS_LABELS;
                                                $color = $statusColors[$server->status] ?? '#6b7280';
                                            @endphp
                                            <span style="color:{{ $color }};font-weight:600;">
                                                {{ $statusLabels[$server->status] ?? $server->status }}
                                            </span>
                                        </td>
                                        <td style="padding:10px 14px;font-size:12px;color:#6b7280;border-bottom:1px solid #f3f4f6;">
                                            {{ $server->abteilung?->anzeigename ?? '—' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">
                                Bitte hinterlegen Sie für diese Server einen Administrator unter
                                <a href="{{ route('server.index') }}" style="color:#4f46e5;">Server → Liste</a>.
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
