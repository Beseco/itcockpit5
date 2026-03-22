<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Arbeitsvorgang extends Model
{
    protected $table = 'stellen_arbeitsvorgaenge';

    protected $fillable = ['stelle_id', 'betreff', 'beschreibung', 'anteil', 'sort_order'];

    protected $casts = [
        'anteil' => 'integer',
        'sort_order' => 'integer',
    ];

    public function stelle()
    {
        return $this->belongsTo(Stelle::class);
    }
}
