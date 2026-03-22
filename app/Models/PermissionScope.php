<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PermissionScope extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'permission_id',
        'scope_type',
        'scope_id',
    ];

    /**
     * Get the user that owns the permission scope.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the permission associated with this scope.
     *
     * @return BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Permission::class);
    }

    /**
     * Get the scopable entity (polymorphic relationship).
     *
     * @return MorphTo
     */
    public function scopable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'scope_type', 'scope_id');
    }
}
