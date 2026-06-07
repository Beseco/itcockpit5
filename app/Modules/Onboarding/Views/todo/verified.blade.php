<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-Mail bestätigt</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #F9FAFB; }
        .card { background: #fff; border-radius: 12px; padding: 40px; max-width: 420px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { color: #059669; font-size: 22px; margin: 0 0 12px; }
        p { color: #6B7280; font-size: 14px; line-height: 1.6; margin: 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✓</div>
        <h1>E-Mail-Empfang bestätigt!</h1>
        <p>
            Hallo {{ $record->vorname }},<br>
            Ihr E-Mail-Postfach funktioniert. Sie können dieses Fenster schließen.
        </p>
    </div>
</body>
</html>
