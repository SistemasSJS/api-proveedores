<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('currentProveedor')) {
  /**
   * Devuelve el proveedor actual, ya sea:
   * - El proveedor real si el usuario tiene rol PROVEEDOR
   * - El proveedor "simulado" si el admin está contextualizado
   */
  function currentProveedor(): ?\App\Models\Proveedor
  {
    $user = Auth::user();

    if (!$user) {
      return null;
    }

    // Si es proveedor real, devuelve el proveedor vinculado
    if ($user->role->nombre === 'PROVEEDOR') {
      return $user->proveedor;
    }

    // Si es admin y está contextualizado, busca el proveedor de la sesión
    if (
      $user->role->nombre === 'ADMIN'
      && session()->has('proveedor_context_id')
    ) {
      return \App\Models\Proveedor::find(session('proveedor_context_id'));
    }

    return null;
  }
}

if (!function_exists('isContextualizedAsProveedor')) {
  /**
   * Retorna true si el usuario es admin y está actuando como proveedor
   */
  function isContextualizedAsProveedor(): bool
  {
    $user = Auth::user();

    return $user
      && $user->role->nombre === 'ADMIN'
      && session()->has('proveedor_context_id');
  }
}

if (!function_exists('isRealProveedor')) {
  /**
   * Retorna true si el usuario tiene rol de proveedor y no está contextualizado
   */
  function isRealProveedor(): bool
  {
    $user = Auth::user();

    return $user
      && $user->role->nombre === 'PROVEEDOR'
      && !session()->has('proveedor_context_id');
  }
}
