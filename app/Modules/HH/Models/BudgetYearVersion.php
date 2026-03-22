<?php

namespace App\Modules\HH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetYearVersion extends Model
{
    protected $table = 'hh_budget_year_versions';

    public $timestamps = false;

    protected $fillable = [
        'budget_year_id',
        'version_number',
        'is_active',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the budget year this version belongs to.
     */
    public function budgetYear(): BelongsTo
    {
        return $this->belongsTo(BudgetYear::class);
    }

    /**
     * Get all budget positions in this version.
     */
    public function budgetPositions(): HasMany
    {
        return $this->hasMany(BudgetPosition::class);
    }
}
