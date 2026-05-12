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
            $archivoMeta = $this->extractArchivoMetadata($validated['archivo_base64']);

            $anexo = PresupuestoAnexo::create([
                'presupuesto_id' => (int) $presupuesto->id,
                'titulo' => $validated['titulo'],
                'descripcion' => $validated['descripcion'] ?? null,
                'precio' => $validated['precio'] ?? null,
                'orden' => isset($validated['orden'])
                    ? (int) $validated['orden']
                    : ((int) $presupuesto->anexos()->max('orden') + 1),
                'archivo_path' => $validated['archivo_base64'],
                'archivo_width' => $archivoMeta['width'],
                'archivo_height' => $archivoMeta['height'],
                'archivo_aspect_ratio' => $archivoMeta['aspect_ratio'],
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
                $archivoMeta = $this->extractArchivoMetadata($validated['archivo_base64']);
                $payload['archivo_path'] = $validated['archivo_base64'];
                $payload['archivo_width'] = $archivoMeta['width'];
                $payload['archivo_height'] = $archivoMeta['height'];
                $payload['archivo_aspect_ratio'] = $archivoMeta['aspect_ratio'];
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

    /**
     * @return array{width:int|null,height:int|null,aspect_ratio:float|null}
     */
    private function extractArchivoMetadata(string $dataUri): array
    {
        $matches = [];
        if (! preg_match('/^data:image\/(?:jpeg|jpg|png|webp);base64,(.+)$/', $dataUri, $matches)) {
            return [
                'width' => null,
                'height' => null,
                'aspect_ratio' => null,
            ];
        }

        $binary = base64_decode($matches[1], true);
        if ($binary === false) {
            return [
                'width' => null,
                'height' => null,
                'aspect_ratio' => null,
            ];
        }

        $imageInfo = @getimagesizefromstring($binary);
        if (! is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return [
                'width' => null,
                'height' => null,
                'aspect_ratio' => null,
            ];
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];

        return [
            'width' => $width,
            'height' => $height,
            'aspect_ratio' => $height > 0 ? round($width / $height, 6) : null,
        ];
    }
}
