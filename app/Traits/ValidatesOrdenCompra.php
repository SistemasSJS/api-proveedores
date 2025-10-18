<?php

namespace App\Traits;

use App\Models\OrdenCompra;
use App\Enums\EstadoOrdenCompra;
use Carbon\Carbon;

trait ValidatesOrdenCompra
{
    /**
     * Validar que una orden de compra pueda generar solicitudes de pago
     */
    public function validarElegibilidadParaSP(OrdenCompra $ordenCompra, float $montoSolicitud): array
    {
        $errores = [];
        $advertencias = [];

        // 1. Validar estado
        if ($ordenCompra->estado !== EstadoOrdenCompra::APROBADA) {
            $errores[] = "La orden de compra debe estar aprobada. Estado actual: {$ordenCompra->estado->label()}.";
        }

        // 2. Validar monto disponible
        $montoDisponible = $ordenCompra->getMontoDisponible();
        if ($montoDisponible <= 0) {
            $errores[] = 'La orden de compra no tiene monto disponible para nuevas solicitudes de pago.';
        } elseif ($montoSolicitud > $montoDisponible) {
            $errores[] = "El monto solicitado ($montoSolicitud) excede el disponible ($montoDisponible).";
        }

        // 3. Validar monto mínimo
        $montoMinimo = config('ordenes-compra.conversion.monto_minimo_sp', 0.01);
        if ($montoSolicitud < $montoMinimo) {
            $errores[] = "El monto debe ser mayor o igual a $montoMinimo.";
        }

        // 4. Advertencias
        if ($montoSolicitud < $montoDisponible && $montoSolicitud > 0) {
            $saldoRestante = $montoDisponible - $montoSolicitud;
            $advertencias[] = "Se generará un pago parcial. Quedará un saldo de $saldoRestante disponible.";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'advertencias' => $advertencias,
        ];
    }

    /**
     * Validar coherencia de fechas
     */
    public function validarFechasCoherentes(?string $fechaSP, OrdenCompra $ordenCompra): ?string
    {
        if (!$fechaSP || !config('ordenes-compra.conversion.validar_fechas', true)) {
            return null;
        }

        $fechaSolicitud = Carbon::parse($fechaSP);
        $fechaOrden = $ordenCompra->fecha_orden;
        
        if ($fechaSolicitud->lt($fechaOrden)) {
            return 'La fecha de la solicitud de pago no puede ser anterior a la fecha de la orden de compra.';
        }

        // Verificar que no sea muy posterior (más de 1 año)
        $maxDias = config('ordenes-compra.validaciones.fechas.rango_fechas_maximo', 365);
        if ($fechaSolicitud->diffInDays($fechaOrden) > $maxDias) {
            return "La fecha de la solicitud no puede ser más de $maxDias días posterior a la orden de compra.";
        }

        return null;
    }

    /**
     * Validar que el proveedor esté autorizado para una OC
     */
    public function validarProveedorAutorizado(OrdenCompra $ordenCompra, int $proveedorId): bool
    {
        return $ordenCompra->proveedor_id === $proveedorId;
    }

    /**
     * Validar que la suma de SP no exceda el total de la OC
     */
    public function validarSumaNoExcedaTotal(OrdenCompra $ordenCompra, float $nuevoMonto, ?int $solicitudExceptuada = null): array
    {
        $montoActual = $ordenCompra->monto_sp_asociado;
        
        // Si hay una solicitud exceptuada (para actualizaciones), restar su monto
        if ($solicitudExceptuada) {
            $spExceptuada = $ordenCompra->solicitudesPago()->where('solicitud_pago_id', $solicitudExceptuada)->first();
            if ($spExceptuada) {
                $montoActual -= $spExceptuada->pivot->monto_asociado;
            }
        }

        $nuevoTotal = $montoActual + $nuevoMonto;
        $excedeTotal = $nuevoTotal > $ordenCompra->importe_total;

        return [
            'excede' => $excedeTotal,
            'monto_actual' => $montoActual,
            'nuevo_total' => $nuevoTotal,
            'limite_oc' => $ordenCompra->importe_total,
            'disponible' => $ordenCompra->importe_total - $montoActual,
        ];
    }

    /**
     * Validar número de orden único por proveedor
     */
    public function validarNumeroOrdenUnico(string $numeroOrden, int $proveedorId, ?int $exceptoId = null): bool
    {
        $query = OrdenCompra::where('numero_orden', $numeroOrden)
            ->where('proveedor_id', $proveedorId);

        if ($exceptoId) {
            $query->where('id', '!=', $exceptoId);
        }

        return !$query->exists();
    }

