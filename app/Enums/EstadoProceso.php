<?php

namespace App\Enums;

enum EstadoProceso: string
{
  case Pendiente    = 'pendiente';
  case EnProceso    = 'en_proceso';
  case Completado   = 'completado';
  case Cancelado    = 'cancelado';
  case Fallido      = 'fallido';
  case Aprobado     = 'aprobado';
  case Rechazado    = 'rechazado';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
