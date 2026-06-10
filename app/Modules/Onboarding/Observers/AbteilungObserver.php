<?php

namespace App\Modules\Onboarding\Observers;

use App\Models\Abteilung;
use App\Modules\Onboarding\Models\OnboardingVorlage;

/**
 * Hält Onboarding-Vorlagen 1:1 mit den Organisationseinheiten synchron:
 * jede OE hat genau eine Vorlage. Anlegen/Umbenennen/Löschen einer OE
 * pflegt die zugehörige Vorlage automatisch mit.
 */
class AbteilungObserver
{
    public function created(Abteilung $abteilung): void
    {
        if (!OnboardingVorlage::where('abteilung_id', $abteilung->id)->exists()) {
            OnboardingVorlage::create([
                'name'         => $abteilung->name,
                'abteilung_id' => $abteilung->id,
                'is_active'    => true,
            ]);
        }
    }

    public function updated(Abteilung $abteilung): void
    {
        if ($abteilung->wasChanged('name')) {
            OnboardingVorlage::where('abteilung_id', $abteilung->id)
                ->update(['name' => $abteilung->name]);
        }
    }

    public function deleted(Abteilung $abteilung): void
    {
        OnboardingVorlage::where('abteilung_id', $abteilung->id)->delete();
    }
}
