<?php

namespace App\Services\Auth;

use App\Models\Proveedor;
use App\Models\User;
use App\Models\UserProveedor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar el acceso de usuarios a proveedores
 * a través de la tabla intermedia user_proveedor.
 *
 * Centraliza toda la lógica de verificación de permisos y relaciones
 * entre usuarios y proveedores.
 */
class ProveedorAccessService
{
    /**
     * Resuelve acceso y tipo de relación en una sola consulta y una entrada de caché
     * (el middleware proveedor.access llamaba antes hasAccess + relation = 2 hits).
     *
     * @return string|null 'PRINCIPAL', 'SECUNDARIO' o null si no hay relación activa
     */
    public function resolveProveedorAccess(int $userId, int $proveedorId): ?string
    {
        $cacheKey = "user_proveedor_access_ctx_{$userId}_{$proveedorId}";

        return Cache::remember($cacheKey, 300, function () use ($userId, $proveedorId) {
            return UserProveedor::query()
                ->where('user_id', $userId)
                ->where('proveedor_id', $proveedorId)
                ->where('activo', true)
                ->value('tipo_relacion');
        });
    }

    /**
     * Verifica si un usuario tiene acceso a un proveedor específico.
     */
    public function hasAccessToProveedor(int $userId, int $proveedorId): bool
    {
        return $this->resolveProveedorAccess($userId, $proveedorId) !== null;
    }

    /**
     * Obtiene el tipo de relación del usuario con un proveedor.
     *
     * @return string|null 'PRINCIPAL', 'SECUNDARIO' o null
     */
    public function getUserProveedorRelationType(int $userId, int $proveedorId): ?string
    {
        return $this->resolveProveedorAccess($userId, $proveedorId);
    }

    /**
     * Verifica si un usuario es el usuario principal de un proveedor.
     */
    public function isMainUser(int $userId, int $proveedorId): bool
    {
        return $this->getUserProveedorRelationType($userId, $proveedorId) === 'PRINCIPAL';
    }

    /**
     * Verifica si un usuario es secundario de un proveedor.
     */
    public function isSecondaryUser(int $userId, int $proveedorId): bool
    {
        return $this->getUserProveedorRelationType($userId, $proveedorId) === 'SECUNDARIO';
    }

    /**
     * Obtiene todos los proveedores a los que tiene acceso un usuario.
     *
     * @param  bool  $onlyActive  Solo proveedores activos
     */
    public function getUserProveedores(int $userId, bool $onlyActive = true): Collection
    {
        $cacheKey = "user_proveedores_{$userId}_".($onlyActive ? 'active' : 'all');

        return Cache::remember($cacheKey, 300, function () use ($userId, $onlyActive) {
            $query = UserProveedor::with('proveedor')
                ->where('user_id', $userId)
                ->where('activo', true);

            if ($onlyActive) {
                $query->whereHas('proveedor', function ($q) {
                    $q->where('estatus', 'ACTIVO');
                });
            }

            return $query->get()->map(function ($userProveedor) {
                return [
                    'proveedor' => $userProveedor->proveedor,
                    'tipo_relacion' => $userProveedor->tipo_relacion,
                    'fecha_asignacion' => $userProveedor->fecha_asignacion,
                    'observaciones' => $userProveedor->observaciones,
                ];
            });
        });
    }

    /**
     * Obtiene el proveedor principal de un usuario (si existe).
     */
    public function getUserMainProveedor(int $userId): ?Proveedor
    {
        $cacheKey = "user_main_proveedor_{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $userProveedor = UserProveedor::with('proveedor')
                ->where('user_id', $userId)
                ->where('activo', true)
                ->where('tipo_relacion', 'PRINCIPAL')
                ->first();

            return $userProveedor ? $userProveedor->proveedor : null;
        });
    }

    /**
     * Obtiene todos los usuarios que tienen acceso a un proveedor.
     *
     * @param  string|null  $tipoRelacion  Filtrar por tipo de relación
     */
    public function getProveedorUsers(int $proveedorId, ?string $tipoRelacion = null): Collection
    {
        $cacheKey = "proveedor_users_{$proveedorId}_".($tipoRelacion ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($proveedorId, $tipoRelacion) {
            $query = UserProveedor::with('user.role')
                ->where('proveedor_id', $proveedorId)
                ->where('activo', true);

            if ($tipoRelacion) {
                $query->where('tipo_relacion', $tipoRelacion);
            }

            return $query->get()->map(function ($userProveedor) {
                return [
                    'user' => $userProveedor->user,
                    'tipo_relacion' => $userProveedor->tipo_relacion,
                    'fecha_asignacion' => $userProveedor->fecha_asignacion,
                    'observaciones' => $userProveedor->observaciones,
                ];
            });
        });
    }

    /**
     * Verifica si un usuario puede realizar acciones administrativas en un proveedor.
     * Solo usuarios principales y ciertos roles pueden hacer acciones críticas.
     */
    public function canPerformAdminActions(int $userId, int $proveedorId): bool
    {
        $user = User::with('role')->find($userId);

        if (! $user) {
            return false;
        }

        // Los administradores globales pueden hacer todo
        if ($user->role && $user->role->name === 'ADMINISTRADOR') {
            return true;
        }

        // Solo usuarios principales pueden hacer acciones administrativas
        return $this->isMainUser($userId, $proveedorId);
    }

    /**
     * Verifica si un usuario puede gestionar otros usuarios del proveedor.
     */
    public function canManageProveedorUsers(int $userId, int $proveedorId): bool
    {
        return $this->canPerformAdminActions($userId, $proveedorId);
    }

    /**
     * Verifica si un usuario puede ver datos sensibles del proveedor.
     */
    public function canViewSensitiveData(int $userId, int $proveedorId): bool
    {
        $user = User::with('role')->find($userId);

        if (! $user) {
            return false;
        }

        // Administradores y gerentes pueden ver datos sensibles
        if ($user->role && in_array($user->role->name, ['ADMINISTRADOR', 'GERENTE'])) {
            return true;
        }

        // Usuarios principales también pueden ver datos sensibles
        return $this->isMainUser($userId, $proveedorId);
    }

    /**
     * Limpia el cache de acceso para un usuario específico.
     */
    public function clearUserAccessCache(int $userId): void
    {
        $patterns = [
            "user_proveedor_access_{$userId}_*",
            "user_proveedor_access_ctx_{$userId}_*",
            "user_proveedor_relation_{$userId}_*",
            "user_proveedores_{$userId}_*",
            "user_main_proveedor_{$userId}",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Limpia el cache de acceso para un proveedor específico.
     */
    public function clearProveedorAccessCache(int $proveedorId): void
    {
        $patterns = [
            "proveedor_users_{$proveedorId}_*",
            "user_proveedor_access_*_{$proveedorId}",
            "user_proveedor_access_ctx_*_{$proveedorId}",
            "user_proveedor_relation_*_{$proveedorId}",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Registra un evento de acceso para auditoría.
     */
    public function logProveedorAccess(int $userId, int $proveedorId, string $action, array $context = []): void
    {
        // Implementar logging de auditoría aquí
        // Por ejemplo, usando el sistema de logs de Laravel o una tabla específica

        Log::info('Proveedor Access', [
            'user_id' => $userId,
            'proveedor_id' => $proveedorId,
            'action' => $action,
            'context' => $context,
            'timestamp' => now(),
        ]);
    }
}
