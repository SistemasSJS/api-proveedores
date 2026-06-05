<?php

namespace App\Support;

use App\Models\Presupuesto;
use App\Models\Proveedor;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            'term_cond_moneda' => $presupuesto->term_cond_moneda ?? 'MXN',
            'subtotal' => $presupuesto->subtotal,
            'porcentaje_descuento' => $presupuesto->porcentaje_descuento,
            'cantidad_descuento' => $presupuesto->cantidad_descuento,
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
            'pdf_theme' => $presupuesto->pdf_theme,
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
            ->setOption('chroot', public_path())
            ->setOption('compress', true)
            ->setOption('dpi', 96);
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
        $logos = ['facturapro' => '', 'constucc' => '', 'gestionplus' => ''];

        if (! extension_loaded('gd')) {
            return $logos;
        }

        $paths = [
            'facturapro' => public_path('assets/logos/logo-facturapro.png'),
            'constucc' => public_path('assets/logos/logo-construcc.png'),
            'gestionplus' => EmailLogoHelper::gestionPlusLogoAbsolutePath(),
        ];

        foreach ($paths as $key => $path) {
            if (! $path || ! file_exists($path) || ! is_readable($path)) {
                continue;
            }
            $data = @file_get_contents($path);
            if ($data !== false && $data !== '') {
                $logos[$key] = 'data:image/png;base64,' . base64_encode($data);
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
                    'archivo_width' => $anexo->archivo_width !== null ? (int) $anexo->archivo_width : null,
                    'archivo_height' => $anexo->archivo_height !== null ? (int) $anexo->archivo_height : null,
                    'archivo_aspect_ratio' => $anexo->archivo_aspect_ratio !== null ? (float) $anexo->archivo_aspect_ratio : null,
                ];
            })
            ->toArray();
    }

    private static function convertirArchivoAnexoABase64(?string $archivoPath): string
    {
        if (! $archivoPath) {
            return '';
        }

        $path = trim($archivoPath);
        if (str_starts_with($path, 'data:image/')) {
            if (! preg_match('/^data:image\/[^;]+;base64,(.+)$/i', $path, $m)) {
                return '';
            }
            $decoded = base64_decode($m[1], true);

            return $decoded !== false
                ? PresupuestoAnexoImagenOptimizer::dataUriParaPdf($decoded)
                : '';
        }

        if (! Storage::disk('public')->exists($path)) {
            return '';
        }

        $binary = Storage::disk('public')->get($path);

        return PresupuestoAnexoImagenOptimizer::dataUriParaPdf($binary);
    }

    /**
     * Anexos listos para la plantilla Blade del PDF.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function anexosParaPlantillaPdf(Presupuesto $presupuesto): array
    {
        return self::normalizarAnexosParaPdf($presupuesto);
    }

    public static function formatMontoLegal(float|int|string|null $value, ?string $currency = 'MXN'): string
    {
        return self::formatMontoLetraLegal($value, $currency);
    }

    public static function formatMontoNumeroLegal(float|int|string|null $value, ?string $currency = 'MXN'): string
    {
        $amount = round((float) ($value ?? 0), 2);
        $codigo = self::normalizarCodigoMoneda($currency);
        $formatted = number_format($amount, 2, '.', ',');

        return match ($codigo) {
            'USD' => "USD {$formatted}",
            'EUR' => "EUR {$formatted}",
            default => '$' . $formatted . ' MXN',
        };
    }

    public static function formatMontoLetraLegal(float|int|string|null $value, ?string $currency = 'MXN'): string
    {
        $amount = round((float) ($value ?? 0), 2);
        $codigo = self::normalizarCodigoMoneda($currency);
        [$enteroStr, $decimales] = explode('.', number_format($amount, 2, '.', ''));

        $entero = abs((int) $enteroStr);
        $texto = self::numeroALetras($entero, true);

        if ((int) $enteroStr < 0) {
            $texto = 'menos ' . $texto;
        }

        [$singular, $plural, $sufijo] = self::currencyNames($codigo);
        $monedaTexto = $entero === 1 ? $singular : $plural;

        $linea = trim($texto . ' ' . $monedaTexto . ' ' . $decimales . '/100 ' . $sufijo);

        return '(' . self::mayusculasEspanol($linea) . ')';
    }

    private static function normalizarCodigoMoneda(?string $currency): string
    {
        $codigo = strtoupper(trim((string) ($currency ?? 'MXN')));

        return in_array($codigo, ['MXN', 'USD', 'EUR'], true)
            ? $codigo
            : 'MXN';
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private static function currencyNames(string $codigo): array
    {
        return match ($codigo) {
            'USD' => ['DÓLAR ESTADOUNIDENSE', 'DÓLARES ESTADOUNIDENSES', 'USD'],
            'EUR' => ['EURO', 'EUROS', 'EUR'],
            default => ['PESO', 'PESOS', 'M.N.'],
        };
    }

    private static function mayusculasEspanol(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($value, 'UTF-8');
        }

        return strtoupper($value);
    }

    private static function numeroALetras(int $numero, bool $apocope = true): string
    {
        if ($numero === 0) {
            return 'cero';
        }

        if ($numero < 0) {
            return 'menos ' . self::numeroALetras(abs($numero), $apocope);
        }

        if ($numero < 1000) {
            return self::centenasALetras($numero, $apocope);
        }

        if ($numero < 1000000) {
            $miles = intdiv($numero, 1000);
            $resto = $numero % 1000;
            $texto = $miles === 1
                ? 'mil'
                : self::numeroALetras($miles, true) . ' mil';

            return trim($texto . ' ' . ($resto > 0 ? self::numeroALetras($resto, $apocope) : ''));
        }

        if ($numero < 1000000000) {
            $millones = intdiv($numero, 1000000);
            $resto = $numero % 1000000;
            $texto = $millones === 1
                ? 'un millón'
                : self::numeroALetras($millones, true) . ' millones';

            return trim($texto . ' ' . ($resto > 0 ? self::numeroALetras($resto, $apocope) : ''));
        }

        $milesMillones = intdiv($numero, 1000000000);
        $resto = $numero % 1000000000;
        $texto = $milesMillones === 1
            ? 'mil millones'
            : self::numeroALetras($milesMillones, true) . ' mil millones';

        return trim($texto . ' ' . ($resto > 0 ? self::numeroALetras($resto, $apocope) : ''));
    }

    private static function centenasALetras(int $numero, bool $apocope): string
    {
        $basicos = [
            0 => '',
            1 => $apocope ? 'un' : 'uno',
            2 => 'dos',
            3 => 'tres',
            4 => 'cuatro',
            5 => 'cinco',
            6 => 'seis',
            7 => 'siete',
            8 => 'ocho',
            9 => 'nueve',
            10 => 'diez',
            11 => 'once',
            12 => 'doce',
            13 => 'trece',
            14 => 'catorce',
            15 => 'quince',
            16 => 'dieciséis',
            17 => 'diecisiete',
            18 => 'dieciocho',
            19 => 'diecinueve',
            20 => 'veinte',
            21 => $apocope ? 'veintiún' : 'veintiuno',
            22 => 'veintidós',
            23 => 'veintitrés',
            24 => 'veinticuatro',
            25 => 'veinticinco',
            26 => 'veintiséis',
            27 => 'veintisiete',
            28 => 'veintiocho',
            29 => 'veintinueve',
        ];

        if ($numero < 30) {
            return $basicos[$numero];
        }

        if ($numero < 100) {
            $decenas = [
                3 => 'treinta',
                4 => 'cuarenta',
                5 => 'cincuenta',
                6 => 'sesenta',
                7 => 'setenta',
                8 => 'ochenta',
                9 => 'noventa',
            ];
            $d = intdiv($numero, 10);
            $u = $numero % 10;

            if ($u === 0) {
                return $decenas[$d];
            }

            $unidad = $u === 1
                ? ($apocope ? 'un' : 'uno')
                : $basicos[$u];

            return $decenas[$d] . ' y ' . $unidad;
        }

        if ($numero === 100) {
            return 'cien';
        }

        $centenas = [
            1 => 'ciento',
            2 => 'doscientos',
            3 => 'trescientos',
            4 => 'cuatrocientos',
            5 => 'quinientos',
            6 => 'seiscientos',
            7 => 'setecientos',
            8 => 'ochocientos',
            9 => 'novecientos',
        ];

        $c = intdiv($numero, 100);
        $resto = $numero % 100;

        return trim($centenas[$c] . ' ' . ($resto > 0 ? self::centenasALetras($resto, $apocope) : ''));
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
