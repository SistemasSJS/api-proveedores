<?php

namespace App\Policies;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;


class NotificacionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Todos los usuarios pueden ver sus notificaciones
    }

    public function view(User $user, Notificacion $notificacion)
    {
        return $user->id === $notificacion->usuario_id;
    }

    public function update(User $user, Notificacion $notificacion)
    {
        // Solo el propietario puede marcar como leída
        return $user->id === $notificacion->usuario_id;
    }

    public function delete(User $user, Notificacion $notificacion)
    {
        return $user->id === $notificacion->usuario_id;
    }
}
