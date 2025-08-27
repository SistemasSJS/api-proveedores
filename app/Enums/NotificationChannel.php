<?php

namespace App\Enums;

/**
 * Canales de emicion para notificaciones
 */
enum NotificationChannel: string
{
  case DATABASE = 'database';
  case MAIL     = 'mail';
  case SMS      = 'sms';
  case PUSH     = 'push';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  public function label(): string
  {
    return match ($this) {
      self::DATABASE => 'Base de datos',
      self::MAIL     => 'Correo electrónico',
      self::SMS      => 'SMS',
      self::PUSH     => 'Notificación Push',
    };
  }
}
