<?php

namespace App\Modules\Schulen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DienstKategorie extends Model
{
    protected $table = 'dienst_kategorien';

    protected $fillable = ['name', 'sort_order'];

    public function dienstleistungen(): HasMany
    {
        return $this->hasMany(Dienstleistung::class, 'dienst_kategorie_id')->orderBy('sort_order')->orderBy('name');
    }
}
