@php
    use App\Services\Presupuesto\PresupuestoThemeService;
    use App\Support\PresupuestoPdf;

    $margenMm = (float) ($margenMm ?? 25.4);
    $footerHeightMm = (float) ($footerHeightMm ?? 25.4);
    $footerBottomMm = 6.0;
    $gapAtentamenteFooterMm = (float) ($gapAtentamenteFooterMm ?? 2.8);
    $espacioTrasTituloAtentamenteMm = (float) ($espacioTrasTituloAtentamenteMm ?? (2 * 2.8));
    $pdfVariant = (string) ($pdfVariant ?? 'tailwind');

    $pdfThemeVariables = null;
    if ($pdfVariant === 'tailwind' && ! empty($pdfThemeKey ?? null)) {
        $pdfThemeVariables = app(PresupuestoThemeService::class)
            ->getTheme((string) $pdfThemeKey)['variables'];
    }

    $atentamentePieLineas = PresupuestoPdf::lineasAtentamentePieUltimaPaginaDesdePayload($presupuesto);
    $atentamenteEstilos = PresupuestoPdf::estilosAtentamentePiePorRol($pdfVariant, $pdfThemeVariables);
    $atentamentePieX = (int) round($margenMm * 2.834645669);

    $mmToPt = static fn (float $mm): int => (int) round($mm * 2.834645669);

    $atentamenteEnPieDePagina = (bool) ($atentamenteEnPieDePagina ?? false);

    $atentamentePageScript = '';
    if ($atentamenteEnPieDePagina && count($atentamentePieLineas) > 0) {
        $blockHeightPt = 6;
        $espacioTrasTituloPt = $mmToPt($espacioTrasTituloAtentamenteMm);
        $tieneLineasTrasTitulo = count($atentamentePieLineas) > 1;
        foreach ($atentamentePieLineas as $linea) {
            $role = $linea['role'] ?? 'info';
            $blockHeightPt += (int) ceil($atentamenteEstilos[$role]['lh'] ?? 9.0);
        }
        if ($tieneLineasTrasTitulo) {
            $blockHeightPt += $espacioTrasTituloPt;
        }
        $footerReservePt = $mmToPt($footerBottomMm + $footerHeightMm + $gapAtentamenteFooterMm);
        $estilosJsonEsc = addcslashes(json_encode($atentamenteEstilos, JSON_UNESCAPED_UNICODE), "\\'");

        $jsonEsc = addcslashes(
            json_encode($atentamentePieLineas, JSON_UNESCAPED_UNICODE),
            "\\'"
        );
        $atentamentePageScript = <<<SCRIPT
if (\$PAGE_NUM != \$PAGE_COUNT) {
    return;
}
\$lineas = json_decode('{$jsonEsc}', true);
\$estilos = json_decode('{$estilosJsonEsc}', true);
if (!is_array(\$lineas) || !is_array(\$estilos)) {
    return;
}
\$x = {$atentamentePieX};
\$pageHeight = \$pdf->get_height();
\$y = \$pageHeight - {$footerReservePt} - {$blockHeightPt};
foreach (\$lineas as \$i => \$item) {
    if (!is_array(\$item)) {
        continue;
    }
    \$text = isset(\$item['text']) ? (string) \$item['text'] : '';
    if (\$text === '') {
        continue;
    }
    \$role = isset(\$item['role']) ? (string) \$item['role'] : 'info';
    if (\$role === 'title') {
        \$text = mb_strtoupper(\$text, 'UTF-8');
    }
    if (!isset(\$estilos[\$role])) {
        \$role = 'info';
    }
    \$cfg = \$estilos[\$role];
    \$size = (float) (\$cfg['size'] ?? 7);
    \$lh = (float) (\$cfg['lh'] ?? 9);
    \$color = \$cfg['color'] ?? array(0.067, 0.094, 0.153);
    if (!is_array(\$color) || count(\$color) < 3) {
        \$color = array(0.067, 0.094, 0.153);
    }
    \$bold = !empty(\$cfg['bold']);
    \$font = \$bold
        ? \$fontMetrics->getFont('DejaVu Sans', 'bold')
        : \$fontMetrics->getFont('DejaVu Sans', 'normal');
    \$pdf->text(\$x, \$y, \$text, \$font, \$size, array((float) \$color[0], (float) \$color[1], (float) \$color[2]));
    \$y += \$lh;
    if (\$role === 'title' && \$i < count(\$lineas) - 1) {
        \$y += {$espacioTrasTituloPt};
    }
}
SCRIPT;
    }

    $atentamentePageScriptExport = var_export($atentamentePageScript, true);
@endphp
<script type="text/php">
if (isset($pdf) && isset($fontMetrics)) {
    $font = $fontMetrics->getFont("DejaVu Sans", "normal");
    $size = 7;
    $sample = "Página 99 de 99";
    $width = $fontMetrics->getTextWidth($sample, $font, $size);
    $x = ($pdf->get_width() - $width) / 2 + 5;
    $pdf->page_text($x, $pdf->get_height() - 45, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, $size);

    $atentamentePieScript = {!! $atentamentePageScriptExport !!};
    if ($atentamentePieScript !== '') {
        $pdf->page_script($atentamentePieScript);
    }
}
</script>
