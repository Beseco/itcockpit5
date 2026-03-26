<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Abteilung extends Model
{
    protected $table = 'abteilungen';

    protected $fillable = ['name', 'kurzzeichen', 'parent_id', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Abteilung::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Abteilung::class, 'parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('name');
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

    /** Anzeigename: "KZ – Name" oder nur "Name" */
    public function getAnzeigenameAttribute(): string
    {
        return $this->kurzzeichen
            ? "{$this->kurzzeichen} – {$this->name}"
            : $this->name;
    }

    /** Nur Root-Einträge (ohne Parent) */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')
                     ->orderBy('sort_order')
                     ->orderBy('name');
    }
}
