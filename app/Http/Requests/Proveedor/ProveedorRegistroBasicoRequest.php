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
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'token' => ['required', 'string'],
            'empresa_construcc_id' => ['nullable', 'integer'],
            'empresa_construcc_nombre' => ['nullable', 'string', 'max:255'],
            'usuario_construcc_id' => ['nullable', 'integer'],
            'usuario_construcc_nombre' => ['nullable', 'string', 'max:255'],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'nombre_comercial.max' => 'El nombre comercial no debe exceder los 255 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'token.required' => 'El token de invitación es obligatorio.',
        ];
    }
}
