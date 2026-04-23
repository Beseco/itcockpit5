<?php

namespace App\Modules\Server\Models;

use Illuminate\Database\Eloquent\Model;

class ServerAdminNotificationSettings extends Model
{
    protected $table = 'server_admin_notification_settings';

    protected $fillable = [
        'enabled', 'email', 'interval_weeks', 'weekday', 'hour', 'last_sent_at',
    ];

    protected $casts = [
        'enabled'      => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'enabled'        => false,
            'email'          => '',
            'interval_weeks' => 2,
            'weekday'        => 5,
            'hour'           => 9,
            'last_sent_at'   => null,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->enabled && filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }

    public function isDue(): bool
    {
        $now = now();

        if ($now->dayOfWeekIso !== (int) $this->weekday) {
            return false;
        }

        if ((int) $now->hour !== (int) $this->hour) {
            return false;
        }

        if ($this->last_sent_at === null) {
            return true;
        }

        return $now->greaterThanOrEqualTo(
            $this->last_sent_at->copy()->addWeeks((int) $this->interval_weeks)
        );
    }
}
