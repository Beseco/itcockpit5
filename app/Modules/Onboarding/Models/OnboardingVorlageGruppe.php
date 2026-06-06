<?php

namespace App\Modules\Onboarding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingVorlageGruppe extends Model
{
    protected $table = 'onboarding_vorlage_gruppen';

    protected $fillable = [
        'vorlage_id',
        'ad_group_dn',
        'ad_group_name',
    ];

    public function vorlage(): BelongsTo
    {
        return $this->belongsTo(OnboardingVorlage::class, 'vorlage_id');
    }
}
