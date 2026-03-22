<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dienstleister extends Model
{
    protected $table = 'dienstleister';

    protected $fillable = [
        'firmenname',
        'strasse',
        'plz',
        'ort',
        'land',
        'website',
        'email',
        'telefon',
        'bemerkungen',
        'dienstleister_typ',
        'fachgebiet',
        'leistungsbeschreibung',
        'kritischer_dienstleister',
        'verarbeitet_personenbezogene_daten',
        'av_vertrag_vorhanden',
        'av_vertrag_datum',
        'av_bemerkungen',
        'status',
        'bewertung_gesamt',
        'bewertung_fachlich',
        'bewertung_zuverlaessigkeit',
        'empfehlung',
        'bewertungsnotiz',
        'verantwortliche_stelle',
        'angelegt_am',
        'aktualisiert_am',
    ];

    protected $casts = [
        'kritischer_dienstleister'           => 'boolean',
        'verarbeitet_personenbezogene_daten' => 'boolean',
        'av_vertrag_vorhanden'               => 'boolean',
        'av_vertrag_datum'                   => 'date',
        'empfehlung'                         => 'boolean',
        'bewertung_gesamt'                   => 'integer',
        'bewertung_fachlich'                 => 'integer',
        'bewertung_zuverlaessigkeit'         => 'integer',
        'angelegt_am'                        => 'datetime',
        'aktualisiert_am'                    => 'datetime',
    ];

    public const TYPEN = [
        'Hardware'       => 'Hardware Lieferant',
        'Software'       => 'Software Hersteller',
        'Consulting'     => 'Beratung / Consulting',
        'Dienstleistung' => 'Allg. Dienstleistung',
        'Handwerk'       => 'Handwerk / Reparatur',
        'Sonstiges'      => 'Sonstiges',
    ];

    public const STATUS = [
        'aktiv'      => 'Aktiv',
        'inaktiv'    => 'Inaktiv / Ehemalig',
        'gesperrt'   => 'Gesperrt (Blacklist)',
        'potenziell' => 'Potenziell (Lead)',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'vendor_id');
    }
}
