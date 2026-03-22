<?php

namespace App\Modules\HH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    protected $table = 'hh_cost_centers';

    protected $fillable = [
        'number',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all budget positions assigned to this cost center.
     */
    public function budgetPositions(): HasMany
    {
        return $this->hasMany(BudgetPosition::class);
    }

    /**
     * Get all user-role assignments for this cost center.
     */
    public function userCostCenterRoles(): HasMany
    {
        return $this->hasMany(UserCostCenterRole::class);
    }
}
