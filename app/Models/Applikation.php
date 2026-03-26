<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Applikation extends Model
{
    protected $table = 'applikationen';

    protected $fillable = [
        'name', 'sg', 'abteilung_id', 'einsatzzweck',
        'confidentiality', 'integrity', 'availability',
        'baustein', 'verantwortlich_sg', 'verantwortlich_ad_user_id', 'admin_user_id', 'admin', 'ansprechpartner',
        'hersteller', 'revision_date', 'doc_url', 'updated_by',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function verantwortlichAdUser(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\AdUsers\Models\AdUser::class, 'verantwortlich_ad_user_id');
    }

    public function abteilung(): BelongsTo
    {
        return $this->belongsTo(Abteilung::class, 'abteilung_id');
    }

    protected $casts = [
        'revision_date' => 'date',
    ];

    const BAUSTEINE = [
        'APP.1'    => 'APP.1 – Anwendungen allgemein',
        'APP.2'    => 'APP.2 – Verzeichnisdienste',
        'APP.3'    => 'APP.3 – Datenbanken',
        'SYS.1'    => 'SYS.1 – Server / Betriebssysteme',
        'NET.1'    => 'NET.1 – Netzwerke',
        'IND.1'    => 'IND.1 – Individualsoftware',
        'Sonstiges'=> 'Sonstiges',
    ];

    const SCHUTZBEDARF = [
        'A' => 'Normal',
        'B' => 'Hoch',
        'C' => 'Sehr hoch',
    ];

    const SCHUTZBEDARF_FARBEN = [
        'A' => 'bg-green-100 text-green-800',
        'B' => 'bg-yellow-100 text-yellow-800',
        'C' => 'bg-red-100 text-red-800',
    ];
}
