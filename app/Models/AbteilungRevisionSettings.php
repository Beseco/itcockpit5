<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbteilungRevisionSettings extends Model
{
    protected $table = 'abteilung_revision_settings';

    protected $fillable = ['new_app_email'];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'new_app_email' => 'informatiotechnik@kreis-fs.de',
        ]);
    }
}
