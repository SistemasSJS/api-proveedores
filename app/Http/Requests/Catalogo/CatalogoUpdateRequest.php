<?php

namespace App\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="CatalogoUpdateRequest",
 *     @OA\Property(property="nombre", type="string", example="Catálogo actualizado"),
 *     @OA\Property(property="descripcion", type="string", example="Nueva descripción del catálogo"),
 *     @OA\Property(property="proveedor_id", type="integer", example=2),
 *     @OA\Property(
 *         property="photo_path",
 *         type="string",
 *         format="binary",
 *         description="Imagen del usuario en formato JPEG, PNG o WEBP (máximo 2MB)"
 *     )
 * )
 */
class CatalogoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'nombre' => ['sometimes', 'string', 'max:60'],
            'descripcion' => ['sometimes', 'email', 'max:255'],
            'proveedor_id' => ['sometimes', 'integer', 'exists:proveedores,id'], // espera password_confirmation
            'photo_path' => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'descripcion.required' => 'El nombre es obligatorio.',
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
            'photo_path.image' => 'La foto debe ser una imagen válida.',
            'photo_path.mimes' => 'La foto debe ser de tipo jpeg, png, jpg o webp.',
            'photo_path.max' => 'La imagen no debe superar los 2MB.',
        ];
    }
}
