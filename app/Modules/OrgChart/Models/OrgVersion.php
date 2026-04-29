<?php

namespace App\Modules\OrgChart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class OrgVersion extends Model
{
    protected $table = 'orgchart_versions';

    protected $fillable = [
        'name', 'description', 'status', 'color_scheme', 'notes', 'created_by',
    ];

    public const STATUS_LABELS = [
        'entwurf'    => 'Entwurf',
        'abstimmung' => 'In Abstimmung',
        'aktiv'      => 'Aktiv',
        'archiviert' => 'Archiviert',
    ];

    public const COLOR_SCHEMES = [
        'klassisch' => 'Klassisch (Amber)',
        'modern'    => 'Modern (Indigo)',
        'behoerde'  => 'Behörde (Grün/Blau)',
        'bsi'       => 'BSI (Weiß)',
    ];

    public function nodes()
    {
        return $this->hasMany(OrgNode::class, 'version_id')->orderBy('sort_order');
    }

    public function rootNodes()
    {
        return $this->hasMany(OrgNode::class, 'version_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function interfaces()
    {
        return $this->hasMany(OrgInterface::class, 'version_id');
    }

    public function getTotalHeadcountAttribute(): float
    {
        return (float) $this->nodes()->sum('headcount');
    }

    public function getGroupCountAttribute(): int
    {
        return $this->nodes()->whereIn('type', ['frame', 'group'])->count();
    }

    public function getTaskCountAttribute(): int
    {
        return $this->nodes()->where('type', 'task')->count();
    }
}
