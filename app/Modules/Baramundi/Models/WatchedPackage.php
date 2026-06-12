<?php

namespace App\Modules\Baramundi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WatchedPackage extends Model
{
    protected $table = 'bara_watched_packages';

    protected $fillable = [
        'name',
        'server_name',
        'share_path',
        'enabled',
        'email_enabled',
        'download_type',
        'download_command',
        'download_url',
        'notes',
        'last_known_version',
        'last_scan',
        'last_detected',
        'status',
        'zammad_ticket_id',
    ];

    protected $casts = [
        'enabled'       => 'boolean',
        'email_enabled' => 'boolean',
        'last_scan'     => 'datetime',
        'last_detected' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'ok'               => 'OK',
        'new_version'      => 'Neue Version erkannt',
        'download_running' => 'Download läuft',
        'download_ok'      => 'Download erfolgreich',
        'download_failed'  => 'Download fehlgeschlagen',
        'smb_unreachable'  => 'SMB nicht erreichbar',
    ];

    public const STATUS_COLORS = [
        'ok'               => 'bg-green-100 text-green-700',
        'new_version'      => 'bg-blue-100 text-blue-700',
        'download_running' => 'bg-yellow-100 text-yellow-700',
        'download_ok'      => 'bg-green-100 text-green-700',
        'download_failed'  => 'bg-red-100 text-red-700',
        'smb_unreachable'  => 'bg-red-100 text-red-700',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(BaraEvent::class, 'package_id');
    }

    public function getUncPath(): string
    {
        return '\\\\' . $this->server_name . '\\' . $this->share_path;
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-600';
    }
}
