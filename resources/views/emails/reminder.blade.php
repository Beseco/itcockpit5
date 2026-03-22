<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">
    <h2 style="color: #4f46e5;">{{ $reminder->titel }}</h2>
    <div style="white-space: pre-line; line-height: 1.6;">{{ $reminder->nachricht }}</div>
    <hr style="margin-top: 24px; border: none; border-top: 1px solid #e5e7eb;">
    <p style="font-size: 12px; color: #9ca3af;">
        Diese E-Mail wurde automatisch vom IT-Cockpit versendet.
    </p>
</body>
</html>
