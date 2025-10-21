<?php

namespace App\Http\Controllers;

use App\Models\ProductoImagen;
use Illuminate\Http\Request;

class ProductoImagenController extends Controller
{
    public function index(Request $request)
    {
        // Obtener los filtros de la solicitud
        $filters = $request->only(['producto_id']);

        // Aplicar los filtros usando el scopeFilter
        $imagenes = ProductoImagen::filter($filters)->paginate(10);

        return $this->paginated($imagenes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|string|url',
            'producto_id' => 'required|integer|exists:productos,id',
        ]);

        $imagen = ProductoImagen::create([
            'url' => $request->url,
            'producto_id' => $request->producto_id,
        ]);

        return $this->success($imagen, 'Imagen almacenada correctamente.', 201);
    }

    public function show($id)
    {
        $imagen = ProductoImagen::findOrFail($id);

        return $this->success($imagen, 'Imagen encontrada.', 201);
    }

    public function update(Request $request, $id)
    {
        $imagen = ProductoImagen::findOrFail($id);

        $request->validate([
            'url' => 'required|string|url',
            'producto_id' => 'required|integer|exists:productos,id',
        ]);

        $imagen->update([
            'url' => $request->url,
            'producto_id' => $request->producto_id,
        ]);

        return $this->success($imagen, 'Imagen actualizada correctamente.', 201);
    }

    public function destroy($id)
    {
        $imagen = ProductoImagen::findOrFail($id);
        $imagen->delete();

        return $this->success(null, 204);
    }
}
