<?php

namespace App\Modules\AdUsers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AdUserSettings extends Model
{
    protected $table = 'adusers_settings';

    protected $fillable = [
        'server', 'port', 'base_dn', 'bind_dn', 'bind_password',
        'anonymous_bind', 'use_ssl', 'sync_interval_hours', 'max_inactive_days',
        'ou_deaktiviert', 'ou_elternzeit', 'ou_altersteilzeit',
    ];

    /** Gibt alle konfigurierten Spezial-OUs als Array zurück: key → [label, dn, badge_*] */
    public function specialOus(): array
    {
        $ous = [];
        if ($this->ou_deaktiviert) {
            $ous['deaktiviert'] = [
                'label'       => 'Deaktiviert',
                'dn'          => $this->ou_deaktiviert,
                'badge_class' => 'bg-gray-200 text-gray-700',
                'banner_class'=> 'bg-gray-100 border-gray-300 text-gray-700',
            ];
        }
        if ($this->ou_elternzeit) {
            $ous['elternzeit'] = [
                'label'       => 'Elternzeit',
                'dn'          => $this->ou_elternzeit,
                'badge_class' => 'bg-sky-100 text-sky-700',
                'banner_class'=> 'bg-sky-50 border-sky-300 text-sky-700',
            ];
        }
        if ($this->ou_altersteilzeit) {
            $ous['altersteilzeit'] = [
                'label'       => 'Altersteilzeit',
                'dn'          => $this->ou_altersteilzeit,
                'badge_class' => 'bg-amber-100 text-amber-700',
                'banner_class'=> 'bg-amber-50 border-amber-300 text-amber-700',
            ];
        }
        return $ous;
    }

    /** Gibt den Spezial-OU-Key zurück wenn der DN des Benutzers in einer Spezial-OU liegt */
    public function specialOuKeyForDn(?string $dn): ?string
    {
        if (!$dn) return null;
        $dnLower = strtolower($dn);
        foreach ($this->specialOus() as $key => $ou) {
            $ouLower = strtolower($ou['dn']);
            if (str_contains($dnLower, ',' . $ouLower) || strtolower($dnLower) === $ouLower) {
                return $key;
            }
        }
        return null;
    }

    protected $casts = [
        'anonymous_bind' => 'boolean',
        'use_ssl'        => 'boolean',
    ];

    /** Singleton: Einstellungen abrufen oder Standardwerte erzeugen */
    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'server'               => '',
            'port'                 => 389,
            'base_dn'              => '',
            'anonymous_bind'       => false,
            'use_ssl'              => false,
            'sync_interval_hours'  => 24,
            'max_inactive_days'    => 90,
        ]);
    }

    /** Passwort verschlüsselt speichern */
    public function setBindPasswordAttribute(?string $value): void
    {
        $this->attributes['bind_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /** Passwort entschlüsselt zurückgeben */
    public function getBindPasswordAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }
}
