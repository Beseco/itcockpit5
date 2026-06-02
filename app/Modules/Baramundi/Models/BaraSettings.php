<?php

namespace App\Modules\Baramundi\Models;

use Illuminate\Database\Eloquent\Model;

class BaraSettings extends Model
{
    protected $table = 'bara_settings';

    protected $fillable = [
        'scan_interval_minutes',
        'email_on_smb_error',
        'notification_email',
    ];

    protected $casts = [
        'email_on_smb_error' => 'boolean',
    ];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'scan_interval_minutes' => 15,
            'email_on_smb_error'    => false,
            'notification_email'    => null,
        ]);
    }
}
