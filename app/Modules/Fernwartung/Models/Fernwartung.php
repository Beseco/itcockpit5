<?php

namespace App\Modules\Fernwartung\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fernwartung extends Model
{
    protected $table = 'fernwartungen';

    protected $fillable = [
        'externer_name',
        'firma',
        'beobachter_user_id',
        'beobachter_name',
        'ziel',
        'tool',
        'datum',
        'beginn',
        'ende',
        'grund',
        'created_by',
    ];

    protected $casts = ['datum' => 'date'];

    public function beobachter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beobachter_user_id');
    }

    public function ersteller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Anzeigename des Beobachters (User-Name oder Fallback-Text) */
    public function getBeobachterLabelAttribute(): string
    {
        return $this->beobachter?->name ?? $this->beobachter_name ?? '—';
    }

    /** Darf der Eintrag noch gelöscht werden? (innerhalb 1 Stunde) */
    public function kannGeloeschtWerden(): bool
    {
        return $this->created_at->diffInMinutes(now()) <= 60;
    }
}
