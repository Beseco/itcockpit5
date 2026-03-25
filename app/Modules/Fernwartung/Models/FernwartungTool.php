<?php

namespace App\Modules\Fernwartung\Models;

use Illuminate\Database\Eloquent\Model;

class FernwartungTool extends Model
{
    protected $table = 'fernwartung_tools';

    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
