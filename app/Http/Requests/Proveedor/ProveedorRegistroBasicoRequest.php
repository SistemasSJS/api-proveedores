<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorRegistroBasicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permitir que cualquier usuario pueda enviar este request
        return true;
    }

    public function rules(): array
    {
        return [
            'empresa' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('proveedores', 'email')],
            'telefono' => ['required', 'string', 'min:10', 'max:15'],
        ];
    }

    public function messages(): array
    {
        return [
            'empresa.required' => 'El nombre de la empresa es obligatorio.',
            'empresa.string' => 'El nombre de la empresa debe ser una cadena de texto.',
            'empresa.max' => 'El nombre de la empresa no debe exceder los 255 caracteres.',

            'alias.string' => 'El alias de la empresa debe ser una cadena de texto.',
            'alias.max' => 'El alias de la empresa no debe exceder los 255 caracteres.',

            'razon_social.required' => 'La razón social es obligatoria.',
            'razon_social.string' => 'La razón social debe ser una cadena de texto.',
            'razon_social.max' => 'La razón social no debe exceder los 255 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.min' => 'El teléfono debe tener al menos 10 caracteres.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',
        ];
    }
}
