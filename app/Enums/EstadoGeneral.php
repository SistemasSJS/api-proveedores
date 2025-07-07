<?php

namespace App\Enums;

enum EstadoGeneral: string
{
  case Borrador   = 'borrador';
  case Activo     = 'activo';
  case Inactivo   = 'inactivo';
  case Archivado  = 'archivado';
  case Eliminado  = 'eliminado';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
