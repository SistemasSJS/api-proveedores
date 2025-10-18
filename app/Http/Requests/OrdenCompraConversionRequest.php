<?php

namespace App\Http\Requests;

use App\Models\OrdenCompra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdenCompraConversionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'orden_compra_id' => [
                'required',
                'integer',
                'exists:ordenes_compra,id',
                function ($attribute, $value, $fail) {
                    $ordenCompra = OrdenCompra::find($value);
                    if ($ordenCompra && $ordenCompra->proveedor_id !== $this->route('proveedor')->id) {
                        $fail('La orden de compra no pertenece a este proveedor.');
                    }
                }
            ],
            'monto_total' => 'required|numeric|min:0.01',
            'descripcion_concepto' => 'nullable|string|max:500',
            'residente' => 'nullable|string|max:255',
            'sucursal_id' => 'nullable|integer|exists:sucursales,id',
            'cotizacion_id' => 'nullable|integer|exists:cotizaciones,id',
            'notas_vinculacion' => 'nullable|string|max:1000',
            
            // Cuentas bancarias opcionales
            'cuentas_bancarias' => 'nullable|array',
            'cuentas_bancarias.*.cuenta_bancaria_id' => 'required|integer|exists:cuentas_bancarias,id',
            'cuentas_bancarias.*.datos_especificos' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'orden_compra_id.exists' => 'La orden de compra especificada no existe.',
            'monto_total.required' => 'El monto total es obligatorio.',
            'monto_total.min' => 'El monto total debe ser mayor a 0.',
            'descripcion_concepto.max' => 'La descripción no puede exceder 500 caracteres.',
            'cuentas_bancarias.*.cuenta_bancaria_id.exists' => 'Una de las cuentas bancarias especificadas no existe.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->orden_compra_id || !$this->monto_total) {
                return;
            }

            $ordenCompra = OrdenCompra::find($this->orden_compra_id);
            
            if (!$ordenCompra) {
                return;
            }

            // Validar que la OC esté aprobada
            if ($ordenCompra->estado !== \App\Enums\EstadoOrdenCompra::APROBADA) {
                $validator->errors()->add('orden_compra_id', 'La orden de compra debe estar aprobada para generar solicitudes de pago.');
            }

            // Validar que haya monto disponible
            $montoDisponible = $ordenCompra->getMontoDisponible();
            if ($montoDisponible <= 0) {
                $validator->errors()->add('orden_compra_id', 'La orden de compra no tiene monto disponible.');
            } elseif ($this->monto_total > $montoDisponible) {
                $validator->errors()->add('monto_total', "El monto solicitado excede el disponible en la orden de compra (disponible: $montoDisponible).");
            }

            // Validar cuentas bancarias pertenecen al proveedor
            if ($this->cuentas_bancarias) {
                $proveedorId = $this->route('proveedor')->id;
                foreach ($this->cuentas_bancarias as $index => $cuentaData) {
                    if (isset($cuentaData['cuenta_bancaria_id'])) {
                        $cuenta = \App\Models\CuentaBancaria::find($cuentaData['cuenta_bancaria_id']);
                        if ($cuenta && $cuenta->proveedor_id !== $proveedorId) {
                            $validator->errors()->add("cuentas_bancarias.{$index}.cuenta_bancaria_id", 'La cuenta bancaria no pertenece a este proveedor.');
                        }
                    }
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'orden_compra_id' => 'orden de compra',
            'monto_total' => 'monto total',
            'descripcion_concepto' => 'descripción del concepto',
            'residente' => 'residente',
            'notas_vinculacion' => 'notas de vinculación',
        ];
    }
}
