<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Willkommen im IT-Cockpit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 15px;
            color: #333333;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 620px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #1e3a5f;
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 22px;
            margin: 0 0 4px 0;
            letter-spacing: 0.5px;
        }
        .header p {
            color: #a8c4e0;
            font-size: 13px;
            margin: 0;
        }
        .content {
            padding: 36px 40px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 16px;
        }
        .description {
            background-color: #f0f4f8;
            border-left: 4px solid #1e3a5f;
            padding: 14px 18px;
            margin: 20px 0;
            font-size: 14px;
            color: #444444;
            line-height: 1.6;
        }
        .credentials {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 18px 22px;
            margin: 24px 0;
        }
        .credentials table {
            border-collapse: collapse;
            width: 100%;
        }
        .credentials td {
            padding: 7px 0;
            vertical-align: top;
            font-size: 14px;
        }
        .credentials td:first-child {
            font-weight: bold;
            width: 150px;
            color: #555555;
        }
        .btn {
            display: inline-block;
            margin: 8px 0 24px 0;
            background-color: #1e3a5f;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .hint {
            background-color: #fff8e1;
            border-left: 4px solid #f0a500;
            padding: 12px 16px;
            margin: 20px 0;
            font-size: 14px;
            color: #555555;
        }
        .footer {
            background-color: #f7f7f7;
            border-top: 1px solid #eeeeee;
            padding: 20px 40px;
            font-size: 12px;
            color: #999999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>IT-Cockpit</h1>
            <p>Landratsamt Freising – IT-Infrastruktur-Management</p>
        </div>

        <div class="content">
            <p class="greeting">Hallo {{ $user->name }},</p>

            <p>herzlich willkommen im <strong>IT-Cockpit</strong> des Landratsamts Freising. Ihr Zugang wurde erfolgreich eingerichtet.</p>

            <div class="description">
                Das IT-Cockpit ist die zentrale Plattform zur Verwaltung der IT-Infrastruktur. Es bietet Ihnen unter anderem Zugriff auf Netzwerk- und VLAN-Übersichten, Störungsmeldungen, IT-Bestellungen sowie organisatorische Informationen – abgestimmt auf Ihre persönlichen Zugriffsrechte.
            </div>

            <p>Ihre Zugangsdaten:</p>

            <div class="credentials">
                <table>
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

            <a href="{{ config('app.url') . '/login' }}" class="btn">Zum IT-Cockpit &rarr;</a>

            <div class="hint">
                <strong>Wichtiger Hinweis:</strong> Bitte ändern Sie Ihr Passwort nach dem ersten Login über Ihr Benutzerprofil.
            </div>

            <p>Bei Fragen oder Problemen wenden Sie sich bitte an Ihren IT-Administrator.</p>
        </div>

        <div class="footer">
            Diese E-Mail wurde automatisch vom IT-Cockpit generiert. Bitte antworten Sie nicht auf diese Nachricht.<br>
            &copy; {{ date('Y') }} Landratsamt Freising – IT-Abteilung
        </div>
    </div>
</body>
</html>
