<?php

namespace App\Modules\Onboarding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class OnboardingSettings extends Model
{
    protected $table = 'onboarding_settings';

    protected $fillable = [
        'ldap_write_bind_dn',
        'ldap_write_bind_password',
        'group_search_base_dn',
        'exchange_url',
        'exchange_user',
        'exchange_password',
        'exchange_auth',
        'exchange_mailbox_db',
        'smb_user',
        'smb_password',
        'temp_password',
        'welcome_mail_subject',
        'welcome_mail_body',
        'supervisor_mail_subject',
        'supervisor_mail_body',
    ];

    protected $hidden = ['ldap_write_bind_password', 'exchange_password', 'smb_password', 'temp_password'];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'welcome_mail_subject'   => 'Willkommen – Ihr neues Benutzerkonto ist bereit',
            'welcome_mail_body'      => "Guten Tag %vorname% %nachname%,\n\nherzlich willkommen! Ihr persönliches Windows-Benutzerkonto wurde erfolgreich eingerichtet. Ab sofort können Sie sich an Ihrem Arbeitsplatz anmelden.\n\nIhre Zugangsdaten:\n  Benutzername:        %benutzername%\n  Temporäres Passwort: %passwort%\n  E-Mail-Adresse:      %upn%\n  Rufnummer:           %rufnummer%\n\nWichtig: Sie werden beim ersten Anmelden aufgefordert, ein neues persönliches Passwort zu vergeben. Bitte wählen Sie ein sicheres Passwort.\n\nBei Fragen oder Problemen steht Ihnen die IT-Abteilung gerne zur Verfügung.\n\nMit freundlichen Grüßen\nIT-Abteilung · Landratsamt Freising",
            'supervisor_mail_subject' => 'Information: Neues Benutzerkonto für %vorname% %nachname% angelegt',
            'supervisor_mail_body'   => "Guten Tag,\n\nim Auftrag Ihrer Organisationseinheit wurde für die unten genannte Person ein neues AD-Benutzerkonto eingerichtet. Die betreffende Person kann sich ab sofort an ihrem Arbeitsplatz anmelden.\n\nBitte überprüfen Sie die nachfolgenden Kontodaten und melden Sie der IT-Abteilung umgehend etwaige Fehler oder Änderungswünsche.",
        ]);
    }

    public function setLdapWriteBindPasswordAttribute(?string $value): void
    {
        $this->attributes['ldap_write_bind_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getLdapWriteBindPasswordAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try { return Crypt::decryptString($value); } catch (\Exception) { return null; }
    }

    public function setExchangePasswordAttribute(?string $value): void
    {
        $this->attributes['exchange_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getExchangePasswordAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try { return Crypt::decryptString($value); } catch (\Exception) { return null; }
    }

    public function setSmbPasswordAttribute(?string $value): void
    {
        $this->attributes['smb_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSmbPasswordAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try { return Crypt::decryptString($value); } catch (\Exception) { return null; }
    }

    public function hasSmbCredentials(): bool
    {
        return !empty($this->smb_user) && !empty($this->smb_password);
    }

    public function setTempPasswordAttribute(?string $value): void
    {
        $this->attributes['temp_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getTempPasswordAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try { return Crypt::decryptString($value); } catch (\Exception) { return null; }
    }
}
