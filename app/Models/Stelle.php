<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stelle extends Model
{
    protected $table = 'stellen';

    protected $fillable = ['bezeichnung', 'stellennummer', 'gruppe_id', 'user_id', 'tvod_bewertung', 'stunden'];

    protected $casts = [
        'stunden' => 'decimal:1',
    ];

    const TVOD = [
        'EG1', 'EG2', 'EG3', 'EG4', 'EG5', 'EG6', 'EG7', 'EG8',
        'EG9a', 'EG9b', 'EG10', 'EG11', 'EG12', 'EG13', 'EG14', 'EG15',
        'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8a', 'S8b', 'S9', 'S10',
        'S11a', 'S11b', 'S12', 'S13', 'S14', 'S15', 'S16', 'S17', 'S18',
    ];

    public function gruppe()
    {
        return $this->belongsTo(Gruppe::class);
    }

    public function stelleninhaber()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function arbeitsvorgaenge()
    {
        return $this->hasMany(Arbeitsvorgang::class)->orderBy('sort_order');
    }

    public function gesamtanteil(): int
    {
        return (int) $this->arbeitsvorgaenge->sum('anteil');
    }

    public function isVollzeit(): bool
    {
        return $this->stunden !== null && (float) $this->stunden >= 39.0;
    }

    public function isBesetzt(): bool
    {
        return $this->user_id !== null;
    }
}
