<?php

namespace App\Modules\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TicketsSettings extends Model
{
    protected $table = 'tickets_settings';

    protected $fillable = [
        'url', 'api_token', 'enabled',
        'email_enabled', 'email_threshold', 'score_green_max', 'score_red_min',
        'test_email', 'test_user_id',
    ];

    protected $casts = [
        'enabled'       => 'boolean',
        'email_enabled' => 'boolean',
        'email_threshold' => 'decimal:1',
        'score_green_max' => 'decimal:1',
        'score_red_min'   => 'decimal:1',
    ];

    /** Singleton: Einstellungen abrufen oder Standardwerte erzeugen */
    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'url'             => '',
            'api_token'       => null,
            'enabled'         => false,
            'email_enabled'   => false,
            'email_threshold' => 3.0,
            'score_green_max' => 3.0,
            'score_red_min'   => 6.0,
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
