<?php

namespace App\Services\Presupuesto;

use App\Models\Presupuesto;
use App\Models\PresupuestoAnexo;
use App\Models\PresupuestoAnexoPdf;
use App\Models\PresupuestoConcepto;
use App\Models\PresupuestoPlantilla;
use App\Models\PresupuestoPlantillaConcepto;
use App\Models\User;
use App\Support\PresupuestoAnexoArchivoResponse;
use App\Support\PresupuestoAnexoImagenOptimizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Aplica una plantilla a un presupuesto nuevo (borrador).
 * Aislado del flujo duplicar/store del documento.
 */
class PresupuestoPlantillaAplicarService
{
    public function aplicar(PresupuestoPlantilla $plantilla, User $user): Presupuesto
    {
        $plantilla->loadMissing(['conceptos', 'anexos', 'anexosPdf']);

        return DB::transaction(function () use ($plantilla, $user) {
            $payload = [
                'proveedor_id' => (int) $plantilla->proveedor_id,
                'user_id' => (int) $user->id,
                'numero_presupuesto' => Presupuesto::generarNumeroPresupuesto((int) $plantilla->proveedor_id),
                'fecha_emision' => now()->toDateString(),
                'estado' => Presupuesto::ESTADO_BORRADOR,
                'motivo_rechazo' => null,
                'item_visto' => false,
                'concepto_general' => $plantilla->concepto_general ?: $plantilla->nombre,
                'titulo_anexos' => $plantilla->titulo_anexos,
                'titulo_anexos_pdf' => $plantilla->titulo_anexos_pdf,
                'con_iva' => (bool) $plantilla->con_iva,
                'iva_porcentaje' => $plantilla->iva_porcentaje ?? 16,
                'porcentaje_descuento' => $plantilla->porcentaje_descuento,
                'cantidad_descuento' => $plantilla->cantidad_descuento,
                'term_cond_dias_vigencia' => $plantilla->term_cond_dias_vigencia,
                'term_cond_moneda' => $plantilla->term_cond_moneda ?: 'MXN',
                'term_cond_impuestos_en_pdf' => (bool) ($plantilla->term_cond_impuestos_en_pdf ?? true),
                'term_cond_iva' => $plantilla->term_cond_iva,
                'term_cond_tiempo_entrega_dias' => $plantilla->term_cond_tiempo_entrega_dias,
                'term_cond_inicio_trabajo' => $plantilla->term_cond_inicio_trabajo,
                'term_cond_inicio_trabajo_porcentaje' => $plantilla->term_cond_inicio_trabajo_porcentaje,
                'term_cond_inicio_trabajo_cantidad' => $plantilla->term_cond_inicio_trabajo_cantidad,
                'term_cond_textos_libres' => $plantilla->term_cond_textos_libres,
                'term_cond_visibilidad' => $plantilla->term_cond_visibilidad,
                'validacion_alcances' => $plantilla->validacion_alcances,
                'configuracion_condiciones' => $plantilla->configuracion_condiciones,
                'obs_garantia_dias' => $plantilla->obs_garantia_dias,
                'config_mostrar_totales' => (bool) ($plantilla->config_mostrar_totales ?? true),
                'pdf_theme' => $plantilla->pdf_theme,
                'ppto_config' => $plantilla->ppto_config,
                'config_emisor_presupuesto_id' => $plantilla->config_emisor_presupuesto_id,
                'empresa_emisora_nombre' => $plantilla->empresa_emisora_nombre,
                'empresa_emisora_puesto' => $plantilla->empresa_emisora_puesto,
                'empresa_emisora_telefono' => $plantilla->empresa_emisora_telefono,
                'empresa_emisora_correo' => $plantilla->empresa_emisora_correo,
                'incluir_leyenda_atentamente' => (bool) ($plantilla->incluir_leyenda_atentamente ?? true),
                'empresa_emisora_nombre_comercial' => $plantilla->empresa_emisora_nombre_comercial,
                // Sin receptor: se elige al editar el borrador.
                'empresa_receptora_id' => null,
                'proveedor_receptor_id' => null,
                'empresa_receptora_nombre' => null,
                'empresa_receptora_puesto' => null,
                'empresa_receptora_empresa' => null,
                'empresa_receptora_alias' => null,
                'empresa_receptora_telefono' => null,
                'empresa_receptora_correo' => null,
            ];

            $presupuesto = Presupuesto::create($payload);
            $presupuesto->asegurarTokenPublico();

            $numero = 1;
            foreach ($plantilla->conceptos as $linea) {
                /** @var PresupuestoPlantillaConcepto $linea */
                $tipo = $linea->tipo ?: PresupuestoConcepto::TIPO_CONCEPTO;
                $concepto = new PresupuestoConcepto([
                    'presupuesto_id' => $presupuesto->id,
                    'numero' => $numero++,
                    'tipo' => $tipo,
                    'descripcion' => $linea->descripcion,
                    'cantidad' => $tipo === PresupuestoConcepto::TIPO_PARRAFO ? 0 : (float) $linea->cantidad,
                    'unidad' => $tipo === PresupuestoConcepto::TIPO_PARRAFO
                        ? ''
                        : ($linea->unidad ?: 'pieza'),
                    'precio_unitario' => $tipo === PresupuestoConcepto::TIPO_PARRAFO ? 0 : (float) $linea->precio_unitario,
                ]);

                if ($linea->imagen_path) {
                    $concepto->imagen_path = $this->copiarImagenLinea(
                        (int) $plantilla->proveedor_id,
                        (int) $presupuesto->id,
                        (string) $linea->imagen_path
                    );
                }

                $concepto->calcularImporte();
                $concepto->save();
            }

            $this->copiarAnexosImagen($plantilla, $presupuesto);
            $this->copiarAnexosPdf($plantilla, $presupuesto);

            $presupuesto->recalcularDesdeConceptos();
            $presupuesto->save();

            return $presupuesto->fresh(Presupuesto::eagerLodable());
        });
    }

