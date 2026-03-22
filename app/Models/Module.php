<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Module extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the permissions associated with this module.
     *
     * @return HasMany
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(\Spatie\Permission\Models\Permission::class);
    }

    /**
     * Check if the module is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope a query to only include active modules.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
