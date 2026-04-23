<?php

namespace App\Modules\Server\Models;

use Illuminate\Database\Eloquent\Model;

class VsphereSettings extends Model
{
    protected $table = 'vsphere_settings';

    protected $fillable = [
        'enabled', 'vcenter_url', 'username', 'password', 'verify_ssl',
    ];

    protected $casts = [
        'enabled'    => 'boolean',
        'verify_ssl' => 'boolean',
    ];

    public static function getSingleton(): self
    {
        return self::firstOrNew([], [
            'enabled'     => false,
            'vcenter_url' => '',
            'username'    => '',
            'password'    => '',
            'verify_ssl'  => true,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->enabled
            && !empty($this->vcenter_url)
            && !empty($this->username)
            && !empty($this->password);
    }
}
