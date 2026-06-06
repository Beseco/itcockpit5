<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #4f46e5; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 32px; color: #374151; line-height: 1.7; }
        .body pre { white-space: pre-wrap; font-family: Arial, sans-serif; margin: 0; }
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
    </div>
    <div class="footer">
        IT Cockpit · {{ now()->format('d.m.Y H:i') }} Uhr
    </div>
</div>
</body>
</html>
