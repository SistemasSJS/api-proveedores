<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'nombre'                     => $this->nombre_comercial             ,
            'rfc'                        => $this->rfc,
            'telefono'                   => $this->telefono,
            'email'                      => $this->email,
            'direccion'                  => $this->direccion,
            // 'logo'                       => $this->logo,
            'logo'                       => $this->logo
                ? (preg_match('/^https?:\/\//', $this->logo) ? $this->logo : asset('storage/' . $this->logo))
                : null,
            'tipo_persona'              => $this->tipo_persona,
            'direccion_fiscal'          => $this->direccion_fiscal,
            'estado'                     => $this->estado,
            'municipio'                  => $this->municipio,
            'codigo_postal'             => $this->codigo_postal,
            'estatus'                    => $this->estatus,
            'notas'                      => $this->notas,
            'validado_por'              => $this->validado_por,
            'nombre_propietario'        => $this->nombre_propietario,
            'nombre_de_quien_registra'  => $this->nombre_de_quien_registra,
            'nombre_comercial'          => $this->nombre_comercial,
            'razon_social'              => $this->razon_social,
            'tipos_empresa_id'          => $this->tipos_empresa_id,
            'tipos_empresa_otro'        => $this->tipos_empresa_otro,
            'descripcion_giro_empresa'  => $this->descripcion_giro_empresa,
            'direccion_empresa'         => $this->direccion_empresa,
            'pagina_web'                => $this->pagina_web,
            'contacto_nombre'           => $this->contacto_nombre,
            'contacto_cargo'            => $this->contacto_cargo,
            'contacto_telefono'         => $this->contacto_telefono,
            'contacto_correo'           => $this->contacto_correo,

            // Relación
            'tipos_empresa' => $this->whenLoaded('tipos_empresa', function () {
                return [
                    'id'      => $this->tipos_empresa->id,
                    'nombre'  => $this->tipos_empresa->nombre,
                    'estatus' => $this->tipos_empresa->estatus,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
