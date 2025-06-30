<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index(Request $request, $proveedorId)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        return $this->success($proveedor->sucursales);
    }

    public function store(Request $request, $proveedorId)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
        ]);

        $sucursal = $proveedor->sucursales()->create($request->all());

        return $this->success($sucursal, 201);
    }

    public function show($proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);
        return $this->success($sucursal);
    }

    public function update(Request $request, $proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);

        $sucursal->update($request->all());

        return $this->success(['message' => 'Sucursal actualizada correctamente']);
    }

    public function destroy($proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);

        $sucursal->delete();

        return $this->success(['message' => 'Sucursal eliminada correctamente']);
    }
}
