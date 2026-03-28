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
        'deaktivierung_token', 'deaktivierung_benachrichtigt_at',
        'deaktivierung_bestaetigt_at', 'deaktivierung_bestaetigt_von',
        'loeschung_token', 'loeschung_benachrichtigt_at',
        'loeschung_bestaetigt_at', 'loeschung_bestaetigt_von',
        'personalmeldung_pdf', 'personalmeldung_pdf_name',
        'bestaetigung_pdf', 'bestaetigung_pdf_name',
        'bemerkungen', 'legacy_id', 'imported_at',
    ];

    protected $casts = [
        'datum_ausscheiden'        => 'date',
        'datum_geloescht'          => 'date',
        'bestaetigung_angefragt_at'        => 'datetime',
        'bestaetigung_erhalten_at'         => 'datetime',
        'deaktivierung_benachrichtigt_at'  => 'datetime',
        'deaktivierung_bestaetigt_at'      => 'datetime',
        'loeschung_benachrichtigt_at'      => 'datetime',
        'loeschung_bestaetigt_at'          => 'datetime',
        'imported_at'                      => 'datetime',
    ];

    const STATUS_LABELS = [
        'ausstehend'              => 'Ausstehend',
        'bestaetigung_angefragt'  => 'Bestätigung angefragt',
        'bestaetigt'              => 'Bestätigt',
        'abgeschlossen'           => 'Abgeschlossen',
        'importiert'              => 'Importiert (Altdaten)',
    ];

    const STATUS_COLORS = [
        'ausstehend'              => 'bg-yellow-100 text-yellow-800',
        'bestaetigung_angefragt'  => 'bg-blue-100 text-blue-800',
        'bestaetigt'              => 'bg-green-100 text-green-800',
        'abgeschlossen'           => 'bg-gray-100 text-gray-600',
        'importiert'              => 'bg-purple-100 text-purple-800',
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

    public function getDatumLoeschungAttribute(): \Carbon\Carbon
    {
        return $this->datum_ausscheiden->addDays(60);
    }

    /** Soll heute Deaktivierungs-Mail gesendet werden? */
    public function brauchDeaktivierungsMail(): bool
    {
        return $this->datum_ausscheiden->isToday()
            && $this->deaktivierung_benachrichtigt_at === null
            && !in_array($this->status, ['abgeschlossen']);
    }

    /** Soll heute Löschungs-Mail gesendet werden? */
    public function brauchLoeschungsMail(): bool
    {
        return $this->datum_ausscheiden->addDays(60)->isToday()
            && $this->loeschung_benachrichtigt_at === null
            && !in_array($this->status, ['abgeschlossen']);
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
