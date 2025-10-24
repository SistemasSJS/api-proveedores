<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal público de notificaciones
Broadcast::channel('public-notifications', function () {
    return true;
});

// Canal privado de usuario (notificaciones personales)
Broadcast::channel('App.Models.User.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal de notificaciones por proveedor
// Permite a usuarios autenticados que pertenecen al proveedor recibir notificaciones
Broadcast::channel('proveedor.{proveedorId}', function ($user, $proveedorId) {
    // Verificar si el usuario tiene acceso al proveedor
    return $user->tieneAccesoAProveedor((int) $proveedorId);
});
