<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Revision abgeschlossen: {{ $app->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; color: #333; }
        .wrapper { max-width: 640px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #1f2937; padding: 24px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 18px; }
        .header p { color: #9ca3af; margin: 4px 0 0; font-size: 13px; }
        .body { padding: 28px 32px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 5px 0; vertical-align: top; }
        td:first-child { color: #6b7280; width: 200px; padding-right: 12px; }
        .tag-ok { display:inline-block; background:#d1fae5; color:#065f46; border-radius:4px; padding:1px 8px; font-size:12px; }
        .tag-nein { display:inline-block; background:#fee2e2; color:#991b1b; border-radius:4px; padding:1px 8px; font-size:12px; }
        .change-box { background:#fffbeb; border:1px solid #fcd34d; border-radius:6px; padding:10px 14px; margin-top:10px; font-size:13px; }
        .change-box p { margin:3px 0; }
        .footer { border-top: 1px solid #e5e7eb; padding: 14px 32px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Revision abgeschlossen: {{ $app->name }}</h1>
        <p>{{ now()->format('d.m.Y H:i') }} Uhr · IT Cockpit Revisionssystem</p>
    </div>
    <div class="body">

        <div class="section">
            <div class="section-title">Applikation</div>
            <table>
                <tr><td>Name</td><td><strong>{{ $app->name }}</strong></td></tr>
                <tr><td>Durchgeführt von</td><td>{{ $app->adminUser?->name ?? '–' }}</td></tr>
                <tr><td>Nächste Revision</td><td>{{ $app->revision_date->format('d.m.Y') }}</td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Revisionsergebnis</div>
            <table>
                <tr>
                    <td>Applikation noch aktiv</td>
                    <td>
                        <span class="{{ $answers['app_aktiv'] === 'ja' ? 'tag-ok' : 'tag-nein' }}">
                            {{ $answers['app_aktiv'] === 'ja' ? 'Ja' : 'Nein' }}
                        </span>
                        @if(!empty($answers['app_aktiv_notiz']))
                            <br><small style="color:#6b7280">{{ $answers['app_aktiv_notiz'] }}</small>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Administrator korrekt</td>
                    <td>
                        <span class="{{ $answers['admin_korrekt'] === 'ja' ? 'tag-ok' : 'tag-nein' }}">
                            {{ $answers['admin_korrekt'] === 'ja' ? 'Ja' : 'Nein' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Verfahrensverantw. korrekt</td>
                    <td>
                        <span class="{{ $answers['verantwortlich_korrekt'] === 'ja' ? 'tag-ok' : 'tag-nein' }}">
                            {{ $answers['verantwortlich_korrekt'] === 'ja' ? 'Ja' : 'Nein' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Dokumentation aktuell</td>
                    <td>
                        <span class="{{ $answers['doc_aktuell'] === 'ja' ? 'tag-ok' : 'tag-nein' }}">
                            {{ $answers['doc_aktuell'] === 'ja' ? 'Ja' : 'Nein' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Lieferantendaten korrekt</td>
                    <td>
                        <span class="{{ $answers['lieferant_korrekt'] === 'ja' ? 'tag-ok' : 'tag-nein' }}">
                            {{ $answers['lieferant_korrekt'] === 'ja' ? 'Ja' : 'Nein' }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        @if(!empty($changes))
        <div class="section">
            <div class="section-title">Direkt vorgenommene Änderungen</div>
            <div class="change-box">
                @if(isset($changes['admin_alt']))
                    <p><strong>Administrator:</strong> {{ $changes['admin_alt'] ?? '(leer)' }} → <strong>{{ $changes['admin_neu'] }}</strong></p>
                @endif
                @if(isset($changes['verantwortlich_alt']))
                    <p><strong>Verfahrensverantw.:</strong> {{ $changes['verantwortlich_alt'] ?? '(leer)' }} → <strong>{{ $changes['verantwortlich_neu'] }}</strong></p>
                @endif
                @if(isset($changes['doc_url_alt']))
                    <p><strong>Dokumentations-URL:</strong><br>
                        <span style="color:#6b7280">{{ $changes['doc_url_alt'] ?? '(leer)' }}</span><br>
                        → <a href="{{ $changes['doc_url_neu'] }}" style="color:#4f46e5">{{ $changes['doc_url_neu'] }}</a>
                    </p>
                @endif
                @if(isset($changes['hersteller_alt']))
                    <p><strong>Hersteller:</strong> {{ $changes['hersteller_alt'] ?? '(leer)' }} → <strong>{{ $changes['hersteller_neu'] }}</strong></p>
                @endif
                @if(isset($changes['ansprechpartner_alt']))
                    <p><strong>Ansprechpartner:</strong> {{ $changes['ansprechpartner_alt'] ?? '(leer)' }} → <strong>{{ $changes['ansprechpartner_neu'] }}</strong></p>
                @endif
            </div>
        </div>
        @endif

        @if(!empty($answers['anmerkungen']))
        <div class="section">
            <div class="section-title">Allgemeine Anmerkungen</div>
            <p style="font-size:14px; color:#374151;">{{ $answers['anmerkungen'] }}</p>
        </div>
        @endif

    </div>
    <div class="footer">IT Cockpit · Automatisches Revisionssystem</div>
</div>
</body>
</html>
