<?php

namespace App\Http\Requests\ProveedorUsuario;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ProveedorUsuarioUpdateRelacionRequest",
 *     required={},
 *     properties={
 *         @OA\Property(property="tipo_relacion", type="string", enum={"PRINCIPAL", "SECUNDARIO"}, example="SECUNDARIO"),
 *         @OA\Property(property="activo", type="boolean", example=true),
 *         @OA\Property(property="observaciones", type="string", example="Usuario actualizado a principal")
 *     }
 * )
 */
class ProveedorUsuarioUpdateRelacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_relacion' => ['sometimes', 'string', 'in:PRINCIPAL,SECUNDARIO'],
            'activo' => ['sometimes', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_relacion.in' => 'El tipo de relación debe ser PRINCIPAL o SECUNDARIO.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 500 caracteres.',
        ];
    }
}