    private function copiarAnexosImagen(PresupuestoPlantilla $plantilla, Presupuesto $presupuesto): void
    {
        $disk = Storage::disk('public');
        $proveedorId = (int) $presupuesto->proveedor_id;

        foreach ($plantilla->anexos as $anexo) {
            $origenPath = (string) ($anexo->archivo_path ?? '');
            if ($origenPath === '' || ! $disk->exists($origenPath)) {
                continue;
            }

            $extension = pathinfo($origenPath, PATHINFO_EXTENSION) ?: 'jpg';
            $nuevoPath = sprintf(
                'proveedores/%d/presupuestos/%d/anexos/%s.%s',
                $proveedorId,
                (int) $presupuesto->id,
                Str::uuid()->toString(),
                $extension
            );
            $disk->copy($origenPath, $nuevoPath);

            PresupuestoAnexo::create([
                'presupuesto_id' => $presupuesto->id,
                'titulo' => $anexo->titulo,
                'descripcion' => $anexo->descripcion,
                'precio' => $anexo->precio,
                'orden' => $anexo->orden,
                'archivo_path' => $nuevoPath,
                'archivo_width' => $anexo->archivo_width,
                'archivo_height' => $anexo->archivo_height,
                'archivo_aspect_ratio' => $anexo->archivo_aspect_ratio,
            ]);
        }
    }

    private function copiarAnexosPdf(PresupuestoPlantilla $plantilla, Presupuesto $presupuesto): void
    {
        $disk = Storage::disk('public');
        $proveedorId = (int) $presupuesto->proveedor_id;

        foreach ($plantilla->anexosPdf as $anexo) {
            $origenPath = (string) ($anexo->archivo_path ?? '');
            if ($origenPath === '' || ! $disk->exists($origenPath)) {
                continue;
            }

            $nuevoPath = sprintf(
                'proveedores/%d/presupuestos/%d/anexos-pdf/%s.pdf',
                $proveedorId,
                (int) $presupuesto->id,
                Str::uuid()->toString()
            );
            $disk->copy($origenPath, $nuevoPath);

            PresupuestoAnexoPdf::create([
                'presupuesto_id' => $presupuesto->id,
                'titulo' => $anexo->titulo,
                'orden' => $anexo->orden,
                'archivo_path' => $nuevoPath,
                'paginas' => $anexo->paginas,
                'mostrar_estampado' => $anexo->mostrar_estampado,
                'mostrar_numero_pagina' => $anexo->mostrar_numero_pagina,
                'mostrar_datos_presupuesto' => $anexo->mostrar_datos_presupuesto,
            ]);
        }
    }

    private function copiarImagenLinea(int $proveedorId, int $presupuestoId, string $origenPath): ?string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($origenPath)) {
            $base64 = PresupuestoAnexoArchivoResponse::archivoBase64($origenPath);
            if ($base64 === null) {
                return null;
            }

            return $this->guardarImagenBase64($proveedorId, $presupuestoId, $base64);
        }

        $ext = pathinfo($origenPath, PATHINFO_EXTENSION) ?: 'jpg';
        $destino = sprintf(
            'presupuestos/%d/%d/conceptos/%s.%s',
            $proveedorId,
            $presupuestoId,
            Str::uuid()->toString(),
            $ext
        );
        $disk->copy($origenPath, $destino);

        return $destino;
    }

    private function guardarImagenBase64(int $proveedorId, int $presupuestoId, string $base64): ?string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/', $base64, $m)) {
            return null;
        }
        $binary = base64_decode($m[2], true);
        if ($binary === false) {
            return null;
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $optimized = PresupuestoAnexoImagenOptimizer::optimizarParaAlmacenamiento($binary);
        $path = sprintf(
            'presupuestos/%d/%d/conceptos/%s.%s',
            $proveedorId,
            $presupuestoId,
            Str::uuid()->toString(),
            $optimized['extension'] ?? 'jpg'
        );
        Storage::disk('public')->put($path, $optimized['binary'] ?? $binary);

        return $path;
    }
}
