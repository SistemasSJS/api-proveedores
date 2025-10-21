<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidarPermisosOrdenesCompra
{
    /**
     * Maneja una request entrante para validar permisos de órdenes de compra.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $accion = 'ver'): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'Usuario no autenticado',
                'message' => 'Se requiere autenticación para acceder a órdenes de compra',
            ], 401);
        }

        // Verificar permisos según el rol del usuario
        if (! $this->tienePermiso($user, $accion)) {
            return response()->json([
                'error' => 'Permisos insuficientes',
                'message' => "No tiene permisos para $accion órdenes de compra",
                'accion_solicitada' => $accion,
                'rol_usuario' => $user->rol ?? 'sin_rol',
            ], 403);
        }

        // Validaciones específicas por acción
        if (! $this->validarAccionEspecifica($request, $user, $accion)) {
            return response()->json([
                'error' => 'Validación fallida',
                'message' => $this->getMensajeValidacion($accion),
            ], 422);
        }

        return $next($request);
    }

    /**
     * Verifica si el usuario tiene permisos para la acción solicitada.
     */
    private function tienePermiso($user, string $accion): bool
    {
        $permisos = [
            'ver' => ['admin', 'gerente', 'empleado', 'proveedor'],
            'crear' => ['admin', 'gerente', 'empleado'],
            'editar' => ['admin', 'gerente', 'empleado'],
            'eliminar' => ['admin', 'gerente'],
            'aprobar' => ['admin', 'gerente'],
            'rechazar' => ['admin', 'gerente'],
            'cancelar' => ['admin', 'gerente'],
            'convertir' => ['admin', 'gerente', 'empleado'],
            'reportes' => ['admin', 'gerente'],
        ];

        $rolUsuario = $user->rol ?? 'sin_rol';
        $rolesPermitidos = $permisos[$accion] ?? [];

        return in_array($rolUsuario, $rolesPermitidos);
    }

    /**
     * Validaciones específicas por acción.
     */
    private function validarAccionEspecifica(Request $request, $user, string $accion): bool
    {
        switch ($accion) {
            case 'ver':
                return $this->validarAccesoLectura($request, $user);

            case 'crear':
                return $this->validarCreacion($request, $user);

            case 'editar':
                return $this->validarEdicion($request, $user);

            case 'aprobar':
            case 'rechazar':
                return $this->validarAprobacion($request, $user);

            case 'convertir':
                return $this->validarConversion($request, $user);

            default:
                return true;
        }
    }

    /**
     * Valida acceso de lectura.
     */
    private function validarAccesoLectura(Request $request, $user): bool
    {
        // Los proveedores solo pueden ver sus propias órdenes
        if ($user->rol === 'proveedor') {
            $ordenId = $request->route('ordenCompra');
            if ($ordenId && ! $this->perteneceAlProveedor($ordenId, $user->id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida creación de órdenes.
     */
    private function validarCreacion(Request $request, $user): bool
    {
        // Verificar si tiene límites de creación
        if ($user->rol === 'empleado' && $this->excedeLimiteCreacion($user)) {
            return false;
        }

        return true;
    }

    /**
     * Valida edición de órdenes.
     */
    private function validarEdicion(Request $request, $user): bool
    {
        $ordenId = $request->route('ordenCompra');

        if (! $ordenId) {
            return false;
        }

        // Verificar estado de la orden
        if (! $this->puedeEditarse($ordenId)) {
            return false;
        }

        return true;
    }

    /**
     * Valida aprobación/rechazo.
     */
    private function validarAprobacion(Request $request, $user): bool
    {
        $ordenId = $request->route('ordenCompra');

        if (! $ordenId) {
            return false;
        }

        // Verificar que la orden esté en estado pendiente
        return $this->estaEnEstadoPendiente($ordenId);
    }

    /**
     * Valida conversión a solicitud de pago.
     */
    private function validarConversion(Request $request, $user): bool
    {
        $ordenId = $request->route('ordenCompra');

        if (! $ordenId) {
            return false;
        }

        // Verificar que la orden esté aprobada
        return $this->estaAprobada($ordenId) && $this->tieneMontoDisponible($ordenId);
    }

    /**
     * Verifica si una orden pertenece a un proveedor.
     */
    private function perteneceAlProveedor($ordenId, $proveedorId): bool
    {
        // Implementar lógica para verificar pertenencia
        // Por ahora retorna true, debe implementarse según el modelo
        return true;
    }

    /**
     * Verifica si el usuario excede el límite de creación.
     */
    private function excedeLimiteCreacion($user): bool
    {
        // Implementar lógica de límites
        // Por ahora retorna false
        return false;
    }

    /**
     * Verifica si una orden puede editarse.
     */
    private function puedeEditarse($ordenId): bool
    {
        // Implementar verificación de estado
        // Por ahora retorna true
        return true;
    }

    /**
     * Verifica si una orden está en estado pendiente.
     */
    private function estaEnEstadoPendiente($ordenId): bool
    {
        // Implementar verificación de estado
        // Por ahora retorna true
        return true;
    }

    /**
     * Verifica si una orden está aprobada.
     */
    private function estaAprobada($ordenId): bool
    {
        // Implementar verificación de estado
        // Por ahora retorna true
        return true;
    }

    /**
     * Verifica si una orden tiene monto disponible.
     */
    private function tieneMontoDisponible($ordenId): bool
    {
        // Implementar verificación de monto
        // Por ahora retorna true
        return true;
    }

    /**
     * Obtiene el mensaje de validación específico.
     */
    private function getMensajeValidacion(string $accion): string
    {
        $mensajes = [
            'ver' => 'No puede acceder a órdenes de compra de otros proveedores',
            'crear' => 'Ha excedido el límite de órdenes de compra permitidas',
            'editar' => 'La orden de compra no puede editarse en su estado actual',
            'aprobar' => 'La orden de compra debe estar en estado pendiente para aprobarla',
            'rechazar' => 'La orden de compra debe estar en estado pendiente para rechazarla',
            'convertir' => 'La orden de compra debe estar aprobada y tener monto disponible',
        ];

        return $mensajes[$accion] ?? 'Validación fallida para la acción solicitada';
    }
}
