<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stelle extends Model
{
    protected $table = 'stellen';

    protected $fillable = [
        'stellennummer',
        'stellenbeschreibung_id',
        'gruppe_id',
        'user_id',
        'haushalt_bewertung',
        'bes_gruppe',
        'belegung',
        'gesamtarbeitszeit',
        'anteil_stelle',
    ];

    protected $casts = [
        'belegung'         => 'decimal:2',
        'gesamtarbeitszeit'=> 'decimal:2',
        'anteil_stelle'    => 'decimal:2',
    ];

    public function getBezeichnungAttribute(): string
    {
        return $this->stellenbeschreibung?->bezeichnung ?? '—';
    }

    public function stellenbeschreibung(): BelongsTo
    {
        return $this->belongsTo(Stellenbeschreibung::class);
    }

    public function gruppe(): BelongsTo
    {
        return $this->belongsTo(Gruppe::class);
    }

    public function stelleninhaber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isBesetzt(): bool
    {
        return $this->user_id !== null;
    }

    public function isFrei(): bool
    {
        return $this->user_id === null;
    }
}
