<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudPagoConfirmarPagoRequest extends FormRequest
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

            'monto_pagado' => ['required', 'numeric', 'min:0.01'],

            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            'observaciones' => ['nullable', 'string', 'max:500'],

            'cuenta_bancaria_empresa_construcc_id' => ['nullable', 'numeric'],

            // Datos del pago
            'fecha' => [
                'required',
                'string',
                'regex:/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/'
            ],

            'hora' => [
                'required',
                'string',
                'regex:/^([01]\d|2[0-3]):[0-5]\d$/'
            ],

            'nombre_beneficiario' => ['required', 'string', 'max:255'],
            'clave_rastreo' => ['required', 'string', 'max:50'],
            'banco' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            // Rol
            'rol.required' => 'Debe especificar el rol que realiza la confirmación.',
            'rol.in' => 'Solo el rol DA (Dirección Administrativa) puede confirmar pagos.',

            // Monto
            'monto_pagado.required' => 'Debe ingresar el monto pagado.',
            'monto_pagado.numeric' => 'El monto pagado debe ser un número válido.',
            'monto_pagado.min' => 'El monto pagado debe ser mayor a cero.',

            // Comprobante
            'comprobante.required' => 'Debe adjuntar un comprobante de pago.',
            'comprobante.file' => 'El comprobante debe ser un archivo válido.',
            'comprobante.mimes' => 'El comprobante debe ser un archivo PDF o imagen (JPG, JPEG, PNG).',
            'comprobante.max' => 'El comprobante no debe superar los 5 MB.',

            // Fecha
            'fecha.required' => 'Debe indicar la fecha del pago.',
            'fecha.regex' => 'La fecha debe tener el formato AAAA-MM-DD.',

            // Hora
            'hora.required' => 'Debe indicar la hora del pago.',
            'hora.regex' => 'La hora debe tener el formato de 24 horas HH:MM.',

            // Beneficiario
            'nombre_beneficiario.required' => 'Debe indicar el nombre del beneficiario.',
            'nombre_beneficiario.max' => 'El nombre del beneficiario no debe exceder los 255 caracteres.',

            // Clave rastreo
            'clave_rastreo.required' => 'Debe indicar la clave de rastreo del pago.',
            'clave_rastreo.max' => 'La clave de rastreo no debe exceder los 50 caracteres.',

            // Banco
            'banco.required' => 'Debe indicar el banco del pago.',
            'banco.max' => 'El banco no debe exceder los 50 caracteres.',

            // Cuenta bancaria
            'cuenta_bancaria_empresa_construcc_id.numeric' =>
            'El identificador de la cuenta bancaria debe ser numérico.',

            // Observaciones
            'observaciones.max' => 'Las observaciones no deben exceder los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rol' => 'rol del usuario',
            'monto_pagado' => 'monto pagado',
            'comprobante' => 'archivo comprobante',
            'fecha' => 'fecha de pago',
            'hora' => 'hora de pago',
            'nombre_beneficiario' => 'nombre del beneficiario',
            'clave_rastreo' => 'clave de rastreo',
            'banco' => 'banco',
            'cuenta_bancaria_empresa_construcc_id' => 'cuenta bancaria de la empresa',
            'observaciones' => 'comentarios u observaciones',
        ];
    }
}
