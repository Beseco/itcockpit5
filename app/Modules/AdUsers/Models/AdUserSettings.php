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
    ];

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
