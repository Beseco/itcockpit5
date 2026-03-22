<?php

namespace App\Modules\HH\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditEntry extends Model
{
    /**
     * Audit entries are immutable – no updated_at column exists.
     */
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $table = 'hh_audit_entries';

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'field',
        'old_value',
        'new_value',
        'created_at',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Audit entries are immutable – updates are not allowed.
     *
     * @throws LogicException
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('AuditEntry records are immutable and cannot be updated.');
    }

    /**
     * Get the user who created this audit entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
