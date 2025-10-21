<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AuthUpdateFotoPerfilRequest",
 *     required={
 *         "foto_perfil"
 *     },
 *
 *     @OA\Property(property="foto_perfil", type="blob")
 * )
 */
class AuthUpdateFotoPerfilRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'foto_perfil' => 'image|mimes:jpg,jpeg,png|max:2048|dimensions:min_width=200,min_height=200,max_width=1000,max_height=1000',
        ];
    }

    public function messages()
    {
        return [
            'foto_perfil.image' => 'El archivo debe ser una imagen válida.',
            'foto_perfil.mimes' => 'La imagen debe estar en formato JPG o PNG.',
            'foto_perfil.max' => 'La imagen no debe pesar más de 2MB.',
            'foto_perfil.dimensions' => 'La imagen debe tener entre 200x200px y 1000x1000px, y ser cuadrada (relación 1:1).',
        ];
    }
}
