<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class Aufgabe extends Model
{
    protected $table = 'aufgaben';

    protected $fillable = ['name', 'parent_id', 'sort_order'];

    public function parent()
    {
        return $this->belongsTo(Aufgabe::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Aufgabe::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function zuweisungen()
    {
        return $this->hasMany(AufgabeZuweisung::class);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order')->orderBy('name');
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
