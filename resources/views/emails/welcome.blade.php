<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zugangsdaten für IT-Cockpit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 15px;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 4px;
            padding: 40px;
        }
        h1 {
            font-size: 20px;
            color: #222222;
            margin-top: 0;
        }
        .credentials {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 16px 20px;
            margin: 24px 0;
        }
        .credentials table {
            border-collapse: collapse;
            width: 100%;
        }
        .credentials td {
            padding: 6px 0;
            vertical-align: top;
        }
        .credentials td:first-child {
            font-weight: bold;
            width: 160px;
            color: #555555;
        }
        .hint {
            background-color: #fff8e1;
            border-left: 4px solid #f0a500;
            padding: 12px 16px;
            margin: 24px 0;
            font-size: 14px;
            color: #555555;
        }
        .footer {
            margin-top: 32px;
            font-size: 13px;
            color: #999999;
            border-top: 1px solid #eeeeee;
            padding-top: 16px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>Willkommen im IT-Cockpit</h1>

        <p>Hallo {{ $user->name }},</p>

        <p>
            Ihr Konto im IT-Cockpit wurde erfolgreich eingerichtet.
            Nachfolgend finden Sie Ihre Zugangsdaten:
        </p>

        <div class="credentials">
            <table>
                <tr>
                    <td>Login-URL:</td>
                    <td><a href="{{ config('app.url') . '/login' }}">{{ config('app.url') . '/login' }}</a></td>
                </tr>
                <tr>
                    <td>Benutzername:</td>
                    <td>{{ $user->email }}</td>
                </tr>
                <tr>
                    <td>Passwort:</td>
                    <td><strong>{{ $plaintextPassword }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="hint">
            <strong>Wichtiger Hinweis:</strong> Bitte ändern Sie Ihr Passwort nach dem ersten Login.
        </div>

        <p>Bei Fragen wenden Sie sich bitte an Ihren Administrator.</p>

        <div class="footer">
            Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese Nachricht.
        </div>
    </div>
</body>
</html>
