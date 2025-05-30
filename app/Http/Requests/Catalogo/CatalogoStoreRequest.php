<?php

namespace App\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;


/**
 * @OA\Schema(
 *     schema="CatalogoStoreRequest",
 *     required={"nombre", "descripcion", "proveedor_id"},
 *     @OA\Property(property="nombre", type="string", example="Catálogo de materiales"),
 *     @OA\Property(property="descripcion", type="string", example="Listado de productos para obra negra"),
 *     @OA\Property(property="proveedor_id", type="integer", example=1)
 * )
 */
class CatalogoStoreRequest extends FormRequest
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
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
        ];
    }
}
