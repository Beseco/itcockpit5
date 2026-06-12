<?php

namespace App\Modules\Vertragsmanagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VertragDokument extends Model
{
    protected $table = 'vertrag_dokumente';

    protected $fillable = [
        'vertrag_id',
        'dateiname',
        'pfad',
        'groesse',
        'mime_type',
        'hochgeladen_von',
    ];

    protected $casts = [
        'groesse' => 'integer',
    ];

    public function vertrag(): BelongsTo
    {
        return $this->belongsTo(Vertrag::class, 'vertrag_id');
    }

    public function hochgeladenVon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hochgeladen_von');
    }

    /** Lesbare Dateigröße (z.B. "1,2 MB"). */
    public function getGroesseLesbarAttribute(): string
    {
        $b = $this->groesse;
        if ($b >= 1024 * 1024) return number_format($b / (1024 * 1024), 1, ',', '.') . ' MB';
        if ($b >= 1024)        return number_format($b / 1024, 0, ',', '.') . ' KB';
        return $b . ' Byte';
    }
}
