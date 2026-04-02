<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DienstleisterAnsprechpartner extends Model
{
    protected $table = 'dienstleister_ansprechpartner';

    protected $fillable = [
        'dienstleister_id',
        'anrede',
        'vorname',
        'nachname',
        'funktion',
        'telefon',
        'handy',
        'email',
        'notiz',
        'sort_order',
    ];

    public function dienstleister()
    {
        return $this->belongsTo(Dienstleister::class);
    }
}
