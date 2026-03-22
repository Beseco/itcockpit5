<?php

namespace App\Modules\HH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'hh_accounts';

    protected $fillable = [
        'number',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all budget positions assigned to this account.
     */
    public function budgetPositions(): HasMany
    {
        return $this->hasMany(BudgetPosition::class);
    }
}
