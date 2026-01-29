<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Proveedor;

class ConstruccPagosSPPRegistrarPago extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Si luego quieres validar roles, aquí es el lugar
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    /** @var Proveedor $proveedor */
    $proveedor = $this->route('proveedor');

    return [
      // ========================
      // Datos del pago
      // ========================
      'comprobante_pago' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
      'fecha_pago'       => 'required|date',
      'referencia_pago'  => 'required|string|max:100',

      // ========================
      // Datos bancarios (origen)
      // ========================
      'banco_pago'                    => 'nullable|string|max:100',
      'cuenta_origen'                 => 'nullable|string|max:50',
      'tipo_cuenta_origen'            => 'nullable|string|max:50',
      'clabe_interbancaria_origen'    => 'nullable|string|max:18',

      // ========================
      // Datos bancarios (destino)
      // ========================
      'banco_destino'                 => 'nullable|string|max:100',
      'cuenta_destino'                => 'nullable|string|max:50',
      'tipo_cuenta_destino'           => 'nullable|string|max:50',
      'clabe_interbancaria_destino'   => 'nullable|string|max:18',
      'titular_cuenta_destino'        => 'nullable|string|max:255',

      // ========================
      // Montos
      // ========================
      'monto_total' => 'required|numeric|min:0.01',

      // ========================
      // Metadatos
      // ========================
      'observaciones'            => 'nullable|string',
      'usuario_registro_id'      => 'nullable|integer',
      'usuario_registro_nombre'  => 'nullable|string|max:255',
      'empresa_construcc_id'     => 'nullable|integer',

      // ========================
      // Solicitudes de pago
      // ========================
      'solicitudes_pago' => 'required|array|min:1',
      'solicitudes_pago.*.solicitud_pago_id' => ['required', 'integer', Rule::exists('solicitudes_pago', 'id')->where('proveedor_id', $proveedor?->id)],
      'solicitudes_pago.*.monto_aplicado' => 'required|numeric|min:0.01',
      'solicitudes_pago.*.estado_pago' => ['required', Rule::in(['aplicado',  'pendiente',  'rechazado',  'parcial',  'completado',])],
      'solicitudes_pago.*.notas' => 'nullable|string',
    ];
  }

  /**
   * Custom messages for validation errors.
   */
  public function messages(): array
  {
    return [
      'comprobante_pago.required' => 'El comprobante de pago es obligatorio.',
      'comprobante_pago.mimes'    => 'El comprobante debe ser PDF, JPG o PNG.',
      'comprobante_pago.max'      => 'El comprobante no debe superar los 10MB.',

      'fecha_pago.required'       => 'La fecha de pago es obligatoria.',
      'referencia_pago.required'  => 'La referencia de pago es obligatoria.',

      'monto_total.required'      => 'El monto total es obligatorio.',
      'monto_total.min'           => 'El monto debe ser mayor a cero.',

      'solicitudes_pago.required' => 'Debe especificar al menos una solicitud de pago.',
      'solicitudes_pago.array'    => 'El formato de solicitudes de pago es inválido.',

      'solicitudes_pago.*.solicitud_pago_id.exists'
      => 'Una o más solicitudes de pago no existen o no pertenecen a este proveedor.',

      'solicitudes_pago.*.monto_aplicado.required'
      => 'El monto aplicado es obligatorio para cada solicitud.',

      'solicitudes_pago.*.estado_pago.in'
      => 'El estado del pago no es válido.',
    ];
  }
}
