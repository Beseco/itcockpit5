<?php

namespace App\Modules\Baramundi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaraEvent extends Model
{
    protected $table = 'bara_events';

    protected $fillable = [
        'package_id',
        'version',
        'event_type',
        'message',
    ];

    public const TYPE_LABELS = [
        'version_detected'  => 'Neue Version',
        'smb_unreachable'   => 'SMB nicht erreichbar',
        'download_started'  => 'Download gestartet',
        'download_ok'       => 'Download erfolgreich',
        'download_failed'   => 'Download fehlgeschlagen',
        'config_changed'    => 'Konfiguration geändert',
        'file_provided'     => 'Datei bereitgestellt',
    ];

    public const TYPE_COLORS = [
        'version_detected'  => 'bg-blue-100 text-blue-700',
        'smb_unreachable'   => 'bg-red-100 text-red-700',
        'download_started'  => 'bg-yellow-100 text-yellow-700',
        'download_ok'       => 'bg-green-100 text-green-700',
        'download_failed'   => 'bg-red-100 text-red-700',
        'config_changed'    => 'bg-gray-100 text-gray-600',
        'file_provided'     => 'bg-green-100 text-green-700',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(WatchedPackage::class, 'package_id');
    }

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->event_type] ?? $this->event_type;
    }

    public function getTypeColor(): string
    {
        return self::TYPE_COLORS[$this->event_type] ?? 'bg-gray-100 text-gray-600';
    }
}
