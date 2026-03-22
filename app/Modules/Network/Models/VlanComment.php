<?php

namespace App\Modules\Network\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VlanComment extends Model
{
    protected $fillable = [
        'vlan_id',
        'user_id',
        'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            $comment->created_at = now();
        });
    }

    /**
     * Get the VLAN that owns this comment.
     */
    public function vlan(): BelongsTo
    {
        return $this->belongsTo(Vlan::class);
    }

    /**
     * Get the user who created this comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the given user can delete this comment.
     *
     * @param User $user
     * @return bool
     */
    public function canDelete(User $user): bool
    {
        return $user->isSuperAdmin() || $user->id === $this->user_id;
    }
}
