<?php

namespace App\Modules\AdUsers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdUserGroupChangeLog extends Model
{
    protected $table = 'ad_user_group_change_logs';

    protected $fillable = [
        'samaccountname',
        'user_dn',
        'group_dn',
        'group_name',
        'action',
        'performed_by_user_id',
        'reverted_at',
        'reverted_by_user_id',
        'notes',
    ];

    protected $casts = [
        'reverted_at' => 'datetime',
    ];

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function revertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by_user_id');
    }

    public function isReverted(): bool
    {
        return $this->reverted_at !== null;
    }

    public function actionLabel(): string
    {
        return $this->action === 'add' ? 'Hinzugefügt' : 'Entfernt';
    }

    public function revertActionLabel(): string
    {
        return $this->action === 'add' ? 'remove' : 'add';
    }
}
