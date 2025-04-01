<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']); // Asegura que el usuario esté autenticado con Sanctum
    }

    // Mostrar el perfil del proveedor autenticado
    public function show($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        // Verifica que el proveedor pertenece al usuario autenticado
        if ($proveedor->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return response()->json($proveedor);
    }

    // Actualizar el perfil del proveedor
    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        // Verifica que el proveedor pertenece al usuario autenticado
        if ($proveedor->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Validar los datos antes de actualizar
        $request->validate([
            'razon_social' => [
                'required',
                'string',
                'max:255',
                Rule::unique('proveedores')->ignore($proveedor->id),
            ],
            'nombre_comercial' => [
                'required',
                'string',
                'max:255',
                Rule::unique('proveedores')->ignore($proveedor->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('proveedores')->ignore($proveedor->id),
            ],
        ]);

        // Actualizar proveedor
        $proveedor->update($request->all());

        return response()->json(['message' => 'Proveedor actualizado correctamente', 'proveedor' => $proveedor]);
    }
}
