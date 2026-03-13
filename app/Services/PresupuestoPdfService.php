<?php

namespace App\Services;

use App\Models\Presupuesto;
use App\Models\Proveedor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PresupuestoPdfService
{
    /**
     * Genera y retorna la respuesta PDF de un presupuesto.
     */
    public function generarPdf(Presupuesto $presupuesto): Response
    {
        $presupuesto->load(Presupuesto::eagerLodable());

        $logoProveedorBase64 = $this->convertirLogoProveedorABase64($presupuesto->proveedor);
        $logosBase64 = $this->convertirLogosABase64();
        $gdDisponible = extension_loaded('gd');

        $datosPresupuesto = [
            'proveedor' => $presupuesto->proveedor,
            'logo_proveedor_base64' => $logoProveedorBase64,
            'logos_base64' => $logosBase64,
            'gd_disponible' => $gdDisponible,
            'numero_presupuesto' => $presupuesto->numero_presupuesto,
            'uuid' => $presupuesto->uuid ?? null,
            'clave_unica' => $presupuesto->id,
            'fecha_emision' => $presupuesto->fecha_emision,
            'lugar' => ($presupuesto->condiciones['lugar'] ?? null)
                ?: ($presupuesto->proveedor?->ciudad
                    ? ($presupuesto->proveedor->ciudad . ', ' . ($presupuesto->proveedor->estado ?? 'México'))
                    : null),
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
            'conceptos' => $presupuesto->conceptos->map(fn ($c) => [
                'descripcion' => $c->descripcion,
                'cantidad' => $c->cantidad,
                'unidad' => $c->unidad,
                'precio_unitario' => $c->precio_unitario,
                'precio_total' => $c->precio_total,
            ])->toArray(),
            'condiciones' => $presupuesto->condiciones ?? [],
            'observaciones' => $presupuesto->observaciones,
            'qr_code' => null,
        ];

        $filename = "Presupuesto_{$presupuesto->numero_presupuesto}.pdf";

        $pdf = Pdf::loadView('presupuestos.pdf', ['presupuesto' => $datosPresupuesto])
            ->setPaper('letter', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('margin-top', 25)
            ->setOption('margin-bottom', 25)
            ->setOption('margin-left', 25)
            ->setOption('margin-right', 25)
            ->setOption('enable-local-file-access', false)
            ->setOption('chroot', public_path());

        return $pdf->download($filename);
    }

    private function convertirLogosABase64(): array
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

    private function convertirLogoProveedorABase64(?Proveedor $proveedor): string
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
}
