<?php

namespace App\Modules\Entsorgung\Models;

use App\Models\User;
use App\Modules\AdUsers\Models\AdUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entsorgung extends Model
{
    protected $table = 'entsorgungen';

    protected $fillable = [
        'name',
        'modell',
        'hersteller',
        'typ',
        'inventar',
        'entsorger',
        'user',
        'ad_user_id',
        'grundschutz',
        'grundschutzgrund',
        'entsorgungsgrund',
        'datum',
        'created_by',
    ];

    protected $casts = [
        'datum'       => 'date',
        'grundschutz' => 'boolean',
    ];

    public function ersteller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nutzer(): BelongsTo
    {
        return $this->belongsTo(AdUser::class, 'ad_user_id');
    }

    public function kannGeloeschtWerden(): bool
    {
        return $this->created_at->diffInMinutes(now()) <= 60;
    }

    public function getNutzerNameAttribute(): string
    {
        return $this->nutzer?->anzeigenameOrName ?? $this->user ?? '—';
    }
}
