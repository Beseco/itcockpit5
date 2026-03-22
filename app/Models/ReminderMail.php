<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderMail extends Model
{
    protected $table = 'erinnerungsmail';

    protected $fillable = [
        'user_id',
        'status',
        'titel',
        'nachricht',
        'nextsend',
        'mailto',
        'intervall_nummer',
        'intervall_faktor',
    ];

    protected $casts = [
        'nextsend'          => 'datetime',
        'status'            => 'integer',
        'intervall_nummer'  => 'integer',
        'intervall_faktor'  => 'integer',
    ];

    const FAKTOREN = [
        60    => 'Minute(n)',
        3600  => 'Stunde(n)',
        86400 => 'Tag(e)',
    ];

    public function getFaktorLabelAttribute(): string
    {
        $label = self::FAKTOREN[$this->intervall_faktor] ?? "{$this->intervall_faktor}s";
        // Singular für Wert 1
        if ($this->intervall_nummer === 1) {
            $label = rtrim($label, '(n)');
            $label = rtrim($label, '(e)');
            $label = trim($label, '()');
        }
        return $label;
    }

    public function getRestzeitAttribute(): string
    {
        if ($this->nextsend->isPast()) {
            return 'überfällig';
        }
        $diff = now()->diff($this->nextsend);
        if ($diff->days > 0) {
            return $diff->days . ' ' . ($diff->days === 1 ? 'Tag' : 'Tage');
        }
        if ($diff->h > 0) {
            return $diff->h . ' ' . ($diff->h === 1 ? 'Stunde' : 'Stunden');
        }
        return $diff->i . ' ' . ($diff->i === 1 ? 'Minute' : 'Minuten');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
