<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a; padding: 20mm 20mm 15mm 20mm; }

        .header { border-bottom: 2px solid #b91c1c; padding-bottom: 8px; margin-bottom: 16px; }
        .header-label { font-size: 8pt; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .header-title { font-size: 16pt; font-weight: bold; color: #1a1a1a; margin-top: 3px; }
        .header-sub { font-size: 9pt; color: #555; margin-top: 2px; }

        .section { margin-bottom: 18px; }
        .section-title { font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;
                         border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 10px; color: #333; }

        .field-row { display: flex; margin-bottom: 6px; font-size: 10pt; }
        .field-label { width: 55mm; color: #555; flex-shrink: 0; }
        .field-value { font-weight: bold; border-bottom: 1px solid #ccc; flex: 1; padding-bottom: 1px; }

        .checklist { margin: 8px 0; }
        .check-item { display: flex; align-items: flex-start; margin-bottom: 5px; font-size: 10pt; }
        .check-box { width: 12px; height: 12px; border: 1.5px solid #333; border-radius: 2px;
                     background: #1d4ed8; flex-shrink: 0; margin-right: 8px; margin-top: 1px;
                     display: inline-flex; align-items: center; justify-content: center; }
        .check-mark { color: white; font-size: 9pt; line-height: 1; }

        .notice { background: #fef3c7; border: 1px solid #fbbf24; border-radius: 4px;
                  padding: 8px 10px; margin-bottom: 14px; font-size: 9pt; }
        .notice strong { color: #92400e; }

        .signature-block { border-top: 2px solid #333; margin-top: 20px; padding-top: 12px; }
        .sig-row { display: flex; gap: 20mm; margin-bottom: 8px; }
        .sig-field { flex: 1; }
        .sig-label { font-size: 8pt; color: #666; border-top: 1px solid #999; padding-top: 3px; margin-top: 12px; }
        .sig-value { font-size: 10pt; font-weight: bold; }

        .digital-badge { background: #dcfce7; border: 1px solid #16a34a; border-radius: 4px;
                         padding: 8px 10px; margin-top: 12px; font-size: 9pt; }
        .digital-badge strong { color: #15803d; }

        .footer { position: fixed; bottom: 8mm; left: 20mm; right: 20mm;
                  border-top: 1px solid #ddd; padding-top: 4px;
                  font-size: 8pt; color: #888; display: flex; justify-content: space-between; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-label">IT Cockpit · Landratsamt Freising</div>
        <div class="header-title">Checkliste Mitarbeiter Ausscheiden</div>
        <div class="header-sub">Bestätigung-Löschung – Digitale Ausfertigung</div>
    </div>

    {{-- 1. Stammdaten --}}
    <div class="section">
        <div class="section-title">1. Stammdaten Mitarbeiter</div>
        <div class="field-row">
            <div class="field-label">Vorname:</div>
            <div class="field-value">{{ $record->vorname }}</div>
        </div>
        <div class="field-row">
            <div class="field-label">Nachname:</div>
            <div class="field-value">{{ $record->nachname }}</div>
        </div>
        @if ($record->personalnummer)
        <div class="field-row">
            <div class="field-label">Personalnummer:</div>
            <div class="field-value">{{ $record->personalnummer }}</div>
        </div>
        @endif
        <div class="field-row">
            <div class="field-label">Abteilung/Sachgebiet:</div>
            <div class="field-value">{{ $record->abteilung ?? '—' }}</div>
        </div>
        <div class="field-row">
            <div class="field-label">Beendigung Dienstverhältnis:</div>
            <div class="field-value">{{ $record->datum_ausscheiden->format('d.m.Y') }}</div>
        </div>
    </div>

    {{-- Hinweisbox --}}
    <div class="notice">
        <strong>Termine:</strong>
        Deaktivierung des Benutzerkontos: <strong>{{ $record->datum_ausscheiden->format('d.m.Y') }}</strong> &nbsp;·&nbsp;
        Löschung aller Daten: <strong>{{ $record->datum_ausscheiden->addDays(60)->format('d.m.Y') }}</strong> (60 Tage nach Ausscheiden)
    </div>

    {{-- 2. Bestätigung --}}
    <div class="section">
        <div class="section-title">2. Bestätigung</div>
        <p style="font-size:10pt; margin-bottom:10px;">
            Hiermit bestätige ich, dass auf folgenden Geräten <strong>keine privaten Daten</strong> gespeichert sind
            und diese bedenkenlos gelöscht werden können:
        </p>
        <div class="checklist">
            @foreach([
                'dem lokalen PC',
                'den Serverlaufwerken',
                'dem Terminalserver',
                'Tablets und Smartphones',
                'in Programmen (Outlook, Word, etc.)',
                'Internet-Browsern',
            ] as $item)
            <div class="check-item">
                <div class="check-box"><span class="check-mark">✓</span></div>
                <span>Auf <strong>{{ $item }}</strong> sind keine privaten Daten gespeichert.</span>
            </div>
            @endforeach
        </div>
        <p style="font-size:10pt; margin-top:10px;">
            Die mir zur Verfügung gestellten Benutzerkonten, Geräte und Datenablagen können bedenkenlos gelöscht werden.
        </p>
    </div>

    {{-- Unterschrift --}}
    <div class="signature-block">
        <div class="sig-row">
            <div class="sig-field">
                <div class="sig-value">Freising</div>
                <div class="sig-label">Ort</div>
            </div>
            <div class="sig-field">
                <div class="sig-value">{{ $record->bestaetigung_erhalten_at->format('d.m.Y') }}</div>
                <div class="sig-label">Datum</div>
            </div>
            <div class="sig-field" style="flex:2">
                <div class="sig-value">{{ $record->bestaetigung_name }}</div>
                <div class="sig-label">Unterschrift (digital)</div>
            </div>
        </div>

        <div class="digital-badge">
            <strong>✓ Digital bestätigt</strong> am
            {{ $record->bestaetigung_erhalten_at->format('d.m.Y') }} um
            {{ $record->bestaetigung_erhalten_at->format('H:i') }} Uhr &nbsp;·&nbsp;
            Name: {{ $record->bestaetigung_name }} &nbsp;·&nbsp;
            IP: {{ $record->bestaetigung_ip }}
        </div>
    </div>

    <div class="footer">
        <span>Einstufung: Intern</span>
        <span>IT Cockpit · Automatisch generiert am {{ now()->format('d.m.Y H:i') }} Uhr</span>
    </div>

</body>
</html>
