<?php

namespace App\Modules\Network\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IpAddressResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'ip_address'    => $this->ip_address,
            'dns_name'      => $this->dns_name,
            'mac_address'   => $this->mac_address,
            'is_online'     => $this->is_online,
            'last_online_at'=> $this->last_online_at?->toIso8601String(),
            'ping_ms'       => $this->ping_ms,
            'comment'       => $this->comment,
            'vlan_id'       => $this->vlan_id,
            'vlan'          => $this->whenLoaded('vlan', fn() => [
                'id'       => $this->vlan->id,
                'vlan_id'  => $this->vlan->vlan_id,
                'vlan_name'=> $this->vlan->vlan_name,
            ]),
            'created_at'    => $this->created_at->toIso8601String(),
            'updated_at'    => $this->updated_at->toIso8601String(),
        ];
    }
}
