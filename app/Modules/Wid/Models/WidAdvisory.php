<?php

namespace App\Modules\Wid\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WidAdvisory extends Model
{
    protected $table = 'wid_advisories';

    protected $fillable = [
        'uuid', 'name', 'title', 'classification', 'temporal_score',
        'published', 'status', 'no_patch', 'exploit', 'fetched_at',
    ];

    protected $casts = [
        'published'  => 'datetime',
        'fetched_at' => 'datetime',
        'no_patch'   => 'boolean',
        'exploit'    => 'boolean',
    ];

    const CLASSIFICATIONS = ['keine', 'niedrig', 'mittel', 'hoch', 'kritisch'];

    const CLASSIFICATION_COLORS = [
        'keine'    => 'gray',
        'niedrig'  => 'blue',
        'mittel'   => 'yellow',
        'hoch'     => 'orange',
        'kritisch' => 'red',
    ];

    const CLASSIFICATION_ORDER = [
        'keine'    => 0,
        'niedrig'  => 1,
        'mittel'   => 2,
        'hoch'     => 3,
        'kritisch' => 4,
    ];

    public function scopeAboveMinClassification(Builder $query, string $min): Builder
    {
        $minOrder = self::CLASSIFICATION_ORDER[$min] ?? 0;
        $allowed = array_keys(array_filter(
            self::CLASSIFICATION_ORDER,
            fn($order) => $order >= $minOrder
        ));
        return $query->whereIn('classification', $allowed);
    }

    public function getColorClass(): string
    {
        return match ($this->classification) {
            'kritisch' => 'bg-red-100 text-red-800',
            'hoch'     => 'bg-orange-100 text-orange-800',
            'mittel'   => 'bg-yellow-100 text-yellow-800',
            'niedrig'  => 'bg-blue-100 text-blue-800',
            default    => 'bg-gray-100 text-gray-700',
        };
    }
}
