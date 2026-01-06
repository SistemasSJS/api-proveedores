<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProveedorPublicController extends Controller
{
  use ApiResponse;

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

    // ✅ Proveedor válido con constancia
    return $this->success(
      $proveedor,
      'Proveedor disponible.',
      200
    );
  }
}
