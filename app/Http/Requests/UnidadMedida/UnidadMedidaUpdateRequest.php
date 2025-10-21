<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnidadMedidaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave' => ['required', 'string', 'max:10'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'clave' => 'clave',
            'nombre' => 'nombre de unidad',
            'descripcion' => 'descripción',
            'activo' => 'estado activo',
        ];
    }
}
