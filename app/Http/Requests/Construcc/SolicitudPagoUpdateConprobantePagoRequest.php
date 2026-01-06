<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudPagoUpdateConprobantePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real se valida por rol en el controller
        return true;
    }

    public function rules(): array
    {
        return [
            'rol' => ['required', 'string', Rule::in(['DA'])],
            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rol.required' => 'Debe especificar el rol que realiza la acción.',
            'rol.in' => 'Solo el rol DA (Dirección Administrativa) puede actualizar el comprobante.',

            'comprobante.required' => 'Debe adjuntar un comprobante de pago.',
            'comprobante.file' => 'El comprobante debe ser un archivo válido.',
            'comprobante.mimes' => 'El comprobante debe ser un archivo PDF o imagen (JPG, JPEG, PNG).',
            'comprobante.max' => 'El comprobante no debe superar los 5 MB.',

            'observaciones.max' => 'Las observaciones no deben exceder los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rol' => 'rol del usuario',
            'comprobante' => 'archivo comprobante',
            'observaciones' => 'comentarios u observaciones',
        ];
    }
}
