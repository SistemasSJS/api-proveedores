<?php

namespace App\Helpers;

/**
 * Helper para construir listas de términos y condiciones y observaciones
 * a partir del JSON `condiciones` del presupuesto.
 *
 * Alineado con app-proveedores/helpers/presupuesto-terminos-condiciones.helper.ts
 * Solo incluye ítems cuando el flag *_activo correspondiente es true.
 * Si no hay ningún término activo, devuelve lista vacía (no fallback).
 *
 * @see Presupuesto::$condiciones
 */
final class PresupuestoCondicionesHelper
{
    /**
     * Construye la lista de términos y condiciones para PDF/preview.
     *
     * @param  array<string, mixed>|null  $condiciones  JSON condiciones del presupuesto
     * @return array<int, array{titulo: string, texto: string}>
     */
    public static function buildTerminosLista(
        ?array $condiciones,
        bool $conIva,
        float $ivaPorcentaje
    ): array {
        $cond = $condiciones ?? [];
        $lista = [];

        if (! empty($cond['vigencia_activo'])) {
            if (! empty($cond['vigencia_dias'])) {
                $dias = (int) ($cond['vigencia_dias'] ?? 7);
                $lista[] = [
                    'titulo' => 'Vigencia del presupuesto',
                    'texto' => "Este presupuesto tiene una vigencia de {$dias} días naturales a partir de su fecha de emisión.",
                ];
            } else {
                $vigencia = trim((string) ($cond['vigencia'] ?? ''));
                if ($vigencia !== '') {
                    $lista[] = ['titulo' => 'Vigencia del presupuesto', 'texto' => $vigencia];
                }
            }
        }

        if (! empty($cond['moneda_activo'])) {
            $lista[] = [
                'titulo' => 'Moneda',
                'texto' => 'Los precios están expresados en moneda nacional (MXN), salvo que se indique lo contrario.',
            ];
        }

        if (! empty($cond['impuestos_activo'])) {
            $texto = $conIva
                ? 'Los precios incluyen el Impuesto al Valor Agregado (IVA) al ' . (int) $ivaPorcentaje . '%.'
                : 'Los precios no incluyen el Impuesto al Valor Agregado (IVA).';
            $lista[] = ['titulo' => 'Impuestos', 'texto' => $texto];
        }

        if (! empty($cond['anticipo_activo']) && isset($cond['anticipo_porcentaje'])) {
            $pct = (int) $cond['anticipo_porcentaje'];
            $lista[] = [
                'titulo' => 'Anticipo',
                'texto' => "Para iniciar los trabajos se requiere un anticipo del {$pct}% del monto total.",
            ];
        }

        if (! empty($cond['entrega_activo']) && ! empty($cond['entrega_tipo'])) {
            $texto = ($cond['entrega_tipo'] ?? '') === 'despues'
                ? 'Una vez entregados los trabajos o productos se deberá cubrir el 100% del monto total del presupuesto.'
                : 'Para la entrega de los trabajos o productos se deberá haber cubierto el 100% del monto total del presupuesto.';
            $lista[] = ['titulo' => 'Entrega de trabajos o productos', 'texto' => $texto];
        }

        if (! empty($cond['tiempo_entrega_activo'])) {
            if (! empty($cond['tiempo_entrega_dias'])) {
                $dias = (int) $cond['tiempo_entrega_dias'];
                $lista[] = [
                    'titulo' => 'Tiempo de entrega o ejecución',
                    'texto' => "Una vez recibido el anticipo, el tiempo estimado de entrega o ejecución total de los trabajos será de {$dias} días naturales.",
                ];
            } else {
                $tiempo = trim((string) ($cond['tiempo_entrega'] ?? ''));
                if ($tiempo !== '') {
                    $lista[] = ['titulo' => 'Tiempo de entrega', 'texto' => $tiempo];
                }
            }
        }

        if (! empty($cond['disponibilidad_materiales_activo'])) {
            $disp = trim((string) ($cond['disponibilidad_materiales_texto'] ?? ''));
            if ($disp !== '') {
                $lista[] = ['titulo' => 'Disponibilidad de materiales', 'texto' => $disp];
            } else {
                $lista[] = [
                    'titulo' => 'Disponibilidad de materiales o refacciones',
                    'texto' => 'Los tiempos de entrega o ejecución pueden variar dependiendo de la disponibilidad de materiales, refacciones o insumos necesarios.',
                ];
            }
        }

        if (! empty($cond['trabajos_adicionales_activo'])) {
            $lista[] = [
                'titulo' => 'Trabajos o conceptos adicionales',
                'texto' => 'Cualquier trabajo o concepto no incluido en este presupuesto será cotizado por separado.',
            ];
        }

        if (! empty($cond['alcance_activo'])) {
            $alcance = trim((string) ($cond['alcance_texto'] ?? ''));
            $texto = $alcance !== ''
                ? $alcance
                : 'Este presupuesto incluye únicamente los trabajos o productos descritos en este documento.';
            $lista[] = ['titulo' => 'Alcance del presupuesto', 'texto' => $texto];
        }

        if (! empty($cond['cancelacion_activo'])) {
            $cancel = trim((string) ($cond['cancelacion_texto'] ?? ''));
            if ($cancel !== '') {
                $lista[] = ['titulo' => 'Cancelación del servicio', 'texto' => $cancel];
            } else {
                $lista[] = [
                    'titulo' => 'Cancelación del pedido o servicio',
                    'texto' => 'En caso de cancelación del servicio o pedido una vez autorizado el presupuesto, los gastos o trabajos ya realizados deberán ser cubiertos por el cliente.',
                ];
            }
        }

        if (! empty($cond['autorizacion_gestionpro_activo'])) {
            $lista[] = [
                'titulo' => 'Autorización mediante GestiónPro',
                'texto' => 'La autorización de este presupuesto mediante la aplicación GestiónPro implica la confirmación del cliente para el inicio de los trabajos o suministros descritos.',
            ];
        }

        // Condicionantes adicionales: solo si hay al menos un término principal activo
        if (count($lista) > 0) {
            foreach (['condicionantes_adicionales_1', 'condicionantes_adicionales_2', 'condicionantes_adicionales_3', 'condicionantes_adicionales_4'] as $key) {
                $txt = trim((string) ($cond[$key] ?? ''));
                if ($txt !== '') {
                    $lista[] = ['titulo' => '', 'texto' => $txt];
                }
            }
        }

        return $lista;
    }

