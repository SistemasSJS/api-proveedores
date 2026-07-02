<?php

namespace App\Support;

/**
 * Estimación de alturas (mm) del cuerpo presupuesto para alinear «Atentamente» al pie de la última hoja de la sección 1.
 */
final class PresupuestoPdfLayout
{
    private const ALTURA_HOJA_LETRA_MM = 279.4;

    /** Reserva vertical del subencabezado compacto (page_script) en páginas 2+. */
    private const SUBENCABEZADO_CONTINUACION_MM = 24.0;

    private const THEAD_TABLA_MM = 6.0;

    /** Padding superior del contenedor tras salto antes de Atentamente (pdf-pagina-con-subencabezado). */
    private const PADDING_SALTO_ATENTAMENTE_MM = 23.0;

    private const MARGEN_SEGURIDAD_MM = 3.0;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     espaciador_mm: float,
     *     salto_pagina_antes: bool,
     *     reserva_pie_html_mm: float,
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
            'reserva_pie_html_mm' => 0.0,
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

        $alturaAtentamente = self::estimarAlturaBloqueAtentamenteMm($payload, $medidas);
        $reservaPieHtmlMm = $alturaAtentamente + $gapPie + 2.0;

        $flujo = self::simularFlujoHastaFinTerminos($payload, $variant, $alturaUtil);
        $pageFin = $flujo['page'];
        $yFin = $flujo['y'];
        $alturaAntesCierre = $flujo['altura_total'];

        $capFin = self::capacidadPaginaMm($pageFin, $alturaUtil);
        $salto = ($yFin + $reservaPieHtmlMm + self::MARGEN_SEGURIDAD_MM) > $capFin;

        if ($salto) {
            $capAtte = self::capacidadPaginaMm($pageFin + 1, $alturaUtil) - self::PADDING_SALTO_ATENTAMENTE_MM;
            $espacioLibre = max(0.0, $capAtte - $reservaPieHtmlMm);
            $espaciador = max(2.0, $espacioLibre - self::MARGEN_SEGURIDAD_MM);
        } else {
            $espacioLibre = max(0.0, $capFin - $yFin - $reservaPieHtmlMm);
            $espaciador = max(2.0, $espacioLibre);
        }

        $espaciadorMaximoMm = max(2.0, $alturaUtil - $reservaPieHtmlMm - self::MARGEN_SEGURIDAD_MM);
        if ($espaciador > $espaciadorMaximoMm) {
            $espaciador = $espaciadorMaximoMm;
        }

