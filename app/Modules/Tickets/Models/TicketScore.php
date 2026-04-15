<?php

namespace App\Modules\Tickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketScore extends Model
{
    protected $table = 'ticket_scores';

    protected $fillable = ['user_id', 'score', 'yellow_count', 'red_count', 'calculated_at'];

    protected $casts = [
        'calculated_at' => 'datetime',
        'score'         => 'decimal:1',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Letzten Score-Eintrag eines Users zurückgeben (oder null) */
    public static function forUser(int $userId): ?self
    {
        return self::where('user_id', $userId)->latest('calculated_at')->first();
    }
}
