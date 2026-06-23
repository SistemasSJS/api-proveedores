<?php

namespace App\Support;

/**
 * Estimación de alturas (mm) del cuerpo presupuesto para alinear «Atentamente» al pie de la última hoja de la sección 1.
 */
final class PresupuestoPdfLayout
{
    private const ALTURA_HOJA_LETRA_MM = 279.4;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     espaciador_mm: float,
     *     salto_pagina_antes: bool,
     *     altura_contenido_antes_cierre_mm: float,
     *     altura_atentamente_mm: float,
     *     altura_util_pagina_mm: float,
     *     secciones_mm: array<string, float>
     * }
     */
    public static function calcularCierreAtentamente(array $payload, string $variant = 'default'): array
    {
        $vacio = [
            'espaciador_mm' => 0.0,
            'salto_pagina_antes' => false,
            'altura_contenido_antes_cierre_mm' => 0.0,
            'altura_atentamente_mm' => 0.0,
            'altura_util_pagina_mm' => 0.0,
            'secciones_mm' => [],
        ];

        if (! PresupuestoPdf::debeMostrarBloqueAtentamenteDesdePayload($payload)) {
            return $vacio;
        }

        $medidas = self::medidasHojaMm($variant);
        $alturaUtil = $medidas['altura_util_mm'];
        $gapPie = $medidas['gap_atentamente_footer_mm'];

        $secciones = self::estimarSeccionesMm($payload, $variant);
        $terminosMm = (float) ($secciones['terminos_y_observaciones'] ?? 0.0);
        $alturaAntesTerminos = 0.0;
        foreach ($secciones as $clave => $valor) {
            if ($clave === 'terminos_y_observaciones') {
                continue;
            }
            $alturaAntesTerminos += (float) $valor;
        }
        $alturaAntesTerminos += self::huecosEntreSeccionesMm($secciones);

        $alturaAntesCierre = $alturaAntesTerminos + $terminosMm;
        $alturaAtentamente = self::estimarAlturaBloqueAtentamenteMm($payload, $medidas);

        $posicionFin = fmod($alturaAntesCierre, $alturaUtil);
        if ($alturaAntesCierre > 0 && $posicionFin < 1.0) {
            $posicionFin = $alturaUtil;
        }

        $espacioLibre = max(0.0, $alturaUtil - $posicionFin);
        $necesario = $alturaAtentamente + $gapPie;
        $margenSeguridadMm = 3.0;

        $salto = $espacioLibre < ($necesario + $margenSeguridadMm);
        // Espaciador solo para empujar Atentamente al pie de la misma hoja; nunca rellenar una hoja entera (evita hoja en blanco).
        $espaciador = max(2.0, $espacioLibre - $necesario);
        $espaciadorMaximoMm = max(2.0, $alturaUtil - $necesario - $margenSeguridadMm);
        if ($espaciador > $espaciadorMaximoMm) {
            $espaciador = $espaciadorMaximoMm;
        }

        return [
            'espaciador_mm' => max(2.0, round($espaciador, 2)),
            'salto_pagina_antes' => $salto,
            'reserva_pie_html_mm' => round($alturaAtentamente + $gapPie + 2.0, 2),
            'altura_contenido_antes_cierre_mm' => round($alturaAntesCierre, 2),
            'altura_atentamente_mm' => round($alturaAtentamente, 2),
            'altura_util_pagina_mm' => round($alturaUtil, 2),
            'secciones_mm' => $secciones,
        ];
    }

    /**
     * Espacio vertical estimado entre bloques del cuerpo (márgenes / separadores).
     *
     * @param  array<string, float>  $secciones
     */
    private static function huecosEntreSeccionesMm(array $secciones): float
    {
        $hueco = 0.0;
        if (($secciones['descripcion_general'] ?? 0) > 0) {
            $hueco += 3.0;
        }
        if (($secciones['titulo_presupuesto'] ?? 0) > 0) {
            $hueco += 4.0;
        }
        if (($secciones['tabla_conceptos'] ?? 0) > 0) {
            $hueco += 3.0;
        }
        if (($secciones['totales'] ?? 0) > 0) {
            $hueco += 3.0;
        }
        if (($secciones['terminos_y_observaciones'] ?? 0) > 0) {
            $hueco += 2.0;
        }

        return $hueco;
    }

    /**
     * @return array{
     *     altura_util_mm: float,
     *     gap_atentamente_footer_mm: float,
     *     espacio_tras_titulo_atentamente_mm: float
     * }
     */
    public static function medidasHojaMm(string $variant = 'default'): array
    {
        $margenMm = $variant === 'tailwind' ? 20.0 : 25.4;
        $footerHeightMm = 25.4;
        $lineaEspacioMm = 2.8;
        $margenSuperiorMm = max(8.0, $margenMm - (4 * $lineaEspacioMm));
        $footerBottomMm = 6.0;
        $margenPaginaMm = 25.5;

        $alturaUtil = self::ALTURA_HOJA_LETRA_MM
            - $margenSuperiorMm
            - $margenPaginaMm
            - $footerHeightMm
            - $footerBottomMm
            - $margenPaginaMm;

        return [
            'altura_util_mm' => max(165.0, $alturaUtil),
            'gap_atentamente_footer_mm' => $lineaEspacioMm,
            'espacio_tras_titulo_atentamente_mm' => 2 * $lineaEspacioMm,
        ];
    }

    /**
     * Alturas estimadas por bloque (sin términos ni Atentamente).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, float>
     */
    public static function estimarSeccionesMm(array $payload, string $variant = 'default'): array
    {
        $conceptos = $payload['conceptos'] ?? [];
        $terminos = $payload['terminos_enunciados'] ?? [];
        $validaciones = $payload['validaciones_enunciados'] ?? [];
        $observaciones = $payload['observaciones_enunciados'] ?? [];

        $lineaTabla = $variant === 'tailwind' ? 5.2 : 5.5;
        $lineaTermino = 4.2;

        $tablaMm = 6.0;
        foreach ($conceptos as $concepto) {
            $tipo = (string) ($concepto['tipo'] ?? 'concepto');
            if ($tipo === 'parrafo') {
                $tablaMm += self::alturaTextoMm((string) ($concepto['descripcion'] ?? ''), 72, 4.0) + 2.5;
            } else {
                $tablaMm += $lineaTabla;
            }
        }
        if (count($conceptos) === 0) {
            $tablaMm += 10.0;
        }

        $terminosMm = 0.0;
        if (count($terminos) > 0 || count($validaciones) > 0 || count($observaciones) > 0) {
            $terminosMm += 4.0;
        }
        if (count($terminos) > 0) {
            $terminosMm += 6.5;
            foreach ($terminos as $texto) {
                $terminosMm += self::alturaTextoMm((string) $texto, 95, $lineaTermino);
            }
        }
        if (count($validaciones) > 0) {
            $terminosMm += 5.0 + count($validaciones) * $lineaTermino;
        }
        if (count($observaciones) > 0) {
            $terminosMm += 5.0;
            foreach ($observaciones as $obs) {
                $terminosMm += self::alturaTextoMm((string) $obs, 95, $lineaTermino);
            }
        }

        $receptorLineas = $payload['receptor_lineas'] ?? [];
        $receptorMm = 5.0 + max(1, count($receptorLineas)) * 4.2;

        $conceptoGeneral = trim((string) ($payload['concepto_general'] ?? ''));
        $descripcionMm = $conceptoGeneral !== ''
            ? 7.0 + self::alturaTextoMm($conceptoGeneral, 88, 3.8)
            : 0.0;

        $totalesMm = 0.0;
        if ($payload['config_mostrar_totales'] ?? true) {
            $totalesMm = 32.0;
        }

        return [
            'encabezado' => $variant === 'tailwind' ? 36.0 : 38.0,
            'receptor' => $receptorMm,
            'descripcion_general' => $descripcionMm,
            'titulo_presupuesto' => 11.0,
            'tabla_conceptos' => $tablaMm,
            'totales' => $totalesMm,
            'terminos_y_observaciones' => $terminosMm,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, float>  $medidas
     */
    private static function estimarAlturaBloqueAtentamenteMm(array $payload, array $medidas): float
    {
        $lineas = PresupuestoPdf::lineasAtentamentePieUltimaPaginaDesdePayload($payload);
        if (count($lineas) === 0) {
            return 0.0;
        }

        $altura = 4.0;
        $trasTitulo = (float) $medidas['espacio_tras_titulo_atentamente_mm'];
        $tieneLineasTrasTitulo = count($lineas) > 1;

        foreach ($lineas as $i => $linea) {
            $role = (string) ($linea['role'] ?? 'info');
            $altura += match ($role) {
                'title' => 4.5,
                'name' => 4.8,
                default => 3.8,
            };
            if ($role === 'title' && $tieneLineasTrasTitulo && $i < count($lineas) - 1) {
                $altura += $trasTitulo;
            }
        }

        return $altura + 1.5;
    }

    private static function alturaTextoMm(string $text, float $charsPorLinea, float $alturaLineaMm): float
    {
        $text = trim($text);
        if ($text === '') {
            return 0.0;
        }

        $charsPorLinea = max(20.0, $charsPorLinea);
        $lineas = (int) ceil(mb_strlen($text) / $charsPorLinea);

        return max(1, $lineas) * $alturaLineaMm;
    }
}
