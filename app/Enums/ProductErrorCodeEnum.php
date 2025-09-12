<?php

namespace App\Enums;

enum ProductErrorCodeEnum: string
{
    case DUPLICATE_CONFLICT = 'duplicate_conflict';
    case INVALID_INPUT = 'invalid_input';
    case RESOURCE_NOT_FOUND = 'resource_not_found';
    case DELETE_RESTRICTED = 'delete_restricted';
    case VALIDATION_FAILED = 'validation_failed';
    case UNAUTHORIZED_ACCESS = 'unauthorized_access';
    case MISSING_REQUIRED_FIELDS = 'missing_required_fields';
    case INVALID_DATA_FORMAT = 'invalid_data_format';
    case CONCURRENT_MODIFICATION = 'concurrent_modification';
    case BUSINESS_RULE_VIOLATION = 'business_rule_violation';

    /**
     * Obtiene el mensaje descriptivo para el código de error
     */
    public function getMessage(): string
    {
        return match($this) {
            self::DUPLICATE_CONFLICT => 'Se encontraron productos duplicados con la misma combinación de código, nombre y descripción',
            self::INVALID_INPUT => 'Los datos proporcionados no son válidos',
            self::RESOURCE_NOT_FOUND => 'El producto solicitado no fue encontrado',
            self::DELETE_RESTRICTED => 'El producto no puede ser eliminado debido a restricciones del sistema',
            self::VALIDATION_FAILED => 'Los datos no cumplen con las reglas de validación establecidas',
            self::UNAUTHORIZED_ACCESS => 'No tiene permisos suficientes para realizar esta operación',
            self::MISSING_REQUIRED_FIELDS => 'Faltan campos obligatorios en la solicitud',
            self::INVALID_DATA_FORMAT => 'El formato de los datos proporcionados es incorrecto',
            self::CONCURRENT_MODIFICATION => 'El producto ha sido modificado por otro usuario. Actualice y vuelva a intentar',
            self::BUSINESS_RULE_VIOLATION => 'La operación viola las reglas de negocio establecidas',
        };
    }

    /**
     * Obtiene el código HTTP apropiado para el error
     */
    public function getHttpCode(): int
    {
        return match($this) {
            self::DUPLICATE_CONFLICT => 409,
            self::INVALID_INPUT => 422,
            self::RESOURCE_NOT_FOUND => 404,
            self::DELETE_RESTRICTED => 403,
            self::VALIDATION_FAILED => 422,
            self::UNAUTHORIZED_ACCESS => 401,
            self::MISSING_REQUIRED_FIELDS => 422,
            self::INVALID_DATA_FORMAT => 422,
            self::CONCURRENT_MODIFICATION => 409,
            self::BUSINESS_RULE_VIOLATION => 422,
        };
    }
}
