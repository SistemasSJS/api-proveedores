<?php

use Illuminate\Support\Facades\Broadcast;

// Canal público de notificaciones
Broadcast::channel('public-notifications', function () {
    return true;
});

// Canal privado de usuario (notificaciones personales)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal de notificaciones por proveedor - DESACTIVADO
// Permite a usuarios autenticados que pertenecen al proveedor recibir notificaciones
// Broadcast::channel('proveedor.{proveedorId}', function ($user, $proveedorId) {
//     // Verificar si el usuario tiene acceso al proveedor
//     return $user->tieneAccesoAProveedor((int) $proveedorId);
// });
