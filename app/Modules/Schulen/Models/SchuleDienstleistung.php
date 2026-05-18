<?php

namespace App\Modules\Schulen\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SchuleDienstleistung extends Pivot
{
    protected $table = 'schule_dienstleistung';

    public $incrementing = true;

    protected $fillable = ['schule_id', 'dienstleistung_id', 'status', 'stunden_override', 'notizen'];

    protected $casts = [
        'stunden_override' => 'float',
    ];

    const STATUS_LABELS = [
        'aktiv'            => 'Aktiv',
        'geplant'          => 'Geplant',
        'nicht_vorhanden'  => 'Nicht vorhanden',
        'nicht_gewuenscht' => 'Nicht gewünscht',
        'nicht_moeglich'   => 'Nicht möglich',
    ];

    const STATUS_COLORS = [
        'aktiv'            => 'bg-green-100 text-green-800',
        'geplant'          => 'bg-yellow-100 text-yellow-800',
        'nicht_vorhanden'  => 'bg-gray-100 text-gray-500',
        'nicht_gewuenscht' => 'bg-orange-100 text-orange-800',
        'nicht_moeglich'   => 'bg-red-100 text-red-800',
    ];

    const STATUS_ICONS = [
        'aktiv'            => '✅',
        'geplant'          => '📌',
        'nicht_vorhanden'  => '–',
        'nicht_gewuenscht' => '🚫',
        'nicht_moeglich'   => '⛔',
    ];

    /** Effektive Jahresstunden: Override > Dienstleistungs-Default */
    public function effektiveStunden(?Dienstleistung $dienst = null): ?float
    {
        if ($this->stunden_override !== null) {
            return $this->stunden_override;
        }
        return $dienst?->jahresstunden();
    }
}
