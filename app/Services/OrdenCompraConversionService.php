<?php

namespace App\Services;

use App\Enums\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\SolicitudPago;
use App\Models\Proveedor;
use App\Models\EmpresaConstrucc;
use Illuminate\Support\Facades\DB;
use Exception;

class OrdenCompraConversionService
{
    /**
     * Convertir orden de compra a solicitud de pago
     */
    public function convertToSolicitudPago(
        OrdenCompra $ordenCompra,
        array $datosSolicitud,
        ?array $cuentasBancarias = null
    ): SolicitudPago {
        // Validar que la conversión sea posible
        $this->validateConversion($ordenCompra, $datosSolicitud['monto_total']);

        try {
            DB::beginTransaction();

            // Mapear datos de OC a SP
            $datosCompletos = $this->mapOCToSP($ordenCompra, $datosSolicitud);

            // Crear la solicitud de pago
            $solicitudPago = SolicitudPago::create($datosCompletos);

            // Asociar con la orden de compra
            $solicitudPago->asociarConOrdenCompra(
                $ordenCompra,
                $datosSolicitud['monto_total'],
                $datosSolicitud['notas_vinculacion'] ?? null
            );

            // Sincronizar cuentas bancarias si se proporcionan
            if ($cuentasBancarias) {
                $solicitudPago->sincronizarCuentasBancarias($cuentasBancarias);
            }

            // Actualizar estado de OC si está completada
            $this->actualizarEstadoOrdenCompra($ordenCompra);

            DB::commit();

            // Disparar evento
            event('orden_compra.convertida', [
                'orden_compra' => $ordenCompra,
                'solicitud_pago' => $solicitudPago,
                'monto_convertido' => $datosSolicitud['monto_total']
            ]);

            return $solicitudPago->load(['proveedor', 'empresaConstrucc', 'cuentasBancarias', 'ordenesCompra']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validar que la conversión sea posible
     */
    public function validateConversion(OrdenCompra $ordenCompra, float $montoSolicitud): void
    {
        if ($ordenCompra->estado !== EstadoOrdenCompra::APROBADA) {
            throw new Exception('La orden de compra debe estar aprobada para generar solicitudes de pago');
        }

        if (!$ordenCompra->tieneMontoDisponible()) {
            throw new Exception('La orden de compra no tiene monto disponible para nuevas solicitudes de pago');
        }

        if ($montoSolicitud > $ordenCompra->getMontoDisponible()) {
            throw new Exception('El monto de la solicitud excede el monto disponible de la orden de compra');
        }

        if ($montoSolicitud <= 0) {
            throw new Exception('El monto de la solicitud debe ser mayor a cero');
        }
    }

    /**
     * Calcular monto disponible de una OC
     */
    public function calculateAvailableAmount(OrdenCompra $ordenCompra): float
    {
        return $ordenCompra->getMontoDisponible();
    }

    /**
     * Mapear campos de OC a SP
     */
    public function mapOCToSP(OrdenCompra $ordenCompra, array $datosSolicitud): array
    {
        // Generar número de folio para la nueva solicitud
        $numeroFolio = SolicitudPago::generarNumeroFolio($ordenCompra->proveedor);

        // Mapear campos obligatorios
        $datosBase = [
            'numero_folio_solicitud' => $numeroFolio,
            'proveedor_id' => $ordenCompra->proveedor_id,
            'empresa_construcc_id' => $ordenCompra->empresa_construcc_id,
            'estado_solicitud' => 'pendiente',
            'fecha_registro_pendiente' => now(),
            
            // Campos de tracking de OC
            'referencia_oc' => $ordenCompra->numero_orden,
            'origen_oc' => true,
            'monto_oc_original' => $ordenCompra->importe_total,
            
            // Campos de monto
            'monto_total' => $datosSolicitud['monto_total'],
            'saldo_pendiente' => $datosSolicitud['monto_total'],
            'monto_abonado' => 0,
            'pago_completo' => false,
        ];

        // Mapear campos opcionales desde la solicitud
        $camposOpcionales = [
            'descripcion_concepto',
            'residente',
            'sucursal_id',
            'cotizacion_id',
            'observaciones',
        ];

        foreach ($camposOpcionales as $campo) {
            if (isset($datosSolicitud[$campo])) {
                $datosBase[$campo] = $datosSolicitud[$campo];
            }
        }

        // Si no se proporciona descripción, usar datos de la OC
        if (empty($datosBase['descripcion_concepto'])) {
            $datosBase['descripcion_concepto'] = "Solicitud de pago para OC #{$ordenCompra->numero_orden}";
            
            if ($ordenCompra->observaciones) {
                $datosBase['descripcion_concepto'] .= " - {$ordenCompra->observaciones}";
            }
        }

        return $datosBase;
    }

    /**
     * Verificar si OC permite pagos parciales
     */
    public function allowsPartialPayments(OrdenCompra $ordenCompra): bool
    {
        // Por defecto, todas las OC permiten pagos parciales
        // Esta lógica puede ser extendida según reglas de negocio específicas
        return $ordenCompra->estado === EstadoOrdenCompra::APROBADA && 
               $ordenCompra->getMontoDisponible() > 0;
    }

    /**
     * Obtener preview de datos pre-llenados para conversión
     */
    public function getConversionPreview(OrdenCompra $ordenCompra, ?float $montoSugerido = null): array
    {
        $montoDisponible = $ordenCompra->getMontoDisponible();
        $montoSugerido = $montoSugerido ?? $montoDisponible;

        return [
            'orden_compra' => [
                'id' => $ordenCompra->id,
                'numero_orden' => $ordenCompra->numero_orden,
                'fecha_orden' => $ordenCompra->fecha_orden,
                'importe_total' => $ordenCompra->importe_total,
                'monto_sp_asociado' => $ordenCompra->monto_sp_asociado,
                'monto_disponible' => $montoDisponible,
                'sp_count' => $ordenCompra->sp_count,
                'empresa_construcc' => $ordenCompra->empresaConstrucc->only(['id', 'nombre', 'rfc'])
            ],
            'datos_prellenados' => [
                'empresa_construcc_id' => $ordenCompra->empresa_construcc_id,
                'referencia_oc' => $ordenCompra->numero_orden,
                'monto_sugerido' => $montoSugerido,
                'monto_maximo' => $montoDisponible,
                'descripcion_sugerida' => "Solicitud de pago para OC #{$ordenCompra->numero_orden}",
                'permite_pagos_parciales' => $this->allowsPartialPayments($ordenCompra)
            ],
            'validaciones' => [
                'monto_minimo' => 0.01,
                'monto_maximo' => $montoDisponible,
                'estado_requerido' => EstadoOrdenCompra::APROBADA->value,
                'estado_actual' => $ordenCompra->estado->value
            ]
        ];
    }

    /**
     * Validar datos antes de conversión
     */
    public function preValidateConversion(OrdenCompra $ordenCompra, array $datos): array
    {
        $errores = [];
        $advertencias = [];

        // Validar estado de OC
        if ($ordenCompra->estado !== EstadoOrdenCompra::APROBADA) {
            $errores[] = 'La orden de compra debe estar aprobada';
        }

        // Validar monto disponible
        $montoDisponible = $ordenCompra->getMontoDisponible();
        if ($montoDisponible <= 0) {
            $errores[] = 'No hay monto disponible en la orden de compra';
        }

        // Validar monto solicitado
        $montoSolicitado = $datos['monto_total'] ?? 0;
        if ($montoSolicitado <= 0) {
            $errores[] = 'El monto debe ser mayor a cero';
        } elseif ($montoSolicitado > $montoDisponible) {
            $errores[] = "El monto solicitado ($montoSolicitado) excede el disponible ($montoDisponible)";
        }

        // Advertencia si es pago parcial
        if ($montoSolicitado < $montoDisponible && $montoSolicitado > 0) {
            $advertencias[] = 'Se generará un pago parcial. Quedará un saldo de ' . 
                             ($montoDisponible - $montoSolicitado) . ' disponible en la OC';
        }

        // Validar empresa
        if (isset($datos['empresa_construcc_id']) && 
            $datos['empresa_construcc_id'] !== $ordenCompra->empresa_construcc_id) {
            $advertencias[] = 'La empresa seleccionada es diferente a la de la orden de compra';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'advertencias' => $advertencias,
            'datos_calculados' => [
                'monto_disponible' => $montoDisponible,
                'monto_solicitado' => $montoSolicitado,
                'quedara_saldo' => $montoSolicitado < $montoDisponible,
                'saldo_restante' => max(0, $montoDisponible - $montoSolicitado)
            ]
        ];
    }

    /**
     * Actualizar estado de orden de compra después de conversión
     */
    private function actualizarEstadoOrdenCompra(OrdenCompra $ordenCompra): void
    {
        // Actualizar contadores
        $ordenCompra->actualizarContadores();

        // Si no queda monto disponible, marcar como completada
        if ($ordenCompra->getMontoDisponible() <= 0.01) {
            $ordenCompra->update(['estado' => EstadoOrdenCompra::COMPLETADA]);
        } else {
            // Si hay SP asociadas pero queda monto, marcar como parcial
            if ($ordenCompra->sp_count > 0) {
                $ordenCompra->update(['estado' => EstadoOrdenCompra::PARCIAL]);
            }
        }
    }

    /**
     * Obtener historial de conversiones de una OC
     */
    public function getConversionHistory(OrdenCompra $ordenCompra): array
    {
        $solicitudesPago = $ordenCompra->solicitudesPago()
            ->with(['proveedor', 'empresaConstrucc'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'orden_compra' => [
                'numero_orden' => $ordenCompra->numero_orden,
                'importe_total' => $ordenCompra->importe_total,
                'monto_convertido' => $ordenCompra->monto_sp_asociado,
                'porcentaje_convertido' => ($ordenCompra->monto_sp_asociado / $ordenCompra->importe_total) * 100
            ],
            'conversiones' => $solicitudesPago->map(function ($sp) {
                return [
                    'id' => $sp->id,
                    'numero_folio_solicitud' => $sp->numero_folio_solicitud,
                    'fecha_creacion' => $sp->created_at,
                    'monto_total' => $sp->monto_total,
                    'estado_solicitud' => $sp->estado_solicitud,
                    'monto_asociado_oc' => $sp->pivot->monto_asociado,
                    'fecha_vinculacion' => $sp->pivot->fecha_vinculacion,
                    'notas' => $sp->pivot->notas
                ];
            })
        ];
    }
}