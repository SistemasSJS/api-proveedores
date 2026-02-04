<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProveedorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            /* =========================
             * Identificación
             * ========================= */
            'id' => $this->id,
            'nombre' => $this->nombre_comercial,
            'razon_social' => $this->razon_social,
            'nombre_comercial' => $this->nombre_comercial,
            'rfc' => $this->rfc,

            /* =========================
             * Contacto general
             * ========================= */
            'telefono' => $this->telefono,
            'email' => $this->email,
            'pagina_web' => $this->pagina_web,

            /* =========================
             * Archivos
             * ========================= */
            'logo' => $this->logo
                ? Storage::disk('public')->url($this->logo)
                : null,

            'constancia_fiscal' => $this->constancia_fiscal
                ? Storage::disk('public')->url($this->constancia_fiscal)
                : null,

            /* =========================
             * Datos fiscales
             * ========================= */
            'tipo_persona' => $this->tipo_persona,
            'regimen_fiscal_clave' => $this->regimen_fiscal_clave,
            'regimen_fiscal_nombre' => $this->regimen_fiscal_nombre,

            'direccion' => $this->direccion_empresa,

            'direccion_fiscal' => [
                'calle' => $this->calle,
                'numero_exterior' => $this->numero_exterior,
                'numero_interior' => $this->numero_interior,
                'colonia' => $this->colonia,
                'ciudad' => $this->ciudad,
                'estado' => $this->estado,
                'codigo_postal' => $this->codigo_postal,
                'pais' => $this->pais,
            ],

            /* =========================
             * Información de la empresa
             * ========================= */
            'tipos_empresa_id' => $this->tipos_empresa_id,
            'tipos_empresa_otro' => $this->tipos_empresa_otro,
            'descripcion_giro_empresa' => $this->descripcion_giro_empresa,
            'categoria' => $this->categoria ?? null,
            'ciudad' => $this->ciudad ?? null,

            /* =========================
             * Contacto principal
             * ========================= */
            'contacto_nombre' => $this->contacto_nombre,
            'contacto_cargo' => $this->contacto_cargo,
            'contacto_telefono' => $this->contacto_telefono,
            'contacto_correo' => $this->contacto_correo,

            /* =========================
             * Metadatos / estado
             * ========================= */
            'estatus' => $this->estatus,
            'notas' => $this->notas,
            'validado_por' => $this->validado_por,
            'nombre_propietario' => $this->nombre_propietario,
            'nombre_de_quien_registra' => $this->nombre_de_quien_registra,

            'principal' => $this->principal ?? null,
            'calificacion' => $this->calificacion ?? null,

            'is_proveedor_sp' => $this->is_proveedor_sp ?? null,
            'is_proveedor_catalogo' => $this->is_proveedor_catalogo ?? null,
            'cambiar_pass_default' => $this->cambiar_pass_default ?? null,
            'perfil_empresa_completo' => $this->perfil_empresa_completo ?? null,

            /* =========================
             * Relaciones
             * ========================= */
            'tipos_empresa' => $this->whenLoaded('tipos_empresa', function () {
                return [
                    'id' => $this->tipos_empresa->id,
                    'nombre' => $this->tipos_empresa->nombre,
                    'estatus' => $this->tipos_empresa->estatus,
                ];
            }),

            /* =========================
             * Timestamps
             * ========================= */
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
