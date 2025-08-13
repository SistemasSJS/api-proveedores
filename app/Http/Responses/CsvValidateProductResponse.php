<?php

namespace App\Http\Responses;

/**
 * Response para el endpoint de validación de producto CSV
 * POST /api/proveedor/{id}/csv-import/validate-producto
 */
class CsvValidateProductResponse
{
    public function __construct(
        public bool $valido,
        public array $errores,
        public array $advertencias,
        public bool $existe,
        public ?array $productoExistente,
        public array $sugerencias,
        public array $accionesRecomendadas
    ) {}

    /**
     * Convierte la respuesta a array para ApiResponse
     */
    public function toArray(): array
    {
        return [
            'valido' => $this->valido,
            'errores' => $this->errores,
            'advertencias' => $this->advertencias,
            'existe' => $this->existe,
            'producto_existente' => $this->productoExistente,
            'sugerencias' => $this->sugerencias,
            'acciones_recomendadas' => $this->accionesRecomendadas
        ];
    }

    /**
     * Crear respuesta desde resultado de validación
     */
    public static function fromValidation(
        array $validationResult,
        bool $productExists = false,
        ?array $existingProduct = null,
        array $recommendedActions = []
    ): self {
        return new self(
            valido: empty($validationResult['errors']),
            errores: $validationResult['errors'] ?? [],
            advertencias: $validationResult['warnings'] ?? [],
            existe: $productExists,
            productoExistente: $existingProduct,
            sugerencias: [],
            accionesRecomendadas: $recommendedActions
        );
    }

    /**
     * Crear respuesta exitosa
     */
    public static function valid(array $recommendedActions = []): self
    {
        return new self(
            valido: true,
            errores: [],
            advertencias: [],
            existe: false,
            productoExistente: null,
            sugerencias: [],
            accionesRecomendadas: $recommendedActions
        );
    }

    /**
     * Crear respuesta de error
     */
    public static function invalid(array $errors, array $warnings = [], array $recommendedActions = []): self
    {
        return new self(
            valido: false,
            errores: $errors,
            advertencias: $warnings,
            existe: false,
            productoExistente: null,
            sugerencias: [],
            accionesRecomendadas: $recommendedActions
        );
    }
}
