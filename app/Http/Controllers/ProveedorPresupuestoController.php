<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presupuesto\StorePresupuestoRequest;
use App\Http\Requests\Presupuesto\UpdatePresupuestoRequest;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Models\CarteraCliente;
use App\Models\Presupuesto;
use App\Models\PresupuestoConcepto;
use App\Models\Proveedor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controlador de administración para el módulo Presupuesto Básico.
 */
class ProveedorPresupuestoController extends Controller
{
    private bool $logEnabled = true;

    /**
     * Obtiene el siguiente folio de presupuesto para el proveedor autenticado.
     * Este endpoint no consume el consecutivo, solo lo consulta para previsualización.
     */
    public function nextFolio(Request $request): JsonResponse
    {
        $user = $request->user();
        $proveedor = $user?->proveedorPrincipal();

        if (! $user || ! $proveedor) {
            return $this->error('No fue posible resolver el proveedor del usuario autenticado.', null, 422);
        }

        if (! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        return $this->success([
            'folio' => $this->formatearFolioSiguiente($proveedor),
            'proveedor_id' => (int) $proveedor->id,
        ]);
    }

    /**
     * Obtiene el siguiente folio de presupuesto para un proveedor específico.
     * No incrementa el consecutivo en base de datos.
     */
    public function nextFolioByProveedor(Request $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        return $this->success([
            'folio' => $this->formatearFolioSiguiente($proveedor),
            'proveedor_id' => (int) $proveedor->id,
        ]);
    }

    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $filters = $request->only(Presupuesto::getFilters());
        $filters['proveedor_id'] = $proveedor->id;
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = Presupuesto::query()
            ->with(Presupuesto::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = PresupuestoResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    public function store(StorePresupuestoRequest $request, Proveedor $proveedor): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = $request->user();

            if (! $user || ! method_exists($user, 'tieneAccesoAProveedor') || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
            }

            if ((int) $validated['proveedor_id'] !== (int) $proveedor->id) {
                return $this->error('El proveedor del payload no coincide con el proveedor de la ruta.', null, 422);
            }

            if (! empty($validated['empresa_receptora_id']) && ! CarteraCliente::query()
                ->where('proveedor_id', $proveedor->id)
                ->whereKey((int) $validated['empresa_receptora_id'])
                ->exists()) {
                return $this->error('El cliente de cartera no pertenece al proveedor indicado.', null, 422);
            }

            $presupuesto = DB::transaction(function () use ($request, $validated) {
                $payload = collect($validated)->except(['conceptos'])->toArray();
                $payload['user_id'] = $request->user()->id;
                $payload['proveedor_id'] = (int) $validated['proveedor_id'];
                $payload['numero_presupuesto'] = $payload['numero_presupuesto']
                    ?? Presupuesto::generarNumeroPresupuesto((int) $payload['proveedor_id']);
                $payload['con_iva'] = $payload['con_iva'] ?? true;
                $payload['iva_porcentaje'] = $payload['iva_porcentaje'] ?? 16.00;
                $payload = $this->normalizarEmpresaReceptora($payload, (int) $payload['proveedor_id']);

                $presupuesto = Presupuesto::create($payload);

                $this->sincronizarConceptos($presupuesto, $validated['conceptos']);
                $presupuesto->recalcularDesdeConceptos();
                $presupuesto->save();

                return $presupuesto->fresh(Presupuesto::eagerLodable());
            });

            $this->log('Presupuesto creado', ['presupuesto_id' => $presupuesto->id]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto creado correctamente.',
                201
            );
        } catch (Throwable $e) {
            $this->log('Error al crear presupuesto', ['error' => $e->getMessage()]);

            return $this->error('No fue posible crear el presupuesto.', [$e->getMessage()], 500);
        }
    }

    public function show(Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        if ($presupuesto->proveedor_id !== $proveedor->id) {
            return $this->error('Presupuesto no pertenece a este proveedor.', null, 403);
        }

        $presupuesto->load(Presupuesto::eagerLodable());

        return $this->success(new PresupuestoResource($presupuesto));
    }

