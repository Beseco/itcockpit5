<?php

namespace App\Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DhcpServer extends Model
{
    protected $table = 'network_dhcp_servers';

    protected $fillable = ['name', 'symbol', 'color', 'description'];

    public const SYMBOLS = [
        'server'   => 'Server',
        'firewall' => 'Firewall',
        'router'   => 'Router',
        'switch'   => 'Switch',
        'cloud'    => 'Cloud',
    ];

    public const COLORS = [
        'blue'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'icon' => 'text-blue-600'],
        'red'    => ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'icon' => 'text-red-600'],
        'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-700',  'icon' => 'text-green-600'],
        'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'text-yellow-600'],
        'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'icon' => 'text-purple-600'],
    ];

    public function vlans(): HasMany
    {
        return $this->hasMany(Vlan::class, 'dhcp_server_id');
    }

    public function getColorClassesAttribute(): array
    {
        return self::COLORS[$this->color] ?? self::COLORS['blue'];
    }
}
