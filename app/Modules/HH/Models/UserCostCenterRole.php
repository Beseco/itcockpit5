<?php

namespace App\Modules\HH\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCostCenterRole extends Model
{
    protected $table = 'hh_user_cost_center_roles';

    protected $fillable = [
        'user_id',
        'cost_center_id',
        'role',
    ];

    /**
     * Get the user this role assignment belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cost center this role assignment belongs to.
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
