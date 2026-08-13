<?php

namespace App\Http\Requests\User;

use App\Enums\EstadoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="UserStoreRequest",
 *     required={"name","email","password","role_id"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Juan Pérez"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="juan.perez@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="contraseña123"),
 *     @OA\Property(property="password_confirmation", type="string", format="password"),
 *     @OA\Property(property="telefono", type="string", example="123456789"),
 *     @OA\Property(property="role_id", type="integer", example=2),
 *     @OA\Property(property="status", type="string", example="registrado")
 * )
 */
class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Control de permisos por middleware
    }

    protected function prepareForValidation(): void
    {
        // Front admin histórico envía `role` (id); normalizar a role_id
        if ($this->filled('role') && ! $this->filled('role_id')) {
            $this->merge(['role_id' => $this->input('role')]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'telefono_codigo_pais' => ['nullable', 'string', 'max:10'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status' => ['sometimes', 'string', Rule::in(EstadoUsuario::values())],
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
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
            'status.in' => 'El estado del usuario no es válido.',
            'telefono.max' => 'El teléfono no debe exceder los 20 caracteres.',
        ];
    }
}
