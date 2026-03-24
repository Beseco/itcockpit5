<?php

namespace App\Modules\Calendar\Models;

use App\Models\User;
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
    ];

    protected $casts = [
        'start_at'            => 'datetime',
        'end_at'              => 'datetime',
        'ganztag'             => 'boolean',
        'erinnerung_gesendet' => 'boolean',
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

    public function getEffektiveFarbeAttribute(): string
    {
        return $this->farbe ?: (self::STANDARD_FARBEN[$this->typ] ?? '#4f46e5');
    }
}
