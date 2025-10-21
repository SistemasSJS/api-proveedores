<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PedidoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any pedidos.
     */
    public function viewAny(User $user): bool
    {
        return true; // Todos los usuarios autenticados pueden ver sus pedidos
    }

    /**
     * Determine whether the user can view the pedido.
     */
    public function view(User $user, Pedido $pedido): bool
    {
        // El usuario puede ver el pedido si creó la requisición
        if ($user->id === $pedido->requisicion->usuario_id) {
            return true;
        }

        // Los usuarios del proveedor pueden ver pedidos dirigidos a su proveedor
        if ($user->proveedores()->where('proveedor_id', $pedido->requisicion->proveedor_id)->exists()) {
            return true;
        }

        // Los administradores pueden ver todos los pedidos
        return $user->role?->name === 'ADMINISTRADOR';
    }

    /**
     * Determine whether the user can create pedidos.
     */
    public function create(User $user): bool
    {
        // Todos los usuarios autenticados pueden crear pedidos
        return true;
    }

    /**
     * Determine whether the user can update the pedido.
     */
    public function update(User $user, Pedido $pedido): bool
    {
        // Solo el creador de la requisición puede actualizar (cancelar) el pedido
        if ($user->id === $pedido->requisicion->usuario_id) {
            return in_array($pedido->estatus, ['confirmado', 'en_preparacion']);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the pedido.
     */
    public function delete(User $user, Pedido $pedido): bool
    {
        // Solo administradores pueden eliminar pedidos
        return $user->role?->name === 'ADMINISTRADOR';
    }

    /**
     * Determine whether the user can view proveedor pedidos.
     */
    public function viewProveedorPedidos(User $user, Proveedor $proveedor): bool
    {
        // El usuario debe tener relación con el proveedor
        if (! $user->proveedores()->where('proveedor_id', $proveedor->id)->exists()) {
            return false;
        }

        // Verificar que tenga permisos para ver pedidos
        return in_array($user->role?->name, ['ADMINISTRADOR', 'GERENTE', 'VENTAS', 'SUPERVISOR']);
    }

    /**
     * Determine whether the user can update proveedor pedidos.
     */
    public function updateProveedorPedidos(User $user, Proveedor $proveedor): bool
    {
        // El usuario debe tener relación con el proveedor
        if (! $user->proveedores()->where('proveedor_id', $proveedor->id)->exists()) {
            return false;
        }

        // Solo ciertos roles pueden actualizar pedidos
        return in_array($user->role?->name, ['ADMINISTRADOR', 'GERENTE', 'VENTAS']);
    }

    /**
     * Determine whether the user can manage shipments.
     */
    public function manageShipments(User $user, Proveedor $proveedor): bool
    {
        // El usuario debe tener relación con el proveedor
        if (! $user->proveedores()->where('proveedor_id', $proveedor->id)->exists()) {
            return false;
        }

        // Solo ciertos roles pueden gestionar envíos
        return in_array($user->role?->name, ['ADMINISTRADOR', 'GERENTE', 'VENTAS']);
    }

    /**
     * Determine whether the user can confirm deliveries.
     */
    public function confirmDeliveries(User $user, Proveedor $proveedor): bool
    {
        // El usuario debe tener relación con el proveedor
        if (! $user->proveedores()->where('proveedor_id', $proveedor->id)->exists()) {
            return false;
        }

        // Solo ciertos roles pueden confirmar entregas
        return in_array($user->role?->name, ['ADMINISTRADOR', 'GERENTE', 'VENTAS']);
    }

    /**
     * Determine whether the user can reject pedidos.
     */
    public function rejectPedidos(User $user, Proveedor $proveedor): bool
    {
        // El usuario debe tener relación con el proveedor
        if (! $user->proveedores()->where('proveedor_id', $proveedor->id)->exists()) {
            return false;
        }

        // Solo gerentes y administradores pueden rechazar pedidos
        return in_array($user->role?->name, ['ADMINISTRADOR', 'GERENTE']);
    }

    /**
     * Determine whether the user can export pedidos.
     */
    public function export(User $user): bool
    {
        // Todos los usuarios autenticados pueden exportar sus pedidos
        return true;
    }

    /**
     * Determine whether the user can export proveedor pedidos.
     */
    public function exportProveedorPedidos(User $user, Proveedor $proveedor): bool
    {
        // El usuario debe tener relación con el proveedor
        if (! $user->proveedores()->where('proveedor_id', $proveedor->id)->exists()) {
            return false;
        }

        // Solo ciertos roles pueden exportar
        return in_array($user->role?->name, ['ADMINISTRADOR', 'GERENTE', 'SUPERVISOR']);
    }

    /**
     * Determine whether the user can access dashboard.
     */
    public function viewDashboard(User $user, Proveedor $proveedor): bool
    {
        // El usuario debe tener relación con el proveedor
        if (! $user->proveedores()->where('proveedor_id', $proveedor->id)->exists()) {
            return false;
        }

        // Todos los roles pueden ver el dashboard
        return in_array($user->role?->name, ['ADMINISTRADOR', 'GERENTE', 'VENTAS', 'SUPERVISOR', 'AUXILIAR']);
    }

    /**
     * Determine whether the user can duplicate pedidos.
     */
    public function duplicate(User $user, Pedido $pedido): bool
    {
        // Solo el creador de la requisición puede duplicar el pedido
        if ($user->id !== $pedido->requisicion->usuario_id) {
            return false;
        }

        // Solo se pueden duplicar pedidos entregados o facturados
        return in_array($pedido->estatus, ['entregado', 'facturado']);
    }

    /**
     * Determine whether the user can confirm reception.
     */
    public function confirmReception(User $user, Pedido $pedido): bool
    {
        // Solo el creador de la requisición puede confirmar recepción
        if ($user->id !== $pedido->requisicion->usuario_id) {
            return false;
        }

        // Solo se puede confirmar recepción de pedidos entregados
        return $pedido->estatus === 'entregado';
    }
}
