<?php

namespace App\Http\Requests\Presupuesto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la creación de un presupuesto básico con sus conceptos.
 */
class StorePresupuestoRequest extends FormRequest
{
    /**
     * Determina si el usuario puede realizar esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para almacenar presupuesto.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero_presupuesto' => 'nullable|string|max:255',
            'proveedor_id' => 'required|exists:proveedores,id',
            'empresa_receptora_id' => 'nullable|exists:proveedores,id',
            'empresa_receptora_nombre' => 'nullable|string|max:255|required_without:empresa_receptora_id',
            'empresa_receptora_rfc' => 'nullable|string|max:20',
            'empresa_receptora_direccion' => 'nullable|string',
            'empresa_receptora_telefono' => 'nullable|string|max:30',
            'empresa_receptora_correo' => 'nullable|email|max:255',
            'fecha_emision' => 'required|date',
            'concepto_general' => 'required|string',
            'con_iva' => 'nullable|boolean',
            'iva_porcentaje' => 'nullable|numeric|min:0|max:100',
            'condiciones' => 'nullable|array',
            'observaciones' => 'nullable|string',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.descripcion' => 'required|string',
            'conceptos.*.cantidad' => 'required|numeric|min:0.0001',
            'conceptos.*.unidad' => 'required|string|max:50',
            'conceptos.*.precio_unitario' => 'required|numeric|min:0',
        ];
    }

    /**
     * Mensajes personalizados para validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_presupuesto.string' => 'El número de presupuesto debe ser texto.',
            'numero_presupuesto.max' => 'El número de presupuesto no debe exceder 255 caracteres.',
            'proveedor_id.required' => 'El proveedor emisor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor emisor seleccionado no existe.',
            'empresa_receptora_id.exists' => 'La empresa receptora seleccionada no existe.',
            'empresa_receptora_nombre.required_without' => 'El nombre de la empresa receptora es obligatorio cuando no se envía empresa_receptora_id.',
            'empresa_receptora_nombre.string' => 'El nombre de empresa receptora debe ser texto.',
            'empresa_receptora_nombre.max' => 'El nombre de empresa receptora no debe exceder 255 caracteres.',
            'empresa_receptora_rfc.string' => 'El RFC de empresa receptora debe ser texto.',
            'empresa_receptora_rfc.max' => 'El RFC de empresa receptora no debe exceder 20 caracteres.',
            'empresa_receptora_direccion.string' => 'La dirección de empresa receptora debe ser texto.',
            'empresa_receptora_telefono.string' => 'El teléfono de empresa receptora debe ser texto.',
            'empresa_receptora_telefono.max' => 'El teléfono de empresa receptora no debe exceder 30 caracteres.',
            'empresa_receptora_correo.email' => 'El correo de empresa receptora debe ser válido.',
            'empresa_receptora_correo.max' => 'El correo de empresa receptora no debe exceder 255 caracteres.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_emision.date' => 'La fecha de emisión debe tener un formato válido.',
            'concepto_general.required' => 'El concepto general es obligatorio.',
            'concepto_general.string' => 'El concepto general debe ser texto.',
            'con_iva.boolean' => 'El indicador con IVA debe ser verdadero o falso.',
            'iva_porcentaje.numeric' => 'El porcentaje de IVA debe ser numérico.',
            'iva_porcentaje.min' => 'El porcentaje de IVA no puede ser menor a 0.',
            'iva_porcentaje.max' => 'El porcentaje de IVA no puede ser mayor a 100.',
            'condiciones.array' => 'Las condiciones deben enviarse como arreglo.',
            'observaciones.string' => 'Las observaciones deben ser texto.',
            'conceptos.required' => 'Debe registrar al menos un concepto.',
            'conceptos.array' => 'Los conceptos deben enviarse como arreglo.',
            'conceptos.min' => 'Debe registrar al menos un concepto.',
            'conceptos.*.descripcion.required' => 'La descripción del concepto es obligatoria.',
            'conceptos.*.descripcion.string' => 'La descripción del concepto debe ser texto.',
            'conceptos.*.cantidad.required' => 'La cantidad del concepto es obligatoria.',
            'conceptos.*.cantidad.numeric' => 'La cantidad del concepto debe ser numérica.',
            'conceptos.*.cantidad.min' => 'La cantidad del concepto debe ser mayor a cero.',
            'conceptos.*.unidad.required' => 'La unidad del concepto es obligatoria.',
            'conceptos.*.unidad.string' => 'La unidad del concepto debe ser texto.',
            'conceptos.*.unidad.max' => 'La unidad del concepto no debe exceder 50 caracteres.',
            'conceptos.*.precio_unitario.required' => 'El precio unitario del concepto es obligatorio.',
            'conceptos.*.precio_unitario.numeric' => 'El precio unitario del concepto debe ser numérico.',
            'conceptos.*.precio_unitario.min' => 'El precio unitario del concepto no puede ser negativo.',
        ];
    }
}
