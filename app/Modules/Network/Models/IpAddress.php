<?php

namespace App\Modules\Network\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAddress extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\IpAddressFactory::new();
    }

    protected $fillable = [
        'vlan_id',
        'ip_address',
        'dns_name',
        'mac_address',
        'is_online',
        'last_online_at',
        'last_scanned_at',
        'ping_ms',
        'comment',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_online_at' => 'datetime',
        'last_scanned_at' => 'datetime',
        'ping_ms' => 'float',
    ];

    /**
     * Get the VLAN that owns this IP address.
     */
    public function vlan(): BelongsTo
    {
        return $this->belongsTo(Vlan::class);
    }

    /**
     * Scope to filter online IP addresses.
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    /**
     * Scope to filter offline IP addresses.
     */
    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }

    /**
     * Scope to filter IP addresses that have never been scanned.
     */
    public function scopeNeverScanned($query)
    {
        return $query->whereNull('last_scanned_at');
    }

    /**
     * Get the CSS class for the status badge.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->last_scanned_at === null) {
            return 'bg-gray-200 text-gray-700';
        }

        return $this->is_online 
            ? 'bg-green-100 text-green-800' 
            : 'bg-gray-300 text-gray-700';
    }

    /**
     * Get the human-readable status text.
     */
    public function getStatusTextAttribute(): string
    {
        if ($this->last_scanned_at === null) {
            return 'Not Scanned';
        }

        return $this->is_online ? 'Online' : 'Offline';
    }

    /**
     * Update the IP address record with scan results.
     *
     * @param bool $isOnline Whether the IP is online
     * @param float|null $pingMs Ping response time in milliseconds
     * @param string|null $macAddress MAC address if resolved
     * @return void
     */
    public function updateFromScan(bool $isOnline, ?float $pingMs = null, ?string $macAddress = null): void
    {
        $this->is_online = $isOnline;
        $this->last_scanned_at = now();

        if ($isOnline) {
            $this->last_online_at = now();
            $this->ping_ms = $pingMs;
        }

        if ($macAddress !== null) {
            $this->mac_address = $macAddress;
        }

        $this->save();
    }

    /**
     * Check if this IP address is within the VLAN's DHCP range.
     */
    public function isInDhcpRange(): bool
    {
        $vlan = $this->vlan;

        if (!$vlan || !$vlan->dhcp_from || !$vlan->dhcp_to) {
            return false;
        }

        $ipLong = ip2long($this->ip_address);
        $dhcpFromLong = ip2long($vlan->dhcp_from);
        $dhcpToLong = ip2long($vlan->dhcp_to);

        return $ipLong >= $dhcpFromLong && $ipLong <= $dhcpToLong;
    }

    /**
     * Get formatted MAC address (uppercase with colons).
     */
    public function getFormattedMacAddress(): ?string
    {
        if (!$this->mac_address) {
            return null;
        }

        return strtoupper(str_replace('-', ':', $this->mac_address));
    }

    /**
     * Get the previous IP address in the VLAN (by numeric order).
     */
    public function getPreviousIpAddress(): ?IpAddress
    {
        // For SQLite compatibility, we'll use a different approach
        // Get all IPs in the VLAN and sort them manually
        $allIps = static::where('vlan_id', $this->vlan_id)->get();
        
        // Convert IPs to long integers and sort
        $sorted = $allIps->sortBy(function ($ip) {
            return ip2long($ip->ip_address);
        })->values();
        
        // Find current IP position
        $currentIndex = $sorted->search(function ($ip) {
            return $ip->id === $this->id;
        });
        
        // Return previous IP if exists
        if ($currentIndex > 0) {
            return $sorted[$currentIndex - 1];
        }
        
        return null;
    }

    /**
     * Get the next IP address in the VLAN (by numeric order).
     */
    public function getNextIpAddress(): ?IpAddress
    {
        // For SQLite compatibility, we'll use a different approach
        // Get all IPs in the VLAN and sort them manually
        $allIps = static::where('vlan_id', $this->vlan_id)->get();
        
        // Convert IPs to long integers and sort
        $sorted = $allIps->sortBy(function ($ip) {
            return ip2long($ip->ip_address);
        })->values();
        
        // Find current IP position
        $currentIndex = $sorted->search(function ($ip) {
            return $ip->id === $this->id;
        });
        
        // Return next IP if exists
        if ($currentIndex !== false && $currentIndex < $sorted->count() - 1) {
            return $sorted[$currentIndex + 1];
        }
        
        return null;
    }

    /**
     * Scope to filter IP addresses within DHCP range.
     */
    public function scopeInDhcpRange($query)
    {
        return $query->whereHas('vlan', function ($q) {
            $q->whereNotNull('dhcp_from')
              ->whereNotNull('dhcp_to');
        })->whereRaw('INET_ATON(ip_addresses.ip_address) >= INET_ATON(vlans.dhcp_from)')
          ->whereRaw('INET_ATON(ip_addresses.ip_address) <= INET_ATON(vlans.dhcp_to)')
          ->join('vlans', 'ip_addresses.vlan_id', '=', 'vlans.id');
    }

    /**
     * Scope to filter IP addresses with DNS names.
     */
    public function scopeHasDnsName($query)
    {
        return $query->whereNotNull('dns_name')
                     ->where('dns_name', '!=', '');
    }

    /**
     * Scope to filter IP addresses with comments.
     */
    public function scopeHasComment($query)
    {
        return $query->whereNotNull('comment')
                     ->where('comment', '!=', '');
    }

    /**
     * Scope to filter by online/offline status.
     */
    public function scopeFilterByStatus($query, ?string $status)
    {
        if ($status === 'online') {
            return $query->where('is_online', true);
        } elseif ($status === 'offline') {
            return $query->where('is_online', false);
        }

        return $query;
    }

    /**
     * Scope to search by term across multiple fields.
     */
    public function scopeSearchByTerm($query, string $term)
    {
        $normalizedTerm = str_replace([':', '-'], '', $term);

        return $query->where(function ($q) use ($term, $normalizedTerm) {
            $q->where('ip_address', 'LIKE', "%{$term}%")
              ->orWhere('dns_name', 'LIKE', "%{$term}%")
              ->orWhereRaw('REPLACE(REPLACE(mac_address, ":", ""), "-", "") LIKE ?', ["%{$normalizedTerm}%"]);
        });
    }
}
