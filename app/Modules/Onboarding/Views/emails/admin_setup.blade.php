<!DOCTYPE html>
<html lang="de">
<head><meta charset="utf-8"><title>Neues Konto angelegt</title></head>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#333;margin:0;padding:20px;">

<h2 style="color:#4F46E5;">IT-Cockpit: Neues Konto angelegt</h2>

<p>Das AD-Konto für <strong>{{ $record->vorname }} {{ $record->nachname }}</strong> wurde erfolgreich angelegt.</p>

<table style="border-collapse:collapse;margin:16px 0;">
    <tr>
        <td style="padding:4px 16px 4px 0;color:#666;font-weight:bold;">Benutzername:</td>
        <td style="padding:4px 0;font-family:monospace;">{{ $record->samaccountname }}</td>
    </tr>
    <tr>
        <td style="padding:4px 16px 4px 0;color:#666;font-weight:bold;">E-Mail / UPN:</td>
        <td style="padding:4px 0;font-family:monospace;">{{ $record->upn }}</td>
    </tr>
    <tr>
        <td style="padding:4px 16px 4px 0;color:#666;font-weight:bold;">Temporäres Passwort:</td>
        <td style="padding:4px 0;font-family:monospace;font-size:16px;letter-spacing:2px;color:#1D4ED8;">{{ $tempPassword }}</td>
    </tr>
</table>

<p style="color:#92400E;background:#FEF3C7;border:1px solid #FCD34D;border-radius:6px;padding:12px;">
    <strong>Hinweis:</strong> Das temporäre Passwort muss <em>nicht</em> beim ersten Login geändert werden.
    Das endgültige Passwort (mit Änderungspflicht) wird erst nach Abschluss der Todo-Liste vergeben.
</p>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:24px 0;">

<p><strong>Nächste Schritte – bitte die Todo-Liste abarbeiten:</strong></p>

<a href="{{ $todoUrl }}"
   style="display:inline-block;background:#4F46E5;color:#fff;text-decoration:none;padding:12px 24px;border-radius:6px;font-size:15px;font-weight:bold;margin:8px 0;">
    &rarr; Todo-Liste öffnen
</a>

<p style="color:#6B7280;font-size:12px;margin-top:24px;">
    Sobald alle Punkte erledigt sind, wird das endgültige Passwort vergeben und der Benutzer
    sowie der Vorgesetzte werden per E-Mail benachrichtigt.
</p>

</body>
</html>
