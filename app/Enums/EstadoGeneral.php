<?php

namespace App\Enums;

enum EstadoGeneral: string
{
  case BORRADOR   = 'borrador';
  case ACTIVO     = 'activo';
  case INACTIVO   = 'inactivo';
  case ARCHIVADO  = 'archivado';
  case ELIMINADO  = 'eliminado';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }


  public function label(): string
  {
    return match ($this) {
      self::BORRADOR => 'Borrador',
      self::ACTIVO   => 'Activo',
      self::INACTIVO => 'Inactivo',
      self::ARCHIVADO => 'Archivado',
      self::ELIMINADO => 'Eliminado',
    };
  }
}
