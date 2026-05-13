<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presupuesto\StorePresupuestoAnexoRequest;
use App\Http\Requests\Presupuesto\UpdatePresupuestoAnexoRequest;
use App\Http\Resources\Presupuesto\PresupuestoAnexoResource;
use App\Models\Presupuesto;
use App\Models\PresupuestoAnexo;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProveedorPresupuestoAnexoController extends Controller
{
    public function index(Request $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        $access = $this->validateAccess($request, $proveedor, $presupuesto);
        if ($access !== null) {
            return $access;
        }

        $anexos = $presupuesto->anexos()
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return $this->success(PresupuestoAnexoResource::collection($anexos), 'Operación exitosa.');
    }

    public function store(
        StorePresupuestoAnexoRequest $request,
        Proveedor $proveedor,
        Presupuesto $presupuesto
    ): JsonResponse {
        $access = $this->validateAccess($request, $proveedor, $presupuesto);
        if ($access !== null) {
            return $access;
        }

        try {
            $validated = $request->validated();

            $anexo = PresupuestoAnexo::create([
                'presupuesto_id' => (int) $presupuesto->id,
                'titulo' => $validated['titulo'],
                'descripcion' => $validated['descripcion'] ?? null,
                'precio' => $validated['precio'] ?? null,
                'orden' => isset($validated['orden'])
                    ? (int) $validated['orden']
                    : ((int) $presupuesto->anexos()->max('orden') + 1),
                'archivo_path' => $this->guardarImagenBase64(
                    $proveedor,
                    $presupuesto,
                    $validated['archivo_base64']
                ),
            ])->fresh(PresupuestoAnexo::eagerLodable());

            return $this->success(
                new PresupuestoAnexoResource($anexo),
                'Anexo creado correctamente.',
                201
            );
        } catch (Throwable $e) {
            return $this->error('No fue posible crear el anexo.', [$e->getMessage()], 500);
        }
    }

    public function show(
        Request $request,
        Proveedor $proveedor,
        Presupuesto $presupuesto,
        PresupuestoAnexo $anexo
    ): JsonResponse {
        $access = $this->validateAccess($request, $proveedor, $presupuesto, $anexo);
        if ($access !== null) {
            return $access;
        }

        return $this->success(new PresupuestoAnexoResource($anexo), 'Operación exitosa.');
    }

    public function update(
        UpdatePresupuestoAnexoRequest $request,
        Proveedor $proveedor,
        Presupuesto $presupuesto,
        PresupuestoAnexo $anexo
    ): JsonResponse {
        $access = $this->validateAccess($request, $proveedor, $presupuesto, $anexo);
        if ($access !== null) {
            return $access;
        }

        try {
            $validated = $request->validated();
            $payload = [
                'titulo' => $validated['titulo'],
                'descripcion' => $validated['descripcion'] ?? null,
                'precio' => $validated['precio'] ?? null,
                'orden' => isset($validated['orden']) ? (int) $validated['orden'] : (int) $anexo->orden,
            ];

            if (array_key_exists('archivo_base64', $validated) && ! empty($validated['archivo_base64'])) {
                if ($anexo->archivo_path && Storage::disk('public')->exists($anexo->archivo_path)) {
                    Storage::disk('public')->delete($anexo->archivo_path);
                }
                $payload['archivo_path'] = $this->guardarImagenBase64(
                    $proveedor,
                    $presupuesto,
                    $validated['archivo_base64']
                );
            }

            $anexo->update($payload);

            return $this->success(
                new PresupuestoAnexoResource($anexo->fresh(PresupuestoAnexo::eagerLodable())),
                'Anexo actualizado correctamente.'
            );
        } catch (Throwable $e) {
            return $this->error('No fue posible actualizar el anexo.', [$e->getMessage()], 500);
        }
    }

    public function destroy(
        Request $request,
        Proveedor $proveedor,
        Presupuesto $presupuesto,
        PresupuestoAnexo $anexo
    ): JsonResponse {
        $access = $this->validateAccess($request, $proveedor, $presupuesto, $anexo);
        if ($access !== null) {
            return $access;
        }

        try {
            if ($anexo->archivo_path && Storage::disk('public')->exists($anexo->archivo_path)) {
                Storage::disk('public')->delete($anexo->archivo_path);
            }
            $anexo->delete();

            return $this->success(null, 'Anexo eliminado correctamente.');
        } catch (Throwable $e) {
            return $this->error('No fue posible eliminar el anexo.', [$e->getMessage()], 500);
        }
    }

    private function validateAccess(
        Request $request,
        Proveedor $proveedor,
        Presupuesto $presupuesto,
        ?PresupuestoAnexo $anexo = null
    ): ?JsonResponse {
        $user = $request->user();

        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        if ((int) $presupuesto->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El presupuesto no pertenece al proveedor indicado.', null, 403);
        }

        if ($anexo && (int) $anexo->presupuesto_id !== (int) $presupuesto->id) {
            return $this->error('El anexo no pertenece al presupuesto indicado.', null, 403);
        }

        return null;
    }

    private function guardarImagenBase64(Proveedor $proveedor, Presupuesto $presupuesto, string $dataUri): string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/i', $dataUri, $matches)) {
            throw new \InvalidArgumentException('La imagen del anexo no es válida.');
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false) {
            throw new \InvalidArgumentException('La imagen del anexo no es válida.');
        }

        $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $path = sprintf(
            'proveedores/%d/presupuestos/%d/anexos/%s.%s',
            (int) $proveedor->id,
            (int) $presupuesto->id,
            Str::uuid()->toString(),
            $extension
        );

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
