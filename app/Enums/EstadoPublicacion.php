<?php

namespace App\Enums;


enum EstadoPublicacion: string
{
  case Enviado       = 'enviado';
  case EnRevision    = 'en_revision';
  case Verificado    = 'verificado';
  case Publicado     = 'publicado';
  case NoPublicado   = 'no_publicado';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
