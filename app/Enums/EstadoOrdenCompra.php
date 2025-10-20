<?php

namespace App\Enums;

enum EstadoOrdenCompra: string
{
    case PENDIENTE = 'pendiente';
    case APROBADA = 'aprobada';
    case RECHAZADA = 'rechazada';
    case COMPLETADA = 'completada';
    case PARCIAL = 'parcial';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::APROBADA => 'Aprobada',
            self::RECHAZADA => 'Rechazada',
            self::COMPLETADA => 'Completada',
            self::PARCIAL => 'Parcial',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDIENTE => 'warning',
            self::APROBADA => 'success',
            self::RECHAZADA => 'danger',
            self::COMPLETADA => 'primary',
            self::PARCIAL => 'info',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
