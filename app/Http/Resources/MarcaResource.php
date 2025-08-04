<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarcaResource extends JsonResource
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
            'nombre'     => $this->nombre,
            'logo'       => asset('storage/' . $this->logo),
            // 'estatus'    => $this->estatus,
            // 'created_at' => $this->created_at?->toDateTimeString(),
            // 'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
