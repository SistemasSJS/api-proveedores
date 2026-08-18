<?php

namespace App\Support;

use App\Models\PresupuestoConcepto;

/**
 * Texto y altura fija de líneas tipo párrafo en el PDF del presupuesto (~5 renglones).
 */
final class PresupuestoParrafoPdf
{
    public const ALTURA_FILA_MM = 22.0;

    public const CHARS_POR_LINEA = 120;

    public const MAX_LINEAS = 5;

    public const DESCRIPCION_MAX = PresupuestoConcepto::DESCRIPCION_PARRAFO_MAX;

    public static function alturaFilaMm(): float
    {
        return self::ALTURA_FILA_MM;
    }

    /**
     * Normaliza texto de párrafo (sin saltos de línea; espacios colapsados).
     */
    public static function normalizarTexto(string $text): string
    {
        $result = preg_replace('/\R/u', ' ', $text) ?? $text;
        $result = preg_replace('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', '', $result) ?? $result;
        $result = preg_replace('/ +/u', ' ', $result) ?? $result;

        return trim($result);
    }

    /**
     * Texto listo para guardar o mostrar en PDF (normalizado y acotado al bloque impreso).
     */
    public static function sanitizarTexto(string $text): string
    {
        $normalizado = self::normalizarTexto($text);
        if ($normalizado === '') {
            return '';
        }

        if (mb_strlen($normalizado) <= self::DESCRIPCION_MAX) {
            return $normalizado;
        }

        return mb_substr($normalizado, 0, self::DESCRIPCION_MAX);
    }

    /**
     * @param  array<string, mixed>  $concepto
     */
    public static function esLineaParrafo(array $concepto): bool
    {
        if (($concepto['tipo'] ?? PresupuestoConcepto::TIPO_CONCEPTO) === PresupuestoConcepto::TIPO_PARRAFO) {
            return true;
        }

        return mb_strtolower(trim((string) ($concepto['unidad'] ?? ''))) === 'párrafo';
    }

    /**
     * Descripción para celdas del PDF (mismo criterio que almacenamiento).
     */
    public static function descripcionParaPdf(array $concepto): string
    {
        return self::sanitizarTexto((string) ($concepto['descripcion'] ?? ''));
    }
}
