<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    // Mostrar las sucursales de un proveedor
    public function index($proveedorId)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        return response()->json($proveedor->sucursales);
    }

    // Crear una nueva sucursal
    public function store(Request $request, $proveedorId)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);

        // Validación de la sucursal
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
        ]);

        // Crear la sucursal
        $sucursal = $proveedor->sucursales()->create($request->all());

        return response()->json($sucursal, 201);
    }

    // Mostrar los detalles de una sucursal
    public function show($proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);
        return response()->json($sucursal);
    }

    // Actualizar una sucursal
    public function update(Request $request, $proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);

        $sucursal->update($request->all());

        return response()->json(['message' => 'Sucursal actualizada correctamente']);
    }

    // Eliminar una sucursal
    public function destroy($proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);

        $sucursal->delete();

        return response()->json(['message' => 'Sucursal eliminada correctamente']);
    }
}
