<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\User;
use App\Models\Notificacion;

class NotificacionService
{
    /**
     * Enviar notificación de pedido creado
     */
    public static function enviarPedidoCreado(Pedido $pedido): void
    {
        // Notificar al proveedor
        $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($pedido) {
            $query->where('proveedor_id', $pedido->requisicion->proveedor_id);
        })
        ->whereHas('role', function ($query) {
            $query->whereIn('name', ['GERENTE', 'VENTAS', 'ADMINISTRADOR']);
        })
        ->get();

        foreach ($usuariosProveedor as $usuario) {
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'pedido_nuevo',
                'titulo' => 'Nuevo Pedido Recibido',
                'mensaje' => "Se ha confirmado el pedido #{$pedido->numero_pedido} por #{$pedido->total}",
                'datos' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'total' => $pedido->total,
                    'cliente' => $pedido->requisicion->usuario->name,
                    'fecha_entrega' => $pedido->fecha_entrega_estimada->format('Y-m-d')
                ],
            ]);
        }

        // Notificar al cliente (confirmación)
        Notificacion::create([
            'usuario_id' => $pedido->requisicion->usuario_id,
            'tipo' => 'pedido_confirmado',
            'titulo' => 'Pedido Confirmado',
            'mensaje' => "Tu pedido #{$pedido->numero_pedido} ha sido confirmado correctamente",
            'datos' => [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'total' => $pedido->total,
                'fecha_entrega' => $pedido->fecha_entrega_estimada->format('Y-m-d')
            ],
        ]);
    }

    /**
     * Enviar notificación de cambio de estatus de pedido
     */
    public static function enviarCambioEstatusPedido(Pedido $pedido): void
    {
        $estatusTexto = match ($pedido->estatus) {
            'en_preparacion' => 'está en preparación',
            'listo_para_entrega' => 'está listo para entrega',
            'en_transito' => 'está en tránsito',
            'entregado' => 'ha sido entregado',
            'facturado' => 'ha sido facturado',
            'cancelado' => 'ha sido cancelado',
            default => 'ha sido actualizado'
        };

        $titulo = match ($pedido->estatus) {
            'en_preparacion' => 'Preparando tu Pedido',
            'listo_para_entrega' => 'Pedido Listo para Entrega',
            'en_transito' => 'Pedido en Tránsito',
            'entregado' => 'Pedido Entregado',
            'facturado' => 'Pedido Facturado',
            'cancelado' => 'Pedido Cancelado',
            default => 'Actualización de Pedido'
        };

        $datos = [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'estatus' => $pedido->estatus,
            'estatus_texto' => $pedido->getEstadoTexto(),
        ];

        // Agregar información específica según el estatus
        if ($pedido->estatus === 'en_transito') {
            $datos['numero_guia'] = $pedido->numero_guia;
            $datos['transportista'] = $pedido->transportista;
        }

        if ($pedido->estatus === 'entregado') {
            $datos['fecha_entrega'] = $pedido->fecha_entrega_real?->format('Y-m-d H:i:s');
        }

        Notificacion::create([
            'usuario_id' => $pedido->requisicion->usuario_id,
            'tipo' => 'pedido_actualizado',
            'titulo' => $titulo,
            'mensaje' => "Tu pedido #{$pedido->numero_pedido} {$estatusTexto}",
            'datos' => $datos,
        ]);
    }

    /**
     * Enviar notificación de pedido en tránsito
     */
    public static function enviarPedidoEnTransito(Pedido $pedido): void
    {
        $mensaje = "Tu pedido #{$pedido->numero_pedido} está en tránsito";
        if ($pedido->numero_guia) {
            $mensaje .= " con guía #{$pedido->numero_guia}";
        }
        if ($pedido->transportista) {
            $mensaje .= " vía {$pedido->transportista}";
        }

        Notificacion::create([
            'usuario_id' => $pedido->requisicion->usuario_id,
            'tipo' => 'pedido_en_transito',
            'titulo' => 'Pedido en Tránsito',
            'mensaje' => $mensaje,
            'datos' => [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'numero_guia' => $pedido->numero_guia,
                'transportista' => $pedido->transportista,
                'fecha_envio' => $pedido->fecha_envio?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Enviar notificación de pedido entregado
     */
    public static function enviarPedidoEntregado(Pedido $pedido): void
    {
        // Notificar al cliente
        Notificacion::create([
            'usuario_id' => $pedido->requisicion->usuario_id,
            'tipo' => 'pedido_entregado',
            'titulo' => 'Pedido Entregado',
            'mensaje' => "Tu pedido #{$pedido->numero_pedido} ha sido entregado correctamente",
            'datos' => [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'fecha_entrega' => $pedido->fecha_entrega_real?->format('Y-m-d H:i:s'),
                'requiere_confirmacion' => true,
            ],
        ]);

        // Notificar al proveedor
        $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($pedido) {
            $query->where('proveedor_id', $pedido->requisicion->proveedor_id);
        })->get();

        foreach ($usuariosProveedor as $usuario) {
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'pedido_entregado_proveedor',
                'titulo' => 'Pedido Entregado',
                'mensaje' => "El pedido #{$pedido->numero_pedido} ha sido entregado al cliente",
                'datos' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->requisicion->usuario->name,
                    'fecha_entrega' => $pedido->fecha_entrega_real?->format('Y-m-d H:i:s'),
                ],
            ]);
        }
    }

    /**
     * Enviar notificación de pedido cancelado
     */
    public static function enviarPedidoCancelado(Pedido $pedido): void
    {
        // Notificar al proveedor
        $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($pedido) {
            $query->where('proveedor_id', $pedido->requisicion->proveedor_id);
        })->get();

        foreach ($usuariosProveedor as $usuario) {
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'pedido_cancelado',
                'titulo' => 'Pedido Cancelado',
                'mensaje' => "El pedido #{$pedido->numero_pedido} ha sido cancelado",
                'datos' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->requisicion->usuario->name,
                    'motivo' => $pedido->motivo_cancelacion,
                    'fecha_cancelacion' => $pedido->fecha_cancelacion?->format('Y-m-d H:i:s'),
                ],
            ]);
        }

        // Notificar al cliente (confirmación)
        Notificacion::create([
            'usuario_id' => $pedido->requisicion->usuario_id,
            'tipo' => 'pedido_cancelado_confirmacion',
            'titulo' => 'Pedido Cancelado',
            'mensaje' => "Tu pedido #{$pedido->numero_pedido} ha sido cancelado correctamente",
            'datos' => [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'motivo' => $pedido->motivo_cancelacion,
                'fecha_cancelacion' => $pedido->fecha_cancelacion?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Enviar notificación de pedido rechazado por el proveedor
     */
    public static function enviarPedidoRechazado(Pedido $pedido): void
    {
        Notificacion::create([
            'usuario_id' => $pedido->requisicion->usuario_id,
            'tipo' => 'pedido_rechazado',
            'titulo' => 'Pedido Rechazado',
            'mensaje' => "Tu pedido #{$pedido->numero_pedido} ha sido rechazado por el proveedor",
            'datos' => [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'motivo' => $pedido->motivo_cancelacion,
                'proveedor' => $pedido->requisicion->proveedor->nombre_comercial,
                'fecha_rechazo' => $pedido->fecha_cancelacion?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Enviar notificación de recepción confirmada
     */
    public static function enviarRecepcionConfirmada(Pedido $pedido): void
    {
        // Notificar al proveedor
        $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($pedido) {
            $query->where('proveedor_id', $pedido->requisicion->proveedor_id);
        })->get();

        foreach ($usuariosProveedor as $usuario) {
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'recepcion_confirmada',
                'titulo' => 'Recepción Confirmada',
                'mensaje' => "El cliente ha confirmado la recepción del pedido #{$pedido->numero_pedido}",
                'datos' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->requisicion->usuario->name,
                    'calificacion' => $pedido->calificacion_cliente,
                    'observaciones' => $pedido->observaciones_entrega,
                ],
            ]);
        }
    }

    /**
     * Enviar notificación de pedido próximo a vencer
     */
    public static function enviarPedidoProximoVencer(Pedido $pedido): void
    {
        // Notificar al proveedor
        $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($pedido) {
            $query->where('proveedor_id', $pedido->requisicion->proveedor_id);
        })->get();

        $diasRestantes = $pedido->diasParaVencimiento();

        foreach ($usuariosProveedor as $usuario) {
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'pedido_proximo_vencer',
                'titulo' => 'Pedido Próximo a Vencer',
                'mensaje' => "El pedido #{$pedido->numero_pedido} vence en {$diasRestantes} días",
                'datos' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'dias_restantes' => $diasRestantes,
                    'fecha_vencimiento' => $pedido->fecha_entrega_estimada->format('Y-m-d'),
                    'cliente' => $pedido->requisicion->usuario->name,
                ],
            ]);
        }
    }

    /**
     * Enviar notificación de pedido vencido
     */
    public static function enviarPedidoVencido(Pedido $pedido): void
    {
        // Notificar al proveedor
        $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($pedido) {
            $query->where('proveedor_id', $pedido->requisicion->proveedor_id);
        })->get();

        foreach ($usuariosProveedor as $usuario) {
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'pedido_vencido',
                'titulo' => 'Pedido Vencido',
                'mensaje' => "El pedido #{$pedido->numero_pedido} ha vencido",
                'datos' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'fecha_vencimiento' => $pedido->fecha_entrega_estimada->format('Y-m-d'),
                    'dias_vencido' => abs($pedido->diasParaVencimiento()),
                    'cliente' => $pedido->requisicion->usuario->name,
                ],
            ]);
        }

        // Notificar al cliente
        Notificacion::create([
            'usuario_id' => $pedido->requisicion->usuario_id,
            'tipo' => 'pedido_vencido_cliente',
            'titulo' => 'Pedido Vencido',
            'mensaje' => "Tu pedido #{$pedido->numero_pedido} ha vencido su fecha de entrega",
            'datos' => [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'fecha_vencimiento' => $pedido->fecha_entrega_estimada->format('Y-m-d'),
                'dias_vencido' => abs($pedido->diasParaVencimiento()),
                'proveedor' => $pedido->requisicion->proveedor->nombre_comercial,
            ],
        ]);
    }

    /**
     * Enviar recordatorio de evaluación de pedido
     */
    public static function enviarRecordatorioEvaluacion(Pedido $pedido): void
    {
        if ($pedido->estatus !== 'entregado') {
            return;
        }

        // Solo enviar si no se ha evaluado después de 3 días
        if ($pedido->fecha_entrega_real && 
            $pedido->fecha_entrega_real->addDays(3)->isPast() && 
            !$pedido->calificacion_cliente) {
            
            Notificacion::create([
                'usuario_id' => $pedido->requisicion->usuario_id,
                'tipo' => 'recordatorio_evaluacion',
                'titulo' => 'Evalúa tu Pedido',
                'mensaje' => "¿Cómo fue tu experiencia con el pedido #{$pedido->numero_pedido}?",
                'datos' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'proveedor' => $pedido->requisicion->proveedor->nombre_comercial,
                    'fecha_entrega' => $pedido->fecha_entrega_real?->format('Y-m-d'),
                ],
            ]);
        }
    }

    /**
     * Enviar resumen semanal de pedidos al proveedor
     */
    public static function enviarResumenSemanalProveedor(int $proveedorId): void
    {
        $fechaInicio = now()->startOfWeek();
        $fechaFin = now()->endOfWeek();

        $pedidos = Pedido::whereHas('requisicion', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        })
        ->whereBetween('fecha_confirmacion', [$fechaInicio, $fechaFin])
        ->get();

        if ($pedidos->isEmpty()) {
            return;
        }

        $resumen = [
            'total_pedidos' => $pedidos->count(),
            'total_ventas' => $pedidos->whereIn('estatus', ['entregado', 'facturado'])->sum('total'),
            'pedidos_pendientes' => $pedidos->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado'])->count(),
            'pedidos_entregados' => $pedidos->where('estatus', 'entregado')->count(),
            'pedidos_cancelados' => $pedidos->where('estatus', 'cancelado')->count(),
            'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
        ];

        $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        })
        ->whereHas('role', function ($query) {
            $query->whereIn('name', ['GERENTE', 'ADMINISTRADOR']);
        })
        ->get();

        foreach ($usuariosProveedor as $usuario) {
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'resumen_semanal',
                'titulo' => 'Resumen Semanal de Pedidos',
                'mensaje' => "Resumen de pedidos de la semana: {$resumen['total_pedidos']} pedidos por \${$resumen['total_ventas']}",
                'datos' => $resumen,
            ]);
        }
    }

    /**
     * Enviar resumen mensual al cliente
     */
    public static function enviarResumenMensualCliente(int $usuarioId): void
    {
        $fechaInicio = now()->startOfMonth();
        $fechaFin = now()->endOfMonth();

        $pedidos = Pedido::whereHas('requisicion', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        })
        ->whereBetween('fecha_confirmacion', [$fechaInicio, $fechaFin])
        ->get();

        if ($pedidos->isEmpty()) {
            return;
        }

        $resumen = [
            'total_pedidos' => $pedidos->count(),
            'total_gastado' => $pedidos->whereIn('estatus', ['entregado', 'facturado'])->sum('total'),
            'pedidos_entregados' => $pedidos->where('estatus', 'entregado')->count(),
            'pedidos_pendientes' => $pedidos->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado'])->count(),
            'ahorro_estimado' => $pedidos->sum('descuento'),
            'proveedor_favorito' => $pedidos->groupBy('requisicion.proveedor_id')
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first(),
            'periodo' => $fechaInicio->format('F Y'),
        ];

        Notificacion::create([
            'usuario_id' => $usuarioId,
            'tipo' => 'resumen_mensual',
            'titulo' => 'Resumen Mensual de Pedidos',
            'mensaje' => "Tu resumen de {$fechaInicio->format('F')}: {$resumen['total_pedidos']} pedidos por \${$resumen['total_gastado']}",
            'datos' => $resumen,
        ]);
    }

    /**
     * Enviar notificación de descuento especial
     */
    public static function enviarDescuentoEspecial(int $usuarioId, array $descuento): void
    {
        Notificacion::create([
            'usuario_id' => $usuarioId,
            'tipo' => 'descuento_especial',
            'titulo' => 'Descuento Especial Disponible',
            'mensaje' => "Tienes un descuento del {$descuento['porcentaje']}% en tu próximo pedido",
            'datos' => [
                'porcentaje' => $descuento['porcentaje'],
                'monto_minimo' => $descuento['monto_minimo'] ?? null,
                'fecha_vencimiento' => $descuento['fecha_vencimiento'],
                'codigo_descuento' => $descuento['codigo'] ?? null,
                'condiciones' => $descuento['condiciones'] ?? null,
            ],
        ]);
    }

    /**
     * Enviar notificación de nuevo producto del proveedor
     */
    public static function enviarNuevoProducto(int $proveedorId, array $producto): void
    {
        // Obtener clientes frecuentes del proveedor
        $clientesFrecuentes = User::whereHas('requisiciones', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId)
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('usuario_id')
                ->havingRaw('COUNT(*) >= 3');
        })->get();

        foreach ($clientesFrecuentes as $cliente) {
            Notificacion::create([
                'usuario_id' => $cliente->id,
                'tipo' => 'nuevo_producto',
                'titulo' => 'Nuevo Producto Disponible',
                'mensaje' => "Tu proveedor favorito tiene un nuevo producto: {$producto['nombre']}",
                'datos' => [
                    'producto_id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'imagen' => $producto['imagen'] ?? null,
                    'proveedor_id' => $proveedorId,
                ],
            ]);
        }
    }

    /**
     * Enviar alerta de stock bajo
     */
    public static function enviarAlertaStockBajo(int $proveedorId, array $producto): void
    {
        $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        })
        ->whereHas('role', function ($query) {
            $query->whereIn('name', ['GERENTE', 'VENTAS', 'ADMINISTRADOR']);
        })
        ->get();

        foreach ($usuariosProveedor as $usuario) {
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'stock_bajo',
                'titulo' => 'Stock Bajo',
                'mensaje' => "El producto {$producto['nombre']} tiene stock bajo ({$producto['stock']} unidades)",
                'datos' => [
                    'producto_id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'sku' => $producto['sku'],
                    'stock_actual' => $producto['stock'],
                    'stock_minimo' => $producto['stock_minimo'],
                ],
            ]);
        }
    }

    /**
     * Enviar notificación de promoción
     */
    public static function enviarPromocion(array $usuarioIds, array $promocion): void
    {
        foreach ($usuarioIds as $usuarioId) {
            Notificacion::create([
                'usuario_id' => $usuarioId,
                'tipo' => 'promocion',
                'titulo' => $promocion['titulo'],
                'mensaje' => $promocion['mensaje'],
                'datos' => [
                    'promocion_id' => $promocion['id'],
                    'descuento' => $promocion['descuento'],
                    'productos_incluidos' => $promocion['productos'] ?? [],
                    'fecha_inicio' => $promocion['fecha_inicio'],
                    'fecha_fin' => $promocion['fecha_fin'],
                    'condiciones' => $promocion['condiciones'] ?? null,
                ],
            ]);
        }
    }
}
