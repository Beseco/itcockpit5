<?php

namespace App\Modules\HH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetPosition extends Model
{
    protected $table = 'hh_budget_positions';

    protected $fillable = [
        'budget_year_version_id',
        'cost_center_id',
        'account_id',
        'project_name',
        'description',
        'amount',
        'start_year',
        'end_year',
        'is_recurring',
        'priority',
        'category',
        'status',
        'origin_position_id',
        'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'start_year'   => 'integer',
        'end_year'     => 'integer',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the budget year version this position belongs to.
     */
    public function budgetYearVersion(): BelongsTo
    {
        return $this->belongsTo(BudgetYearVersion::class);
    }

    /**
     * Get the cost center this position is assigned to.
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * Get the account (Sachkonto) this position is assigned to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the origin position this position was copied from (Mehrjahresplanung).
     */
    public function originPosition(): BelongsTo
    {
        return $this->belongsTo(BudgetPosition::class, 'origin_position_id');
    }

    /**
     * Get all positions that were copied from this position (Mehrjahresplanung).
     */
    public function copies(): HasMany
    {
        return $this->hasMany(BudgetPosition::class, 'origin_position_id');
    }
}
