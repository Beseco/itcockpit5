<?php

namespace App\Modules\Vertragsmanagement\Models;

use App\Models\Dienstleister;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vertrag extends Model
{
    protected $table = 'vertraege';

    protected $fillable = [
        'name',
        'dienstleister_id',
        'vertragsbeginn',
        'vertragsende',
        'kuendigungsfrist_monate',
        'erinnerung_vorlauf_wochen',
        'benachrichtigungs_email',
        'status',
        'notizen',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'vertragsbeginn'            => 'date',
        'vertragsende'              => 'date',
        'kuendigungsfrist_monate'   => 'integer',
        'erinnerung_vorlauf_wochen' => 'integer',
        'last_reminder_sent_at'     => 'datetime',
    ];

    public const STATUS = [
        'aktiv'      => 'Aktiv',
        'gekündigt'  => 'Gekündigt',
        'abgelaufen' => 'Abgelaufen',
    ];

    public const STATUS_COLORS = [
        'aktiv'      => 'bg-green-100 text-green-700',
        'gekündigt'  => 'bg-amber-100 text-amber-700',
        'abgelaufen' => 'bg-gray-100 text-gray-500',
    ];

    // ── Relationen ──────────────────────────────────────────────────────────

    public function dienstleister(): BelongsTo
    {
        return $this->belongsTo(Dienstleister::class, 'dienstleister_id');
    }

    public function dokumente(): HasMany
    {
        return $this->hasMany(VertragDokument::class, 'vertrag_id')->latest();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /** Empfänger der Erinnerungs-Mail: individuell → Fallback → globale From-Adresse. */
    public function getEmpfaengerEmail(): ?string
    {
        return $this->benachrichtigungs_email
            ?: VertragSettings::getSingleton()->fallback_email
            ?: config('mail.from.address');
    }

    /** Kündigungsstichtag = Vertragsende minus Kündigungsfrist (Monate). */
    public function getKuendigungsstichtag(): ?Carbon
    {
        if (!$this->vertragsende) {
            return null;
        }
        return $this->kuendigungsfrist_monate
            ? $this->vertragsende->copy()->subMonths($this->kuendigungsfrist_monate)
            : $this->vertragsende->copy();
    }

    /** Beginn der Erinnerungsphase = Vertragsende minus Vorlauf-Wochen. */
    public function getErinnerungsstart(): ?Carbon
    {
        if (!$this->vertragsende) {
            return null;
        }
        return $this->vertragsende->copy()->subWeeks($this->erinnerung_vorlauf_wochen);
    }

    /** Liegt der Vertrag aktuell in der Erinnerungsphase? */
    public function isInErinnerungsphase(): bool
    {
        if ($this->status !== 'aktiv' || !$this->vertragsende) {
            return false;
        }
        $heute = Carbon::today();
        return $heute->gte($this->getErinnerungsstart())
            && $heute->lte($this->vertragsende);
    }

    public function getStatusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-600';
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Aktive Verträge mit Enddatum, die aktuell in der Erinnerungsphase liegen
     * (heute liegt zwischen Erinnerungsstart und Vertragsende).
     */
    public function scopeFaelligFuerErinnerung(Builder $query): Builder
    {
        return $query->where('status', 'aktiv')
            ->whereNotNull('vertragsende')
            ->whereDate('vertragsende', '>=', Carbon::today())
            ->whereRaw('vertragsende <= DATE_ADD(?, INTERVAL erinnerung_vorlauf_wochen WEEK)', [Carbon::today()->toDateString()]);
    }
}
