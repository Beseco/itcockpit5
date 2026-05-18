<?php

namespace App\Modules\Schulen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schule extends Model
{
    protected $table = 'schulen';

    protected $fillable = [
        'name', 'kurzname', 'schul_typ_id', 'strasse', 'plz', 'ort',
        'telefon', 'email', 'website', 'notizen', 'sort_order',
    ];

    public function schulTyp(): BelongsTo
    {
        return $this->belongsTo(SchulTyp::class, 'schul_typ_id');
    }

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

    public function typLabel(): string
    {
        return $this->schulTyp?->name ?? '—';
    }

    public function typFarbe(): string
    {
        return $this->schulTyp?->farbe_klassen ?? 'bg-gray-100 text-gray-800';
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
