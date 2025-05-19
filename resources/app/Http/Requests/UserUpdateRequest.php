<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="UserUpdateRequest",
 *     required={"name", "email"},
 *     @OA\Property(property="name", type="string", example="Juan Pérez", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", example="juan.perez@example.com", maxLength=255),
 *     @OA\Property(property="password", type="string", format="password", example="contraseña123", maxLength=255),
 *     @OA\Property(property="telefono", type="string", example="123456789", maxLength=255),
 *     @OA\Property(property="role", type="string", example="user")
 * )
 */
class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user'); // asumiendo que la ruta pasa el id del usuario como 'user'

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'], // opcional, solo si cambia
            'telefono' => ['nullable', 'string', 'max:15'],
            'role' => ['nullable', 'string', 'in:admin,user,editor'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',
            'role.in' => 'El rol seleccionado no es válido.',
        ];
    }
}
