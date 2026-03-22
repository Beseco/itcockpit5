<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'message',
        'starts_at',
        'ends_at',
        'is_fixed',
        'fixed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_fixed' => 'boolean',
        'fixed_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active announcements.
     * Active announcements are those within their time window and not expired (8-hour rule for fixed).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        $now = now();
        $eightHoursAgo = now()->subHours(8);

        return $query
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($q) use ($eightHoursAgo) {
                $q->where('is_fixed', false)
                  ->orWhere('fixed_at', '>=', $eightHoursAgo);
            });
    }

    /**
     * Scope a query to only include critical announcements.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCritical($query)
    {
        return $query->where('type', 'critical');
    }

    /**
     * Scope a query to only include maintenance announcements.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMaintenance($query)
    {
        return $query->where('type', 'maintenance');
    }

    /**
     * Scope a query to only include info announcements.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInfo($query)
    {
        return $query->where('type', 'info');
    }

    /**
     * Check if the announcement is critical.
     *
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->type === 'critical';
    }

    /**
     * Check if the announcement is resolved (marked as fixed).
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->is_fixed;
    }

    /**
     * Get the Tailwind CSS color class for the announcement.
     *
     * @return string
     */
    public function getColorClass(): string
    {
        if ($this->isCritical() && !$this->isResolved()) {
            return 'bg-red-100 border-red-500 text-red-900';
        }
        
        if ($this->isCritical() && $this->isResolved()) {
            return 'bg-green-100 border-green-500 text-green-900';
        }
        
        if ($this->type === 'maintenance') {
            return 'bg-yellow-100 border-yellow-500 text-yellow-900';
        }
        
        return 'bg-blue-100 border-blue-500 text-blue-900';
    }

    /**
     * Get the icon class for the announcement.
     *
     * @return string
     */
    public function getIconClass(): string
    {
        return match($this->type) {
            'critical' => $this->isResolved() ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle',
            'maintenance' => 'heroicon-o-wrench',
            'info' => 'heroicon-o-information-circle',
            default => 'heroicon-o-bell',
        };
    }
}
