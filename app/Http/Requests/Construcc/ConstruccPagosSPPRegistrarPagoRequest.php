<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConstruccPagosSPPRegistrarPagoRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    $NIVEL_USUARIO_CONSTRUCC_DA = 3; // Rol DA

    return [

      // =========================
      // Comprobante de pago
      // =========================
      'comprobante_pago' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

      // =========================
      // Datos básicos del pago
      // =========================
      'empresa_id'     => ['required', 'integer'],
      'proveedor_id'   => ['required', 'integer'],
      'usuario_id'     => ['required', 'integer'],
      'usuario_nombre' => ['required', 'string', 'max:255'],
      'monto_total'    => ['required', 'numeric', 'min:0.01'],
      'nivel_usuario'  => ['required', 'integer', Rule::in([$NIVEL_USUARIO_CONSTRUCC_DA])],

      // =========================
      // Datos obligatorios del comprobante
      // =========================
      'fecha_pago'      => ['required', 'date'],
      'referencia_pago' => ['required', 'string', 'max:50'],

      // =========================
      // Información extraída del comprobante (OCR)
      // =========================
      'info_comprobante'                    => ['nullable', 'array'],
      'info_comprobante.monto'              => ['nullable', 'numeric', 'min:0.01'],
      'info_comprobante.fecha'              => ['nullable', 'date'],
      'info_comprobante.hora'               => ['nullable', 'string'],
      'info_comprobante.referencia'         => ['nullable', 'string', 'max:50'],
      'info_comprobante.bancoDestino'       => ['nullable', 'string', 'max:255'],
      'info_comprobante.nombreBeneficiario' => ['nullable', 'string', 'max:255'],
      'info_comprobante.claveRastreo'       => ['nullable', 'string', 'max:255'],

      // =========================
      // Solicitudes de pago
      // =========================
      'solicitudes'                   => ['required', 'array', 'min:1'],
      'solicitudes.*.solicitud_id'    => ['required', 'integer', 'exists:solicitudes_pago,id'],
      'solicitudes.*.monto_pago'      => ['required', 'numeric', 'min:0.01'],
    ];
  }

  public function messages(): array
  {
    return [

      'comprobante_pago.required' => 'Debes subir el comprobante de pago.',
      'comprobante_pago.file'     => 'El comprobante debe ser un archivo válido.',
      'comprobante_pago.mimes'    => 'El comprobante debe ser PDF, JPG o PNG.',
      'comprobante_pago.max'      => 'El comprobante no debe pesar más de 10 MB.',

      'empresa_id.required'     => 'No se recibió la empresa.',
      'proveedor_id.required'   => 'No se recibió el proveedor.',
      'usuario_id.required'     => 'No se pudo identificar al usuario.',
      'usuario_nombre.required' => 'El nombre del usuario es obligatorio.',

      'monto_total.required' => 'Debes indicar el monto total del pago.',
      'monto_total.numeric'  => 'El monto total debe ser numérico.',
      'monto_total.min'      => 'El monto total debe ser mayor a cero.',

      'nivel_usuario.in' => 'No tienes permisos para registrar este pago.',

      'fecha_pago.required' => 'La fecha de pago es obligatoria.',
      'referencia_pago.required' => 'La referencia del pago es obligatoria.',

      'solicitudes.required' => 'Debes seleccionar al menos una solicitud.',
      'solicitudes.*.solicitud_id.exists' => 'Una o más solicitudes no son válidas.',
      'solicitudes.*.monto_pago.required' => 'Falta el monto de una solicitud.',
    ];
  }

  public function attributes(): array
  {
    return [
      'comprobante_pago' => 'comprobante de pago',
      'monto_total'      => 'monto total del pago',
      'fecha_pago'       => 'fecha de pago',
      'referencia_pago'  => 'referencia de pago',
    ];
  }
}
