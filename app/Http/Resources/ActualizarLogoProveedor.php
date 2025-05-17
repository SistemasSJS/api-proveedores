<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActualizarLogoProveedor extends JsonResource
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '' => 'image|mimes:jpg,jpeg,png|max:2048|dimensions:min_width=200,min_height=200,max_width=1000,max_height=1000',
        ];
    }

    public function messages()
    {
        return [
            'logo.image' => 'El archivo debe ser una imagen válida.',
            'logo.mimes' => 'La imagen debe estar en formato JPG o PNG.',
            'logo.max' => 'La imagen no debe pesar más de 2MB.',
            'logo.dimensions' => 'La imagen debe tener entre 200x200px y 1000x1000px, y ser cuadrada (relación 1:1).',
        ];
    }
}
