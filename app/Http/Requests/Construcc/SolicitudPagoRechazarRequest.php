<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudPagoRechazarRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Todos los roles DT, PC, SI, DA, DG pueden rechazar según reglas
        return true;
    }

    public function rules(): array
    {
        return [
            'rol' => ['required', 'string', Rule::in(['DG', 'DT', 'PC', 'SI', 'DA'])],
            'motivo_rechazo' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rol.required' => 'Debe indicar el rol que rechaza la solicitud.',
            'rol.in' => 'El rol debe ser DG, DT, PC, SI o DA.',

            'motivo_rechazo.required' => 'Debe especificar el motivo del rechazo.',
            'motivo_rechazo.string' => 'El motivo del rechazo debe ser un texto válido.',
            'motivo_rechazo.max' => 'El motivo del rechazo no puede exceder los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rol' => 'rol que rechaza',
            'motivo_rechazo' => 'motivo del rechazo',
        ];
    }
}
