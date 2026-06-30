<?php

namespace App\Support;

use App\Models\Presupuesto;
use App\Services\Presupuesto\PresupuestoThemeService;

/**
 * Encabezado de estampado para hojas de anexo PDF (inspirado en subencabezado compacto del presupuesto;
 * implementación propia para FPDI, sin modificar el page_script existente).
 */
final class PresupuestoPdfAnexoEstampado
{
    /**
     * @param  array{primary: array{0: int, 1: int, 2: int}, heading: array{0: int, 1: int, 2: int}, border: array{0: int, 1: int, 2: int}}  $palette
     */
    public static function aplicar(
        PresupuestoAnexoFpdi $pdf,
        Presupuesto $presupuesto,
        string $tituloAnexo,
        int $page,
        int $total
    ): void {
        $palette = self::paletteDesdePresupuesto($presupuesto);

        $pageWidth = $pdf->GetPageWidth();
        $marginX = min(25.0, max(8.0, $pageWidth * 0.08));
        $bandTop = 0.0;
        $bandHeight = 22.0;
        $rowCenterY = $bandTop + ($bandHeight / 2);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(0, $bandTop, $pageWidth, $bandHeight, 'F');

        $radius = 5.0;
        $circleGap = 2.5;
        $circleCx = $marginX + $radius;
        self::dibujarBadgePagina($pdf, $circleCx, $rowCenterY, $radius, $page, $total, $palette['primary']);

        $folio = (string) ($presupuesto->numero_presupuesto ?? '');
        $folioLabel = 'ANEXO';

        $pdf->SetFont('Helvetica', 'B', 10);
        $folioW = $folio !== '' ? $pdf->GetStringWidth($folio) : 0;
        $pdf->SetFont('Helvetica', '', 5.5);
        $labelW = $pdf->GetStringWidth($folioLabel);
        $rightColW = max($folioW, $labelW);
        $rightX = $pageWidth - $marginX - $rightColW;

        $textX = $circleCx + $radius + $circleGap;
        $textMaxW = max(20.0, $rightX - $textX - 3.0);

        $tituloRaw = trim($tituloAnexo !== '' ? $tituloAnexo : 'Anexo PDF');
        $titulo = self::textoPdf(mb_strtoupper($tituloRaw, 'UTF-8'));

        $pdf->SetFont('Helvetica', 'B', 7.5);
        self::aplicarColorRgb($pdf, 'text', $palette['heading']);
        $titleLineH = 4.0;
        $pdf->SetXY($textX, $rowCenterY - ($titleLineH / 2));
        $pdf->Cell($textMaxW, $titleLineH, self::truncar($titulo, 52), 0, 0, 'L');

        $rightBlockH = 9.0;
        $rightTop = $rowCenterY - ($rightBlockH / 2);
        $pdf->SetFont('Helvetica', '', 5.5);
        self::aplicarColorRgb($pdf, 'text', $palette['primary']);
        $pdf->SetXY($rightX, $rightTop);
        $pdf->Cell($rightColW, 3.0, $folioLabel, 0, 2, 'R');

        if ($folio !== '') {
            $pdf->SetFont('Helvetica', 'B', 10);
            self::aplicarColorRgb($pdf, 'text', $palette['primary']);
            $pdf->SetXY($rightX, $rightTop + 3.2);
            $pdf->Cell($rightColW, 4.5, $folio, 0, 0, 'R');
        }

        $lineY = $bandTop + $bandHeight - 1.0;
        self::aplicarColorRgb($pdf, 'draw', $palette['primary']);
        $pdf->SetLineWidth(0.45);
        $pdf->Line($marginX, $lineY, $pageWidth - $marginX, $lineY);
    }

    /**
     * @return array{primary: array{0: int, 1: int, 2: int}, heading: array{0: int, 1: int, 2: int}, border: array{0: int, 1: int, 2: int}}
     */
    private static function paletteDesdePresupuesto(Presupuesto $presupuesto): array
    {
        $service = app(PresupuestoThemeService::class);
        $themeKey = $service->resolveThemeKey($presupuesto->pdf_theme);
        $variables = $service->getTheme($themeKey)['variables'];

        return [
            'primary' => self::hexToRgb255((string) ($variables['color-primary'] ?? '#2563eb')),
            'heading' => self::hexToRgb255((string) ($variables['color-heading'] ?? '#1e293b')),
            'border' => self::hexToRgb255((string) ($variables['color-slate-200'] ?? '#e2e8f0')),
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function dibujarBadgePagina(
        PresupuestoAnexoFpdi $pdf,
        float $cx,
        float $rowCenterY,
        float $radius,
        int $page,
        int $total,
        array $primaryRgb
    ): void {
        $label = $page.'/'.$total;
        $fontSize = $total > 99 ? 5.5 : 6.5;
        $cy = $rowCenterY;

        $pdf->SetLineWidth(0.35);
        self::aplicarColorRgb($pdf, 'draw', $primaryRgb);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->circulo($cx, $cy, $radius, 'FD');

        $pdf->SetFont('Helvetica', 'B', $fontSize);
        self::aplicarColorRgb($pdf, 'text', $primaryRgb);
        $textWidth = $pdf->GetStringWidth($label);
        $pdf->SetXY($cx - ($textWidth / 2), $cy - ($fontSize / 2.6));
        $pdf->Cell($textWidth, 3.5, $label, 0, 0, 'C');
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function aplicarColorRgb(PresupuestoAnexoFpdi $pdf, string $mode, array $rgb): void
    {
        if ($mode === 'draw') {
            $pdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);

            return;
        }

        $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb255(string $hex): array
    {
        $normalized = PresupuestoPdf::hexColorToPdfRgb($hex);

        return [
            (int) round($normalized[0] * 255),
            (int) round($normalized[1] * 255),
            (int) round($normalized[2] * 255),
        ];
    }

    private static function truncar(string $text, int $maxLen): string
    {
        if (strlen($text) <= $maxLen) {
            return $text;
        }

        return substr($text, 0, max(0, $maxLen - 1)).'…';
    }

    private static function textoPdf(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        return is_string($converted) && $converted !== '' ? $converted : $text;
    }
}
