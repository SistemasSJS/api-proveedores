<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Proveedor;
use App\Http\Resources\ProveedorResource;
use App\Http\Requests\Proveedor\ProveedorUpdateRequest;
use App\Http\Requests\Proveedor\ProveedorUpdateLogoRequest;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Resources\UserResource;

/**
 * @OA\Tag(
 *     name="Proveedores",
 *     description="Endpoints para la gestión de proveedores"
 * )
 */
class ProveedorController extends Controller
{

    /**
     * @OA\Put(
     *     path="/api/proveedores/logo",
     *     tags={"Proveedores"},
     *     summary="Actualizar logo del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ProveedorUpdateLogoRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logo actualizado"
     *     )
     * )
     */
    public function updateLogo(ProveedorUpdateLogoRequest $request, Proveedor $proveedor)
    {
        $user = $request->user();
        $proveedor = $user->proveedorPrincipal();
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        if ($proveedor->logo !== null) {
            $rutaAnterior = str_replace(asset('storage') . '/', '', $proveedor->logo);
            Storage::disk('public')->delete($rutaAnterior);
        }

        if ($user->foto_perfil_url !== null) {
            $rutaAnterior = str_replace(asset('storage') . '/', '', $user->foto_perfil_url);
            Storage::disk('public')->delete($rutaAnterior);
        }

        $file = $request->file('logo');
        $filename = 'logo_' . $proveedor->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("uploads", $filename, 'public');
        // $url = asset("storage/{$path}");
        $proveedor->update(['logo' => $path]);
        $user->update(['foto_perfil_url' => $path]);

        return $this->success([
            'proveedor' => new ProveedorResource(($proveedor->fresh(Proveedor::eagerLodable()))),
            'user' => new UserResource(($user->fresh(User::eagerLodable()))),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/user/{id}",
     *     tags={"Proveedores"},
     *     summary="Obtener proveedor por ID de usuario",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
    public function getProveedorByUserId(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            throw new ResourceNotFoundException("Usuario no encontrado.");
        }
        $proveedor = $user->proveedorPrincipal();
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }
        return $this->success(new ProveedorResource($proveedor->load(Proveedor::eagerLodable())));
    }



    /**
     * @OA\Get(
     *     path="/api/proveedores",
     *     tags={"Proveedores"},
     *     summary="Listar todos los proveedores",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de proveedores"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(Proveedor::getFilters());
        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = Proveedor::with(Proveedor::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);
        $data = ProveedorResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{id}",
     *     tags={"Proveedores"},
     *     summary="Obtener un proveedor por ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
    public function show(Request $request, Proveedor $proveedor)
    {
        // $proveedor = Proveedor::with(Proveedor::eagerLodable())->find($id);
        // if (!$proveedor) {
        //     throw new ResourceNotFoundException("Proveedor no encontrado.");
        // }

        return $this->success(new ProveedorResource($proveedor));
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{id}",
     *     tags={"Proveedores"},
     *     summary="Actualizar proveedor existente",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProveedorUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor actualizado"
     *     )
     * )
     */
    public function update(ProveedorUpdateRequest $request, Proveedor $proveedor)
    {
        $validated = $request->validated();
        $proveedor->update($validated);
        $proveedor = $proveedor->fresh(Proveedor::eagerLodable());
        return $this->success(new ProveedorResource($proveedor), 'Proveedor actualizado con éxito.', 200);
    }



    /**
     * @OA\Delete(
     *     path="/api/proveedores/{id}",
     *     tags={"Proveedores"},
     *     summary="Eliminar un proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Proveedor eliminado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }
        $proveedor->update([['estatus' => 'baja']]);
        return $this->success(null, 204);
    }

    // public function test(Request $request)
    // {
    //     $user = User::findOrFail(1);
    //     $proveedor = Proveedor::findOrFail(1);
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return $this->success([
    //         'user' => new UserAuthenticateResource($user->load(User::eagerLodable())),
    //         'proveedor' => $proveedor->load(Proveedor::eagerLodable()),
    //         'token' => $token,
    //     ], 'Registro completado', 201);
    // }
}
