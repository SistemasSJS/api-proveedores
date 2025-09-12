<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorUpdateConstanciaFiscalRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // Si tienes policies, puedes ajustarlo
  }

  public function rules(): array
  {
    return [
      'constancia_fiscal' => 'required|file|mimes:pdf|max:5120',
      // solo PDFs hasta 5MB
    ];
  }
}
  
    /**
     * Actualiza la constancia fiscal del proveedor principal del usuario autenticado.
     *
     * - Elimina la constancia fiscal anterior del proveedor (si existe).
     * - Guarda y asigna la nueva constancia fiscal.
     *
     * @param  ProveedorUpdateConstanciaFiscalRequest  $request
     * @param  Proveedor  $proveedor
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws ResourceNotFoundException Si el proveedor no existe.
     */
    public function updateConstanciaFiscal(ProveedorUpdateConstanciaFiscalRequest $request, Proveedor $proveedor)
    {
        $user = $request->user();
        $proveedor = $user->proveedorPrincipal();
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        if ($proveedor->constancia_fiscal !== null) {
            $rutaAnterior = str_replace(asset('storage') . '/', '', $proveedor->constancia_fiscal);
            Storage::disk('public')->delete($rutaAnterior);
        }

        $file = $request->file('constancia_fiscal');
        $filename = 'constancia_fiscal_' . $proveedor->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("uploads", $filename, 'public');

        $proveedor->update(['constancia_fiscal' => $path]);

        return $this->success([
            'proveedor' => new ProveedorResource(($proveedor->fresh(Proveedor::eagerLodable()))),
            'user' => new UserResource(($user->fresh(User::eagerLodable()))),
        ]);
    }