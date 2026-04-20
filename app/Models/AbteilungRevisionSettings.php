<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbteilungRevisionSettings extends Model
{
    protected $table = 'abteilung_revision_settings';

    protected $fillable = [
        'new_app_email',
        'enabled',
        'interval_weeks',
        'weekday',
        'hour',
        'last_sent_at',
    ];

    protected $casts = [
        'enabled'      => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'new_app_email'  => 'informatiotechnik@kreis-fs.de',
            'enabled'        => false,
            'interval_weeks' => 1,
            'weekday'        => 5,
            'hour'           => 8,
        ]);
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
