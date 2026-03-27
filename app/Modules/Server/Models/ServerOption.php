<?php

namespace App\Modules\Server\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServerOption extends Model
{
    protected $table = 'server_options';

    protected $fillable = ['category', 'label', 'sort_order'];

    const CATEGORIES = ['os_type', 'role', 'backup_level', 'patch_ring'];

    const CATEGORY_LABELS = [
        'os_type'      => 'Betriebssystem-Typ',
        'role'         => 'Rolle',
        'backup_level' => 'Backup-Stufe',
        'patch_ring'   => 'Patch-Ring',
    ];

    public function scopeCategory(Builder $q, string $cat): Builder
    {
        return $q->where('category', $cat)->orderBy('sort_order')->orderBy('label');
    }
}
