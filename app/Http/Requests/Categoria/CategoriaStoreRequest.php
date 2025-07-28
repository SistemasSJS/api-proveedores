<?php

namespace App\Http\Requests\Categoria;

use App\Models\Categoria;
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
        $proveedorId = $this->route('proveedor')?->id;

        return [
            'nombre' => ['required', 'string', 'max:60'],
            'descripcion' => ['required', 'string', 'max:255'],
            'categoria_padre_id' => [
                'nullable',
                'integer',
                'exists:categorias,id',
                function ($attribute, $value, $fail) use ($proveedorId) {
                    if (!Categoria::where('id', $value)
                        ->where('proveedor_id', $proveedorId)
                        ->where('nivel', '<', 2) // No permitir más de 2 niveles
                        ->exists()) {
                        $fail('La categoría padre no es válida o no pertenece a este proveedor.');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'categoria_padre_id.exists' => 'La categoría seleccionada no es válida.',
        ];
    }
}
