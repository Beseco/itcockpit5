<?php

namespace App\Modules\HH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetYear extends Model
{
    protected $table = 'hh_budget_years';

    protected $fillable = [
        'year',
        'status',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    /**
     * Get all versions for this budget year.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(BudgetYearVersion::class);
    }

    /**
     * Get the currently active version.
     */
    public function activeVersion(): HasMany
    {
        return $this->hasMany(BudgetYearVersion::class)->where('is_active', true);
    }
}
