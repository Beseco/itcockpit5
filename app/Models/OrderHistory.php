<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderHistory extends Model
{
    protected $table = 'it_order_history';

    protected $fillable = [
        'order_id',
        'changed_by',
        'field',
        'old_value',
        'new_value',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
