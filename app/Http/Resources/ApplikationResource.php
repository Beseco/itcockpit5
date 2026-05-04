<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplikationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'sg'            => $this->sg,
            'einsatzzweck'  => $this->einsatzzweck,
            'hersteller'    => $this->hersteller,
            'baustein'      => (array) ($this->baustein ?? []),
            'confidentiality'=> $this->confidentiality,
            'integrity'     => $this->integrity,
            'availability'  => $this->availability,
            'revision_date' => $this->revision_date?->toDateString(),
            'doc_url'       => $this->doc_url,
            'abteilung'     => $this->whenLoaded('abteilung', fn() => [
                'id'   => $this->abteilung->id,
                'name' => $this->abteilung->name,
            ]),
            'admin_user'    => $this->whenLoaded('adminUser', fn() => [
                'id'   => $this->adminUser->id,
                'name' => $this->adminUser->name,
            ]),
            'created_at'    => $this->created_at->toIso8601String(),
            'updated_at'    => $this->updated_at->toIso8601String(),
        ];
    }
}
