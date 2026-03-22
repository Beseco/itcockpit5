<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountCode extends Model
{
    protected $table = 'it_account_codes';

    protected $fillable = ['code', 'description'];

    public function orders()
    {
        return $this->hasMany(Order::class, 'account_code_id');
    }
}
