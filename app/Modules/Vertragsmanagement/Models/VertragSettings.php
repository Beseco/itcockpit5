<?php

namespace App\Modules\Vertragsmanagement\Models;

use Illuminate\Database\Eloquent\Model;

class VertragSettings extends Model
{
    protected $table = 'vertrag_settings';

    protected $fillable = [
        'fallback_email',
    ];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'fallback_email' => null,
        ]);
    }
}
