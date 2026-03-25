<?php

namespace App\Modules\AdUsers\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdUser extends Model
{
    protected $table = 'adusers';

    protected $fillable = [
        'samaccountname', 'vorname', 'nachname', 'anzeigename',
        'email', 'organisation', 'abteilung', 'telefon',
        'distinguished_name', 'ad_vorhanden', 'ad_aktiv',
        'letzter_import_at', 'raw_data',
    ];

    protected $casts = [
        'ad_vorhanden'     => 'boolean',
        'ad_aktiv'         => 'boolean',
        'letzter_import_at' => 'datetime',
        'raw_data'         => 'array',
    ];

    public function getAnzeigenameOrNameAttribute(): string
    {
        return $this->anzeigename
            ?? trim("{$this->vorname} {$this->nachname}")
            ?: $this->samaccountname;
    }

    /** Status-Badge Text + Farbe */
    public function getStatusBadgeAttribute(): array
    {
        if (!$this->ad_vorhanden) {
            return ['label' => 'Nicht vorhanden', 'class' => 'bg-red-100 text-red-700'];
        }
        if (!$this->ad_aktiv) {
            return ['label' => 'Deaktiviert', 'class' => 'bg-yellow-100 text-yellow-700'];
        }
        return ['label' => 'Aktiv', 'class' => 'bg-green-100 text-green-700'];
    }

    /** Seit X Tagen nicht mehr synchronisiert */
    public function istVeraltet(int $maxTage): bool
    {
        return $this->letzter_import_at === null
            || $this->letzter_import_at->diffInDays(now()) > $maxTage;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeAktiv(Builder $q): Builder
    {
        return $q->where('ad_aktiv', true)->where('ad_vorhanden', true);
    }

    public function scopeNichtVorhanden(Builder $q): Builder
    {
        return $q->where('ad_vorhanden', false);
    }

    public function scopeInaktivSeit(Builder $q, int $tage): Builder
    {
        return $q->where('letzter_import_at', '<', now()->subDays($tage));
    }
}
