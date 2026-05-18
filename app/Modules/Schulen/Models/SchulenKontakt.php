<?php

namespace App\Modules\Schulen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchulenKontakt extends Model
{
    protected $table = 'schulen_kontakte';

    protected $fillable = [
        'schule_id', 'rolle', 'vorname', 'nachname', 'telefon', 'email', 'notizen',
    ];

    const ROLLE_LABELS = [
        'rektor'         => 'Rektor/in',
        'konrektor'      => 'Konrektor/in',
        'sekretaerin'    => 'Sekretär/in',
        'systembetreuer' => 'Systembetreuer/in',
        'sonstige'       => 'Sonstige',
    ];

    public function schule(): BelongsTo
    {
        return $this->belongsTo(Schule::class, 'schule_id');
    }

    public function vollname(): string
    {
        return trim($this->vorname . ' ' . $this->nachname);
    }

    public function rollelabel(): string
    {
        return self::ROLLE_LABELS[$this->rolle] ?? $this->rolle;
    }
}
