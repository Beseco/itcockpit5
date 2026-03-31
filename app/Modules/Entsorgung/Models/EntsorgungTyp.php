<?php

namespace App\Modules\Entsorgung\Models;

use Illuminate\Database\Eloquent\Model;

class EntsorgungTyp extends Model
{
    protected $table = 'entsorgung_typen';

    protected $fillable = ['name'];
}
