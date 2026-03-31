<?php

namespace App\Modules\Entsorgung\Models;

use Illuminate\Database\Eloquent\Model;

class EntsorgungGrund extends Model
{
    protected $table = 'entsorgung_gruende';

    protected $fillable = ['name'];
}
