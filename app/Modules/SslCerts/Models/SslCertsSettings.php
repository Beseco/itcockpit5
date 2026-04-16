<?php

namespace App\Modules\SslCerts\Models;

use Illuminate\Database\Eloquent\Model;

class SslCertsSettings extends Model
{
    protected $table = 'ssl_certs_settings';

    protected $fillable = [
        'notification_email',
        'notifications_enabled',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
    ];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'notification_email'    => null,
            'notifications_enabled' => false,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->notifications_enabled && !empty($this->notification_email);
    }
}
