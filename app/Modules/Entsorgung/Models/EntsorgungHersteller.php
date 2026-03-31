<?php

namespace App\Modules\Entsorgung\Models;

use Illuminate\Database\Eloquent\Model;

class EntsorgungHersteller extends Model
{
    protected $table = 'entsorgung_hersteller';

    protected $fillable = ['name'];
}
