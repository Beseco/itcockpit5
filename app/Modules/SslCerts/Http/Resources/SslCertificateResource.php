<?php

namespace App\Modules\SslCerts\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SslCertificateResource extends JsonResource
{
    public function toArray($request): array
    {
        $daysLeft = $this->valid_to ? now()->diffInDays($this->valid_to, false) : null;

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'subject_cn'      => $this->subject_cn,
            'subject_o'       => $this->subject_o,
            'issuer_cn'       => $this->issuer_cn,
            'valid_from'      => $this->valid_from?->toIso8601String(),
            'valid_to'        => $this->valid_to?->toIso8601String(),
            'days_left'       => $daysLeft !== null ? (int) $daysLeft : null,
            'is_expired'      => $daysLeft !== null && $daysLeft < 0,
            'san'             => $this->san,
            'urls'            => $this->urls,
            'doc_url'         => $this->doc_url,
            'responsible_user'=> $this->whenLoaded('responsibleUser', fn() => [
                'id'   => $this->responsibleUser->id,
                'name' => $this->responsibleUser->name,
            ]),
            'created_at'      => $this->created_at->toIso8601String(),
            'updated_at'      => $this->updated_at->toIso8601String(),
        ];
    }
}
