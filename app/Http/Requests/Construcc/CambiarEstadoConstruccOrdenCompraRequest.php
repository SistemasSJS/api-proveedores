<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoConstruccOrdenCompraRequest extends FormRequest
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
            'estado' => 'required|string|in:pendiente,aprobada,rechazada,completada,cancelada',
            'observaciones' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'estado' => 'estado',
            'observaciones' => 'observaciones',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'El estado es requerido.',
            'estado.in' => 'El estado seleccionado no es válido. Los valores permitidos son: pendiente, aprobada, rechazada, completada, cancelada.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 1000 caracteres.',
        ];
    }
}