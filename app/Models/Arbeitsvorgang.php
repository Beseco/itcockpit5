<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Arbeitsvorgang extends Model
{
    protected $table = 'stellen_arbeitsvorgaenge';

    protected $fillable = ['stellenbeschreibung_id', 'stelle_id', 'betreff', 'beschreibung', 'anteil', 'sort_order'];

    protected $casts = [
        'anteil'     => 'integer',
        'sort_order' => 'integer',
    ];

    public function stellenbeschreibung(): BelongsTo
    {
        return $this->belongsTo(Stellenbeschreibung::class);
    }

    public function stelle(): BelongsTo
    {
        return $this->belongsTo(Stelle::class);
    }
}
