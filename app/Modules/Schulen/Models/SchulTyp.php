<?php

namespace App\Modules\Schulen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchulTyp extends Model
{
    protected $table = 'schul_typen';

    protected $fillable = ['name', 'farbe_klassen', 'sort_order'];

    const FARBEN = [
        'bg-blue-100 text-blue-800'   => 'Blau',
        'bg-purple-100 text-purple-800' => 'Lila',
        'bg-green-100 text-green-800' => 'Grün',
        'bg-yellow-100 text-yellow-800' => 'Gelb',
        'bg-red-100 text-red-800'     => 'Rot',
        'bg-orange-100 text-orange-800' => 'Orange',
        'bg-pink-100 text-pink-800'   => 'Pink',
        'bg-indigo-100 text-indigo-800' => 'Indigo',
        'bg-teal-100 text-teal-800'   => 'Türkis',
        'bg-gray-100 text-gray-800'   => 'Grau',
    ];

    public function schulen(): HasMany
    {
        return $this->hasMany(Schule::class, 'schul_typ_id');
    }
}
