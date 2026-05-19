<?php

namespace App\Modules\Wid\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class WidSettings extends Model
{
    protected $table = 'wid_settings';

    protected $fillable = [
        'api_key', 'api_url', 'enabled', 'max_items', 'min_classification',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function getInstance(): self
    {
        return self::firstOrNew([], [
            'api_key'            => null,
            'api_url'            => 'https://wid.lsi.bybn.de/content',
            'enabled'            => false,
            'max_items'          => 20,
            'min_classification' => 'keine',
        ]);
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->api_key);
    }
}