    /**
     * Validar formato de número de orden (si está configurado)
     */
    public function validarFormatoNumeroOrden(string $numeroOrden): ?string
    {
        $formatoRegex = config('ordenes-compra.validaciones.numero_orden.formato_regex');
        
        if (!$formatoRegex) {
            return null;
        }

        if (!preg_match($formatoRegex, $numeroOrden)) {
            return 'El formato del número de orden no es válido.';
        }

        return null;
    }

    /**
     * Validar monto máximo permitido
     */
    public function validarMontoMaximo(float $monto): ?string
    {
        $montoMaximo = config('ordenes-compra.validaciones.montos.monto_maximo', 999999999.99);
        
        if ($monto > $montoMaximo) {
            return "El monto no puede exceder $montoMaximo.";
        }

        return null;
    }

    /**
     * Validar integridad de detalles vs importe total
     */
    public function validarIntegridadDetalles(array $detalles, float $importeTotal): array
    {
        $sumatoriaDetalles = 0;
        $erroresDetalle = [];

        foreach ($detalles as $index => $detalle) {
            $cantidad = $detalle['cantidad'] ?? 0;
            $precioUnitario = $detalle['precio_unitario'] ?? 0;
            $importeCalculado = $cantidad * $precioUnitario;

            $sumatoriaDetalles += $importeCalculado;

            // Validar que el importe coincida si está especificado
            if (isset($detalle['importe']) && abs($detalle['importe'] - $importeCalculado) > 0.01) {
                $erroresDetalle[] = "Detalle $index: El importe especificado no coincide con cantidad × precio unitario.";
            }
        }

        $diferencia = abs($sumatoriaDetalles - $importeTotal);
        $coincide = $diferencia <= 0.01; // Tolerancia de 1 centavo

        return [
            'coincide' => $coincide,
            'sumatoria_detalles' => $sumatoriaDetalles,
            'importe_total' => $importeTotal,
            'diferencia' => $diferencia,
            'errores_detalle' => $erroresDetalle,
        ];
    }

    /**
     * Validar que una OC esté disponible para conversión
     */
    public function validarDisponibilidadConversion(OrdenCompra $ordenCompra): array
    {
        $disponible = $ordenCompra->puedeGenerarSolicitudPago();
        $motivos = [];

        if (!$disponible) {
            if ($ordenCompra->estado !== EstadoOrdenCompra::APROBADA) {
                $motivos[] = 'La orden debe estar aprobada';
            }
            
            if (!$ordenCompra->tieneMontoDisponible()) {
                $motivos[] = 'No hay monto disponible';
            }
        }

        return [
            'disponible' => $disponible,
            'motivos' => $motivos,
            'monto_disponible' => $ordenCompra->getMontoDisponible(),
            'estado_actual' => $ordenCompra->estado->value,
        ];
    }

    /**
     * Obtener reglas de validación comunes para OC
     */
    public function getReglasValidacionOC(int $proveedorId, ?int $exceptoId = null): array
    {
        $reglas = [
            'numero_orden' => [
                'required',
                'string',
                'max:' . config('ordenes-compra.validaciones.numero_orden.longitud_maxima', 255),
                new \App\Rules\OCUnica($proveedorId, $exceptoId),
            ],
            'fecha_orden' => 'required|date',
            'empresa_construcc_id' => 'required|integer|exists:empresas_construcc,id',
            'importe_total' => [
                'required',
                'numeric',
                'min:0.01',
                'max:' . config('ordenes-compra.validaciones.montos.monto_maximo', 999999999.99),
            ],
            'estado' => 'required|string|in:' . implode(',', \App\Enums\EstadoOrdenCompra::values()),
        ];

        // Añadir validación de formato si está configurada
        $formatoRegex = config('ordenes-compra.validaciones.numero_orden.formato_regex');
        if ($formatoRegex) {
            $reglas['numero_orden'][] = 'regex:' . $formatoRegex;
        }

        return $reglas;
    }

    /**
     * Validar datos completos de una OC
     */
    public function validarOrdenCompraCompleta(array $datos, int $proveedorId, ?int $exceptoId = null): array
    {
        $errores = [];
        $advertencias = [];

        // Validar datos básicos usando las reglas estándar
        $validator = validator($datos, $this->getReglasValidacionOC($proveedorId, $exceptoId));
        
        if ($validator->fails()) {
            $errores = array_merge($errores, $validator->errors()->all());
        }

        // Validar integridad de detalles si están presentes
        if (isset($datos['detalles']) && isset($datos['importe_total'])) {
            $validacionDetalles = $this->validarIntegridadDetalles($datos['detalles'], $datos['importe_total']);
            
            if (!$validacionDetalles['coincide']) {
                $errores[] = 'La suma de los detalles no coincide con el importe total de la orden.';
            }
            
            $errores = array_merge($errores, $validacionDetalles['errores_detalle']);
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'advertencias' => $advertencias,
        ];
    }
}