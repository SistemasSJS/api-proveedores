<?php

namespace App\Http\Requests\Presupuesto;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePresupuestoAnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:40'],
            'descripcion' => ['nullable', 'string', 'max:100'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'orden' => ['nullable', 'integer', 'min:1'],
            'archivo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'El título del anexo es obligatorio.',
            'titulo.string' => 'El título del anexo debe ser texto.',
            'titulo.max' => 'El título del anexo no debe exceder 40 caracteres.',
            'descripcion.string' => 'La descripción del anexo debe ser texto.',
            'descripcion.max' => 'La descripción del anexo no debe exceder 100 caracteres.',
            'precio.numeric' => 'El precio del anexo debe ser numérico.',
            'precio.min' => 'El precio del anexo no puede ser menor a 0.',
            'orden.integer' => 'El orden del anexo debe ser un número entero.',
            'orden.min' => 'El orden del anexo debe ser mayor a 0.',
            'archivo.image' => 'El archivo del anexo debe ser una imagen válida.',
            'archivo.mimes' => 'La imagen del anexo debe estar en formato JPG, JPEG, PNG o WEBP.',
            'archivo.max' => 'La imagen del anexo no debe superar 5 MB.',
        ];
    }
}
