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
        'profilpfad_pattern', 'heimatverzeichnis_pattern', 'heimatverzeichnis_laufwerk', 'anmeldeskript',
        'laufwerke', 'abteilung_ad', 'ad_beschreibung', 'buero', 'firma',
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

    // ─── Effektive Werte (eigener Wert oder globale Vorgabe aus den Einstellungen) ──

    private static ?OnboardingSettings $settingsCache = null;

    private static function settings(): OnboardingSettings
    {
        return self::$settingsCache ??= OnboardingSettings::getSingleton();
    }

    /** Eigener Wert wenn gesetzt, sonst die globale Vorgabe. */
    private function withDefault(?string $own, string $settingsField): ?string
    {
        $own = $own !== null ? trim($own) : '';
        return $own !== '' ? $own : (self::settings()->{$settingsField} ?: null);
    }

    public function effectiveSamaccountnamePattern(): ?string
    {
        return $this->withDefault($this->samaccountname_pattern, 'default_samaccountname_pattern');
    }

    public function effectiveUpnPattern(): ?string
    {
        return $this->withDefault($this->upn_pattern, 'default_upn_pattern');
    }

    public function effectiveProfilpfadPattern(): ?string
    {
        return $this->withDefault($this->profilpfad_pattern, 'default_profilpfad_pattern');
    }

    public function effectiveHeimatverzeichnisPattern(): ?string
    {
        return $this->withDefault($this->heimatverzeichnis_pattern, 'default_heimatverzeichnis_pattern');
    }

    public function effectiveHeimatverzeichnisLaufwerk(): ?string
    {
        return $this->withDefault($this->heimatverzeichnis_laufwerk, 'default_heimatverzeichnis_laufwerk');
    }

    public function effectiveAnmeldeskript(): ?string
    {
        return $this->withDefault($this->anmeldeskript, 'default_anmeldeskript');
    }

    public function effectiveLaufwerke(): ?array
    {
        return !empty($this->laufwerke) ? $this->laufwerke : (self::settings()->default_laufwerke ?: null);
    }
}
