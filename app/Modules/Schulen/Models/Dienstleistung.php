<?php

namespace App\Modules\Schulen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dienstleistung extends Model
{
    protected $table = 'dienstleistungen';

    protected $fillable = [
        'dienst_kategorie_id', 'name', 'beschreibung', 'dokumentation_url',
        'stunden_modus', 'stunden_wert', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'stunden_wert' => 'float',
    ];

    /** Netto-Jahresstunden für 1 VZE (Tarifbeschäftigte Bayern) */
    const VZE_JAHRESSTUNDEN = 1600;

    /** Rechenfaktor Wochenstunden → Jahresstunden */
    const WOCHEN_FAKTOR = 46;

    public function kategorie(): BelongsTo
    {
        return $this->belongsTo(DienstKategorie::class, 'dienst_kategorie_id');
    }

    public function schulen(): BelongsToMany
    {
        return $this->belongsToMany(Schule::class, 'schule_dienstleistung')
            ->using(SchuleDienstleistung::class)
            ->withPivot(['status', 'stunden_override', 'notizen'])
            ->withTimestamps();
    }

    /** Jahresstunden für diese Dienstleistung (ohne Schul-Override) */
    public function jahresstunden(): ?float
    {
        if ($this->stunden_wert === null) {
            return null;
        }
        if ($this->stunden_modus === 'wochenstunden') {
            return $this->stunden_wert * self::WOCHEN_FAKTOR;
        }
        return $this->stunden_wert;
    }

    /** VZE-Bedarf für 1 Schule */
    public function vzeProSchule(): ?float
    {
        $h = $this->jahresstunden();
        return $h !== null ? round($h / self::VZE_JAHRESSTUNDEN, 3) : null;
    }

    public function scopeAktiv($query)
    {
        return $query->where('is_active', true);
    }
}
