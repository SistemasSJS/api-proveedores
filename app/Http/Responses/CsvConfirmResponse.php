<?php

namespace App\Http\Responses;

/**
 * Response para el endpoint de confirmación de importación CSV
 * POST /api/proveedor/{id}/csv-import/confirm
 */
class CsvConfirmResponse
{
    public function __construct(
        public int $auditId,
        public string $estado,
        public array $estadisticas,
        public array $resumen,
        public ?array $erroresDetalle = null
    ) {}

    /**
     * Convierte la respuesta a array para ApiResponse
     */
    public function toArray(): array
    {
        $data = [
            'audit_id' => $this->auditId,
            'estado' => $this->estado,
            'estadisticas' => $this->estadisticas,
            'resumen' => $this->resumen
        ];

        if ($this->erroresDetalle !== null) {
            $data['errores_detalle'] = $this->erroresDetalle;
        }

        return $data;
    }

    /**
     * Crear respuesta exitosa
     */
    public static function success(int $auditId, array $importStats): self
    {
        return new self(
            auditId: $auditId,
            estado: 'completado',
            estadisticas: $importStats,
            resumen: [
                'total_procesados' => $importStats['total_processed'],
                'creados' => $importStats['created'],
                'actualizados' => $importStats['updated'],
                'errores' => $importStats['errors'],
                'tasa_exito' => $importStats['success_rate']
            ]
        );
    }

    /**
     * Crear respuesta de error
     */
    public static function error(int $auditId, array $importStats, array $errorDetails): self
    {
        return new self(
            auditId: $auditId,
            estado: 'error',
            estadisticas: $importStats,
            resumen: [
                'total_procesados' => $importStats['total_processed'] ?? 0,
                'creados' => $importStats['created'] ?? 0,
                'actualizados' => $importStats['updated'] ?? 0,
                'errores' => $importStats['errors'] ?? 0,
                'tasa_exito' => $importStats['success_rate'] ?? 0
            ],
            erroresDetalle: $errorDetails
        );
    }
}
