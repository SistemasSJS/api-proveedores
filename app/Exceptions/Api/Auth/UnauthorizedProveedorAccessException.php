<?php

namespace App\Exceptions\Api\Auth;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Excepción lanzada cuando un usuario intenta acceder a recursos de un proveedor
 * sin tener los permisos necesarios a través de la tabla user_proveedor.
 */
class UnauthorizedProveedorAccessException extends Exception
{
    protected $message = 'No tienes permisos para acceder a los recursos de este proveedor.';

    protected $code = 403;

    /**
     * Constructor de la excepción.
     *
     * @param  string  $message  Mensaje personalizado de error
     * @param  int  $code  Código de error HTTP (por defecto 403)
     * @param  Exception|null  $previous  Excepción anterior
     */
    public function __construct(?string $message = null, int $code = 403, ?Exception $previous = null)
    {
        $this->message = $message ?? $this->message;
        $this->code = $code;

        parent::__construct($this->message, $this->code, $previous);
    }

    /**
     * Renderiza la excepción como respuesta HTTP.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ERROR',
            'message' => $this->getMessage(),
            'error_code' => 'UNAUTHORIZED_PROVEEDOR_ACCESS',
            'timestamp' => now()->toISOString(),
        ], $this->getCode());
    }

    /**
     * Reporta la excepción al sistema de logs.
     */
    public function report(): bool
    {
        Log::warning('Unauthorized Proveedor Access Attempt', [
            'message' => $this->getMessage(),
            // 'user_id' => auth()->userid(),
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);

        return true;
    }

    /**
     * Crea una instancia específica para acceso denegado por falta de relación.
     */
    public static function noRelationFound(int $userId, int $proveedorId): static
    {
        return new static(
            "El usuario {$userId} no tiene una relación activa con el proveedor {$proveedorId}."
        );
    }

    /**
     * Crea una instancia específica para relación inactiva.
     */
    public static function inactiveRelation(int $userId, int $proveedorId): static
    {
        return new static(
            "La relación del usuario {$userId} con el proveedor {$proveedorId} está inactiva."
        );
    }

    /**
     * Crea una instancia específica para permisos insuficientes.
     */
    public static function insufficientPermissions(string $action): static
    {
        return new static(
            "No tienes permisos suficientes para realizar la acción: {$action}."
        );
    }

    /**
     * Crea una instancia específica para usuarios secundarios que intentan acciones administrativas.
     */
    public static function secondaryUserAdminAction(): static
    {
        return new static(
            'Los usuarios secundarios no pueden realizar acciones administrativas. Solo el usuario principal tiene estos permisos.'
        );
    }
}
