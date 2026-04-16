<?php

namespace App\Modules\SslCerts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SslCertificate extends Model
{
    protected $table = 'ssl_certificates';

    protected $fillable = [
        'name', 'subject_cn', 'subject_o', 'subject_ou',
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

    /** Restlaufzeit als lesbarer String */
    public function getDaysRemaining(): int
    {
        if (!$this->valid_to) return 9999;
        return (int) now()->diffInDays($this->valid_to, false);
    }
}
