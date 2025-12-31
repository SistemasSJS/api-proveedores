<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConstrucc;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaConstruccController extends Controller
{
    public function all(Request $request, Proveedor $proveedor): JsonResponse
    {
        // Filtros dinámicos según los definidos en el modelo
        $filters = $request->only(EmpresaConstrucc::getFilters());

        // Búsqueda por término
        $search = $request->input('search', '');

        // Columnas para ordenar, con valores por defecto
        $sortBy = $request->input('sort_by', 'nombre');
        $order = $request->input('order', 'asc');

        // Consulta base: empresas asociadas al proveedor
        $query = $proveedor->empresasConstrucc()
            ->filter($filters)
            ->search($search) // usa scopeSearch en el modelo
            ->orderBy($sortBy, $order);

        // Obtener todas las empresas (sin paginación por defecto)
        $empresas = $query->get()
            ->unique('id') // eliminar duplicados
            ->values();    // reindexar array

        return $this->success(
            $empresas,
            'Listado completo de empresas asociadas al proveedor.'
        );
    }


    /**
     * Buscar empresas de construcción asociadas a un proveedor
     */
    public function search(Request $request, Proveedor $proveedor): JsonResponse
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 20);

        $query = $proveedor->empresasConstrucc();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('razon_social', 'LIKE', "%{$search}%")
                    ->orWhere('rfc', 'LIKE', "%{$search}%");
            });
        }

        $empresas = $query->limit($limit)->get();

        return $this->success($empresas);
    }

    /**
     * Listado paginado de empresas por proveedor
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        // Filtros dinámicos según los definidos en el modelo
        $filters = $request->only(EmpresaConstrucc::getFilters());

        // Columnas para ordenar, con valores por defecto
        $sortBy = $request->input('sort_by', 'nombre');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 15);

        // Consulta base: empresas asociadas al proveedor
        $query = $proveedor->empresasConstrucc()->filter($filters);

        // Aplicar ordenamiento dinámico
        $query->orderBy($sortBy, $order);

        // Paginación
        $empresas = $query->paginate($perPage);

        // Devolver paginación en el formato esperado
        return $this->paginated($empresas);
    }

    // public function index(Request $request, Proveedor $proveedor): JsonResponse
    // {
    //     $perPage = $request->input('per_page', 15);
    //     $search = $request->input('search', '');

    //     $query = $proveedor->empresasConstrucc();

    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('nombre', 'LIKE', "%{$search}%")
    //                 ->orWhere('razon_social', 'LIKE', "%{$search}%")
    //                 ->orWhere('rfc', 'LIKE', "%{$search}%");
    //         });
    //     }

    //     $empresas = $query->orderBy('nombre')->paginate($perPage);

    //     return $this->paginated($empresas);
    // }

    /**
     * Crear empresa y asociar a proveedor
     */
    public function store(Request $request, Proveedor $proveedor): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'rfc' => 'nullable|string|max:13',
            'razon_social' => 'nullable|string|max:255',
            'direccion' => 'nullable|string',
            'ciudad' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'representante_legal' => 'nullable|string|max:255',
        ]);

        $empresa = EmpresaConstrucc::create($request->all());

        // Asociar con proveedor
        $proveedor->empresasConstrucc($empresa->id);

        return $this->success($empresa, 'Empresa de construcción creada y asociada correctamente', 201);
    }

    /**
     * Mostrar detalle de empresa asociada a proveedor
     */
    public function show(Proveedor $proveedor, EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        if (! $proveedor->empresasConstrucc->contains('id', $empresaConstrucc->id)) {
            return $this->error('La empresa no pertenece a este proveedor', 403);
        }

        return $this->success($empresaConstrucc);
    }

    /**
     * Obtener usuarios asociados a una empresa en la tabla pivot
     */
    public function usuarios(Proveedor $proveedor, EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        if (! $proveedor->empresasConstrucc->contains('id', $empresaConstrucc->id)) {
            return $this->error('La empresa no pertenece a este proveedor', 403);
        }

        // Obtener todos los registros de la pivot table para esta empresa y proveedor
        $usuarios = \DB::table('empresa_construcc_proveedor')
            ->where('empresa_construcc_id', $empresaConstrucc->id)
            ->where('proveedor_id', $proveedor->id)
            ->select('usuario_construcc_id', 'usuario_construcc_nombre')
            ->distinct()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->usuario_construcc_id,
                    'nombre' => $item->usuario_construcc_nombre
                ];
            });

        return $this->success($usuarios, 'Usuarios de la empresa obtenidos correctamente');
    }

    /**
     * Actualizar empresa
     */
    public function update(Request $request, Proveedor $proveedor, EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        if (! $proveedor->empresasConstrucc('empresa_construcc_id', $empresaConstrucc->id)->exists()) {
            return $this->error('La empresa no pertenece a este proveedor', 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'rfc' => 'nullable|string|max:13',
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
     * Desasociar empresa del proveedor (soft delete)
     */
    public function destroy(Proveedor $proveedor, EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        if (! $proveedor->empresasConstrucc('empresa_construcc_id', $empresaConstrucc->id)->exists()) {
            return $this->error('La empresa no pertenece a este proveedor', 403);
        }

        // Opcional: desactivar empresa
        $empresaConstrucc->update(['activo' => false]);

        // Desasociar del proveedor
        $proveedor->empresasConstrucc($empresaConstrucc->id);

        return $this->success(null, 'Empresa de construcción desactivada y desasociada correctamente');
    }
}