    /**
     * Construye la lista de observaciones generales para PDF/preview.
     *
     * @param  array<string, mixed>|null  $condiciones  JSON condiciones del presupuesto
     * @param  string|null  $observacionesPresupuesto  Campo observaciones del presupuesto
     * @return array<int, string>
     */
    public static function buildObservacionesLista(
        ?array $condiciones,
        ?string $observacionesPresupuesto
    ): array {
        $cond = $condiciones ?? [];
        $lista = [];

        if (! empty($cond['garantia_activo'])) {
            if (! empty($cond['garantia_dias'])) {
                $dias = (int) ($cond['garantia_dias'] ?? 30);
                $lista[] = "La garantía de los trabajos o productos tendrá una vigencia de {$dias} días a partir de la finalización de los trabajos o entrega de los productos.";
            } else {
                $garantia = trim((string) ($cond['garantia'] ?? ''));
                if ($garantia !== '') {
                    $lista[] = "Garantía: {$garantia}";
                }
            }
        }

        if (! empty($cond['gastos_traslado_activo']) && isset($cond['gastos_traslado'])) {
            $incluidos = ($cond['gastos_traslado'] ?? '') === 'incluidos';
            $lista[] = 'Los trabajos contemplados en este presupuesto ' . ($incluidos ? 'sí' : 'no') . ' incluyen los gastos de traslado al sitio donde se realizarán los trabajos.';
        }

        if (! empty($cond['viaticos_activo']) && isset($cond['viaticos'])) {
            $incluidos = ($cond['viaticos'] ?? '') === 'incluidos';
            $lista[] = 'Los trabajos contemplados en este presupuesto ' . ($incluidos ? 'sí' : 'no') . ' incluyen los gastos de viáticos derivados de la ubicación donde deberán realizarse los trabajos.';
        }

        $revisionTecnica = trim((string) ($cond['revision_tecnica_texto'] ?? ''));
        if (! empty($cond['revision_tecnica_activo']) && $revisionTecnica !== '') {
            $lista[] = "Revisión técnica: {$revisionTecnica}";
        }

        $condicionesSitio = trim((string) ($cond['condiciones_sitio_texto'] ?? ''));
        if (! empty($cond['condiciones_sitio_activo']) && $condicionesSitio !== '') {
            $lista[] = "Condiciones del sitio: {$condicionesSitio}";
        }

        foreach (['observaciones_adicionales_1', 'observaciones_adicionales_2', 'observaciones_adicionales_3', 'observaciones_adicionales_4'] as $key) {
            $txt = trim((string) ($cond[$key] ?? ''));
            if ($txt !== '') {
                $lista[] = $txt;
            }
        }

        $obs = trim((string) ($observacionesPresupuesto ?? ''));
        if ($obs !== '') {
            $lista[] = $obs;
        }

        return $lista;
    }
}
