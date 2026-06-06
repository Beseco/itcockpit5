<?php

namespace App\Modules\Onboarding\Models;

use App\Models\Abteilung;
use App\Modules\AdUsers\Models\AdUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingVorlage extends Model
{
    protected $table = 'onboarding_vorlagen';

    protected $fillable = [
        'name', 'beschreibung', 'abteilung_id',
        'samaccountname_pattern', 'upn_pattern',
        'rufnummer_praefix', 'fax_praefix',
        'strasse', 'plz', 'ort',
        'profilpfad_pattern', 'heimatverzeichnis_pattern', 'anmeldeskript',
        'laufwerke', 'abteilung_ad', 'ad_beschreibung', 'firma',
        'vorgesetzter_ad_user_id',
        'welcome_mail_override', 'supervisor_mail_override',
        'is_active',
    ];

    protected $casts = [
        'laufwerke'  => 'array',
        'is_active'  => 'boolean',
    ];

    public function abteilung(): BelongsTo
    {
        return $this->belongsTo(Abteilung::class);
    }

    public function vorgesetzter(): BelongsTo
    {
        return $this->belongsTo(AdUser::class, 'vorgesetzter_ad_user_id');
    }

    public function gruppen(): HasMany
    {
        return $this->hasMany(OnboardingVorlageGruppe::class, 'vorlage_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(OnboardingRecord::class, 'vorlage_id');
    }
}
