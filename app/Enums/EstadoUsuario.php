<?php

namespace App\Enums;

enum EstadoUsuario: string
{
  case Registrado   = 'registrado';
  case Verificado   = 'verificado';
  case Suspendido   = 'suspendido';
  case Bloqueado    = 'bloqueado';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
