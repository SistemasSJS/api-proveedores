<?php

namespace App\Http\Resources;

use App\Models\Role;
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
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            // 'foto_perfil_url'   => $this->foto_perfil_url,

            'foto_perfil_url' => asset('storage/' . $this->foto_perfil_url),

            'role'              => new RoleResource($this->whenLoaded('role')),
            /**
             * NOTE: Extra data no debería ser parte del recurso de usuario autenticado.
             *      En su lugar, se debería crear un recurso específico para el proveedor
             *      y usarlo en el controlador correspondiente.
             *      La ruta seria algio como:
             *     /api/proveedor/{id}/user  
             *      En el controlador se puede usar el recurso de usuario autenticado
             */
            // 
            // 'extra_data'    => $this->when(
            //     $this->role->nombre === 'PROVEEDOR',
            //     fn() => new ProveedorResource($this->main_proveedor)
            // ),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
