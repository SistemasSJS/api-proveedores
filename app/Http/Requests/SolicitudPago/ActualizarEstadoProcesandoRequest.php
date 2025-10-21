<?php

namespace App\Http\Requests\SolicitudPago;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarEstadoProcesandoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:solicitudes_pago,id',
            'estado_solicitud' => 'required|in:procesando',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'El ID de la solicitud es obligatorio',
            'id.exists' => 'La solicitud de pago no existe',
            'estado_solicitud.required' => 'El estado es obligatorio',
            'estado_solicitud.in' => 'El estado debe ser "procesando"',
        ];
    }
}
