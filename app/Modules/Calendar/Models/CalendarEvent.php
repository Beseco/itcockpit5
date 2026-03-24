<?php

namespace App\Modules\Calendar\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    protected $fillable = [
        'user_id',
        'titel',
        'beschreibung',
        'start_at',
        'end_at',
        'ganztag',
        'typ',
        'farbe',
        'erinnerung_minuten',
        'erinnerung_gesendet',
        'wiederholung_typ',
        'wiederholung_config',
        'wiederholung_bis',
    ];

    protected $casts = [
        'start_at'            => 'datetime',
        'end_at'              => 'datetime',
        'ganztag'             => 'boolean',
        'erinnerung_gesendet' => 'boolean',
        'wiederholung_config' => 'array',
        'wiederholung_bis'    => 'date',
    ];

    const TYPEN = [
        'termin'    => 'Termin',
        'wartung'   => 'Wartung',
        'sonstiges' => 'Sonstiges',
    ];

    const STANDARD_FARBEN = [
        'termin'    => '#4f46e5',
        'wartung'   => '#dc2626',
        'sonstiges' => '#059669',
    ];

    const ERINNERUNG_OPTIONEN = [
        null  => 'Keine',
        15    => '15 Minuten vorher',
        60    => '1 Stunde vorher',
        240   => '4 Stunden vorher',
        1440  => '1 Tag vorher',
        10080 => '1 Woche vorher',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(CalendarEventAttendee::class, 'event_id');
    }

    const WIEDERHOLUNG_TYPEN = [
        ''         => 'Keine',
        'daily'    => 'Täglich',
        'weekly'   => 'Wöchentlich',
        'monthly'  => 'Monatlich',
        'yearly'   => 'Jährlich',
    ];

    const DOW_MAP = ['So'=>0,'Mo'=>1,'Di'=>2,'Mi'=>3,'Do'=>4,'Fr'=>5,'Sa'=>6];

    public function getEffektiveFarbeAttribute(): string
    {
        return $this->farbe ?: (self::STANDARD_FARBEN[$this->typ] ?? '#4f46e5');
    }

    /**
     * Alle Vorkommen des Events im angegebenen Zeitraum berechnen.
     * Gibt Carbon-Instanzen zurück (jeweils start_at des Vorkommens).
     */
    public function getOccurrences(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        if (!$this->wiederholung_typ) {
            return [$this->start_at];
        }

        $occurrences = [];
        $duration    = $this->end_at ? $this->start_at->diffInSeconds($this->end_at) : 0;
        $cfg         = $this->wiederholung_config ?? [];
        $limit       = $this->wiederholung_bis ? Carbon::parse($this->wiederholung_bis)->endOfDay() : $rangeEnd;
        $effectiveEnd = $limit->lt($rangeEnd) ? $limit : $rangeEnd;

        $cursor = $this->start_at->copy();
        $maxIterations = 500;

        while ($cursor->lte($effectiveEnd) && $maxIterations-- > 0) {
            if ($cursor->gte($rangeStart)) {
                $occurrences[] = $cursor->copy();
            }
            $cursor = $this->advance($cursor, $cfg);
        }

        return $occurrences;
    }

    private function advance(Carbon $from, array $cfg): Carbon
    {
        $every = max(1, (int)($cfg['every'] ?? 1));

        return match ($this->wiederholung_typ) {
            'daily'   => $from->copy()->addDays($every),
            'weekly'  => $this->nextWeekly($from, $cfg),
            'monthly' => $from->copy()->addMonths($every),
            'yearly'  => $from->copy()->addYears(1),
            default   => $from->copy()->addDay(),
        };
    }

    private function nextWeekly(Carbon $from, array $cfg): Carbon
    {
        $days = $cfg['days'] ?? [];
        if (empty($days)) return $from->copy()->addWeek();

        for ($i = 1; $i <= 7; $i++) {
            $candidate = $from->copy()->addDays($i);
            $key = array_search($candidate->dayOfWeek, self::DOW_MAP);
            if ($key !== false && in_array($key, $days)) {
                return $candidate;
            }
        }
        return $from->copy()->addWeek();
    }
}
