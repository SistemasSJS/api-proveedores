<?php

namespace App\Enums;

enum EstatusPedido: string
{
    case CONFIRMADO          = 'confirmado';
    case EN_PREPARACION      = 'en_preparacion';
    case LISTO_PARA_ENTREGA  = 'listo_para_entrega';
    case EN_TRANSITO         = 'en_transito';
    case ENTREGADO           = 'entregado';
    case FACTURADO           = 'facturado';
    case CANCELADO           = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMADO          => 'Confirmado',
            self::EN_PREPARACION      => 'En preparación',
            self::LISTO_PARA_ENTREGA  => 'Listo para entrega',
            self::EN_TRANSITO         => 'En tránsito',
            self::ENTREGADO           => 'Entregado',
            self::FACTURADO           => 'Facturado',
            self::CANCELADO           => 'Cancelado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn($e) => [
            'value' => $e->value,
            'label' => $e->label()
        ], self::cases());
    }
}
