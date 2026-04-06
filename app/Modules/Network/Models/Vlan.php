<?php

namespace App\Modules\Network\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vlan extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\VlanFactory::new();
    }

    protected $fillable = [
        'vlan_id',
        'vlan_name',
        'network_address',
        'cidr_suffix',
        'gateway',
        'dhcp_enabled',
        'dhcp_from',
        'dhcp_to',
        'dhcp_server_id',
        'description',
        'internes_netz',
        'ipscan',
        'scan_interval_minutes',
        'last_scanned_at',
    ];

    protected $casts = [
        'vlan_id' => 'integer',
        'cidr_suffix' => 'integer',
        'dhcp_enabled' => 'boolean',
        'dhcp_server_id' => 'integer',
        'internes_netz' => 'boolean',
        'ipscan' => 'boolean',
        'scan_interval_minutes' => 'integer',
        'last_scanned_at' => 'datetime',
    ];

    protected $attributes = [
        'internes_netz' => false,
        'ipscan' => false,
        'scan_interval_minutes' => 60,
    ];

    /**
     * Get the DHCP server for this VLAN.
     */
    public function dhcpServer(): BelongsTo
    {
        return $this->belongsTo(DhcpServer::class, 'dhcp_server_id');
    }

    /**
     * Get all IP addresses for this VLAN.
     */
    public function ipAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class);
    }

    /**
     * Get all comments for this VLAN.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(VlanComment::class);
    }

    /**
     * Scope to filter VLANs with scanning enabled.
     */
    public function scopeScanEnabled($query)
    {
        return $query->where('ipscan', true);
    }

    /**
     * Scope to filter VLANs that need scanning.
     */
    public function scopeNeedsScan($query)
    {
        return $query->where('ipscan', true)
            ->where(function ($q) {
                $q->whereNull('last_scanned_at')
                  ->orWhereRaw('last_scanned_at < DATE_SUB(NOW(), INTERVAL scan_interval_minutes MINUTE)');
            });
    }

    /**
     * Scope to search VLANs by term.
     * Handles numeric queries (VLAN ID exact match) and text queries (VLAN name and network address partial match).
     */
    public function scopeSearchByTerm($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            // Exact match for numeric VLAN ID
            if (is_numeric($term)) {
                $q->where('vlan_id', $term);
            }

            // Partial match for VLAN name and network address
            $q->orWhere('vlan_name', 'LIKE', "%{$term}%")
              ->orWhere('network_address', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Get the subnet in CIDR notation.
     */
    public function getSubnetAttribute(): string
    {
        return "{$this->network_address}/{$this->cidr_suffix}";
    }

    /**
     * Get the count of online IP addresses.
     */
    public function getOnlineCountAttribute(): int
    {
        return $this->ipAddresses()->where('is_online', true)->count();
    }

    /**
     * Get the total count of IP addresses.
     */
    public function getTotalIpCountAttribute(): int
    {
        return $this->ipAddresses()->count();
    }

    /**
     * Determine if this VLAN should be scanned now.
     */
    public function shouldScan(): bool
    {
        if (!$this->ipscan) {
            return false;
        }

        if ($this->last_scanned_at === null) {
            return true;
        }

        $intervalMinutes = $this->scan_interval_minutes ?? 60;
        $nextScanTime = $this->last_scanned_at->copy()->addMinutes($intervalMinutes);

        return now()->gte($nextScanTime);
    }
}
