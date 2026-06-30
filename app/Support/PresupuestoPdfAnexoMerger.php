<?php

namespace App\Support;

use App\Models\Presupuesto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

/**
 * Une el PDF del presupuesto (DomPDF) con anexos PDF y estampa título + hoja i/n.
 */
final class PresupuestoPdfAnexoMerger
{
    public static function unirSiHayAnexos(Presupuesto $presupuesto, string $mainBinary): string
    {
        $presupuesto->loadMissing('anexosPdf');

        /** @var Collection<int, \App\Models\PresupuestoAnexoPdf> $anexos */
        $anexos = $presupuesto->anexosPdf;
        if ($anexos->isEmpty()) {
            return $mainBinary;
        }

        $tmpMain = tempnam(sys_get_temp_dir(), 'pres_main_');
        if ($tmpMain === false) {
            return $mainBinary;
        }

        file_put_contents($tmpMain, $mainBinary);

        try {
            return self::mergeDesdePrincipal($tmpMain, $anexos);
        } catch (\Throwable $e) {
            Log::warning('No fue posible unir anexos PDF al presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $mainBinary;
        } finally {
            if (is_file($tmpMain)) {
                @unlink($tmpMain);
            }
        }
    }

    /**
     * @param  Collection<int, \App\Models\PresupuestoAnexoPdf>  $anexos
     */
    private static function mergeDesdePrincipal(string $mainAbsPath, Collection $anexos): string
    {
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);

        $mainPages = $pdf->setSourceFile($mainAbsPath);
        for ($i = 1; $i <= $mainPages; $i++) {
            self::importarPaginaSinEstampado($pdf, $mainAbsPath, $i);
        }

        foreach ($anexos as $anexo) {
            $abs = PresupuestoAnexoPdfArchivoResponse::archivoAbsoluto($anexo->archivo_path);
            if ($abs === null || ! is_file($abs)) {
                continue;
            }

            $total = max(1, (int) ($anexo->paginas ?: 1));
            PresupuestoAnexoPdfStorage::prepararParaImportacionFpdi($abs);
            try {
                $anexoPages = $pdf->setSourceFile($abs);
                $total = max(1, (int) $anexoPages);
            } catch (\Throwable) {
                continue;
            }

            $titulo = self::textoPdf((string) $anexo->titulo);

            for ($page = 1; $page <= $total; $page++) {
                self::importarPaginaSinEstampado($pdf, $abs, $page);
                self::estamparAnexoPdf($pdf, $titulo, $page, $total);
            }
        }

        return $pdf->Output('S');
    }

    private static function importarPaginaSinEstampado(Fpdi $pdf, string $sourcePath, int $pageNumber): void
    {
        $pdf->setSourceFile($sourcePath);
        $tpl = $pdf->importPage($pageNumber);
        $size = $pdf->getTemplateSize($tpl);
        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
        $pdf->useTemplate($tpl);
    }

    private static function estamparAnexoPdf(Fpdi $pdf, string $titulo, int $page, int $total): void
    {
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(25, 40, 70);

        $bandTop = 6.0;
        $bandHeight = 14.0;
        $pageWidth = $pdf->GetPageWidth();
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(0, 0, $pageWidth, $bandTop + $bandHeight, 'F');

        $pdf->SetXY(8, $bandTop);
        $pdf->Cell(0, 5, $titulo !== '' ? $titulo : 'Anexo', 0, 1, 'L');

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetX(8);
        $pdf->Cell(0, 4, 'Hoja '.$page.' de '.$total, 0, 1, 'L');
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
