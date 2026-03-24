<?php

namespace App\Support;

/**
 * Resuelve la vista Blade usada para generar el PDF del presupuesto (DomPDF).
 */
class PresupuestoPdfTemplate
{
    public static function viewName(): string
    {
        return config('presupuestos.pdf_template', 'tailwind') === 'classic'
            ? 'presupuestos.pdf'
            : 'presupuestos.pdf-tailwind';
    }
}
