<?php

namespace App\Modules\Onboarding\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingRecord extends Model
{
    protected $table = 'onboarding_records';

    protected $fillable = [
        'vorlage_id', 'created_by_user_id',
        'vorname', 'nachname', 'samaccountname', 'upn', 'distinguished_name',
        'rufnummer', 'ad_attributes_snapshot',
        'status', 'error_message',
        'welcome_mail_sent_at', 'supervisor_mail_sent_at',
    ];

    protected $casts = [
        'ad_attributes_snapshot'  => 'array',
        'welcome_mail_sent_at'    => 'datetime',
        'supervisor_mail_sent_at' => 'datetime',
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

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'erfolgreich' => ['label' => 'Erfolgreich', 'class' => 'bg-green-100 text-green-700'],
            'fehler'      => ['label' => 'Fehler', 'class' => 'bg-red-100 text-red-700'],
            default       => ['label' => 'Ausstehend', 'class' => 'bg-yellow-100 text-yellow-700'],
        };
    }
}
