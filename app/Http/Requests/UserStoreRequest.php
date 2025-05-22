<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UserStoreRequest",
 *     required={
 *        "name",
 *       "email",
 *      "password",
 *       "telefono",
 *      "role"
 *    },
 *    @OA\Property(property="name", type="string", maxLength=255, example="Juan Pérez"),
 *    @OA\Property(property="email", type="string", format="email", maxLength=255, example="juan.perez@example.com"),
 *    @OA\Property(property="password", type="string", format="password", example="contraseña123"),
 *    @OA\Property(property="telefono", type="string", example="123456789"),
 *    @OA\Property(property="role", type="string", example="user"),
 * )
 */

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Control de permisos por middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // espera password_confirmation
            'telefono' => ['nullable', 'string', 'max:15'],
            'role' => ['nullable', 'string', 'in:admin,user,editor'], // ejemplo roles
            'is_main' => ['nullable', 'boolean'], // ejemplo roles
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',
            'is_main.boolean' => 'El campo "is_main" debe ser verdadero o falso.',
            'is_main.boolean' => 'El campo "is_main" debe ser verdadero o falso.',
        ];
    }
}