    public function update(UpdatePresupuestoRequest $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if ($presupuesto->proveedor_id !== $proveedor->id) {
                return $this->error('Presupuesto no pertenece a este proveedor.', null, 403);
            }

            $validated = $request->validated();
            if ((int) $validated['proveedor_id'] !== (int) $proveedor->id) {
                return $this->error('El proveedor del payload no coincide con el proveedor de la ruta.', null, 422);
            }

            if (! empty($validated['empresa_receptora_id']) && ! CarteraCliente::query()
                ->where('proveedor_id', $proveedor->id)
                ->whereKey((int) $validated['empresa_receptora_id'])
                ->exists()) {
                return $this->error('El cliente de cartera no pertenece al proveedor indicado.', null, 422);
            }

            $presupuesto = DB::transaction(function () use ($validated, $presupuesto) {
                $payload = collect($validated)->except(['conceptos'])->toArray();
                $payload['proveedor_id'] = (int) $validated['proveedor_id'];
                $payload['numero_presupuesto'] = $payload['numero_presupuesto'] ?? $presupuesto->numero_presupuesto;
                $payload = $this->normalizarEmpresaReceptora($payload, (int) $payload['proveedor_id']);

                $presupuesto->update($payload);
                $this->sincronizarConceptos($presupuesto, $validated['conceptos']);
                $presupuesto->recalcularDesdeConceptos();
                $presupuesto->save();

                return $presupuesto->fresh(Presupuesto::eagerLodable());
            });

            $this->log('Presupuesto actualizado', ['presupuesto_id' => $presupuesto->id]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto actualizado correctamente.'
            );
        } catch (Throwable $e) {
            $this->log('Error al actualizar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible actualizar el presupuesto.', [$e->getMessage()], 500);
        }
    }

    public function destroy(Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if ($presupuesto->proveedor_id !== $proveedor->id) {
                return $this->error('Presupuesto no pertenece a este proveedor.', null, 403);
            }

            $presupuesto->delete();

            $this->log('Presupuesto eliminado', [
                'presupuesto_id' => $presupuesto->id,
                'numero_presupuesto' => $presupuesto->numero_presupuesto,
            ]);

            return $this->success(null, 'Presupuesto eliminado correctamente.');
        } catch (Throwable $e) {
            $this->log('Error al eliminar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible eliminar el presupuesto.', [$e->getMessage()], 500);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizarEmpresaReceptora(array $payload, int $proveedorId): array
    {
        if (empty($payload['empresa_receptora_id'])) {
            return $payload;
        }

        $cliente = CarteraCliente::query()
            ->where('proveedor_id', $proveedorId)
            ->findOrFail((int) $payload['empresa_receptora_id']);

        $payload['empresa_receptora_nombre'] = $cliente->nombre;
        $payload['empresa_receptora_puesto'] = $cliente->puesto;
        $payload['empresa_receptora_empresa'] = $cliente->empresa;
        $payload['empresa_receptora_telefono'] = $cliente->telefono;
        $payload['empresa_receptora_correo'] = $cliente->correo;

        return $payload;
    }

    /**
     * @param array<int, array<string, mixed>> $conceptos
     */
    private function sincronizarConceptos(Presupuesto $presupuesto, array $conceptos): void
    {
        $presupuesto->conceptos()->delete();

        foreach ($conceptos as $index => $conceptoData) {
            $concepto = new PresupuestoConcepto([
                'numero' => $index + 1,
                'descripcion' => $conceptoData['descripcion'],
                'cantidad' => $conceptoData['cantidad'],
                'unidad' => $conceptoData['unidad'],
                'precio_unitario' => $conceptoData['precio_unitario'],
            ]);
            $concepto->calcularImporte();
            $presupuesto->conceptos()->save($concepto);
        }
    }

    private function log($message, $data = []): void
    {
        if (! $this->logEnabled) {
            return;
        }

        Log::info($message, $data);
    }

    private function formatearFolioSiguiente(Proveedor $proveedor): string
    {
        $consecutivo = (int) ($proveedor->consecutivo_presupuesto_siguiente ?? 1);
        $consecutivo = max($consecutivo, 1);

        return 'PRES-' . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Genera la respuesta PDF usando Laravel DomPDF.
     *
     * @param array<string, mixed> $datosPresupuesto
     */
    private function generarPdfResponse(array $datosPresupuesto, string $numeroPresupuesto): Response
    {
        try {
            // Verificar si GD está disponible antes de generar el PDF
            $gdDisponible = extension_loaded('gd');
            
            if (!$gdDisponible) {
                $this->log('Advertencia: GD no está disponible. Las imágenes PNG/GIF no se mostrarán.', [
                    'numero_presupuesto' => $numeroPresupuesto,
                ]);
            }

            // Generar nombre del archivo
            $filename = "Presupuesto_{$numeroPresupuesto}.pdf";

            // Convertir logos a base64 para incluirlos en el PDF
            // Si GD no está disponible, retornará arrays vacíos y se usarán fallbacks de texto
            $logosBase64 = $this->convertirLogosABase64();

            // Agregar los logos base64 a los datos del presupuesto
            $datosPresupuesto['logos_base64'] = $logosBase64;
            $datosPresupuesto['gd_disponible'] = $gdDisponible; // Información útil para la vista

            // Generar PDF usando el facade PDF de barryvdh/laravel-dompdf
            $pdf = Pdf::loadView('presupuestos.pdf', ['presupuesto' => $datosPresupuesto])
                ->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', false) // Deshabilitar carga remota para evitar timeouts
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('defaultFont', 'DejaVu Sans')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10)
                ->setOption('enable-local-file-access', false) // No necesitamos acceso a archivos locales si usamos base64
                ->setOption('chroot', public_path()); // Establecer directorio raíz para archivos locales

            // Retornar PDF como descarga
            return $pdf->download($filename);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Mensaje más claro si el error es por falta de GD
            if (stripos($errorMessage, 'GD extension') !== false || stripos($errorMessage, 'gd') !== false) {
                $errorMessage = 'La extensión GD de PHP es requerida para generar PDFs con imágenes. Por favor, instala la extensión GD en tu servidor PHP.';
            }

            $this->log('Error al generar PDF', [
                'numero_presupuesto' => $numeroPresupuesto,
                'error' => $e->getMessage(),
                'gd_disponible' => extension_loaded('gd'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible generar el PDF: ' . $errorMessage,
            ], 500);
        }
    }

    /**
     * Convierte los logos de las apps a base64.
     * 
     * IMPORTANTE: DomPDF requiere la extensión GD de PHP para procesar imágenes PNG/GIF.
     * Si GD no está disponible, se retornan arrays vacíos y se usan iconos de texto como fallback.
     * 
     * Solo las imágenes JPEG pueden procesarse sin GD.
     * 
     * @return array<string, string>
     */
    private function convertirLogosABase64(): array
    {
        $logos = [
            'facturapro' => '',
            'constucc' => '',
            'gestionpro' => '',
        ];

        // Verificar si GD está disponible
        // Si no está disponible, DomPDF no podrá procesar PNG/GIF, así que retornamos vacío
        if (!extension_loaded('gd')) {
            $this->log('GD no está disponible - usando fallback de texto para logos', []);
            return $logos;
        }

        $facturaproPath = public_path('assets/logos/logo-facturapro.png');
        $constuccPath = public_path('assets/logos/logo-construcc.png');
        $gestionproPath = public_path('assets/logos/logo-gestionpro.png');

        try {
            if (file_exists($facturaproPath) && is_readable($facturaproPath)) {
                $imageData = @file_get_contents($facturaproPath);
                if ($imageData !== false && !empty($imageData)) {
                    $logos['facturapro'] = 'data:image/png;base64,' . base64_encode($imageData);
                }
            }

            if (file_exists($constuccPath) && is_readable($constuccPath)) {
                $imageData = @file_get_contents($constuccPath);
                if ($imageData !== false && !empty($imageData)) {
                    $logos['constucc'] = 'data:image/png;base64,' . base64_encode($imageData);
                }
            }

            if (file_exists($gestionproPath) && is_readable($gestionproPath)) {
                $imageData = @file_get_contents($gestionproPath);
                if ($imageData !== false && !empty($imageData)) {
                    $logos['gestionpro'] = 'data:image/png;base64,' . base64_encode($imageData);
                }
            }
        } catch (\Exception $e) {
            // Si hay algún error al leer las imágenes, retornar arrays vacíos para usar fallback
            $this->log('Error al convertir logos a base64', [
                'error' => $e->getMessage(),
            ]);
        }

        return $logos;
    }

    /**
     * Convierte el logo del proveedor a base64 si está disponible.
     * 
     * IMPORTANTE: DomPDF requiere GD para procesar PNG/GIF. Si GD no está disponible:
     * - Las imágenes JPEG funcionarán sin GD
     * - Las imágenes PNG/GIF retornarán string vacío (se usará fallback de texto)
     *
     * @param \App\Models\Proveedor|null $proveedor
     * @return string
     */
    private function convertirLogoProveedorABase64($proveedor): string
    {
        if (!$proveedor || empty($proveedor->logo)) {
            return '';
        }

        try {
            $logoPath = null;
            
            // Si el logo es una URL completa, no podemos convertirla sin GD
            if (filter_var($proveedor->logo, FILTER_VALIDATE_URL)) {
                return '';
            }

            // Si es una ruta relativa, construir la ruta completa
            if (strpos($proveedor->logo, '/') === 0) {
                $logoPath = public_path($proveedor->logo);
            } elseif (strpos($proveedor->logo, 'storage/') === 0) {
                $logoPath = public_path($proveedor->logo);
            } else {
                $logoPath = public_path('storage/' . $proveedor->logo);
            }

            if (!$logoPath || !file_exists($logoPath) || !is_readable($logoPath)) {
                return '';
            }

            // Detectar el tipo de imagen por extensión
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            
            // Verificar si GD está disponible para PNG/GIF
            // JPEG puede procesarse sin GD
            if (in_array($extension, ['png', 'gif']) && !extension_loaded('gd')) {
                $this->log('GD no disponible para procesar logo PNG/GIF - usando fallback', [
                    'logo' => $proveedor->logo,
                    'extension' => $extension,
                ]);
                return '';
            }

            $imageData = @file_get_contents($logoPath);
            if ($imageData === false || empty($imageData)) {
                return '';
            }

            // Determinar MIME type
            $mimeType = 'image/png';
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $mimeType = 'image/jpeg';
            } elseif ($extension === 'gif') {
                $mimeType = 'image/gif';
            }
            
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            $this->log('Error al convertir logo del proveedor a base64', [
                'error' => $e->getMessage(),
                'logo' => $proveedor->logo ?? 'N/A',
            ]);
        }

        return '';
    }

    /**
     * Genera y descarga el PDF de un presupuesto guardado.
     */
    public function generarPdf(Proveedor $proveedor, Presupuesto $presupuesto): Response
    {
        try {
            if ($presupuesto->proveedor_id !== $proveedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presupuesto no pertenece a este proveedor.',
                ], 403);
            }

            $presupuesto->load(Presupuesto::eagerLodable());

            $this->log('Generación de PDF solicitada', [
                'presupuesto_id' => $presupuesto->id,
                'numero_presupuesto' => $presupuesto->numero_presupuesto,
            ]);

            // Convertir logo del proveedor a base64
            $logoProveedorBase64 = $this->convertirLogoProveedorABase64($presupuesto->proveedor);

            // Preparar datos para la vista
            $datosPresupuesto = [
                'proveedor' => $presupuesto->proveedor,
                'logo_proveedor_base64' => $logoProveedorBase64,
                'numero_presupuesto' => $presupuesto->numero_presupuesto,
                'fecha_emision' => $presupuesto->fecha_emision,
                'concepto_general' => $presupuesto->concepto_general,
                'con_iva' => $presupuesto->con_iva,
                'iva_porcentaje' => $presupuesto->iva_porcentaje,
                'subtotal' => $presupuesto->subtotal,
                'iva_total' => $presupuesto->iva_total,
                'total' => $presupuesto->total,
                'empresa_receptora' => [
                    'nombre' => $presupuesto->empresa_receptora_nombre,
                    'empresa' => $presupuesto->empresa_receptora_empresa,
                    'puesto' => $presupuesto->empresa_receptora_puesto,
                    'telefono' => $presupuesto->empresa_receptora_telefono,
                    'correo' => $presupuesto->empresa_receptora_correo,
                    'direccion' => $presupuesto->condiciones['direccion'] ?? null,
                ],
                'conceptos' => $presupuesto->conceptos->map(function ($concepto) {
                    return [
                        'descripcion' => $concepto->descripcion,
                        'cantidad' => $concepto->cantidad,
                        'unidad' => $concepto->unidad,
                        'precio_unitario' => $concepto->precio_unitario,
                        'precio_total' => $concepto->precio_total,
                    ];
                })->toArray(),
                'condiciones' => $presupuesto->condiciones ?? [],
                'observaciones' => $presupuesto->observaciones,
            ];

            return $this->generarPdfResponse($datosPresupuesto, $presupuesto->numero_presupuesto);
        } catch (Throwable $e) {
            $this->log('Error al generar PDF', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible generar el PDF.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Genera PDF desde datos del formulario (para borradores).
     */
    public function generarPdfDesdeFormulario(StorePresupuestoRequest $request, Proveedor $proveedor): Response
    {
        try {
            $user = $request->user();

            if (! $user || ! method_exists($user, 'tieneAccesoAProveedor') || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario autenticado no tiene acceso al proveedor indicado.',
                ], 403);
            }

            $validated = $request->validated();

            if ((int) $validated['proveedor_id'] !== (int) $proveedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El proveedor del payload no coincide con el proveedor de la ruta.',
                ], 422);
            }

            // Convertir logo del proveedor a base64
            $logoProveedorBase64 = $this->convertirLogoProveedorABase64($proveedor);

            // Preparar datos para el PDF
            $datosPresupuesto = [
                'proveedor' => $proveedor,
                'logo_proveedor_base64' => $logoProveedorBase64,
                'numero_presupuesto' => $validated['numero_presupuesto'] ?? $this->formatearFolioSiguiente($proveedor),
                'fecha_emision' => $validated['fecha_emision'],
                'concepto_general' => $validated['concepto_general'],
                'con_iva' => $validated['con_iva'] ?? true,
                'iva_porcentaje' => $validated['iva_porcentaje'] ?? 16.00,
                'empresa_receptora' => [
                    'nombre' => $validated['empresa_receptora_nombre'] ?? null,
                    'empresa' => $validated['empresa_receptora_empresa'] ?? null,
                    'puesto' => $validated['empresa_receptora_puesto'] ?? null,
                    'telefono' => $validated['empresa_receptora_telefono'] ?? null,
                    'correo' => $validated['empresa_receptora_correo'] ?? null,
                    'direccion' => $validated['condiciones']['direccion'] ?? null,
                ],
                'conceptos' => $validated['conceptos'] ?? [],
                'condiciones' => $validated['condiciones'] ?? [],
                'observaciones' => $validated['observaciones'] ?? null,
            ];

            // Calcular totales
            $subtotal = collect($datosPresupuesto['conceptos'])->sum(function ($concepto) {
                return ($concepto['cantidad'] ?? 0) * ($concepto['precio_unitario'] ?? 0);
            });

            $ivaTotal = $datosPresupuesto['con_iva']
                ? $subtotal * ($datosPresupuesto['iva_porcentaje'] / 100)
                : 0;

            $datosPresupuesto['subtotal'] = round($subtotal, 2);
            $datosPresupuesto['iva_total'] = round($ivaTotal, 2);
            $datosPresupuesto['total'] = round($subtotal + $ivaTotal, 2);

            $this->log('Generación de PDF desde formulario solicitada', [
                'proveedor_id' => $proveedor->id,
                'numero_presupuesto' => $datosPresupuesto['numero_presupuesto'],
            ]);

            return $this->generarPdfResponse($datosPresupuesto, $datosPresupuesto['numero_presupuesto']);
        } catch (Throwable $e) {
            $this->log('Error al generar PDF desde formulario', [
                'proveedor_id' => $proveedor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible generar el PDF.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
