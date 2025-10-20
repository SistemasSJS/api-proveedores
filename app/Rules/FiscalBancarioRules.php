<?php

namespace App\Rules;

use Illuminate\Validation\Rule;

class FiscalBancarioRules
{
    public static function rfc(): array
    {
        return ['required', 'string', 'regex:/^[A-ZÑ&]{3,4}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[A-Z0-9]{2}[0-9A]$/'];
    }

    public static function clabe(): array
    {
        return ['required', 'string', 'regex:/^[0-9]{18}$/'];
    }

    public static function tarjeta(): array
    {
        return ['required', 'string', 'regex:/^[0-9]{16}$/'];
    }

    public static function cuenta(): array
    {
        return ['required', 'string', 'regex:/^[0-9]{10,11}$/'];
    }

    public static function codigoPostal(): array
    {
        return ['required', 'string', 'regex:/^[0-9]{5}$/'];
    }

    public static function cuentaBancaria(): array
    {
        return [
            'banco_clave' => ['required', 'string'],
            'banco_nombre' => ['required', 'string'],
            'alias' => ['required', 'string', 'min:3'],
            'titular_cuenta' => ['required', 'string', 'min:3'],
            'tipo_cuenta' => ['required', Rule::in(['clabe', 'tarjeta', 'cuenta'])],
            'campo_dependiente' => ['required', function ($attribute, $value, $fail) {
                $tipoCuenta = request()->input('tipo_cuenta');
                $rules = match ($tipoCuenta) {
                    'clabe' => self::clabe(),
                    'tarjeta' => self::tarjeta(),
                    'cuenta' => self::cuenta(),
                    default => ['required', 'string']
                };

                $validator = validator(['campo' => $value], ['campo' => $rules]);
                if ($validator->fails()) {
                    $fail($validator->errors()->first('campo'));
                }
            }],
            'referencia' => ['nullable', 'string'],
            'swift' => ['nullable', 'string', 'regex:/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/'],
            'sucursal' => ['nullable', 'string'],
            'preferida' => ['boolean'],
            'estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public static function datosFiscales(): array
    {
        return [
            'razon_social' => ['required', 'string', 'min:3'],
            'rfc' => self::rfc(),
            'regimen_fiscal_clave' => ['required', 'string'],
            'regimen_fiscal_nombre' => ['required', 'string'],
            'calle' => ['required', 'string'],
            'numero_exterior' => ['required', 'string'],
            'numero_interior' => ['nullable', 'string'],
            'colonia' => ['required', 'string'],
            'ciudad' => ['required', 'string'],
            'estado' => ['required', 'string'],
            'codigo_postal' => self::codigoPostal(),
            'pais' => ['required', 'string'],
        ];
    }
}
