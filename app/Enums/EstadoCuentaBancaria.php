<?php

namespace App\Enums;

enum EstadoCuentaBancaria: string
{
    case ACTIVA = 'activa';
    case INACTIVA = 'inactiva';
    case VALIDADA = 'validada';
    case PENDIENTE = 'pendiente';
    case BLOQUEADA = 'bloqueada';
    case ELIMINADA = 'eliminada';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::INACTIVA => 'Inactiva',
            self::VALIDADA => 'Validada',
            self::PENDIENTE => 'Pendiente de validación',
            self::BLOQUEADA => 'Bloqueada por seguridad',
            self::ELIMINADA => 'Eliminada',
        };
    }
}
