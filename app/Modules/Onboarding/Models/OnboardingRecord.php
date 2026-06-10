<?php

namespace App\Modules\Onboarding\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingRecord extends Model
{
    protected $table = 'onboarding_records';

    const TODOS = [
        'mailbox'       => ['label' => 'E-Mail-Postfach anlegen',                  'mail_test' => true],
        'h_laufwerk'    => ['label' => 'H-Laufwerk verfügbar',                     'mail_test' => false],
        'e_laufwerk'    => ['label' => 'E-Laufwerk verfügbar (Heimatverzeichnis)', 'mail_test' => false],
        'p_laufwerk'    => ['label' => 'P-Laufwerk verfügbar',                     'mail_test' => false],
        'outlook'       => ['label' => 'Outlook einrichten',                       'mail_test' => false],
        'fachverfahren' => ['label' => 'Fachverfahren einrichten',                 'mail_test' => false],
    ];

    protected $fillable = [
        'vorlage_id', 'created_by_user_id',
        'vorname', 'nachname', 'samaccountname', 'upn', 'distinguished_name',
        'rufnummer', 'ad_attributes_snapshot', 'creation_log',
        'status', 'error_message',
        'welcome_mail_sent_at', 'supervisor_mail_sent_at',
        'mailbox_status', 'mailbox_enabled_at', 'mailbox_error',
        'phase', 'todo_token', 'todos', 'mail_test_token', 'mail_verified_at', 'completed_at',
    ];

    protected $casts = [
        'ad_attributes_snapshot'  => 'array',
        'creation_log'            => 'array',
        'welcome_mail_sent_at'    => 'datetime',
        'supervisor_mail_sent_at' => 'datetime',
        'mailbox_enabled_at'      => 'datetime',
        'todos'                   => 'array',
        'mail_verified_at'        => 'datetime',
        'completed_at'            => 'datetime',
    ];

    public function vorlage(): BelongsTo
    {
        return $this->belongsTo(OnboardingVorlage::class, 'vorlage_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getAnzeigenameAttribute(): string
    {
        return trim("{$this->vorname} {$this->nachname}");
    }

    public function isTodoDone(string $key): bool
    {
        return in_array($key, $this->todos ?? [], true);
    }

    public function allTodosDone(): bool
    {
        $done = $this->todos ?? [];
        return count(array_diff(array_keys(self::TODOS), $done)) === 0;
    }

    public function isSetupPhase(): bool
    {
        return $this->phase === 'setup';
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'erfolgreich' => ['label' => 'Erfolgreich', 'class' => 'bg-green-100 text-green-700'],
            'fehler'      => ['label' => 'Fehler', 'class' => 'bg-red-100 text-red-700'],
            default       => ['label' => 'Ausstehend', 'class' => 'bg-yellow-100 text-yellow-700'],
        };
    }
}
