<?php

namespace App\Http\Requests\SolicitudPago;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarEstadoPagadoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id'                         => 'required|exists:solicitudes_pago,id',
            'ruta_archivo_comprobante_pago' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'estado_solicitud'           => 'required|in:pagado',
        ];
    }

    public function messages()
    {
        return [
            'id.required'                          => 'El ID de la solicitud es obligatorio',
            'id.exists'                            => 'La solicitud de pago no existe',
            'ruta_archivo_comprobante_pago.required' => 'El comprobante de pago es obligatorio',
            'ruta_archivo_comprobante_pago.file'    => 'Debe cargar un archivo válido',
            'ruta_archivo_comprobante_pago.mimes'   => 'El comprobante debe ser PDF o imagen (JPG, PNG)',
            'ruta_archivo_comprobante_pago.max'     => 'El archivo no debe superar los 10MB',
            'estado_solicitud.required'            => 'El estado es obligatorio',
            'estado_solicitud.in'                  => 'El estado debe ser "pagado"',
        ];
    }
}
