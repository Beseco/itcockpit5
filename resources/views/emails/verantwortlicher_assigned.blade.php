<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Neue Zuordnung als Verfahrensverantwortliche/r</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; color: #333; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #1f2937; padding: 24px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 18px; }
        .body { padding: 28px 32px; font-size: 14px; line-height: 1.7; }
        .meta-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 18px; margin: 18px 0; }
        .meta-box p { margin: 4px 0; }
        .meta-box strong { display: inline-block; width: 180px; color: #6b7280; }
        .hint { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; margin-top: 18px; font-size: 13px; color: #1e40af; }
        .footer { border-top: 1px solid #e5e7eb; padding: 14px 32px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Neue Zuordnung als Verfahrensverantwortliche/r</h1>
    </div>
    <div class="body">
        <p>Guten Tag {{ $adUser->anzeigenameOrName }},</p>

        <p>
            Sie wurden als <strong>Verfahrensverantwortliche/r</strong> für die folgende Applikation eingetragen:
        </p>

        <div class="meta-box">
            <p><strong>Applikation:</strong> {{ $app->name }}</p>
            @if($app->einsatzzweck)
            <p><strong>Einsatzzweck:</strong> {{ $app->einsatzzweck }}</p>
            @endif
            @if($app->adminUser)
            <p><strong>IT-Administrator:</strong> {{ $app->adminUser->name }}</p>
            @endif
            @if($app->hersteller)
            <p><strong>Hersteller:</strong> {{ $app->hersteller }}</p>
            @endif
        </div>

        <p>
            Als Verfahrensverantwortliche/r sind Sie für die fachliche Zuständigkeit dieser Applikation verantwortlich.
            Sie werden zukünftig bei der jährlichen Revision kontaktiert.
        </p>

        <div class="hint">
            <strong>Fragen oder Einwände?</strong><br>
            Bitte wenden Sie sich an den <strong>ServiceDesk der IT</strong>.<br>
            Sollte diese Zuordnung nicht korrekt sein, kann sie dort korrigiert werden.
        </div>
    </div>
    <div class="footer">IT Cockpit · Automatisches Revisionssystem · Bitte nicht direkt antworten.</div>
</div>
</body>
</html>
