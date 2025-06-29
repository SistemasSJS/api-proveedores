<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Requisicion;
use App\Models\User;

class NotificacionService
{
  /**
   * Enviar notificación de nueva requisición a usuarios del proveedor
   */
  public static function enviarNuevaRequisicion(Requisicion $requisicion): void
  {
    // Notificar a usuarios del proveedor con rol GERENTE o VENTAS
    $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($requisicion) {
      $query->where('proveedor_id', $requisicion->proveedor_id);
    })
      ->whereHas('role', function ($query) {
        $query->whereIn('name', ['GERENTE', 'VENTAS']);
      })
      ->get();

    foreach ($usuariosProveedor as $usuario) {
      Notificacion::create([
        'usuario_id' => $usuario->id,
        'tipo' => 'requisicion_nueva',
        'titulo' => 'Nueva Requisición Recibida',
        'mensaje' => "Se ha recibido una nueva requisición #{$requisicion->numero_requisicion} de {$requisicion->usuario->name}",
        'datos' => [
          'requisicion_id' => $requisicion->id,
          'numero_requisicion' => $requisicion->numero_requisicion,
        ],
      ]);
    }
  }

  /**
   * Enviar notificación de cambio de estatus al cliente
   */
  public static function enviarCambioEstatusRequisicion(Requisicion $requisicion): void
  {
    $estatusTexto = match ($requisicion->estatus) {
      'en_proceso' => 'está siendo procesada',
      'cotizada' => 'ha sido cotizada',
      'rechazada' => 'ha sido rechazada',
      'entregada' => 'ha sido entregada',
      default => 'ha sido actualizada'
    };

    Notificacion::create([
      'usuario_id' => $requisicion->usuario_id,
      'tipo' => 'requisicion_actualizada',
      'titulo' => 'Actualización de Requisición',
      'mensaje' => "Su requisición #{$requisicion->numero_requisicion} {$estatusTexto}",
      'datos' => [
        'requisicion_id' => $requisicion->id,
        'numero_requisicion' => $requisicion->numero_requisicion,
        'estatus' => $requisicion->estatus,
      ],
    ]);
  }

  /**
   * Enviar notificación de cotización generada
   */
  public static function enviarCotizacionGenerada(Requisicion $requisicion): void
  {
    Notificacion::create([
      'usuario_id' => $requisicion->usuario_id,
      'tipo' => 'cotizacion_recibida',
      'titulo' => 'Cotización Disponible',
      'mensaje' => "Ha recibido una cotización para la requisición #{$requisicion->numero_requisicion}",
      'datos' => [
        'requisicion_id' => $requisicion->id,
        'numero_requisicion' => $requisicion->numero_requisicion,
        'cotizacion_id' => $requisicion->cotizacion->id,
      ],
    ]);
  }

  /**
   * Enviar notificación de requisición cancelada
   */
  public static function enviarRequisicionCancelada(Requisicion $requisicion): void
  {
    $usuariosProveedor = User::whereHas('proveedores', function ($query) use ($requisicion) {
      $query->where('proveedor_id', $requisicion->proveedor_id);
    })->get();

    foreach ($usuariosProveedor as $usuario) {
      Notificacion::create([
        'usuario_id' => $usuario->id,
        'tipo' => 'requisicion_actualizada',
        'titulo' => 'Requisición Cancelada',
        'mensaje' => "La requisición #{$requisicion->numero_requisicion} ha sido cancelada por el cliente",
        'datos' => [
          'requisicion_id' => $requisicion->id,
          'numero_requisicion' => $requisicion->numero_requisicion,
          'motivo' => $requisicion->motivo_cancelacion,
        ],
      ]);
    }
  }
}