        return [
            'espaciador_mm' => round($espaciador, 2),
            'salto_pagina_antes' => $salto,
            'reserva_pie_html_mm' => round($reservaPieHtmlMm, 2),
            'altura_contenido_antes_cierre_mm' => round($alturaAntesCierre, 2),
            'altura_atentamente_mm' => round($alturaAtentamente, 2),
            'altura_util_pagina_mm' => round($alturaUtil, 2),
            'secciones_mm' => $secciones,
        ];
    }

    /**
     * Simula paginación vertical: bloques fijos, tabla fila a fila (con subencabezado pág. 2+)
     * y términos ítem a ítem (sin partir cada enunciado).
     *
     * @param  array<string, mixed>  $payload
     * @return array{page: int, y: float, altura_total: float}
     */
    private static function simularFlujoHastaFinTerminos(array $payload, string $variant, float $alturaUtil): array
    {
        $pageNum = 1;
        $y = 0.0;
        $alturaTotal = 0.0;

        $consumir = static function (float $h, bool $evitarCorte = false) use (&$pageNum, &$y, &$alturaTotal, $alturaUtil): void {
            if ($h <= 0) {
                return;
            }
            $pendiente = $h;
            while ($pendiente > 0.01) {
                $cap = self::capacidadPaginaMm($pageNum, $alturaUtil);
                $resto = $cap - $y;
                if ($evitarCorte && $pendiente > $resto && $y > 0.01) {
                    $pageNum++;
                    $y = 0.0;

                    continue;
                }
                if ($pendiente <= $resto + 0.01) {
                    $y += $pendiente;
                    $alturaTotal += $pendiente;
                    $pendiente = 0.0;

                    continue;
                }
                if ($y > 0.01) {
                    $alturaTotal += $resto;
                    $pendiente -= $resto;
                    $pageNum++;
                    $y = 0.0;

                    continue;
                }
                $alturaTotal += $cap;
                $pendiente -= $cap;
                $pageNum++;
                $y = 0.0;
            }
        };

        $consumirFilaTabla = static function (float $h) use (&$pageNum, &$y, &$alturaTotal, $alturaUtil): void {
            if ($h <= 0) {
                return;
            }
            $resto = self::capacidadPaginaMm($pageNum, $alturaUtil) - $y;
            if ($h > $resto && $y > 0.01) {
                $pageNum++;
                $y = self::THEAD_TABLA_MM;
                $alturaTotal += self::THEAD_TABLA_MM;
            }
            $y += $h;
            $alturaTotal += $h;
        };

        $secciones = self::estimarSeccionesMm($payload, $variant);

        $consumir((float) ($secciones['encabezado'] ?? 0));
        $consumir((float) ($secciones['receptor'] ?? 0));
        if (($secciones['descripcion_general'] ?? 0) > 0) {
            $consumir(3.0);
            $consumir((float) $secciones['descripcion_general']);
        }
        $consumir(4.0);
        $consumir((float) ($secciones['titulo_presupuesto'] ?? 0));
        $consumir(3.0);

        $conceptos = $payload['conceptos'] ?? [];
        $lineaTabla = $variant === 'tailwind' ? 6.8 : 5.5;
        $consumir(self::THEAD_TABLA_MM);
        if (count($conceptos) === 0) {
            $consumirFilaTabla(10.0);
        } else {
            foreach ($conceptos as $concepto) {
                if (! is_array($concepto)) {
                    continue;
                }
                if (self::esConceptoParrafo($concepto)) {
                    $consumirFilaTabla(PresupuestoParrafoPdf::alturaFilaMm());
                } else {
                    $consumirFilaTabla($lineaTabla);
                }
            }
        }

        $consumir(3.0);
        if (($payload['config_mostrar_totales'] ?? true) && ($secciones['totales'] ?? 0) > 0) {
            $consumir((float) $secciones['totales'], true);
        }

        foreach (self::fragmentosVerticalesTerminosMm($payload, $variant) as $fragmento) {
            $consumir($fragmento['altura_mm'], $fragmento['evitar_corte']);
        }

        return [
            'page' => $pageNum,
            'y' => $y,
            'altura_total' => $alturaTotal,
        ];
    }

    /**
     * Fragmentos verticales de términos / observaciones (un ítem = un bloque indivisible).
     *
     * @param  array<string, mixed>  $payload
     * @return list<array{altura_mm: float, evitar_corte: bool}>
     */
    private static function fragmentosVerticalesTerminosMm(array $payload, string $variant): array
    {
        $terminos = $payload['terminos_enunciados'] ?? [];
        $validaciones = $payload['validaciones_enunciados'] ?? [];
        $observaciones = $payload['observaciones_enunciados'] ?? [];
        $lineaTermino = 4.2;
        $fragmentos = [];

        $hayAlguno = count($terminos) > 0 || count($validaciones) > 0 || count($observaciones) > 0;
        if (! $hayAlguno) {
            return $fragmentos;
        }

        $fragmentos[] = ['altura_mm' => 4.0, 'evitar_corte' => true];

        if (count($terminos) > 0) {
            $fragmentos[] = ['altura_mm' => 6.5, 'evitar_corte' => true];
            foreach ($terminos as $texto) {
                $fragmentos[] = [
                    'altura_mm' => self::alturaTextoMm((string) $texto, 95, $lineaTermino) + 0.6,
                    'evitar_corte' => true,
                ];
            }
        }
        if (count($validaciones) > 0) {
            $fragmentos[] = ['altura_mm' => 5.0, 'evitar_corte' => true];
            foreach ($validaciones as $item) {
                $fragmentos[] = [
                    'altura_mm' => self::alturaTextoMm((string) $item, 95, $lineaTermino) + 0.6,
                    'evitar_corte' => true,
                ];
            }
        }
        if (count($observaciones) > 0) {
            $fragmentos[] = ['altura_mm' => 5.0, 'evitar_corte' => true];
            foreach ($observaciones as $obs) {
                $fragmentos[] = [
                    'altura_mm' => self::alturaTextoMm((string) $obs, 95, $lineaTermino) + 0.6,
                    'evitar_corte' => true,
                ];
            }
        }

        if ($variant === 'tailwind' && count($fragmentos) > 1) {
            $fragmentos[0]['altura_mm'] = 3.0;
        }

        return $fragmentos;
    }

    private static function capacidadPaginaMm(int $pageNum, float $alturaUtil): float
    {
        if ($pageNum <= 1) {
            return $alturaUtil;
        }

        return max(100.0, $alturaUtil - self::SUBENCABEZADO_CONTINUACION_MM);
    }

    /**
     * @param  array<string, mixed>  $concepto
     */
    private static function esConceptoParrafo(array $concepto): bool
    {
        return PresupuestoParrafoPdf::esLineaParrafo($concepto);
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

        $lineaTabla = $variant === 'tailwind' ? 6.8 : 5.5;
        $lineaTermino = 4.2;

        $tablaMm = 6.0;
        foreach ($conceptos as $concepto) {
            if (! is_array($concepto)) {
                continue;
            }
            if (self::esConceptoParrafo($concepto)) {
                $tablaMm += PresupuestoParrafoPdf::alturaFilaMm();
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
