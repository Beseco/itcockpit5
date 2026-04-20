<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Abteilung extends Model
{
    protected $table = 'abteilungen';

    protected $fillable = [
        'name', 'kurzzeichen', 'parent_id', 'sort_order',
        'vorgesetzter_ad_user_id', 'stellvertreter_ad_user_id',
        'revision_date', 'revision_token', 'revision_notified_at', 'revision_completed_at',
    ];

    protected $casts = [
        'revision_date'          => 'date',
        'revision_notified_at'   => 'datetime',
        'revision_completed_at'  => 'datetime',
    ];

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

    public function vorgesetzter(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\AdUsers\Models\AdUser::class, 'vorgesetzter_ad_user_id');
    }

    public function stellvertreter(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\AdUsers\Models\AdUser::class, 'stellvertreter_ad_user_id');
    }

    public function applikationen(): HasMany
    {
        return $this->hasMany(Applikation::class, 'abteilung_id');
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

    public function ensureRevisionToken(): string
    {
        if (!$this->revision_token) {
            $this->revision_token = Str::random(64);
            $this->save();
        }
        return $this->revision_token;
    }
}
