<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderMailLog extends Model
{
    protected $table = 'erinnerungsmail_log';

    protected $fillable = ['typ', 'nachricht'];

    protected $casts = ['typ' => 'integer'];

    const TYPEN = [
        1 => 'Log',
        2 => 'Aktion',
        3 => 'Fehler',
        4 => 'Heartbeat',
    ];
}
