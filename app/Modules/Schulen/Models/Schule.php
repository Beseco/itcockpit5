<?php

namespace App\Modules\Schulen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schule extends Model
{
    protected $table = 'schulen';

    protected $fillable = [
        'name', 'schultyp', 'strasse', 'plz', 'ort',
        'telefon', 'email', 'website', 'notizen', 'sort_order',
    ];

    const SCHULTYP_LABELS = [
        'realschule' => 'Realschule',
        'gymnasium'  => 'Gymnasium',
        'sonstige'   => 'Sonstige Schule',
    ];

    const SCHULTYP_COLORS = [
        'realschule' => 'bg-blue-100 text-blue-800',
        'gymnasium'  => 'bg-purple-100 text-purple-800',
        'sonstige'   => 'bg-gray-100 text-gray-800',
    ];

    public function kontakte(): HasMany
    {
        return $this->hasMany(SchulenKontakt::class, 'schule_id')->orderBy('rolle')->orderBy('nachname');
    }

    public function dienstleistungen(): BelongsToMany
    {
        return $this->belongsToMany(Dienstleistung::class, 'schule_dienstleistung')
            ->using(SchuleDienstleistung::class)
            ->withPivot(['status', 'stunden_override', 'notizen'])
            ->withTimestamps();
    }

    public function schultyplabel(): string
    {
        return self::SCHULTYP_LABELS[$this->schultyp] ?? $this->schultyp;
    }

    public function aktiveDienstleistungenCount(): int
    {
        return $this->dienstleistungen()->wherePivot('status', 'aktiv')->count();
    }

    public function adresse(): string
    {
        $parts = array_filter([$this->strasse, trim($this->plz . ' ' . $this->ort)]);
        return implode(', ', $parts) ?: '—';
    }
}
