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

            // Datos del pago
            'fecha' => ['required', 'string'],
            'hora' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/'],
            'nombre_beneficiario' => ['required', 'string', 'max:255'],
            'clave_rastreo' => ['required', 'string', 'max:50'],
            'banco' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            // Rol
            'rol.required' => 'Debe especificar el rol que realiza la acción.',
            'rol.in' => 'Solo el rol DA (Dirección Administrativa) puede actualizar el comprobante.',

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

            // Clave de rastreo
            'clave_rastreo.required' => 'Debe indicar la clave de rastreo del pago.',
            'clave_rastreo.max' => 'La clave de rastreo no debe exceder los 50 caracteres.',

            // Banco
            'banco.required' => 'Debe indicar el banco del pago.',
            'banco.max' => 'El banco no debe exceder los 50 caracteres.',

            // Observaciones
            'observaciones.max' => 'Las observaciones no deben exceder los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rol' => 'rol del usuario',
            'comprobante' => 'archivo comprobante',
            'fecha' => 'fecha de pago',
            'hora' => 'hora de pago',
            'nombre_beneficiario' => 'nombre del beneficiario',
            'clave_rastreo' => 'clave de rastreo',
            'banco' => 'banco',
            'observaciones' => 'comentarios u observaciones',
        ];
    }
}
