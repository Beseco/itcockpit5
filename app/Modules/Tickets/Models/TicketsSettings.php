<?php

namespace App\Modules\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TicketsSettings extends Model
{
    protected $table = 'tickets_settings';

    protected $fillable = ['url', 'api_token', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /** Singleton: Einstellungen abrufen oder Standardwerte erzeugen */
    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'url'       => '',
            'api_token' => null,
            'enabled'   => false,
        ]);
    }

    /** API-Token verschlüsselt speichern */
    public function setApiTokenAttribute(?string $value): void
    {
        $this->attributes['api_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /** API-Token entschlüsselt zurückgeben */
    public function getApiTokenAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    /** Prüft ob die Zammad-Verbindung vollständig konfiguriert ist */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->url) && !empty($this->api_token);
    }
}
