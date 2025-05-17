<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarLogoProveedor  extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                // // 'image',
                // 'max:2048', // KB
                // 'dimensions:min_width=100,min_height=100,max_width=1000,max_height=1000',
            ],
        ];
    }

    public function messages()
    {
        return [
            'logo.required' => 'El logo es requerido.',
            // 'logo.image' => 'El archivo debe ser una imagen válida.',
            // 'logo.mimes' => 'La imagen debe estar en formato JPG o PNG.',
            // 'logo.max' => 'La imagen no debe pesar más de 2MB.',
            // 'logo.dimensions' => 'La imagen debe tener entre 200x200px y 1000x1000px, y ser cuadrada (relación 1:1).',
        ];
    }
}
