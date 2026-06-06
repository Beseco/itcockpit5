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
        'welcome_mail_subject',
        'welcome_mail_body',
        'supervisor_mail_subject',
        'supervisor_mail_body',
    ];

    protected $hidden = ['ldap_write_bind_password'];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'welcome_mail_subject'   => 'Willkommen – Ihr neues Benutzerkonto',
            'welcome_mail_body'      => "Guten Tag %vorname% %nachname%,\n\nIhr neues Benutzerkonto wurde eingerichtet.\n\nBenutzername: %benutzername%\nTemporäres Passwort: %passwort%\n\nBitte ändern Sie Ihr Passwort beim ersten Login.\n\nBei Fragen wenden Sie sich an die IT-Abteilung.\n\nMit freundlichen Grüßen\nIT-Abteilung",
            'supervisor_mail_subject' => 'Neues Benutzerkonto wurde angelegt',
            'supervisor_mail_body'   => "Guten Tag,\n\nfür %vorname% %nachname% wurde ein neues AD-Benutzerkonto angelegt.\n\nBenutzername: %benutzername%\nE-Mail: %upn%\nTelefon: %rufnummer%\n\nMit freundlichen Grüßen\nIT-Abteilung",
        ]);
    }

    public function setLdapWriteBindPasswordAttribute(?string $value): void
    {
        $this->attributes['ldap_write_bind_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getLdapWriteBindPasswordAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }
}
