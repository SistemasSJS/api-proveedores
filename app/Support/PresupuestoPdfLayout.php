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

    /** Respiro HTML tras salto de página (el margen @page :not(:first) reserva el subencabezado). */
    private const PADDING_HTML_TRAS_SALTO_PAGINA_MM = 2.0;

    /** Padding HTML legacy (cierre Atentamente en hoja nueva). */
    private const PADDING_BLOQUE_TRAS_SUBENCABEZADO_MM = 26.0;

    private const MARGEN_SEGURIDAD_MM = 3.0;

    /** Reserva inferior al paginar filas de la tabla (evita filas bajo el pie fijo). */
    private const RESERVA_PIE_FILA_TABLA_MM = 5.0;

    /** Pie fijo DomPDF: margen extra al decidir salto del bloque totales. */
    private const RESERVA_PIE_FIJO_TOTALES_MM = 14.0;

    /** Reserva inferior para el último concepto (pie fijo DomPDF). */
    private const RESERVA_PIE_ULTIMO_CONCEPTO_MM = 30.0;

    private const BUFFER_ALTURA_TOTALES_MM = 4.0;

    /**
     * Planifica saltos de página por bloque (tabla → totales → términos → Atte) y cierre Atentamente.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     salto_antes_totales: bool,
     *     salto_antes_terminos: bool,
     *     salto_antes_ultimo_concepto: bool,
     *     cierre_atentamente: array<string, mixed>
     * }
     */
    public static function calcularPaginacionBloquesPresupuesto(array $payload, string $variant = 'default'): array
    {
        $cierreVacio = [
            'espaciador_mm' => 0.0,
            'salto_pagina_antes' => false,
            'reserva_pie_html_mm' => 0.0,
            'altura_contenido_antes_cierre_mm' => 0.0,
            'altura_atentamente_mm' => 0.0,
            'altura_util_pagina_mm' => 0.0,
            'secciones_mm' => [],
        ];

        $medidas = self::medidasHojaMm($variant);
        $alturaUtil = $medidas['altura_util_mm'];
        $secciones = self::estimarSeccionesMm($payload, $variant);
        $mostrarAtte = PresupuestoPdf::debeMostrarBloqueAtentamenteDesdePayload($payload);
        $alturaAtentamente = $mostrarAtte
            ? self::estimarAlturaBloqueAtentamenteMm($payload, $medidas)
            : 0.0;
        $gapPie = $medidas['gap_atentamente_footer_mm'];
        $reservaPieHtmlMm = $mostrarAtte ? ($alturaAtentamente + $gapPie + 2.0) : 0.0;

        $sim = self::simularPaginacionPresupuesto(
            $payload,
            $variant,
            $alturaUtil,
            $reservaPieHtmlMm,
            $mostrarAtte,
        );

        $cierreAtentamente = $cierreVacio;
        if ($mostrarAtte) {
            $pageFin = $sim['page'];
            $yFin = $sim['y'];
            $capFin = self::capacidadPaginaMm($pageFin, $alturaUtil);
            $saltoAtte = ($yFin + $reservaPieHtmlMm + self::MARGEN_SEGURIDAD_MM) > $capFin;

            if ($saltoAtte) {
                $capAtte = self::capacidadPaginaMm($pageFin + 1, $alturaUtil) - self::PADDING_BLOQUE_TRAS_SUBENCABEZADO_MM;
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

            $cierreAtentamente = [
                'espaciador_mm' => round($espaciador, 2),
                'salto_pagina_antes' => $saltoAtte,
                'reserva_pie_html_mm' => round($reservaPieHtmlMm, 2),
                'altura_contenido_antes_cierre_mm' => round($sim['altura_total'], 2),
                'altura_atentamente_mm' => round($alturaAtentamente, 2),
                'altura_util_pagina_mm' => round($alturaUtil, 2),
                'secciones_mm' => $secciones,
            ];
        }

        return [
            'salto_antes_totales' => $sim['salto_antes_totales'],
            'salto_antes_terminos' => $sim['salto_antes_terminos'],
            'salto_antes_ultimo_concepto' => $sim['salto_antes_ultimo_concepto'],
            'cierre_atentamente' => $cierreAtentamente,
        ];
    }

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
        return self::calcularPaginacionBloquesPresupuesto($payload, $variant)['cierre_atentamente'];
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

    /**
     * Simula paginación: prefijo, tabla fila a fila, bloque totales indivisible, términos ítem a ítem
     * (reservando zona de Atentamente en el pie mientras fluyen los términos).
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     page: int,
     *     y: float,
     *     altura_total: float,
     *     salto_antes_totales: bool,
     *     salto_antes_terminos: bool,
     *     salto_antes_ultimo_concepto: bool
     * }
     */
    private static function simularPaginacionPresupuesto(
        array $payload,
        string $variant,
        float $alturaUtil,
        float $reservaAttePieMm,
        bool $mostrarAtte,
    ): array {
        $pageNum = 1;
        $y = 0.0;
        $alturaTotal = 0.0;
        $saltoAntesTotales = false;
        $saltoAntesTerminos = false;
        $saltoAntesUltimoConcepto = false;

        $restoEnPagina = static function (bool $reservarAtte) use (&$pageNum, &$y, $alturaUtil, $reservaAttePieMm, $mostrarAtte): float {
            $cap = self::capacidadPaginaMm($pageNum, $alturaUtil);
            $reserva = ($reservarAtte && $mostrarAtte) ? $reservaAttePieMm + self::MARGEN_SEGURIDAD_MM : 0.0;

            return max(0.0, $cap - $y - $reserva);
        };

        $consumir = static function (float $h, bool $evitarCorte = false, bool $reservarAtte = false) use (
            &$pageNum,
            &$y,
            &$alturaTotal,
            $restoEnPagina,
            $alturaUtil,
            $mostrarAtte,
            $reservaAttePieMm
        ): void {
            if ($h <= 0) {
                return;
            }
            $pendiente = $h;
            while ($pendiente > 0.01) {
                $resto = $restoEnPagina($reservarAtte);
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
                $cap = self::capacidadPaginaMm($pageNum, $alturaUtil);
                $reserva = ($reservarAtte && $mostrarAtte) ? $reservaAttePieMm + self::MARGEN_SEGURIDAD_MM : 0.0;
                $usable = max(1.0, $cap - $reserva);
                $alturaTotal += $usable;
                $pendiente -= $usable;
                $pageNum++;
                $y = 0.0;
            }
        };

        $consumirFilaTabla = static function (
            float $h,
            float $reservaPieMm,
            bool $registrarSaltoPagina = false,
        ) use (
            &$pageNum,
            &$y,
            &$alturaTotal,
            &$saltoAntesUltimoConcepto,
            $alturaUtil
        ): void {
            if ($h <= 0) {
                return;
            }
            $cap = self::capacidadPaginaMm($pageNum, $alturaUtil);
            $resto = $cap - $y - $reservaPieMm;
            if ($h > $resto && $y > 0.01) {
                if ($registrarSaltoPagina) {
                    $saltoAntesUltimoConcepto = true;
                }
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

        // --- Bloque 1: tabla de conceptos (filas pueden partir entre páginas) ---
        $conceptos = $payload['conceptos'] ?? [];
        $lineaTabla = $variant === 'tailwind' ? 6.8 : 5.5;
        $ultimoIndiceConcepto = self::indiceUltimoConceptoArray($conceptos);
        $consumir(self::THEAD_TABLA_MM);
        if (count($conceptos) === 0) {
            $consumirFilaTabla(10.0, self::RESERVA_PIE_FILA_TABLA_MM, false);
        } else {
            foreach ($conceptos as $indice => $concepto) {
                if (! is_array($concepto)) {
                    continue;
                }
                $esUltimo = $indice === $ultimoIndiceConcepto;
                $reservaPie = $esUltimo ? self::RESERVA_PIE_ULTIMO_CONCEPTO_MM : self::RESERVA_PIE_FILA_TABLA_MM;
                if (self::esConceptoParrafo($concepto)) {
                    $consumirFilaTabla(
                        PresupuestoParrafoPdf::alturaFilaMm(),
                        $reservaPie,
                        $esUltimo,
                    );
                } else {
                    $consumirFilaTabla($lineaTabla, $reservaPie, $esUltimo);
                }
            }
        }
        $consumir(6.0);

        // --- Bloque 2: totales (indivisible, independiente de la tabla) ---
        $alturaTotales = self::alturaBloqueTotalesMm($payload);
        if ($alturaTotales > 0) {
            $cap = self::capacidadPaginaMm($pageNum, $alturaUtil);
            $restoTotales = $cap - $y - self::RESERVA_PIE_FIJO_TOTALES_MM;
            $necesarioTotales = $alturaTotales + self::MARGEN_SEGURIDAD_MM;
            $ultimoEsParrafo = self::ultimoConceptoEsParrafo($conceptos);
            $sinEspacio = $necesarioTotales > $restoTotales && $y > 0.01;
            // Tras un párrafo al cierre de la tabla, DomPDF suele partir totales si van en la misma hoja.
            $saltoTrasParrafo = $ultimoEsParrafo && $y > 0.01;
            if ($sinEspacio || $saltoTrasParrafo) {
                $saltoAntesTotales = true;
                $pageNum++;
                $y = self::PADDING_HTML_TRAS_SALTO_PAGINA_MM;
            }
            $consumir($alturaTotales, true);
        }

        $fragmentos = self::fragmentosVerticalesTerminosMm($payload, $variant);

        // --- Bloque 3: términos (fragmentos indivisibles) ---
        if (count($fragmentos) > 0) {
            $hInicio = $fragmentos[0]['altura_mm'];
            if (count($fragmentos) > 1) {
                $hInicio += $fragmentos[1]['altura_mm'];
            }
            $restoTerminos = $restoEnPagina(true);
            if ($hInicio + self::MARGEN_SEGURIDAD_MM > $restoTerminos && $y > 0.01) {
                $saltoAntesTerminos = true;
                $pageNum++;
                $y = self::PADDING_HTML_TRAS_SALTO_PAGINA_MM;
            }
            foreach ($fragmentos as $fragmento) {
                $consumir($fragmento['altura_mm'], $fragmento['evitar_corte'], true);
            }
        }

        return [
            'page' => $pageNum,
            'y' => $y,
            'altura_total' => $alturaTotal,
            'salto_antes_totales' => $saltoAntesTotales,
            'salto_antes_terminos' => $saltoAntesTerminos,
            'salto_antes_ultimo_concepto' => $saltoAntesUltimoConcepto,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function alturaBloqueTotalesMm(array $payload): float
    {
        if (! ($payload['config_mostrar_totales'] ?? true)) {
            return 0.0;
        }

        $filas = 2;
        $pct = $payload['porcentaje_descuento'] ?? null;
        if ($pct !== null && (int) $pct > 0) {
            $filas++;
        }
        if ((bool) ($payload['con_iva'] ?? false)) {
            $filas++;
        }

        $tablaMm = 2.0 + ($filas * 5.2);
        $importeLetraMm = 14.0;
        $afterSpaceMm = 3.0;

        return $tablaMm + $importeLetraMm + $afterSpaceMm + self::BUFFER_ALTURA_TOTALES_MM;
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
        if (($concepto['tipo'] ?? 'concepto') === 'parrafo') {
            return true;
        }

        return mb_strtolower(trim((string) ($concepto['unidad'] ?? ''))) === 'párrafo';
    }

    /**
     * @param  list<mixed>  $conceptos
     */
    private static function indiceUltimoConceptoArray(array $conceptos): ?int
    {
        for ($i = count($conceptos) - 1; $i >= 0; $i--) {
            if (is_array($conceptos[$i])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  list<mixed>  $conceptos
     */
    private static function ultimoConceptoEsParrafo(array $conceptos): bool
    {
        if ($conceptos === []) {
            return false;
        }
        $ultimo = $conceptos[array_key_last($conceptos)];

        return is_array($ultimo) && self::esConceptoParrafo($ultimo);
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

        $totalesMm = self::alturaBloqueTotalesMm($payload);

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
