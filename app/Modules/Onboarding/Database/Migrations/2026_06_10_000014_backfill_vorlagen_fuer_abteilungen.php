<?php

use App\Models\Abteilung;
use App\Modules\Onboarding\Models\OnboardingVorlage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Für jede bestehende OE ohne Vorlage automatisch eine anlegen (1:1-Kopplung).
        $belegte = OnboardingVorlage::whereNotNull('abteilung_id')->pluck('abteilung_id')->all();

        foreach (Abteilung::orderBy('name')->get() as $abteilung) {
            if (in_array($abteilung->id, $belegte, true)) {
                continue;
            }
            OnboardingVorlage::create([
                'name'         => $abteilung->name,
                'abteilung_id' => $abteilung->id,
                'is_active'    => true,
            ]);
        }
    }

    public function down(): void
    {
        // Bewusst kein automatisches Löschen – Vorlagen könnten inzwischen
        // mit Gruppen/Adressen gepflegt worden sein.
    }
};
