<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class FechaRequeridaValida implements Rule
{
    public function passes($attribute, $value)
    {
        $fecha = \Carbon\Carbon::parse($value);
        $ahora = now();

        // La fecha debe ser al menos 1 día después de hoy
        if ($fecha->lte($ahora->addDay())) {
            return false;
        }

        // No puede ser más de 6 meses en el futuro
        if ($fecha->gt($ahora->addMonths(6))) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'La fecha requerida debe ser entre mañana y 6 meses en el futuro.';
    }
}
