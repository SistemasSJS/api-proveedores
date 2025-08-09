<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportHistory\ImportHistoryIndexRequest;
use App\Http\Requests\ImportHistory\ImportHistoryStoreRequest;
use App\Http\Resources\ImportHistoryResource;
use App\Http\Resources\ImportHistoryCollection;
use App\Models\ImportAudit;
use App\Models\Proveedor;
use App\Services\ImportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ImportHistoryController extends Controller
{
    use ApiResponse;

    protected ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Listar historial de importaciones
     */
    public function index(ImportHistoryIndexRequest $request, Proveedor $proveedor)
    {
        $filters = $request->getFilters();
        $paginator = ImportAudit::query()
            ->where('proveedor_id', $proveedor->id)
            ->filter($filters)
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('order', 'desc'))
            ->paginate($request->input('per_page', 15));

        return new ImportHistoryCollection($paginator);
    }

    /**
     * Mostrar detalle de una importación específica
     */
    public function show(Request $request, Proveedor $proveedor, ImportAudit $importHistory)
    {
        // Verificar que la importación pertenezca al proveedor
        if ($importHistory->proveedor_id !== $proveedor->id) {
            return $this->error('La importación no pertenece a este proveedor.', 403);
        }

        return $this->success(new ImportHistoryResource($importHistory));
    }

    /**
     * Crear nueva entrada de importación
     */
    public function store(ImportHistoryStoreRequest $request, Proveedor $proveedor)
    {
        $data = $request->validated();
        $data['proveedor_id'] = $proveedor->id;

        $importAudit = ImportAudit::create($data);

        return $this->success(
            new ImportHistoryResource($importAudit),
            'Registro de importación creado correctamente.'
        );
    }

    /**
     * Actualizar una entrada de importación
     */
    public function update(ImportHistoryStoreRequest $request, Proveedor $proveedor, ImportAudit $importHistory)
    {
        // Verificar que la importación pertenezca al proveedor
        if ($importHistory->proveedor_id !== $proveedor->id) {
            return $this->error('La importación no pertenece a este proveedor.', 403);
        }

        $importHistory->update($request->validated());

        return $this->success(
            new ImportHistoryResource($importHistory->fresh()),
            'Registro de importación actualizado correctamente.'
        );
    }

    /**
     * Eliminar una entrada de importación
     */
    public function destroy(Request $request, Proveedor $proveedor, ImportAudit $importHistory)
    {
        // Verificar que la importación pertenezca al proveedor
        if ($importHistory->proveedor_id !== $proveedor->id) {
            return $this->error('La importación no pertenece a este proveedor.', 403);
        }

        $importHistory->delete();

        return $this->success(null, 'Registro de importación eliminado correctamente.');
    }

    /**
     * Ejecutar importación de productos
     */
    public function import(Request $request, Proveedor $proveedor)
    {
        // Delegar al servicio de importación
        return $this->importService->processImport($request, $proveedor);
    }
}
