<?php

namespace App\Http\Requests\ProveedorUsuario;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ProveedorUsuairoStoreRequest",
 *     required={"name","email","password"},
 *     properties={
 *
 *         @OA\Property(property="name", type="string", example="Juan Pérez"),
 *         @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
 *         @OA\Property(property="password", type="string", format="password", example="secret"),
 *         @OA\Property(property="is_main", type="boolean", example=false)
 *     }
 * )
 */
class ProveedorUsuairoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'email' => ['required', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // espera password_confirmation
            'role_id' => ['required', 'integer', 'exists:roles,id'],

            // Campos de la relación pivot
            'tipo_relacion' => ['sometimes', 'string', 'in:PRINCIPAL,SECUNDARIO'],
            'activo' => ['sometimes', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            // 'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
            'tipo_relacion.in' => 'El tipo de relación debe ser PRINCIPAL o SECUNDARIO.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 500 caracteres.',
        ];
    }
}
