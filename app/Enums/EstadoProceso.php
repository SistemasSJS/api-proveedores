<?php

namespace App\Enums;

enum EstadoProceso: string
{
  case PENDIENTE  = 'pendiente';
  case EN_PROCESO = 'en_proceso';
  case COMPLETADO = 'completado';
  case CANCELADO  = 'cancelado';
  case FALLIDO    = 'fallido';
  case APROBADO   = 'aprobado';
  case RECHAZADO  = 'rechazado';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  public function label(): string
  {
    return match ($this) {
      self::PENDIENTE  => 'Pendiente',
      self::EN_PROCESO => 'En Proceso',
      self::COMPLETADO => 'Completado',
      self::CANCELADO  => 'Cancelado',
      self::FALLIDO    => 'Fallido',
      self::APROBADO   => 'Aprobado',
      self::RECHAZADO  => 'Rechazado',
    };
  }
}
