<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbteilungRevisionProposal extends Model
{
    protected $table = 'abteilung_revision_proposals';

    protected $fillable = [
        'abteilung_revision_token',
        'applikation_id',
        'original_data',
        'proposed_data',
        'reason',
        'approval_token',
        'approved_at',
        'skipped',
    ];

    protected $casts = [
        'original_data' => 'array',
        'proposed_data' => 'array',
        'approved_at'   => 'datetime',
        'skipped'       => 'boolean',
    ];

    public function applikation(): BelongsTo
    {
        return $this->belongsTo(Applikation::class, 'applikation_id');
    }
}
