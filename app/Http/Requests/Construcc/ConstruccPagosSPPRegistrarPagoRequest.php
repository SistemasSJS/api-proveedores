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
      'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

      // =========================
      // Datos básicos del pago
      // =========================
      'empresa_id'     => ['required', 'integer'],
      'proveedor_id'   => ['required', 'integer'],
      'usuario_id'     => ['required', 'integer'],
      'usuario_nombre' => ['required', 'string', 'max:255'],
      'total_pago'     => ['required', 'numeric', 'min:0.01'],
      'nivel_usuario'  => ['required', 'integer', Rule::in([$NIVEL_USUARIO_CONSTRUCC_DA])],

      // =========================
      // Información del comprobante
      // =========================
      'info_comprobante'             => ['required', 'array'],
      'info_comprobante.monto'       => ['required', 'numeric', 'min:0.01'],
      'info_comprobante.fecha'       => ['required', 'string'],
      'info_comprobante.hora'        => ['required', 'string'],
      'info_comprobante.concepto'    => ['required', 'string', 'max:50'],
      'info_comprobante.referencia'  => ['required', 'string', 'max:50'],
      'info_comprobante.folio'       => ['required', 'string', 'max:255'],

      // =========================
      // Solicitudes de pago
      // =========================
      'solicitudes'                => ['required', 'array', 'min:1'],
      // 'solicitudes.*.solicitud_id' => ['required', 'integer', Rule::exists('solicitudes_pago', 'id')],
      'solicitudes.*.solicitud_id' => 'required|exists:solicitudes_pago,id',
      'solicitudes.*.monto_pago'   => ['required', 'numeric'],
    ];
  }

  public function messages(): array
  {
    return [

      // Comprobante
      'comprobante.required' => 'Debes subir el comprobante de pago.',
      'comprobante.file'     => 'El comprobante debe ser un archivo válido.',
      'comprobante.mimes'    => 'El comprobante debe ser un archivo PDF, JPG o PNG.',
      'comprobante.max'      => 'El comprobante no debe pesar más de 10 MB.',

      // Datos básicos
      'empresa_id.required'   => 'No se recibió la empresa que realiza el pago.',
      'proveedor_id.required' => 'No se recibió el proveedor al que se aplica el pago.',
      'usuario_id.required'   => 'No se pudo identificar al usuario que realiza el registro.',
      'usuario_nombre.required' => 'El nombre del usuario es obligatorio.',

      'total_pago.required' => 'Debes indicar el monto total del pago.',
      'total_pago.numeric'  => 'El monto total del pago debe ser un número válido.',
      'total_pago.min'      => 'El monto total del pago debe ser mayor a cero.',

      'nivel_usuario.required' => 'No se pudo validar el nivel de acceso del usuario.',
      'nivel_usuario.in'       => 'No tienes permisos para registrar este pago.',

      // Info comprobante
      'info_comprobante.required' => 'No se pudo obtener la información del comprobante.',
      'info_comprobante.array'    => 'La información del comprobante no tiene el formato correcto.',

      'info_comprobante.monto.required' => 'El monto del comprobante es obligatorio.',
      'info_comprobante.monto.numeric'  => 'El monto del comprobante debe ser un número válido.',
      'info_comprobante.monto.min'      => 'El monto del comprobante debe ser mayor a cero.',

      'info_comprobante.fecha.required' => 'La fecha del comprobante es obligatoria.',
      'info_comprobante.hora.required'  => 'La hora del comprobante es obligatoria.',

      'info_comprobante.concepto.required' => 'El concepto del pago es obligatorio.',
      'info_comprobante.concepto.max'      => 'El concepto no debe superar los 50 caracteres.',

      'info_comprobante.referencia.required' => 'La referencia del pago es obligatoria.',
      'info_comprobante.referencia.max'      => 'La referencia no debe superar los 50 caracteres.',

      'info_comprobante.folio.required' => 'El folio del comprobante es obligatorio.',
      'info_comprobante.folio.max'      => 'El folio no debe superar los 255 caracteres.',

      // Solicitudes
      'solicitudes.required' => 'Debes seleccionar al menos una solicitud de pago.',
      'solicitudes.array'    => 'Las solicitudes de pago deben enviarse en formato de lista.',
      'solicitudes.min'      => 'Debes seleccionar al menos una solicitud de pago.',
      'solicitudes.*.solicitud_id.required' => 'Falta el identificador de una de las solicitudes de pago.',
      'solicitudes.*.solicitud_id.exists'   => 'Una o más solicitudes de pago no pertenecen a este proveedor.',
      'solicitudes.*.monto_pago.required'   => 'Debes indicar el monto a aplicar en cada solicitud.',
      'solicitudes.*.monto_pago.numeric'    => 'El monto aplicado en una solicitud debe ser un número válido.',
      'solicitudes.*.monto_pago.min'        => 'El monto aplicado debe ser mayor a cero.',
    ];
  }

  public function attributes(): array
  {
    return [
      'comprobante' => 'comprobante de pago',

      'empresa_id' => 'empresa',
      'proveedor_id' => 'proveedor',
      'usuario_id' => 'usuario',
      'usuario_nombre' => 'nombre del usuario',
      'total_pago' => 'monto total del pago',
      'nivel_usuario' => 'nivel de acceso del usuario',

      'info_comprobante' => 'información del comprobante',
      'info_comprobante.monto' => 'monto del comprobante',
      'info_comprobante.fecha' => 'fecha del comprobante',
      'info_comprobante.hora' => 'hora del comprobante',
      'info_comprobante.concepto' => 'concepto del pago',
      'info_comprobante.referencia' => 'referencia del pago',
      'info_comprobante.folio' => 'folio del comprobante',

      'solicitudes' => 'solicitudes de pago',
      'solicitudes.*.solicitud_id' => 'solicitud de pago',
      'solicitudes.*.monto_pago' => 'monto aplicado',
    ];
  }
}
