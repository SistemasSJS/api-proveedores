<?php

namespace App\Http\Requests\ProveedorUsuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="ProveedorUsuairoStoreRequest",
 *     required={"name","email","password","role_id"},
 *     properties={
 *         @OA\Property(property="name", type="string", example="Juan Pérez"),
 *         @OA\Property(property="email", type="string", example="juan@example.com"),
 *         @OA\Property(property="password", type="string", format="password"),
 *         @OA\Property(property="password_confirmation", type="string", format="password"),
 *         @OA\Property(property="role_id", type="integer", example=2),
 *         @OA\Property(property="telefono", type="string", nullable=true),
 *         @OA\Property(property="telefono_codigo_pais", type="string", nullable=true),
 *         @OA\Property(property="tipo_relacion", type="string", enum={"PRINCIPAL","SECUNDARIO"}),
 *         @OA\Property(property="activo", type="boolean"),
 *         @OA\Property(property="observaciones", type="string", nullable=true),
 *         @OA\Property(property="logo", type="string", format="binary", nullable=true)
 *     }
 * )
 */
class ProveedorUsuairoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->whereIn(
                    'nombre',
                    config('proveedor_gestion_mvp.roles_asignables_empresa', ['SUPERVISOR', 'VENTAS', 'AUXILIAR'])
                ),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'telefono_codigo_pais' => ['nullable', 'string', 'max:10'],

            // Relación pivot — desde gestión empresa MVP siempre SECUNDARIO (forzado en controller)
            'tipo_relacion' => ['sometimes', 'string', 'in:SECUNDARIO'],
            'activo' => ['sometimes', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:500'],

            // Foto opcional en el create
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'Solo se pueden asignar los roles Supervisor, Ventas o Auxiliar.',
            'tipo_relacion.in' => 'Desde la gestión de la empresa solo se pueden crear usuarios secundarios.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 500 caracteres.',
            'logo.image' => 'El archivo debe ser una imagen válida.',
            'logo.mimes' => 'La imagen debe estar en formato JPG, PNG o WEBP.',
            'logo.max' => 'La imagen no debe pesar más de 2MB.',
        ];
    }
}
