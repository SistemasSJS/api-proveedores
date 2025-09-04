<?php

namespace App\Enums;

enum EstadoCotizacion: string
{
  case PENDIENTE  = 'pendiente';
  case ENVIADA    = 'enviada';
  case APROBADA   = 'aprobada';
  case ACEPTADA   = 'aceptada';
  case RECHAZADA  = 'rechazada';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  public function label(): string
  {
    return match ($this) {
      self::PENDIENTE => 'Pendiente',
      self::ENVIADA   => 'Enviada',
      self::APROBADA  => 'Aprobada',
      self::ACEPTADA  => 'Aceptada',
      self::RECHAZADA => 'Rechazada',
    };
  }
}
