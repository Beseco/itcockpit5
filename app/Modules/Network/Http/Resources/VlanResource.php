<?php

namespace App\Modules\Network\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'vlan_id'         => $this->vlan_id,
            'vlan_name'       => $this->vlan_name,
            'network_address' => $this->network_address,
            'cidr_suffix'     => $this->cidr_suffix,
            'gateway'         => $this->gateway,
            'dhcp_enabled'    => $this->dhcp_enabled,
            'dhcp_from'       => $this->dhcp_from,
            'dhcp_to'         => $this->dhcp_to,
            'description'     => $this->description,
            'status'          => $this->status,
            'internes_netz'   => $this->internes_netz,
            'ip_count'        => $this->when(isset($this->ip_count), $this->ip_count),
            'ips'             => IpAddressResource::collection($this->whenLoaded('ipAddresses')),
            'created_at'      => $this->created_at->toIso8601String(),
            'updated_at'      => $this->updated_at->toIso8601String(),
        ];
    }
}
