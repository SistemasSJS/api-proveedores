<?php

namespace App\Enums;

/**
 * este enum se aplica para los campos de roles 'dg','dt','pc','si','da','ro' del mopdelo Splicitudes de pago
 */
enum EstadoSolicitud: int
{
    case PENDIENTE = 0;
    case AUTORIZADA = 1;
    case RECHAZADA = 2;
    case PAGADO = 3;

    // Devuelve todos los valores numéricos del enum
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // Devuelve la etiqueta legible para cada estado
    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::AUTORIZADA => 'Autorizada',
            self::RECHAZADA => 'Rechazada',
            self::PAGADO => 'Pagado',
        };
    }
}
