<?php

namespace App\Modules\Server\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServerSyncOu extends Model
{
    protected $table = 'server_sync_ous';

    protected $fillable = [
        'distinguished_name',
        'label',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true)->orderBy('sort_order');
    }
}
