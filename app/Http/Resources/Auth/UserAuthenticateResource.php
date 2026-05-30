<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAuthenticateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'telefono_codigo_pais' => $this->telefono_codigo_pais,
            'telefono' => $this->telefono,
            'foto_perfil_url' => $this->foto_perfil_url
                ? (
                    preg_match('/^https?:\/\//', $this->foto_perfil_url)
                    ? $this->foto_perfil_url
                    : asset('storage/' . ltrim($this->foto_perfil_url, '/'))
                )
                : null,
            'role' => new RoleResource($this->whenLoaded('role')),
            'estado' => $this->status,
            'solicitar_correo' => $this->solicitarCorreo(),
            'cambiar_pass_default' => $this->cambiar_pass_default,
            'email_verificado' => ! is_null($this->email_verified_at),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
