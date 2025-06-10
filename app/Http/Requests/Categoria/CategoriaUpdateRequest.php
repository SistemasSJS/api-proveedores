<?php

namespace App\Http\Requests\Categoria;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="CategoriaUpdateRequest",
 *     required={"nombre", "descripcion"},
 *     @OA\Property(property="nombre", type="string", example="Catálogo actualizado"),
 *     @OA\Property(property="descripcion", type="string", example="Nueva descripción del catálogo"),
 *     @OA\Property(property="categoria_padre_id", type="integer", example=2),
 * )
 */
class CategoriaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:60'],
            'descripcion' => ['sometimes', 'string', 'max:255'],
            'categoria_padre_id' => ['sometimes', 'integer', 'exists:categorias,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'categoria_padre_id.exists' => 'La categroia seleccionado no es válido.',
        ];
    }
}
