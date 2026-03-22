<?php

namespace App\Http\Resources\Proveedor;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProveedorEmpresaResource extends JsonResource
{
    /**
     * Convierte un string a mayúsculas (UTF-8). Null o vacío se devuelven tal cual.
     */
    private static function upper(?string $value): ?string
    {
        return $value !== null && $value !== '' ? Str::upper($value) : $value;
    }

    /**
     * Transforma el recurso en un arreglo JSON.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nombre' => self::upper($this->nombre),
            'razon_social' => self::upper($this->razon_social),
            'rfc' => $this->rfc,
            'direccion' => self::upper($this->direccion),
            'ciudad' => self::upper($this->ciudad),
            'estado' => self::upper($this->estado),
            'codigo_postal' => $this->codigo_postal,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'representante_legal' => self::upper($this->representante_legal),
            'activo' => (bool) $this->activo,
            'nombre_completo' => self::upper($this->nombre_completo),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),

            /**
             * Relaciones
             */
            'proveedores' => $this->whenLoaded('proveedores', function () {
                return $this->proveedores->map(function ($proveedor) {
                    return [
                        'id' => $proveedor->id,
                        'nombre' => self::upper($proveedor->nombre),
                        'rfc' => $proveedor->rfc,
                        'telefono' => $proveedor->telefono ?? null,
                        'email' => $proveedor->email ?? null,
                    ];
                });
            }),

            'solicitudes_pago' => $this->whenLoaded('solicitudesPago', function () {
                return $this->solicitudesPago->map(function ($sp) {
                    return [
                        'id' => $sp->id,
                        'folio' => $sp->folio ?? null,
                        'monto' => $sp->monto ?? null,
                        'estado_solicitud' => $sp->estado_solicitud ?? null,
                        'created_at' => optional($sp->created_at)->toDateTimeString(),
                    ];
                });
            }),
        ];
    }
}
