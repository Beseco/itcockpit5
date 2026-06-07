<!DOCTYPE html>
<html lang="de">
<head><meta charset="utf-8"><title>E-Mail-Bestätigung</title></head>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#333;margin:0;padding:20px;">

<h2 style="color:#4F46E5;">E-Mail-Adresse bestätigen</h2>

<p>Hallo {{ $record->vorname }} {{ $record->nachname }},</p>

<p>
    Ihr neues E-Mail-Postfach wurde eingerichtet. Bitte klicken Sie auf den nachfolgenden Link,
    um den Empfang dieser E-Mail zu bestätigen:
</p>

<a href="{{ $verifyUrl }}"
   style="display:inline-block;background:#059669;color:#fff;text-decoration:none;padding:12px 24px;border-radius:6px;font-size:15px;font-weight:bold;margin:8px 0;">
    &rarr; E-Mail-Empfang bestätigen
</a>

<p style="color:#6B7280;font-size:12px;margin-top:24px;">
    Dieser Link ist einmalig gültig. Falls Sie diese E-Mail nicht erwartet haben,
    können Sie sie ignorieren.
</p>

</body>
</html>
