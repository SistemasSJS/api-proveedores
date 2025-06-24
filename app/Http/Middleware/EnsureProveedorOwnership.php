<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Api\Auth\UnauthorizedProveedorAccessException;
use App\Services\Auth\ProveedorAccessService;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para asegurar que los usuarios solo accedan a recursos de proveedores
 * a los que tienen acceso a través de la tabla intermedia user_proveedor.
 *
 * Este middleware valida:
 * - Que el usuario esté autenticado
 * - Que tenga una relación activa con el proveedor (PRINCIPAL o SECUNDARIO)
 * - Que la relación esté marcada como activa en user_proveedor
 */
class EnsureProveedorOwnership
{
  protected $proveedorAccessService;

  public function __construct(ProveedorAccessService $proveedorAccessService)
  {
    $this->proveedorAccessService = $proveedorAccessService;
  }

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
   * @param  string|null  $parameterName  Nombre del parámetro que contiene el ID del proveedor (por defecto 'proveedor')
   * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
   *
   * @throws UnauthorizedProveedorAccessException
   */
  public function handle(Request $request, Closure $next, string $parameterName = 'proveedor')
  {
    // Verificar que el usuario esté autenticado
    if (!Auth::check()) {
      throw new UnauthorizedProveedorAccessException('Usuario no autenticado.');
    }

    $user = Auth::user();

    // Los usuarios ADMINISTRADOR tienen acceso a todos los proveedores
    if ($user->role && $user->role->name === 'ADMINISTRADOR') {
      return $next($request);
    }

    // Obtener el ID del proveedor desde los parámetros de la ruta
    $proveedorId = $this->getProveedorIdFromRequest($request, $parameterName);

    if (!$proveedorId) {
      throw new UnauthorizedProveedorAccessException('ID de proveedor no encontrado en la solicitud.');
    }

    $proveedorId = is_numeric($proveedorId) ? $proveedorId : $proveedorId->id;
    // Verificar si el usuario tiene acceso al proveedor
    if (!$this->proveedorAccessService->hasAccessToProveedor($user->id, $proveedorId)) {
      throw new UnauthorizedProveedorAccessException(
        "No tienes permisos para acceder a los recursos de este proveedor."
      );
    }

    // Agregar información del proveedor al request para uso posterior
    $request->merge([
      '_proveedor_id' => $proveedorId,
      '_user_proveedor_relation' => $this->proveedorAccessService->getUserProveedorRelationType($user->id, $proveedorId)
    ]);

    return $next($request);
  }

  /**
   * Extrae el ID del proveedor de la solicitud basándose en diferentes fuentes.
   *
   * @param Request $request
   * @param string $parameterName
   * @return int|null
   */
  protected function getProveedorIdFromRequest(Request $request, string $parameterName)
  {
    // 1. Intentar obtener desde parámetros de ruta
    $proveedorId = $request->route($parameterName);

    if ($proveedorId) {
      // return (int) $proveedorId;
      return  $proveedorId;
    }

    // 2. Intentar obtener desde query parameters
    $proveedorId = $request->query('proveedor_id');

    if ($proveedorId) {
      // return (int) $proveedorId;
      return  $proveedorId;
    }

    // 3. Intentar obtener desde el body de la solicitud
    $proveedorId = $request->input('proveedor_id');

    if ($proveedorId) {
      // return (int) $proveedorId;
      return  $proveedorId;
    }

    // 4. Para rutas anidadas, intentar extraer de la URL
    // Ejemplo: /api/proveedores/123/productos -> extraer 123
    $segments = $request->segments();
    $proveedorIndex = array_search('proveedores', $segments);

    if ($proveedorIndex !== false && isset($segments[$proveedorIndex + 1])) {
      // return (int) $segments[$proveedorIndex + 1];
      return  $segments[$proveedorIndex + 1];
    }

    return null;
  }

  /**
   * Método helper para verificar si el usuario es el usuario principal del proveedor.
   * Útil para acciones que requieren permisos de usuario principal.
   *
   * @param Request $request
   * @return bool
   */
  public static function isMainUser(Request $request): bool
  {
    return $request->get('_user_proveedor_relation') === 'PRINCIPAL';
  }

  /**
   * Método helper para verificar si el usuario es secundario del proveedor.
   *
   * @param Request $request
   * @return bool
   */
  public static function isSecondaryUser(Request $request): bool
  {
    return $request->get('_user_proveedor_relation') === 'SECUNDARIO';
  }

  /**
   * Método helper para obtener el ID del proveedor del request.
   *
   * @param Request $request
   * @return int|null
   */
  public static function getProveedorId(Request $request): ?int
  {
    return $request->get('_proveedor_id');
  }
}
