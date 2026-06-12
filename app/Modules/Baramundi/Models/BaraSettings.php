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
        'zammad_group',
        'smb_domain',
        'smb_username',
        'smb_password',
    ];

    protected $casts = [
        'email_on_smb_error' => 'boolean',
    ];

    protected $hidden = ['smb_password'];

    public function hasSmbCredentials(): bool
    {
        return !empty($this->smb_username) && !empty($this->smb_password);
    }

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'scan_interval_minutes' => 15,
            'email_on_smb_error'    => false,
            'notification_email'    => null,
        ]);
    }
}
