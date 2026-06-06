<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #059669; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 32px; color: #374151; line-height: 1.7; }
        .body pre { white-space: pre-wrap; font-family: Arial, sans-serif; margin: 0; }
        .infobox { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 16px; margin-top: 20px; font-size: 14px; }
        .infobox table { width: 100%; border-collapse: collapse; }
        .infobox td { padding: 4px 8px; }
        .infobox td:first-child { font-weight: bold; color: #065f46; width: 160px; }
        .footer { padding: 16px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ $mailSubject }}</h1>
    </div>
    <div class="body">
        <pre>{{ $mailBody }}</pre>

        <div class="infobox">
            <table>
                <tr><td>Name:</td><td>{{ $record->vorname }} {{ $record->nachname }}</td></tr>
                <tr><td>Benutzername:</td><td>{{ $record->samaccountname }}</td></tr>
                <tr><td>E-Mail / UPN:</td><td>{{ $record->upn }}</td></tr>
                @if($record->rufnummer)
                <tr><td>Telefon:</td><td>{{ $record->rufnummer }}</td></tr>
                @endif
                @if($record->vorlage)
                <tr><td>Vorlage:</td><td>{{ $record->vorlage->name }}</td></tr>
                @endif
                <tr><td>Angelegt am:</td><td>{{ $record->created_at->format('d.m.Y H:i') }} Uhr</td></tr>
            </table>
        </div>
    </div>
    <div class="footer">
        IT Cockpit · {{ now()->format('d.m.Y H:i') }} Uhr
    </div>
</div>
</body>
</html>
