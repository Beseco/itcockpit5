<?php

namespace App\Modules\Backup\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSettings extends Model
{
    protected $table = 'backup_settings';

    protected $fillable = [
        'schedule_time',
        'retention_count',
        'backup_db',
        'backup_files',
        'backup_exports',
    ];

    protected $casts = [
        'backup_db'      => 'boolean',
        'backup_files'   => 'boolean',
        'backup_exports' => 'boolean',
    ];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'schedule_time'   => '05:00',
            'retention_count' => 7,
            'backup_db'       => true,
            'backup_files'    => true,
            'backup_exports'  => true,
        ]);
    }
}
