<?php

namespace App\Modules\OrgChart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class OrgNode extends Model
{
    protected $table = 'orgchart_nodes';

    protected $fillable = [
        'version_id', 'parent_id', 'type', 'name', 'description', 'color', 'headcount', 'sort_order',
    ];

    protected $casts = [
        'headcount' => 'float',
    ];

    public const TYPE_LABELS = [
        'top'   => 'Leitung (z.B. Gruppenleitung)',
        'staff' => 'Stabsstelle',
        'frame' => 'Gruppe / Rahmen (oberste Ebene)',
        'group' => 'Themenblock (z.B. Betrieb, Strategie)',
        'task'  => 'Aufgabe / Tätigkeit',
    ];

    public function version()
    {
        return $this->belongsTo(OrgVersion::class, 'version_id');
    }

    public function parent()
    {
        return $this->belongsTo(OrgNode::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OrgNode::class, 'parent_id')->orderBy('sort_order');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function interfacesFrom()
    {
        return $this->hasMany(OrgInterface::class, 'from_node_id');
    }

    public function interfacesTo()
    {
        return $this->hasMany(OrgInterface::class, 'to_node_id');
    }

    public function getDescendantCount(): int
    {
        $count = 0;
        foreach ($this->children as $child) {
            $count += 1 + $child->getDescendantCount();
        }
        return $count;
    }

    public function allChildren(): Collection
    {
        $result = new Collection();
        foreach ($this->children as $child) {
            $result->push($child);
            $result = $result->merge($child->allChildren());
        }
        return $result;
    }
}
