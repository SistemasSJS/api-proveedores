<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorLineaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Aquí puedes aplicar lógica de autorización si es necesario
    }

    public function rules(): array
    {
        return [
            'nombre'    => ['sometimes', 'string', 'max:100'],
            'marca_id'  => ['sometimes', 'exists:marcas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.string'     => 'El nombre debe ser una cadena de texto.',
            'nombre.max'        => 'El nombre no debe exceder los 100 caracteres.',
            'marca_id.exists'   => 'La marca seleccionada no es válida.',
        ];
    }
}
