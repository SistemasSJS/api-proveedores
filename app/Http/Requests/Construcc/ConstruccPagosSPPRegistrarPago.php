<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Proveedor;

class ConstruccPagosSPPRegistrarPagoRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    /** @var Proveedor $proveedor */
    $proveedor = $this->route('proveedor');
    $rolDA = 3; // Asumiendo que el rol DA tiene ID 3

    /**
     * TODO: Este FormResquest aun no esta completo, es necesariuo revisar:
     *  - Las reglas de validacion
     *  - <IMPORTANTE> Rectificar los mensjaes de error personalizados que se retornan
     *  - Validar que las SPPs pertenezcan al proveedor y a la empresa de construccion
     *  - Cualquier otra validacion que se considere necesaria
     * 
     * 
     * TODO: Controlador que consume este FormRequest:
     *  - app/Http/Controllers/ConstruccPagosSPPController.php
     *  
     *  Es necesario readaptar el controlador por los cambios realizados en este FormRequest
     */

    return [
      'nivel_usuario' => ['required', 'numeric', Rule::in([3])],

      // File
      // datos de comprobante 
      'cuenta_bancaria_empresa_construcc_id' => ['nullable', 'numeric'],
      'fecha_hora_pago' => ['required', 'date_format:Y-m-d H:i:s'],
      'nombre_beneficiario' => ['required', 'string', 'max:255'],
      'clave_rastreo' => ['required', 'string', 'max:50'],
      'banco' => ['required', 'string', 'max:50'],

      // Datos basicos de pago
      'comprobante' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
      'empresa_id'    => 'required|integer',
      'proveedor_id' => 'required|integer',
      'usuario_id'     => 'required|integer',
      'usuario_nombre' => 'required|string|max:255',
      'total_pago' => 'required|numeric|min:0.01',

      // Solicitudes de pago
      'solicitudes_pago' => 'required|array|min:1',
      'solicitudes_pago.*.solicitud_id' => ['required', 'integer', Rule::exists('solicitudes_pago', 'id')->where('proveedor_id', $proveedor?->id)],
      'solicitudes_pago.*.monto_aplicado' => 'required|numeric|min:0.01',
      'solicitudes_pago.*.estado_pago' => ['required',  Rule::in(['aplicado', 'pendiente', 'rechazado', 'parcial', 'completado']),],
      'solicitudes_pago.*.notas' => 'nullable|string|max:500',

      // ========================
      // Opcionales extra (si luego los usas)
      // ========================
      'observaciones' => 'nullable|string|max:500',
    ];
  }

  public function messages(): array
  {
    return [
      'comprobante_pago.required' => 'El comprobante de pago es obligatorio.',
      'comprobante_pago.mimes'    => 'El comprobante debe ser PDF, JPG o PNG.',
      'comprobante_pago.max'      => 'El comprobante no debe superar los 10MB.',

      'fecha_pago.required'      => 'La fecha de pago es obligatoria.',
      'referencia_pago.required' => 'La referencia de pago es obligatoria.',
      'banco_pago.required'      => 'El banco es obligatorio.',

      'monto_total.required'     => 'El monto total es obligatorio.',
      'monto_total.min'          => 'El monto debe ser mayor a cero.',

      'solicitudes_pago.required' => 'Debes enviar al menos una solicitud de pago.',
      'solicitudes_pago.array'    => 'Las solicitudes de pago deben ser un arreglo.',

      'solicitudes_pago.*.solicitud_pago_id.exists'
      => 'Una o más solicitudes de pago no pertenecen a este proveedor.',

      'solicitudes_pago.*.monto_aplicado.required'
      => 'Cada solicitud debe tener un monto aplicado.',

      'solicitudes_pago.*.estado_pago.in'
      => 'El estado del pago no es válido.',
    ];
  }
}
