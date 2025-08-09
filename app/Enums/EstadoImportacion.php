<?php

namespace App\Enums;

enum EstadoImportacion: string
{
  case PENDIENTE = 'pendiente';
  case PROCESANDO = 'procesando';
  case PREVIEW = 'preview';
  case CONFIRMADO = 'confirmado';
  case COMPLETADO = 'completado';
  case ERROR = 'error';
  case REGISTRADO   = 'registrado';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  public function label(): string
  {
    return match ($this) {
      self::PENDIENTE => 'Pendiente',
      self::PROCESANDO => 'Procesando',
      self::PREVIEW => 'Preview',
      self::CONFIRMADO => 'Confirmado',
      self::COMPLETADO => 'Completado',
      self::ERROR => 'Error',
      self::REGISTRADO  => 'Registrado',
    };
  }
}
