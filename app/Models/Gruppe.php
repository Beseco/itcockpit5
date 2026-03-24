<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class Gruppe extends Model
{
    protected $table = 'gruppen';

    protected $fillable = ['name', 'parent_id', 'sort_order', 'vorgesetzter_user_id'];

    public function parent()
    {
        return $this->belongsTo(Gruppe::class, 'parent_id');
    }

    public function vorgesetzter()
    {
        return $this->belongsTo(User::class, 'vorgesetzter_user_id');
    }

    public function children()
    {
        return $this->hasMany(Gruppe::class, 'parent_id')->orderBy('name');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'gruppe_user');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'gruppe_role');
    }

    public function stellen()
    {
        return $this->hasMany(Stelle::class);
    }

    public function aufgabenZuweisungen()
    {
        return $this->hasMany(AufgabeZuweisung::class);
    }

    /**
     * Alle Nachkommen rekursiv laden.
     */
    public function allChildren(): Collection
    {
        $result = new Collection();
        foreach ($this->children as $child) {
            $result->push($child);
            $result = $result->merge($child->allChildren());
        }
        return $result;
    }

    /**
     * Scope: nur Root-Gruppen (ohne Parent).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('name');
    }
}
