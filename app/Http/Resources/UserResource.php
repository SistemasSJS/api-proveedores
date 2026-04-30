<?php

namespace App\Http\Resources;

use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     title="UserResource",
 *     properties={
 *
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Juan Pérez"),
 *         @OA\Property(property="email", type="string", example="juan@example.com"),
 *         @OA\Property(property="role", type="string", example="user"),
 *         @OA\Property(property="is_main", type="boolean", example=false),
 *         @OA\Property(property="status", type="string", example="activo"),
 *         @OA\Property(property="created_at", type="string", example="2023-01-01T00:00:00Z"),
 *         @OA\Property(property="updated_at", type="string", example="2023-01-01T00:00:00Z")
 *     }
 * )
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->pivot ?? null;

        $proveedor = $this->proveedorAsignado();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'foto_perfil_url' => $this->foto_perfil_url,
            'email' => $this->email,
            'role_id' => $this->whenLoaded('role', fn () => $this->role_id),
            'status' => $this->status,
            'estado' => $this->status,
            'role' => new RoleResource($this->whenLoaded('role')),
            'proveedor' => $proveedor === null ? null : [
                'id' => $proveedor->id,
                'nombre_comercial' => $proveedor->nombre_comercial,
                'razon_social' => $proveedor->razon_social,
                'rfc' => $proveedor->rfc,
                'email' => $proveedor->email,
                'telefono' => $proveedor->telefono,
                'telefono_codigo_pais' => $proveedor->telefono_codigo_pais,
                'direccion_empresa' => $proveedor->direccion_empresa,
                'logo' => $proveedor->logo
                    ? Storage::disk('public')->url($proveedor->logo)
                    : null,
            ],
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'extra_data' => $pivot ? [
                'tipo_relacion' => $pivot->tipo_relacion,
                'activo' => $pivot->activo,
                'estado' => $pivot->estado ?? 'registrado',
                'fecha_asignacion' => optional($pivot->fecha_asignacion)->toDateTimeString(),
                'fecha_desasignacion' => optional($pivot->fecha_desasignacion)->toDateTimeString(),
                'observaciones' => $pivot->observaciones,
            ] : null,
        ];
    }

    /**
     * Proveedor principal o secundario activo para vistas de administración (listado/detalle).
     */
    protected function proveedorAsignado(): ?Proveedor
    {
        /** @var User $user */
        $user = $this->resource;

        if ($user->relationLoaded('proveedores')) {
            if ($user->proveedores->isEmpty()) {
                return null;
            }

            $candidatos = $user->proveedores->filter(function (Proveedor $prov) {
                $pivot = $prov->pivot;

                return $pivot
                    && $pivot->activo
                    && in_array($pivot->tipo_relacion, ['PRINCIPAL', 'SECUNDARIO'], true);
            });

            $principal = $candidatos->first(fn (Proveedor $p) => $p->pivot->tipo_relacion === 'PRINCIPAL');

            return $principal ?? $candidatos->first();
        }

        return $user->proveedorPrincipal();
    }
}
