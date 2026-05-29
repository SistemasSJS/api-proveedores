<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigEmisorReceptorPresupuesto\StoreConfigEmisorReceptorPresupuestoRequest;
use App\Http\Requests\ConfigEmisorReceptorPresupuesto\UpdateConfigEmisorReceptorPresupuestoRequest;
use App\Http\Resources\Presupuesto\PresupuestoConfigEmisorReceptorResource;
use App\Models\ConfigEmisorReceptorPresupuesto;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProveedorPresupuestoConfigController extends Controller
{
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        $filters = $request->only(ConfigEmisorReceptorPresupuesto::getFilters());
        $filters['proveedor_id'] = $proveedor->id;

        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 1000);

        $query = ConfigEmisorReceptorPresupuesto::query()
            ->with(ConfigEmisorReceptorPresupuesto::eagerLodable())
            ->filter($filters)
            ->whereIn('estado', [ConfigEmisorReceptorPresupuesto::ESTADO_ACTIVO, ConfigEmisorReceptorPresupuesto::ESTADO_DEFAULT])
            ->orderBy($sortBy, $order);

        $originalPaginator = $query->paginate($perPage);
        $data = PresupuestoConfigEmisorReceptorResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    public function store(StoreConfigEmisorReceptorPresupuestoRequest $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        $data = $request->validated();
        $data['proveedor_id'] = (int) $proveedor->id;

        unset($data['foto_perfil'], $data['file_firma']);

        if ($request->hasFile('foto_perfil')) {
            $data['foto_perfil'] = $request->file('foto_perfil')->store(
                'presupuestos/config-tarjetas/fotos',
                'public'
            );
        }

        if ($request->hasFile('file_firma')) {
            $data['file_firma'] = $request->file('file_firma')->store(
                'presupuestos/config-tarjetas/firmas',
                'public'
            );
        }

        $config = ConfigEmisorReceptorPresupuesto::create($data)
            ->fresh(ConfigEmisorReceptorPresupuesto::eagerLodable());

        return $this->success(
            new PresupuestoConfigEmisorReceptorResource($config),
            'Operación exitosa.'
        );
    }

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

    public function update(UpdateConfigEmisorReceptorPresupuestoRequest $request, Proveedor $proveedor, ConfigEmisorReceptorPresupuesto $config): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        if ((int) $config->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El registro no pertenece al proveedor indicado.', null, 403);
        }

        $data = $request->validated();
        $data['proveedor_id'] = (int) $proveedor->id;

        unset($data['foto_perfil'], $data['file_firma']);

        if ($request->hasFile('foto_perfil')) {
            if ($config->foto_perfil && Storage::disk('public')->exists($config->foto_perfil)) {
                Storage::disk('public')->delete($config->foto_perfil);
            }
            $data['foto_perfil'] = $request->file('foto_perfil')->store(
                'presupuestos/config-tarjetas/fotos',
                'public'
            );
        }

        if ($request->hasFile('file_firma')) {
            if ($config->file_firma && Storage::disk('public')->exists($config->file_firma)) {
                Storage::disk('public')->delete($config->file_firma);
            }
            $data['file_firma'] = $request->file('file_firma')->store(
                'presupuestos/config-tarjetas/firmas',
                'public'
            );
        }

        $config->update($data);
        $config = $config->fresh(ConfigEmisorReceptorPresupuesto::eagerLodable());

        return $this->success(
            new PresupuestoConfigEmisorReceptorResource($config),
            'Operación exitosa.'
        );
    }

    public function destroy(Request $request, Proveedor $proveedor, ConfigEmisorReceptorPresupuesto $config): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        if ((int) $config->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El registro no pertenece al proveedor indicado.', null, 403);
        }

        $config->update(['estado' => ConfigEmisorReceptorPresupuesto::ESTADO_INACTIVO]);

        return $this->success(null, 'Configuración de emisor/receptor de presupuestos desactivada correctamente.');
    }
}
