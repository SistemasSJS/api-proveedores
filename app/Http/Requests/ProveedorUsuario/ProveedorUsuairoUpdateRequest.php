<?php

namespace App\Http\Requests\ProveedorUsuario;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="ProveedorUsuairoUpdateRequest",
 *     properties={
 *         @OA\Property(property="name", type="string", example="Juan Pérez"),
 *         @OA\Property(property="email", type="string", example="juan@example.com"),
 *         @OA\Property(property="password", type="string", format="password", nullable=true),
 *         @OA\Property(property="password_confirmation", type="string", format="password", nullable=true),
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
class ProveedorUsuairoUpdateRequest extends FormRequest
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
        $userId = $this->resolveRouteUserId();

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => [
                'sometimes',
                'integer',
                Rule::exists('roles', 'id')->whereIn(
                    'nombre',
                    config('proveedor_gestion_mvp.roles_asignables_empresa', ['SUPERVISOR', 'VENTAS', 'AUXILIAR'])
                ),
            ],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'telefono_codigo_pais' => ['sometimes', 'nullable', 'string', 'max:10'],

            // Relación pivot (solo si se envían) — no promover a PRINCIPAL desde gestión empresa
            'tipo_relacion' => ['sometimes', 'string', 'in:SECUNDARIO'],
            'activo' => ['sometimes', 'boolean'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:500'],

            // Foto opcional: si no se envía, no afecta la existente
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'El nombre debe ser texto.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'role_id.exists' => 'Solo se pueden asignar los roles Supervisor, Ventas o Auxiliar.',
            'tipo_relacion.in' => 'No se puede promover a usuario principal desde la gestión de la empresa.',
            'logo.image' => 'El archivo debe ser una imagen válida.',
            'logo.mimes' => 'La imagen debe estar en formato JPG, PNG o WEBP.',
            'logo.max' => 'La imagen no debe pesar más de 2MB.',
        ];
    }

    private function resolveRouteUserId(): int|string|null
    {
        $userParam = $this->route('user');

        if ($userParam instanceof User) {
            return $userParam->id;
        }

        return $userParam;
    }
}
