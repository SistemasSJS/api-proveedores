<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConstruccProveedorDetalleResource extends JsonResource
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
            'nombre_comercial' => $this->nombre_comercial,
            'razon_social' => $this->razon_social,
            'rfc' => $this->rfc,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'estatus' => $this->estatus,
            'tipo_alta' => $this->tipo_alta,
            
            // Información empresarial
            'pagina_web' => $this->pagina_web,
            'tipo_persona' => $this->tipo_persona,
            'descripcion_giro_empresa' => $this->descripcion_giro_empresa,
            
            // Información de contacto
            'contacto_nombre' => $this->contacto_nombre,
            'contacto_cargo' => $this->contacto_cargo,
            'contacto_telefono' => $this->contacto_telefono,
            'contacto_correo' => $this->contacto_correo,
            
            // Información de ubicación
            'estado' => $this->estado,
            'municipio' => $this->municipio,
            'codigo_postal' => $this->codigo_postal,
            'direccion_fiscal' => $this->direccion_fiscal,
            'direccion_empresa' => $this->direccion_empresa,
            
            // Logo
            'logo' => $this->logo
                ? (preg_match('/^https?:\/\//', $this->logo) ? $this->logo : asset('storage/' . $this->logo))
                : null,
            
            // Estado y clasificación
            'perfil_empresa_completo' => (bool) $this->perfil_empresa_completo,
            'is_proveedor_sp' => (bool) $this->is_proveedor_sp,
            'is_proveedor_catalogo' => (bool) $this->is_proveedor_catalogo,
            
            // Cuentas bancarias (cuando se cargan)
            'cuentas_bancarias' => $this->whenLoaded('cuentasBancarias', function () {
                return $this->cuentasBancarias->map(function ($cuenta) {
                    return [
                        'id' => $cuenta->id,
                        'alias' => $cuenta->alias,
                        'banco_nombre' => $cuenta->banco_nombre,
                        'banco_clave' => $cuenta->banco_clave,
                        'cuenta' => $cuenta->cuenta,
                        'clabe' => $cuenta->clabe,
                        'tarjeta' => $cuenta->tarjeta,
                        'titular_cuenta' => $cuenta->titular_cuenta,
                        'preferida' => (bool) $cuenta->preferida,
                        'estatus' => $cuenta->estatus,
                    ];
                });
            }),
            
            // Empresas construcción asociadas (cuando se cargan)
            'empresas_construcc' => $this->whenLoaded('empresasConstrucc', function () {
                return $this->empresasConstrucc->map(function ($empresa) {
                    return [
                        'id' => $empresa->id,
                        'nombre' => $empresa->nombre,
                        'rfc' => $empresa->rfc,
                        'usuario_construcc_id' => $empresa->pivot->usuario_construcc_id ?? null,
                        'usuario_construcc_nombre' => $empresa->pivot->usuario_construcc_nombre ?? null,
                    ];
                });
            }),
            
            // Estadísticas de solicitudes de pago
            'estadisticas' => $this->when(isset($this->estadisticas), function () {
                return $this->estadisticas;
            }),
            
            // Total de cuentas y solicitudes (cuando están disponibles)
            'total_cuentas_bancarias' => $this->when(
                $this->relationLoaded('cuentasBancarias'),
                fn() => $this->cuentasBancarias->count()
            ),
            'total_solicitudes_pago' => $this->when(
                isset($this->solicitudes_pago_count),
                fn() => $this->solicitudes_pago_count
            ),
            
            // Fechas
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
