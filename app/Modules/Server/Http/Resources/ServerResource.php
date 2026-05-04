<?php

namespace App\Modules\Server\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'dns_hostname'     => $this->dns_hostname,
            'ip_address'       => $this->ip_address,
            'status'           => $this->status,
            'type'             => $this->type,
            'operating_system' => $this->operating_system,
            'os_version'       => $this->os_version,
            'description'      => $this->description,
            'bemerkungen'      => $this->bemerkungen,
            'doc_url'          => $this->doc_url,
            'revision_date'    => $this->revision_date?->toDateString(),
            'abteilung'        => $this->whenLoaded('abteilung', fn() => [
                'id'   => $this->abteilung->id,
                'name' => $this->abteilung->name,
            ]),
            'admin_user'       => $this->whenLoaded('adminUser', fn() => [
                'id'   => $this->adminUser->id,
                'name' => $this->adminUser->name,
            ]),
            'cpu_count'        => $this->cpu_count,
            'memory_mb'        => $this->memory_mb,
            'disk_gb'          => $this->disk_gb,
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
