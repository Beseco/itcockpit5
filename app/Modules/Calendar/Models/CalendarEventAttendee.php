<?php

namespace App\Modules\Calendar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEventAttendee extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'user_id',
        'email',
        'eingeladen_at',
    ];

    protected $casts = [
        'eingeladen_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEmailAddressAttribute(): string
    {
        return $this->email ?? $this->user?->email ?? '';
    }
}
