<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    protected $table = 'it_cost_centers';

    protected $fillable = ['number', 'description'];

    public function orders()
    {
        return $this->hasMany(Order::class, 'cost_center_id');
    }
}
