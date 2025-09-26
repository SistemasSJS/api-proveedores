<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConstrucc;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmpresaConstruccController extends Controller
{
    /**
     * Buscar empresas de construcción
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 20);
        
        $query = EmpresaConstrucc::activo();
        
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
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');
        
        $query = EmpresaConstrucc::activo();
        
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
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
        
        $empresa = EmpresaConstrucc::create($request->all());
        
        return $this->success($empresa, 'Empresa de construcción creada correctamente', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        return $this->success($empresaConstrucc);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
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
     * Remove the specified resource from storage.
     */
    public function destroy(EmpresaConstrucc $empresaConstrucc): JsonResponse
    {
        $empresaConstrucc->update(['activo' => false]);
        
        return $this->success(null, 'Empresa de construcción desactivada correctamente');
    }
}
