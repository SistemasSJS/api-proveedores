<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index()
    {
        return Proveedor::all();
    }

    public function store(Request $request)
    {
        $proveedor = Proveedor::create($request->all());
        return response()->json($proveedor, 201);
    }

    public function show(Proveedor $proveedor)
    {
        return $proveedor;
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $proveedor->update($request->all());
        return response()->json($proveedor);
    }

    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();
        return response()->json(null, 204);
    }
}
