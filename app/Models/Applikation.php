<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Applikation extends Model
{
    protected $table = 'applikationen';

    protected $fillable = [
        'name', 'sg', 'abteilung_id', 'einsatzzweck',
        'confidentiality', 'integrity', 'availability',
        'baustein', 'verantwortlich_sg', 'verantwortlich_ad_user_id', 'admin_user_id', 'admin', 'ansprechpartner',
        'hersteller', 'revision_date', 'doc_url', 'updated_by',
        'revision_token', 'revision_notified_at', 'revision_completed_at',
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

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Server\Models\Server::class, 'server_applikation');
    }

    protected $casts = [
        'revision_date'          => 'date',
        'revision_notified_at'   => 'datetime',
        'revision_completed_at'  => 'datetime',
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
