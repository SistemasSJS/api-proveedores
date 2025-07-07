<?php

namespace App\Enums;


enum EstadoPublicacion: string
{
  case ENVIADO       = 'enviado';
  case EN_REVISION   = 'en_revision';
  case VERIFICADO    = 'verificado';
  case PUBLICADO     = 'publicado';
  case NO_PUBLICADO  = 'no_publicado';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  public function label(): string
  {
    return match ($this) {
      self::ENVIADO       => 'Enviado',
      self::EN_REVISION   => 'En Revisión',
      self::VERIFICADO    => 'Verificado',
      self::PUBLICADO     => 'Publicado',
      self::NO_PUBLICADO  => 'No Publicado',
    };
  }
}
