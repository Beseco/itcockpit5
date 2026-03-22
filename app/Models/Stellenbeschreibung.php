<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stellenbeschreibung extends Model
{
    protected $table = 'stellenbeschreibungen';

    protected $fillable = ['bezeichnung'];

    public function arbeitsvorgaenge(): HasMany
    {
        return $this->hasMany(Arbeitsvorgang::class)->orderBy('sort_order');
    }

    public function stellen(): HasMany
    {
        return $this->hasMany(Stelle::class);
    }

    public function gesamtanteil(): int
    {
        return $this->arbeitsvorgaenge->sum('anteil');
    }
}
