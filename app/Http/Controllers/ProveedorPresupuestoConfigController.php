<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigEmisorReceptorPresupuesto\StoreConfigEmisorReceptorPresupuestoRequest;
use App\Http\Requests\ConfigEmisorReceptorPresupuesto\UpdateConfigEmisorReceptorPresupuestoRequest;
use App\Http\Requests\ConfigEmisorReceptorPresupuesto\UpdatePresupuestoConfigEmisorReceptorRequest;
use App\Http\Resources\Presupuesto\PresupuestoConfigEmisorReceptorCollection;
use App\Http\Resources\Presupuesto\PresupuestoConfigEmisorReceptorResource;
use App\Models\ConfigEmisorReceptorPresupuesto;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProveedorPresupuestoConfigController extends Controller
{
    /**
     * Listado de configuraciones de emisor/receptor de presupuestos
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @return JsonResponse
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        $filters = $request->only(ConfigEmisorReceptorPresupuesto::getFilters());
        $filters['proveedor_id'] = $proveedor->id;
        $sortBy = $request->input('sort_by', 'id');
        $order = $request->input('order', 'desc');

        $query = ConfigEmisorReceptorPresupuesto::query()
            ->with(ConfigEmisorReceptorPresupuesto::eagerLodable())
            ->filter($filters)
            ->whereIn('estado', [ConfigEmisorReceptorPresupuesto::ESTADO_ACTIVO, ConfigEmisorReceptorPresupuesto::ESTADO_DEFAULT])
            ->orderBy($sortBy, $order);

        return $this->success(
            PresupuestoConfigEmisorReceptorResource::collection($query->get()),
            'Listado de configuraciones de emisor/receptor de presupuestos activas o default.'
        );
    }

    /**
     * Crear una nueva configuración de emisor/receptor de presupuestos
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @return JsonResponse
     */
    public function store(StoreConfigEmisorReceptorPresupuestoRequest $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        $validated = $request->validated();
        $validated['proveedor_id'] = (int) $proveedor->id;

        $config = ConfigEmisorReceptorPresupuesto::create($validated)
            ->fresh(ConfigEmisorReceptorPresupuesto::eagerLodable());

        return $this->success(
            new PresupuestoConfigEmisorReceptorResource($config),
            'Operación exitosa.'
        );
    }

    /**
     * Obtener una configuración de emisor/receptor de presupuestos
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @param ConfigEmisorReceptorPresupuesto $config
     * @return JsonResponse
     */
    public function show(Request $request, Proveedor $proveedor, ConfigEmisorReceptorPresupuesto $config): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        if ((int) $config->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El registro no pertenece al proveedor indicado.', null, 403);
        }

        return $this->success(
            new PresupuestoConfigEmisorReceptorResource($config),
            'Operación exitosa.'
        );
    }

    /**
     * Actualizar una configuración de emisor/receptor de presupuestos
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @param ConfigEmisorReceptorPresupuesto $config
     * @return JsonResponse
     */
    public function update(UpdateConfigEmisorReceptorPresupuestoRequest $request, Proveedor $proveedor, ConfigEmisorReceptorPresupuesto $config): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        if ((int) $config->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El registro no pertenece al proveedor indicado.', null, 403);
        }

        $validated = $request->validated();
        $validated['proveedor_id'] = (int) $proveedor->id;

        $config->update($validated);
        $config->refresh();
        $config = $config->fresh(
            ConfigEmisorReceptorPresupuesto::eagerLodable()
        );

        return $this->success(
            new PresupuestoConfigEmisorReceptorResource($config),
            'Operación exitosa.'
        );
    }

    /**
     * Desactivar una configuración de emisor/receptor de presupuestos
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @param ConfigEmisorReceptorPresupuesto $config
     * @return JsonResponse
     */
    public function destroy(Request $request, Proveedor $proveedor, ConfigEmisorReceptorPresupuesto $config): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        Log::info('config->proveedor_id: ' . json_encode($config->toArray(), JSON_PRETTY_PRINT));
        Log::info('proveedor->id: ' . $proveedor->id);

        if ((int) $config->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El registro no pertenece al proveedor indicado.', null, 403);
        }

        $config->update(['estado' => ConfigEmisorReceptorPresupuesto::ESTADO_INACTIVO]);

        return $this->success(null, 'Configuración de emisor/receptor de presupuestos desactivada correctamente.');
    }
}
