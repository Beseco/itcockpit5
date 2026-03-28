<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title>Offboarding Deaktivierung</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                <tr>
                    <td style="background:#d97706;border-radius:8px 8px 0 0;padding:24px 32px;">
                        <span style="font-size:11px;color:#fde68a;text-transform:uppercase;letter-spacing:1px;font-weight:600;">IT Cockpit · Offboarding</span>
                        <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;">Deaktivierung erforderlich</h1>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">
                        <p style="margin:0 0 16px;font-size:15px;">Hallo <strong>{{ $record->anleger_name }}</strong>,</p>
                        <p style="margin:0 0 16px;line-height:1.7;">
                            das Ausscheidedatum für <strong>{{ $record->voller_name }}</strong> ist heute erreicht.
                            Bitte deaktivieren Sie das Benutzerkonto und bestätigen Sie dies anschließend.
                        </p>

                        @if ($ldapBestaetigt)
                        <div style="background:#dcfce7;border:1px solid #16a34a;border-radius:6px;padding:12px 16px;margin:0 0 20px;">
                            <strong style="color:#15803d;">✓ LDAP-Prüfung:</strong>
                            <span style="color:#166534;">Das Konto ist im Active Directory bereits deaktiviert.</span>
                        </div>
                        @else
                        <div style="background:#fef9c3;border:1px solid #eab308;border-radius:6px;padding:12px 16px;margin:0 0 20px;">
                            <strong style="color:#92400e;">⚠ LDAP-Prüfung:</strong>
                            <span style="color:#78350f;">Das Konto ist im Active Directory noch aktiv – bitte jetzt deaktivieren.</span>
                        </div>
                        @endif

                        <table cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:16px;margin:0 0 24px;width:100%;">
                            <tr><td style="padding:3px 0;font-size:13px;color:#6b7280;width:45%;">Mitarbeiter:</td>
                                <td style="font-size:13px;font-weight:600;">{{ $record->voller_name }}</td></tr>
                            <tr><td style="padding:3px 0;font-size:13px;color:#6b7280;">SAM-Account:</td>
                                <td style="font-size:13px;font-family:monospace;">{{ $record->samaccountname }}</td></tr>
                            <tr><td style="padding:3px 0;font-size:13px;color:#6b7280;">Ausscheiden am:</td>
                                <td style="font-size:13px;font-weight:600;">{{ $record->datum_ausscheiden->format('d.m.Y') }}</td></tr>
                            <tr><td style="padding:3px 0;font-size:13px;color:#6b7280;">Löschung geplant:</td>
                                <td style="font-size:13px;">{{ $record->datum_ausscheiden->addDays(60)->format('d.m.Y') }}</td></tr>
                        </table>

                        <table cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                            <tr>
                                <td style="background:#d97706;border-radius:6px;padding:14px 28px;">
                                    <a href="{{ $confirmUrl }}" style="color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;">
                                        Deaktivierung bestätigen →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0;font-size:12px;color:#9ca3af;">
                            <a href="{{ $confirmUrl }}" style="color:#d97706;">{{ $confirmUrl }}</a>
                        </p>
                    </td>
                </tr>
                <tr><td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:0;border-radius:0 0 8px 8px;padding:16px 32px;text-align:center;">
                    <span style="font-size:12px;color:#9ca3af;">IT Cockpit · Landratsamt Freising</span>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
