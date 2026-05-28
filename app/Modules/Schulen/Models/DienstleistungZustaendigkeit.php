<?php

namespace App\Modules\Schulen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DienstleistungZustaendigkeit extends Model
{
    protected $table = 'dienstleistung_zustaendigkeiten';

    protected $fillable = [
        'dienstleistung_id', 'aufgabe', 'lra_it', 'schule_sb', 'externer_dl', 'sort_order',
    ];

    public function dienstleistung(): BelongsTo
    {
        return $this->belongsTo(Dienstleistung::class);
    }
}
