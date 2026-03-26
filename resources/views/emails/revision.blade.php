<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisionsaufforderung: {{ $app->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; color: #333; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #1f2937; padding: 24px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; }
        .header p { color: #9ca3af; margin: 4px 0 0; font-size: 13px; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; margin-top: 0; }
        .meta-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 20px 0; }
        .meta-box p { margin: 4px 0; font-size: 14px; }
        .meta-box strong { display: inline-block; width: 180px; color: #6b7280; }
        .checklist { padding-left: 20px; }
        .checklist li { margin-bottom: 6px; font-size: 14px; }
        .btn { display: inline-block; margin: 24px 0 8px; padding: 14px 28px; background: #1f2937; color: #fff !important; text-decoration: none; border-radius: 6px; font-size: 15px; font-weight: bold; }
        .hint { font-size: 12px; color: #9ca3af; margin-top: 8px; }
        .footer { border-top: 1px solid #e5e7eb; padding: 16px 32px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>IT Cockpit – Revisionsaufforderung</h1>
        <p>Automatische Benachrichtigung</p>
    </div>
    <div class="body">
        <h2>Hallo {{ $app->adminUser->name }},</h2>

        <p>
            das Revisionsdatum für die Applikation <strong>{{ $app->name }}</strong>
            ist am <strong>{{ $app->revision_date->format('d.m.Y') }}</strong> erreicht.
        </p>
        <p>
            Bitte nehmen Sie sich einen Moment Zeit und überprüfen Sie die folgenden Punkte:
        </p>

        <ul class="checklist">
            <li>Wird die Applikation noch aktiv verwendet?</li>
            <li>Sind Sie noch der zuständige IT-Administrator?</li>
            <li>Ist der Verfahrensverantwortliche noch korrekt / noch im Unternehmen?</li>
            <li>Ist die Dokumentation noch aktuell?</li>
            <li>Stimmen die Hersteller- und Lieferanteninformationen noch?</li>
        </ul>

        <div class="meta-box">
            <p><strong>Applikation:</strong> {{ $app->name }}</p>
            @if($app->hersteller)
            <p><strong>Hersteller:</strong> {{ $app->hersteller }}</p>
            @endif
            @if($app->verantwortlichAdUser)
            <p><strong>Verfahrensverantwortlicher:</strong> {{ $app->verantwortlichAdUser->anzeigenameOrName }}</p>
            @endif
            @if($app->doc_url)
            <p><strong>Dokumentation:</strong> <a href="{{ $app->doc_url }}" style="color:#4f46e5;">{{ $app->doc_url }}</a></p>
            @endif
            <p><strong>Revisionsdatum:</strong> {{ $app->revision_date->format('d.m.Y') }}</p>
        </div>

        <a href="{{ $revisionUrl }}" class="btn">Jetzt Revision durchführen →</a>

        <p class="hint">
            Dieser Link ist personalisiert und nach Abschluss der Revision nicht mehr gültig.<br>
            Falls Sie den Link nicht nutzen können, kopieren Sie diese URL in Ihren Browser:<br>
            <a href="{{ $revisionUrl }}" style="color:#4f46e5; word-break:break-all;">{{ $revisionUrl }}</a>
        </p>
    </div>
    <div class="footer">
        IT Cockpit · Automatisch generierte Nachricht · Bitte nicht direkt antworten.
    </div>
</div>
</body>
</html>
