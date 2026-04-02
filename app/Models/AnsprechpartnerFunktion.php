<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnsprechpartnerFunktion extends Model
{
    protected $table = 'dienstleister_ansprechpartner_funktionen';

    protected $fillable = ['name', 'sort_order'];
}
