<?php

namespace App\Http\Requests\Categoria;


use Illuminate\Foundation\Http\FormRequest;


/**
 * @OA\Schema(
 *     schema="CategoriaStoreRequest",
 *     required={"nombre", "descripcion"},
 *     @OA\Property(property="nombre", type="string", example="Catálogo de materiales"),
 *     @OA\Property(property="descripcion", type="string", example="Listado de productos para obra negra"),
 *     @OA\Property(property="categoria_padre_id", type="integer", example=1)
 * )
 */
class CategoriaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60'],
            'descripcion' => ['required', 'string', 'max:255'],
            'categoria_padre_id' => ['nulleable', 'integer', 'exists:categorias,id'],
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
