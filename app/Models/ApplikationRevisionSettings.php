<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ApplikationRevisionSettings extends Model
{
    protected $table = 'applikation_revision_settings';

    protected $fillable = [
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
            'enabled'        => false,
            'interval_weeks' => 1,
            'weekday'        => 5,
            'hour'           => 8,
            'last_sent_at'   => null,
        ]);
    }

    /**
     * Prüft, ob der Digest jetzt versendet werden soll:
     *   – Wochentag stimmt (1=Mo … 5=Fr, Carbon: 1=Mo … 7=So)
     *   – Aktuelle Stunde stimmt
     *   – Seit dem letzten Versand sind mindestens `interval_weeks` Wochen vergangen
     *     (oder er wurde noch nie versendet)
     */
    public function isDue(): bool
    {
        $now = now();

        // Wochentag prüfen (Carbon::dayOfWeekIso: 1=Mo … 7=So)
        if ($now->dayOfWeekIso !== (int) $this->weekday) {
            return false;
        }

        // Stunde prüfen
        if ((int) $now->hour !== (int) $this->hour) {
            return false;
        }

        // Intervall prüfen
        if ($this->last_sent_at === null) {
            return true;
        }

        $minNextSend = $this->last_sent_at->copy()->addWeeks((int) $this->interval_weeks);

        return $now->greaterThanOrEqualTo($minNextSend);
    }
}
