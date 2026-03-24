<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plantilla Blade para PDF de presupuesto
    |--------------------------------------------------------------------------
    |
    | classic  — plantilla original (presupuestos.pdf)
    | tailwind — plantilla alternativa con estilo tipo Tailwind (slate/indigo)
    |
    */
    'pdf_template' => env('PRESUPUESTO_PDF_TEMPLATE', 'tailwind'),

];
