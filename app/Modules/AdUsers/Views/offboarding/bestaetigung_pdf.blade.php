<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a;
               padding: 15mm 18mm 15mm 18mm; }

        .header-bar { background: #b91c1c; padding: 10px 14px; margin-bottom: 14px; }
        .header-label { font-size: 8pt; color: #fca5a5; text-transform: uppercase; letter-spacing: 1px; }
        .header-title { font-size: 15pt; font-weight: bold; color: #ffffff; margin-top: 3px; }
        .header-sub   { font-size: 9pt; color: #fecaca; margin-top: 2px; }

        h2 { font-size: 10pt; font-weight: bold; text-transform: uppercase;
             letter-spacing: 0.5px; border-bottom: 1px solid #ccc;
             padding-bottom: 4px; margin: 14px 0 8px 0; color: #333; }

        .fields { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .fields td { font-size: 10pt; padding: 3px 0; vertical-align: top; }
        .fields td.lbl { color: #666; width: 55mm; }
        .fields td.val { font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 2px; }

        .notice { background: #fef9c3; border: 1px solid #eab308;
                  padding: 7px 10px; margin: 10px 0; font-size: 9.5pt; }

        .checklist { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .checklist td { font-size: 10pt; padding: 3px 0; vertical-align: middle; }
        .checklist td.box { width: 16px; text-align: center; padding-right: 8px; }
        .checkbox { display: inline-block; width: 12px; height: 12px;
                    background: #1d4ed8; border: 1.5px solid #1e3a8a; }

        p { font-size: 10pt; margin-bottom: 6px; line-height: 1.5; }

        .sig-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .sig-table td { font-size: 10pt; padding: 0 8px 0 0; vertical-align: bottom; width: 33%; }
        .sig-value { font-weight: bold; font-size: 11pt; padding-bottom: 3px; }
        .sig-line  { border-top: 1px solid #666; padding-top: 3px;
                     font-size: 8pt; color: #666; }

        .stamp { background: #dcfce7; border: 1px solid #16a34a;
                 padding: 7px 10px; margin-top: 12px; font-size: 9.5pt; }
        .stamp strong { color: #15803d; }

        .footer { position: fixed; bottom: 6mm; left: 18mm; right: 18mm;
                  border-top: 1px solid #ddd; padding-top: 3px;
                  font-size: 8pt; color: #999; }
    </style>
</head>
<body>

    <div class="header-bar">
        <div class="header-label">IT Cockpit &middot; Landratsamt Freising</div>
        <div class="header-title">Checkliste Mitarbeiter Ausscheiden</div>
        <div class="header-sub">Bestaetigung-Loeschung &ndash; Digitale Ausfertigung</div>
    </div>

    <h2>1. Stammdaten Mitarbeiter</h2>
    <table class="fields">
        <tr><td class="lbl">Vorname:</td><td class="val">{{ $record->vorname }}</td></tr>
        <tr><td class="lbl">Nachname:</td><td class="val">{{ $record->nachname }}</td></tr>
        @if ($record->personalnummer)
        <tr><td class="lbl">Personalnummer:</td><td class="val">{{ $record->personalnummer }}</td></tr>
        @endif
        <tr><td class="lbl">Abteilung/Sachgebiet:</td><td class="val">{{ $record->abteilung ?? '&mdash;' }}</td></tr>
        <tr><td class="lbl">Beendigung Dienstverhaeltnis:</td><td class="val">{{ $record->datum_ausscheiden->format('d.m.Y') }}</td></tr>
    </table>

    <div class="notice">
        <strong>Termine:</strong>
        Deaktivierung des Benutzerkontos: <strong>{{ $record->datum_ausscheiden->format('d.m.Y') }}</strong>
        &nbsp;&middot;&nbsp;
        Loeschung aller Daten: <strong>{{ $record->datum_ausscheiden->addDays(60)->format('d.m.Y') }}</strong>
        (60 Tage nach Ausscheiden)
    </div>

    <h2>2. Bestaetigung</h2>

    <p>Hiermit bestatige ich, dass auf folgenden Geraeten <strong>keine privaten Daten</strong>
    gespeichert sind und diese bedenkenlos geloescht werden koennen:</p>

    <table class="checklist">
        @foreach([
            'dem lokalen PC',
            'den Serverlaufwerken',
            'dem Terminalserver',
            'Tablets und Smartphones',
            'in Programmen (Outlook, Word, etc.)',
            'Internet-Browsern',
        ] as $item)
        <tr>
            <td class="box"><div class="checkbox"></div></td>
            <td>Auf <strong>{{ $item }}</strong> sind keine privaten Daten gespeichert.</td>
        </tr>
        @endforeach
    </table>

    <p style="margin-top:8px;">Die mir zur Verfuegung gestellten Benutzerkonten, Geraete und
    Datenablagen koennen bedenkenlos geloescht werden.</p>

    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-value">Freising</div>
                <div class="sig-line">Ort</div>
            </td>
            <td>
                <div class="sig-value">{{ $record->bestaetigung_erhalten_at->format('d.m.Y') }}</div>
                <div class="sig-line">Datum</div>
            </td>
            <td>
                <div class="sig-value">{{ $record->bestaetigung_name }}</div>
                <div class="sig-line">Unterschrift (digital)</div>
            </td>
        </tr>
    </table>

    <div class="stamp">
        <strong>Digital bestaetigt</strong> am
        {{ $record->bestaetigung_erhalten_at->format('d.m.Y') }} um
        {{ $record->bestaetigung_erhalten_at->format('H:i') }} Uhr
        &nbsp;&middot;&nbsp; Name: {{ $record->bestaetigung_name }}
        &nbsp;&middot;&nbsp; IP: {{ $record->bestaetigung_ip }}
    </div>

    <div class="footer">
        <table width="100%"><tr>
            <td>Einstufung: Intern</td>
            <td style="text-align:right">IT Cockpit &middot; Generiert am {{ now()->format('d.m.Y H:i') }} Uhr</td>
        </tr></table>
    </div>

</body>
</html>
