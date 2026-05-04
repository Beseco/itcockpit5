<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'is_active'      => $this->is_active,
            'roles'          => $this->getRoleNames(),
            'last_login_at'  => $this->last_login_at?->toIso8601String(),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}
