<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationSettings extends Model
{
    protected $table = 'user_notification_settings';

    protected $fillable = [
        'missing_mail_enabled',
        'missing_mail_days',
    ];

    protected function casts(): array
    {
        return [
            'missing_mail_enabled' => 'boolean',
            'missing_mail_days'    => 'integer',
        ];
    }

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'missing_mail_enabled' => false,
            'missing_mail_days'    => 30,
        ]);
    }
}
