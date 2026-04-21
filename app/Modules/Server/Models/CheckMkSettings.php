<?php

namespace App\Modules\Server\Models;

use Illuminate\Database\Eloquent\Model;

class CheckMkSettings extends Model
{
    protected $table = 'checkmk_settings';

    protected $fillable = [
        'enabled', 'url', 'site', 'username', 'secret', 'verify_ssl',
    ];

    protected $casts = [
        'enabled'    => 'boolean',
        'verify_ssl' => 'boolean',
    ];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'enabled'    => false,
            'url'        => '',
            'site'       => '',
            'username'   => 'automation',
            'secret'     => '',
            'verify_ssl' => true,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->enabled
            && !empty($this->url)
            && !empty($this->site)
            && !empty($this->username)
            && !empty($this->secret);
    }

    public function apiBase(): string
    {
        return rtrim($this->url, '/') . '/' . trim($this->site, '/') . '/check_mk/api/1.0';
    }
}
