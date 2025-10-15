<?php

namespace App\Enums;

/**
 * este enum se aplica para el campo 'estado_solicitud' de una SP
 */
enum EstadoSP: string
{
  case PENDIENTE   = 'pendiente';
  case RECHAZADA  = 'rechazada';
  case AUTORIZADA   = 'autorizada';
  case PAGADO      = 'pagado';

  // Devuelve todos los valores del enum
  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  // Devuelve la etiqueta legible para cada estado
  public function label(): string
  {
    return match ($this) {
      self::PENDIENTE  => 'Pendiente',
      self::RECHAZADA => 'Rechazada',
      self::AUTORIZADA  => 'Autorizada',
      self::PAGADO     => 'Pagado',
    };
  }
}
