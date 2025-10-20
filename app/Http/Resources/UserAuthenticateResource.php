<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAuthenticateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'foto_perfil_url' => $this->foto_perfil_url
                ? (preg_match('/^https?:\/\//', $this->foto_perfil_url)
                    ? $this->foto_perfil_url
                    : asset('storage/'.$this->foto_perfil_url))
                : null,
            'role' => new RoleResource($this->whenLoaded('role')),
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
