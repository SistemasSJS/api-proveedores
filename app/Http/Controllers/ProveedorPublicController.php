<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProveedorPublicResource;
use App\Models\Proveedor;
use App\Models\ProveedorPerfilPublico;
use App\Services\PerfilPublico\PerfilPublicoThemeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProveedorPublicController extends Controller
{
  use ApiResponse;

  /**
   * Perfil público de empresa por token (sin autenticación).
   */
  public function perfilPublico(string $token, PerfilPublicoThemeService $themeService): JsonResponse
  {
    $perfil = ProveedorPerfilPublico::query()
      ->where('token', $token)
      ->where('is_published', true)
      ->first();

    if (! $perfil || ! is_array($perfil->snapshot)) {
      return $this->error(
        'Perfil no disponible o el enlace ya no es válido.',
        null,
        404
      );
    }

    $themeKey = $themeService->resolveThemeKey($perfil->theme_key);
    $theme = $themeService->getTheme($themeKey);

    return $this->success([
      'token' => $perfil->token,
      'theme_key' => $themeKey,
      'theme' => $theme,
      'snapshot' => $perfil->snapshot,
      'published_at' => $perfil->published_at?->toIso8601String(),
    ], 'Perfil público disponible.');
  }

  /**
   * Ruta pública para compartir constancia fiscal del proveedor.
   */
  public function compartirConstancia(int $id): JsonResponse
  {
    $proveedor = Proveedor::select([
      'id',
      'logo',
      'nombre_comercial',
      'email',
      'telefono',
      'direccion_empresa',
      'constancia_fiscal',
    ])
      ->whereNotNull('constancia_fiscal')
      ->find($id);

    // ❌ No existe o no tiene constancia
    if (!$proveedor) {
      return $this->error(
        'Proveedor no disponible.',
        null,
        404
      );
    }

    // ✅ Proveedor válido con constancia (usa Resource para URLs completas)
    return $this->success(
      new ProveedorPublicResource($proveedor),
      'Proveedor disponible.',
      200
    );
  }
}
