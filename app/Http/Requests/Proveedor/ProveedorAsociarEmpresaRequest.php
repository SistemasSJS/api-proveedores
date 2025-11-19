<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorAsociarEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permitir que cualquier usuario pueda enviar este request
        return true;
    }

    public function rules(): array
    {
        return [
            'telefono' => ['required', 'string', 'min:10', 'max:10'],
            'empresa_construcc_id' => ['required', 'integer'],
            'empresa_construcc_nombre' => ['nullable', 'string', 'max:255'],
            'usuario_construcc_id' => ['required', 'integer'],
            'usuario_construcc_nombre' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener 10 dígitos.',
            'telefono.max' => 'El teléfono debe tener 10 dígitos.',
            'empresa_construcc_id.required' => 'El ID de la empresa constructora es obligatorio.',
            'empresa_construcc_id.exists' => 'La empresa constructora no existe.',
            'usuario_construcc_id.required' => 'El ID del usuario constructor es obligatorio.',
            'usuario_construcc_nombre.required' => 'El nombre del usuario constructor es obligatorio.',
        ];
    }
}
