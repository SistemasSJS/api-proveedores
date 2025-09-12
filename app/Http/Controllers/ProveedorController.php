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
use App\Http\Requests\Proveedor\ProveedorUpdateConstanciaFiscalRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\Admin\AdminProveedorAcordeonResource;
use Symfony\Component\HttpFoundation\Response;

class ProveedorController extends Controller
{
    /**
     * Actualiza el logo del proveedor principal del usuario autenticado.
     *
     * - Elimina el logo anterior del proveedor (si existe).
     * - Elimina la foto de perfil anterior del usuario (si existe).
     * - Guarda y asigna el nuevo logo.
     *
     * @param  ProveedorUpdateLogoRequest  $request
     * @param  Proveedor  $proveedor
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws ResourceNotFoundException Si el proveedor no existe.
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

        $proveedor->update(['logo' => $path]);

        return $this->success([
            'proveedor' => new ProveedorResource(($proveedor->fresh(Proveedor::eagerLodable()))),
            'user' => new UserResource(($user->fresh(User::eagerLodable()))),
        ]);
    }

    /**
     * Obtiene el proveedor principal asociado a un usuario por ID.
     *
     * @param  Request  $request
     * @param  int  $id  ID del usuario
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws ResourceNotFoundException Si no se encuentra el usuario o proveedor.
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
     * Lista los proveedores con filtros, ordenamiento y paginación.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
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
     * Muestra los datos de un proveedor específico.
     *
     * @param  Request  $request
     * @param  Proveedor  $proveedor
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Proveedor $proveedor)
    {
        return $this->success(new ProveedorResource($proveedor));
    }

    /**
     * Actualiza la información de un proveedor.
     *
     * @param  ProveedorUpdateRequest  $request
     * @param  Proveedor  $proveedor
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ProveedorUpdateRequest $request, Proveedor $proveedor)
    {
        $validated = $request->validated();
        $proveedor->update($validated);
        $proveedor = $proveedor->fresh(Proveedor::eagerLodable());
        return $this->success(new ProveedorResource($proveedor), 'Proveedor actualizado con éxito.', 200);
    }

    /**
     * Marca un proveedor como baja (eliminación lógica).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws ResourceNotFoundException Si no se encuentra el proveedor.
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

    /**
     * Obtiene los proveedores con sus categorías raíz, subcategorías y conteo de productos.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function proveedoresConCategoriasConSubcatCountProductos(Request $request)
    {
        $proveedores = Proveedor::with([
            'categorias' => function ($query) {
                $query->whereNull('parent_id') // solo categorías raíz
                    ->with([
                        'children' => function ($subquery) {
                            $subquery->withCount('productos');
                        }
                    ])
                    ->withCount('productos');
            }
        ])
            ->withCount('productos') // total de productos por proveedor
            ->get();

        return $this->success(
            AdminProveedorAcordeonResource::collection($proveedores),
            "Listado de proveedores con sus categorías, subcategorías y contador de productos."
        );
    }

    /**
     * Subir o actualizar la constancia fiscal del proveedor.
     */
    public function updateConstanciaFiscal(
        ProveedorUpdateConstanciaFiscalRequest $request,
        Proveedor $proveedor
    ) {
        $user = $request->user();

        // Verificar acceso
        if ($user->proveedorPrincipal()?->id !== $proveedor->id) {
            return response()->json([
                'message' => 'No tienes permisos para subir este documento.'
            ], Response::HTTP_FORBIDDEN);
        }

        $file = $request->file('constancia_fiscal');

        // Borrar constancia anterior si existe
        if ($proveedor->constancia_fiscal && Storage::disk('public')->exists($proveedor->constancia_fiscal)) {
            Storage::disk('public')->delete($proveedor->constancia_fiscal);
        }

        // Guardar nueva constancia
        $filename = 'constancia_' . $proveedor->id . '_' . time() . '.pdf';
        $path = $file->storeAs("uploads/constancias", $filename, 'public');

        $proveedor->update(['constancia_fiscal' => $path]);

        return $this->success([
            'proveedor' => new ProveedorResource($proveedor->fresh()),
            'message'   => 'Constancia fiscal actualizada con éxito.'
        ], 200);
    }

    /**
     * Vista previa o descarga de la constancia fiscal.
     */
    public function previewConstanciaFiscal(Request $request, Proveedor $proveedor)
    {
        $user = $request->user();

        // Validar acceso
        if ($user->proveedorPrincipal()?->id !== $proveedor->id) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a este documento.'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$proveedor->constancia_fiscal || !Storage::disk('public')->exists($proveedor->constancia_fiscal)) {
            return response()->json([
                'message' => 'La constancia fiscal no está disponible.'
            ], Response::HTTP_NOT_FOUND);
        }

        $path = Storage::disk('public')->path($proveedor->constancia_fiscal);

        // Mostrar inline en navegador (preview)
        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="constancia_fiscal.pdf"'
        ]);
    }
}
