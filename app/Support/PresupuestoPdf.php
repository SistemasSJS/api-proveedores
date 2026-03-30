<?php

namespace App\Support;

use App\Models\Presupuesto;
use App\Models\Proveedor;
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
                'empresa' => $empDoc['empresa'],
                'nombre' => $empDoc['nombre'],
                'puesto' => $empDoc['puesto'],
                'alias_empresa' => $empDoc['alias_empresa'],
            ]),
            'conceptos' => $presupuesto->conceptos->map(fn ($c) => [
                'descripcion' => $c->descripcion,
                'cantidad' => $c->cantidad,
                'unidad' => $c->unidad,
                'precio_unitario' => $c->precio_unitario,
                'precio_total' => $c->precio_total,
            ])->toArray(),
            'terminos_enunciados' => $presupuesto->getTerminosEnunciados(),
            'observaciones_enunciados' => $presupuesto->getObservacionesEnunciados(),
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
        $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($urlWeb);

        try {
            $context = stream_context_create([
                'http' => ['timeout' => 5],
            ]);
            $qrImage = @file_get_contents($qrApiUrl, false, $context);
            if ($qrImage !== false && ! empty($qrImage)) {
                return 'data:image/png;base64,' . base64_encode($qrImage);
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
     * Líneas para «Dirigido a:» en el PDF. Orden fijo: empresa, contacto, puesto, alias.
     * No se eliminan duplicados entre campos (antes el nombre igual a la empresa ocultaba puesto u otros datos).
     *
     * @param  array{nombre?: string|null, puesto?: string|null, empresa?: string|null, alias_empresa?: string|null}  $r
     * @return list<string>
     */
    public static function lineasDirigidoUnicas(array $r): array
    {
        $ordenados = [
            trim((string) ($r['empresa'] ?? '')),
            trim((string) ($r['nombre'] ?? '')),
            trim((string) ($r['puesto'] ?? '')),
            trim((string) ($r['alias_empresa'] ?? '')),
        ];

        $lines = [];
        foreach ($ordenados as $v) {
            if ($v !== '') {
                $lines[] = $v;
            }
        }

        return $lines;
    }
}
