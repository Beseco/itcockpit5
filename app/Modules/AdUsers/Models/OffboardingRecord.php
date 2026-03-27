<?php

namespace App\Modules\AdUsers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class OffboardingRecord extends Model
{
    protected $table = 'offboarding_records';

    protected $fillable = [
        'aduser_id', 'samaccountname', 'vorname', 'nachname',
        'personalnummer', 'abteilung', 'email_bestaetigung',
        'datum_ausscheiden', 'datum_geloescht', 'geloescht_von',
        'anleger_user_id', 'anleger_name',
        'status', 'bestaetigungstoken',
        'bestaetigung_angefragt_at', 'bestaetigung_erhalten_at',
        'bestaetigung_name', 'bestaetigung_ip',
        'personalmeldung_pdf', 'personalmeldung_pdf_name',
        'bestaetigung_pdf', 'bestaetigung_pdf_name',
        'bemerkungen', 'legacy_id', 'imported_at',
    ];

    protected $casts = [
        'datum_ausscheiden'        => 'date',
        'datum_geloescht'          => 'date',
        'bestaetigung_angefragt_at'=> 'datetime',
        'bestaetigung_erhalten_at' => 'datetime',
        'imported_at'              => 'datetime',
    ];

    const STATUS_LABELS = [
        'ausstehend'              => 'Ausstehend',
        'bestaetigung_angefragt'  => 'Bestätigung angefragt',
        'bestaetigt'              => 'Bestätigt',
        'abgeschlossen'           => 'Abgeschlossen',
    ];

    const STATUS_COLORS = [
        'ausstehend'              => 'bg-yellow-100 text-yellow-800',
        'bestaetigung_angefragt'  => 'bg-blue-100 text-blue-800',
        'bestaetigt'              => 'bg-green-100 text-green-800',
        'abgeschlossen'           => 'bg-gray-100 text-gray-600',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function aduser(): BelongsTo
    {
        return $this->belongsTo(AdUser::class, 'aduser_id');
    }

    public function anleger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anleger_user_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($q)
    {
        return $q->whereIn('status', ['ausstehend', 'bestaetigung_angefragt']);
    }

    public function scopeBestaetigt($q)
    {
        return $q->where('status', 'bestaetigt');
    }

    public function scopeAbgeschlossen($q)
    {
        return $q->where('status', 'abgeschlossen');
    }

    // ─── Methoden ─────────────────────────────────────────────────────────────

    public function getVollerNameAttribute(): string
    {
        return trim($this->vorname . ' ' . $this->nachname);
    }

    public function generateToken(): void
    {
        $this->bestaetigungstoken = Str::random(64);
    }

    public function getPdfResponse(string $type): Response
    {
        $content  = $type === 'personalmeldung' ? $this->personalmeldung_pdf : $this->bestaetigung_pdf;
        $filename = $type === 'personalmeldung' ? $this->personalmeldung_pdf_name : $this->bestaetigung_pdf_name;

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . ($filename ?: $type . '.pdf') . '"',
        ]);
    }

    public function istBestaetigt(): bool
    {
        return $this->bestaetigung_erhalten_at !== null;
    }
}
