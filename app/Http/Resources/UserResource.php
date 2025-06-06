<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


/**
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     title="UserResource",
 *     properties={
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Juan Pérez"),
 *         @OA\Property(property="email", type="string", example="juan@example.com"),
 *         @OA\Property(property="role", type="string", example="user"),
 *         @OA\Property(property="is_main", type="boolean", example=false),
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
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'foto_perfil_url' => $this->foto_perfil_url,
            'email'      => $this->email,
            'role'       => new RoleResource($this->whenLoaded('role')),
            'is_main'    => $this->pivot->is_main ?? false,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
