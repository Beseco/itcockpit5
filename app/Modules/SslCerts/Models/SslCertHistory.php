<?php

namespace App\Modules\SslCerts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertHistory extends Model
{
    protected $table = 'ssl_certificate_history';

    protected $fillable = [
        'ssl_certificate_id', 'user_id', 'user_name', 'action', 'note',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(SslCertificate::class, 'ssl_certificate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
