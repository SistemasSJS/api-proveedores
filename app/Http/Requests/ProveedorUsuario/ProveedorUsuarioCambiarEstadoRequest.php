<?php

namespace App\Http\Requests\ProveedorUsuario;

use App\Enums\EstadoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="ProveedorUsuarioCambiarEstadoRequest",
 *     required={"estado"},
 *     properties={
 *         @OA\Property(property="estado", type="string", enum={"registrado", "verificado", "suspendido", "bloqueado"}, example="verificado"),
 *         @OA\Property(property="observaciones", type="string", example="Usuario verificado tras completar documentación")
 *     }
 * )
 */
class ProveedorUsuarioCambiarEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::in(EstadoUsuario::values())],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser: registrado, verificado, suspendido o bloqueado.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 500 caracteres.',
        ];
    }
}
