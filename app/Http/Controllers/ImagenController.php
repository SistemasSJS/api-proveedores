<?php

namespace App\Http\Controllers;

use App\Models\ProductoImagen;
use Illuminate\Http\Request;

class ProductoImagenController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/imagenes",
     *     summary="Listar todas las imágenes con filtros opcionales y paginación",
     *     operationId="listarImagenes",
     *     tags={"Imagen"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="producto_id", in="query", description="Filtrar por producto_id", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="page", in="query", description="Número de página", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Listado paginado de imágenes")
     * )
     */
    public function index(Request $request)
    {
        // Obtener los filtros de la solicitud
        $filters = $request->only(['producto_id']);

        // Aplicar los filtros usando el scopeFilter
        $imagenes = ProductoImagen::filter($filters)->paginate(10);

        return $this->paginated($imagenes);
    }

    /**
     * @OA\Post(
     *     path="/api/imagenes",
     *     summary="Crear una imagen",
     *     operationId="crearImagen",
     *     tags={"Imagen"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"url", "producto_id"},
     *             @OA\Property(property="url", type="string", format="url"),
     *             @OA\Property(property="producto_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Imagen creada")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/imagenes/{id}",
     *     summary="Obtener una imagen por ID",
     *     operationId="mostrarImagen",
     *     tags={"Imagen"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Imagen encontrada")
     * )
     */
    public function show($id)
    {
        $imagen = ProductoImagen::findOrFail($id);
        return $this->success($imagen, 'Imagen encontrada.', 201);
    }


    /**
     * @OA\Put(
     *     path="/api/imagenes/{id}",
     *     summary="Actualizar imagen",
     *     operationId="actualizarImagen",
     *     tags={"Imagen"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string", format="url"),
     *             @OA\Property(property="producto_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Imagen actualizada")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/imagenes/{id}",
     *     summary="Eliminar imagen",
     *     operationId="eliminarImagen",
     *     tags={"Imagen"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Imagen eliminada")
     * )
     */
    public function destroy($id)
    {
        $imagen = ProductoImagen::findOrFail($id);
        $imagen->delete();

        return $this->success(null, 204);
    }
}
