<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConstrucc;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmpresaConstruccController extends Controller
{
    /**
     * Buscar empresas de construcción
     */
    public function search(Request $request, Proveedor $proveedor): JsonResponse
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 20);

        // Solo empresas asociadas al proveedor
        $query = $proveedor->empresas()->activo();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('razon_social', 'LIKE', "%{$search}%")
                    ->orWhere('rfc', 'LIKE', "%{$search}%");
            });
        }

        $empresas = $query->limit($limit)->get();

        return $this->success(
            $empresas->map(function ($empresa) {
                return [
                    'id' => $empresa->id,
                    'nombre' => $empresa->nombre,
                    'rfc' => $empresa->rfc,
                    'razon_social' => $empresa->razon_social,
                    'representante_legal' => $empresa->representante_legal,
                    'direccion' => $empresa->direccion,
                    'ciudad' => $empresa->ciudad,
                    'estado' => $empresa->estado,
                    'telefono' => $empresa->telefono,
                    'email' => $empresa->email,
                ];
            })
        );
    }

    /**
     * Listado con paginación
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $query = $proveedor->empresas()->activo();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('razon_social', 'LIKE', "%{$search}%")
                    ->orWhere('rfc', 'LIKE', "%{$search}%");
            });
        }

        $empresas = $query->orderBy('nombre')->paginate($perPage);

        return $this->paginated($empresas);
    }

    /**
     * Crear empresa para un proveedor
     */
    public function store(Request $request, Proveedor $proveedor): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'rfc' => 'required|string|max:13|unique:empresa_construcc,rfc',
            'razon_social' => 'nullable|string|max:255',
            'direccion' => 'nullable|string',
            'ciudad' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'representante_legal' => 'nullable|string|max:255',
        ]);

        $empresa = $proveedor->empresas()->create($request->all());

        return $this->success($empresa, 'Empresa de construcción creada correctamente', 201);
    }

    /**
     * Mostrar una empresa de un proveedor
     */
    public function show(Proveedor $proveedor, EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        // Opcional: verificar que la empresa pertenece al proveedor
        if ($empresaConstrucc->proveedor_id !== $proveedor->id) {
            return $this->error('La empresa no pertenece a este proveedor', 403);
        }

        return $this->success($empresaConstrucc);
    }

    /**
     * Actualizar empresa de un proveedor
     */
    public function update(Request $request, Proveedor $proveedor, EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        if ($empresaConstrucc->proveedor_id !== $proveedor->id) {
            return $this->error('La empresa no pertenece a este proveedor', 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'rfc' => 'required|string|max:13|unique:empresa_construcc,rfc,' . $empresaConstrucc->id,
            'razon_social' => 'nullable|string|max:255',
            'direccion' => 'nullable|string',
            'ciudad' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'representante_legal' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        $empresaConstrucc->update($request->all());

        return $this->success($empresaConstrucc, 'Empresa de construcción actualizada correctamente');
    }

    /**
     * Eliminar (desactivar) empresa de un proveedor
     */
    public function destroy(Proveedor $proveedor, EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        if ($empresaConstrucc->proveedor_id !== $proveedor->id) {
            return $this->error('La empresa no pertenece a este proveedor', 403);
        }

        $empresaConstrucc->update(['activo' => false]);

        return $this->success(null, 'Empresa de construcción desactivada correctamente');
    }
}
