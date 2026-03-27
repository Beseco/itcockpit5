<?php

namespace App\Modules\Server\Models;

use App\Models\Abteilung;
use App\Models\Applikation;
use App\Models\Gruppe;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Server extends Model
{
    protected $table = 'servers';

    protected $fillable = [
        'name', 'dns_hostname', 'ip_address', 'operating_system', 'os_version',
        'description', 'bemerkungen', 'doc_url',
        'status', 'type',
        'os_type_id', 'role_id', 'backup_level_id', 'patch_ring_id',
        'abteilung_id', 'admin_user_id', 'gruppe_id',
        'distinguished_name', 'managed_by_ldap', 'ldap_synced', 'last_sync_at', 'raw_ldap_data',
    ];

    protected $casts = [
        'ldap_synced'   => 'boolean',
        'last_sync_at'  => 'datetime',
        'raw_ldap_data' => 'array',
    ];

    const STATUS_LABELS = [
        'produktiv'     => 'Produktiv',
        'testsystem'    => 'Testsystem',
        'ausgeschaltet' => 'Ausgeschaltet',
        'im_aufbau'     => 'Im Aufbau',
        'ausgemustert'  => 'Ausgemustert',
    ];

    const STATUS_COLORS = [
        'produktiv'     => 'bg-green-100 text-green-800',
        'testsystem'    => 'bg-blue-100 text-blue-800',
        'ausgeschaltet' => 'bg-gray-100 text-gray-600',
        'im_aufbau'     => 'bg-yellow-100 text-yellow-800',
        'ausgemustert'  => 'bg-red-100 text-red-700',
    ];

    const TYPE_LABELS = [
        'vm'         => 'VM',
        'bare_metal' => 'Bare Metal',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function abteilung(): BelongsTo
    {
        return $this->belongsTo(Abteilung::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function gruppe(): BelongsTo
    {
        return $this->belongsTo(Gruppe::class);
    }

    public function osType(): BelongsTo
    {
        return $this->belongsTo(ServerOption::class, 'os_type_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ServerOption::class, 'role_id');
    }

    public function backupLevel(): BelongsTo
    {
        return $this->belongsTo(ServerOption::class, 'backup_level_id');
    }

    public function patchRing(): BelongsTo
    {
        return $this->belongsTo(ServerOption::class, 'patch_ring_id');
    }

    public function applikationen(): BelongsToMany
    {
        return $this->belongsToMany(Applikation::class, 'server_applikation');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeProduktiv(Builder $q): Builder
    {
        return $q->where('status', 'produktiv');
    }

    public function scopeLdapSynced(Builder $q): Builder
    {
        return $q->where('ldap_synced', true);
    }
}
