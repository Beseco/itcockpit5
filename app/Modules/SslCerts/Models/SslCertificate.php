<?php

namespace App\Modules\SslCerts\Models;

use App\Models\User;
use App\Modules\Server\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Crypt;

class SslCertificate extends Model
{
    protected $table = 'ssl_certificates';

    protected $fillable = [
        'name', 'description', 'responsible_user_id', 'doc_url',
        'subject_cn', 'subject_o', 'subject_ou',
        'issuer_cn', 'issuer_o', 'serial_number',
        'valid_from', 'valid_to', 'san',
        'fingerprint_sha1', 'fingerprint_sha256',
        'cert_pem', 'private_key',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to'   => 'datetime',
        'san'        => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class, 'ssl_certificate_server');
    }

    // ─── Encryption ──────────────────────────────────────────────────────────

    /** Private Key verschlüsselt speichern */
    public function setPrivateKeyAttribute(?string $value): void
    {
        $this->attributes['private_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /** Private Key entschlüsselt zurückgeben */
    public function getPrivateKeyAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Ablauf-Farbe: 'red' (< 14 Tage), 'yellow' (< 30 Tage), 'green' */
    public function getExpiryColor(): string
    {
        if (!$this->valid_to) return 'green';
        $days = now()->diffInDays($this->valid_to, false);
        if ($days < 0)  return 'red';
        if ($days < 14) return 'red';
        if ($days < 30) return 'yellow';
        return 'green';
    }

    /** Restlaufzeit in Tagen (negativ = abgelaufen) */
    public function getDaysRemaining(): int
    {
        if (!$this->valid_to) return 9999;
        return (int) now()->diffInDays($this->valid_to, false);
    }
}
