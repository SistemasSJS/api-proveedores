<?php

namespace App\Support;

use App\Models\Presupuesto;
use App\Models\Proveedor;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generación de PDF de presupuestos (DomPDF).
 */
final class PresupuestoPdf
{
    /**
     * Genera y retorna la respuesta PDF de un presupuesto.
     */
    public static function generarPdf(Presupuesto $presupuesto): Response
    {
        $pdf = self::buildPdf($presupuesto);
        $filename = "Presupuesto_{$presupuesto->numero_presupuesto}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Contenido binario del PDF (adjuntos en correo, etc.).
     */
    public static function renderPdfBinary(Presupuesto $presupuesto): string
    {
        return self::buildPdf($presupuesto)->output();
    }

    private static function buildPdf(Presupuesto $presupuesto)
    {
        $presupuesto->load(Presupuesto::eagerLodable());
        $presupuesto->asegurarTokenPublico();

        $logoProveedorBase64 = self::convertirLogoProveedorABase64($presupuesto->proveedor);
        $logosBase64 = self::convertirLogosABase64();
        $anexosBase64 = self::normalizarAnexosParaPdf($presupuesto);
        $gdDisponible = extension_loaded('gd');

        $qrCode = self::generarQrCodeParaPresupuesto($presupuesto);
        $qrUrl = $qrCode && $presupuesto->token_publico
            ? rtrim(config('app.frontend_url', config('app.url')), '/') . '/public/presupuesto/' . $presupuesto->token_publico
            : null;

        $proveedor = $presupuesto->proveedor;
        $df = $proveedor?->direccion_fiscal;
        $estado = \Illuminate\Support\Arr::get((array) ($df ?? []), 'estado', $proveedor->estado ?? 'México');
        $lugar = $proveedor?->ciudad ? ($proveedor->ciudad . ', ' . $estado) : null;

        $empDoc = $presupuesto->empresaReceptoraParaDocumento();

        $enunciadosClasificados = $presupuesto->getEnunciadosClasificados();

        $datosPresupuesto = [
            'proveedor' => $proveedor,
            'logo_proveedor_base64' => $logoProveedorBase64,
            'logos_base64' => $logosBase64,
            'gd_disponible' => $gdDisponible,
            'numero_presupuesto' => $presupuesto->numero_presupuesto,
            'uuid' => $presupuesto->uuid ?? null,
            'clave_unica' => $presupuesto->id,
            'fecha_emision' => $presupuesto->fecha_emision,
            'lugar' => $lugar,
            'concepto_general' => $presupuesto->concepto_general,
            'con_iva' => $presupuesto->con_iva,
            'iva_porcentaje' => $presupuesto->iva_porcentaje,
            'subtotal' => $presupuesto->subtotal,
            'iva_total' => $presupuesto->iva_total,
            'total' => $presupuesto->total,
            'empresa_receptora' => $empDoc,
            'receptor_lineas' => self::lineasDirigidoUnicas([
                'alias_empresa' => $empDoc['alias_empresa'],
                'nombre' => $empDoc['nombre'],
                'puesto' => $empDoc['puesto'],
                'empresa' => $empDoc['empresa'],
                'telefono' => $empDoc['telefono'],
                'correo' => $empDoc['correo'],
            ]),
            'conceptos' => $presupuesto->conceptos->map(fn ($c) => [
                'descripcion' => $c->descripcion,
                'cantidad' => $c->cantidad,
                'unidad' => $c->unidad,
                'precio_unitario' => $c->precio_unitario,
                'precio_total' => $c->precio_total,
            ])->toArray(),
            'anexos' => $anexosBase64,
            'terminos_enunciados' => $enunciadosClasificados['terminos'],
            'validaciones_enunciados' => $enunciadosClasificados['validaciones'],
            'observaciones_enunciados' => $enunciadosClasificados['observaciones'],
            'qr_code' => $qrCode,
            'qr_url' => $qrUrl,
        ];

        return Pdf::loadView(PresupuestoPdfTemplate::viewName(), ['presupuesto' => $datosPresupuesto])
            ->setPaper('letter', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('margin-top', 25)
            ->setOption('margin-bottom', 70) // ~25mm: reserva espacio para pie de página
            ->setOption('margin-left', 25)
            ->setOption('margin-right', 25)
            ->setOption('enable-local-file-access', false)
            ->setOption('chroot', public_path());
    }

    private static function generarQrCodeParaPresupuesto(Presupuesto $presupuesto): ?string
    {
        $presupuesto->asegurarTokenPublico();
        $token = $presupuesto->token_publico;
        if (! $token) {
            return null;
        }

        $appUrl = config('app.frontend_url', config('app.url'));
        $urlWeb = rtrim($appUrl, '/') . '/public/presupuesto/' . $token;

        try {
            $renderer = new GDLibRenderer(200);
            $writer = new Writer($renderer);
            $qrPng = $writer->writeString($urlWeb);

            if ($qrPng !== '') {
                return 'data:image/png;base64,' . base64_encode($qrPng);
            }
        } catch (\Throwable $e) {
            Log::warning('Error al generar QR para presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private static function convertirLogosABase64(): array
    {
        $logos = ['facturapro' => '', 'constucc' => '', 'gestionpro' => ''];

        if (! extension_loaded('gd')) {
            return $logos;
        }

        $paths = [
            'facturapro' => public_path('assets/logos/logo-facturapro.png'),
            'constucc' => public_path('assets/logos/logo-construcc.png'),
            'gestionpro' => public_path('assets/logos/logo-gestionpro.png'),
        ];

        foreach ($paths as $key => $path) {
            if (file_exists($path) && is_readable($path)) {
                $data = @file_get_contents($path);
                if ($data !== false && $data !== '') {
                    $logos[$key] = 'data:image/png;base64,' . base64_encode($data);
                }
            }
        }

        return $logos;
    }

    private static function convertirLogoProveedorABase64(?Proveedor $proveedor): string
    {
        if (! $proveedor || empty($proveedor->logo)) {
            return '';
        }

        try {
            $logoPath = null;
            if (filter_var($proveedor->logo, FILTER_VALIDATE_URL)) {
                return '';
            }
            if (str_starts_with($proveedor->logo, '/') || str_starts_with($proveedor->logo, 'storage/')) {
                $logoPath = public_path($proveedor->logo);
            } else {
                $logoPath = public_path('storage/' . $proveedor->logo);
            }
            if (! $logoPath || ! file_exists($logoPath) || ! is_readable($logoPath)) {
                return '';
            }
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'gif']) && ! extension_loaded('gd')) {
                return '';
            }
            $data = @file_get_contents($logoPath);
            if ($data === false || $data === '') {
                return '';
            }
            $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : ($ext === 'gif' ? 'image/gif' : 'image/png');

            return 'data:' . $mime . ';base64,' . base64_encode($data);
        } catch (\Throwable $e) {
            Log::warning('Error al convertir logo proveedor', ['error' => $e->getMessage()]);
        }

        return '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function normalizarAnexosParaPdf(Presupuesto $presupuesto): array
    {
        return $presupuesto->anexos
            ->sortBy([
                ['orden', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(function ($anexo) {
                $base64 = self::convertirArchivoAnexoABase64($anexo->archivo_path);

                return [
                    'id' => $anexo->id,
                    'orden' => (int) ($anexo->orden ?? 0),
                    'titulo' => (string) $anexo->titulo,
                    'descripcion' => $anexo->descripcion,
                    'precio' => $anexo->precio !== null ? (float) $anexo->precio : null,
                    'archivo_base64' => $base64,
                ];
            })
            ->toArray();
    }

    private static function convertirArchivoAnexoABase64(?string $archivoPath): string
    {
        if (! $archivoPath) {
            return '';
        }

        $value = trim($archivoPath);

        return str_starts_with($value, 'data:image/')
            ? $value
            : '';
    }

    /**
     * Líneas para «Dirigido a:» en el PDF (misma vista que el preview: nombre → puesto → empresa).
     *
     * @param  array{
     *   alias_empresa?: string|null,
     *   nombre?: string|null,
     *   puesto?: string|null,
     *   empresa?: string|null,
     *   telefono?: string|null,
     *   correo?: string|null
     * }  $r  Solo se usan nombre, puesto y empresa; el resto se ignora.
     * @return list<string>
     */
    public static function lineasDirigidoUnicas(array $r): array
    {
        $ordenados = [
            trim((string) ($r['nombre'] ?? '')),
            trim((string) ($r['puesto'] ?? '')),
            trim((string) ($r['empresa'] ?? '')),
        ];

        $lines = [];
        foreach ($ordenados as $v) {
            if ($v !== '') {
                $lines[] = $v;
            }
        }

        return $lines;
    }

    /**
     * Misma lógica que {@see lineasDirigidoUnicas} leyendo solo las columnas `empresa_receptora_*` del presupuesto guardado.
     */
    public static function lineasReceptorPdfDesdeColumnasPresupuesto(Presupuesto $p): array
    {
        return self::lineasDirigidoUnicas([
            'alias_empresa' => $p->empresa_receptora_alias,
            'nombre' => $p->empresa_receptora_nombre,
            'puesto' => $p->empresa_receptora_puesto,
            'empresa' => $p->empresa_receptora_empresa,
            'telefono' => $p->empresa_receptora_telefono,
            'correo' => $p->empresa_receptora_correo,
        ]);
    }

    /**
     * Misma secuencia que {@see lineasReceptorPdfDesdeColumnasPresupuesto} desde un arreglo validado/normalizado
     * (claves `empresa_receptora_*`, p. ej. preview PDF desde formulario).
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public static function lineasReceptorPdfDesdePayloadReceptor(array $payload): array
    {
        return self::lineasDirigidoUnicas([
            'alias_empresa' => $payload['empresa_receptora_alias'] ?? null,
            'nombre' => $payload['empresa_receptora_nombre'] ?? null,
            'puesto' => $payload['empresa_receptora_puesto'] ?? null,
            'empresa' => $payload['empresa_receptora_empresa'] ?? null,
            'telefono' => $payload['empresa_receptora_telefono'] ?? null,
            'correo' => $payload['empresa_receptora_correo'] ?? null,
        ]);
    }
}
