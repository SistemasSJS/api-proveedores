<?php

namespace App\Http\Requests\Presupuesto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la creación de un presupuesto básico con sus conceptos.
 */
class StorePresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero_presupuesto' => 'nullable|string|max:255',
            'proveedor_id' => 'required|exists:proveedores,id',
            'empresa_receptora_id' => 'nullable|exists:cartera_clientes,id',
            'empresa_receptora_nombre' => 'nullable|string|max:255|required_without:empresa_receptora_id',
            'empresa_receptora_puesto' => 'nullable|string|max:255',
            'empresa_receptora_empresa' => 'nullable|string|max:255|required_without:empresa_receptora_id',
            'empresa_receptora_alias' => 'nullable|string|max:255',
            'empresa_receptora_telefono' => 'nullable|string|max:30',
            'empresa_receptora_correo' => 'nullable|email|max:255',
            'fecha_emision' => 'required|date',
            'concepto_general' => 'required|string',
            'con_iva' => 'nullable|boolean',
            'iva_porcentaje' => 'nullable|numeric|min:0|max:100',
            'term_cond_dias_vigencia' => 'nullable|integer|min:0',
            'term_cond_moneda' => 'nullable|string|max:10',
            'term_cond_impuestos_en_pdf' => 'nullable|boolean',
            'term_cond_iva' => 'nullable|numeric|min:0|max:100',
            'term_cond_anticipo_porcentaje' => 'nullable|numeric|min:0|max:100',
            'term_cond_tiempo_entrega_dias' => 'nullable|integer|min:0',
            'obs_garantia_dias' => 'nullable|integer|min:0',
            'obs_traslados' => 'nullable|boolean',
            'obs_viaticos' => 'nullable|boolean',
            'configuracion_condiciones' => 'nullable|array',
            'estado' => 'nullable|string|in:borrador,enviado,aceptado,rechazado,rechazado_con_observacion,vencido',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.descripcion' => 'required|string',
            'conceptos.*.cantidad' => 'required|numeric|min:0.0001',
            'conceptos.*.unidad' => 'required|string|max:50',
            'conceptos.*.precio_unitario' => 'required|numeric|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_presupuesto.string' => 'El número de presupuesto debe ser texto.',
            'numero_presupuesto.max' => 'El número de presupuesto no debe exceder 255 caracteres.',
            'proveedor_id.required' => 'El proveedor emisor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor emisor seleccionado no existe.',
            'empresa_receptora_id.exists' => 'El cliente seleccionado en cartera no existe.',
            'empresa_receptora_nombre.required_without' => 'El nombre de la persona es obligatorio cuando no se envía empresa_receptora_id.',
            'empresa_receptora_nombre.string' => 'El nombre de la persona debe ser texto.',
            'empresa_receptora_nombre.max' => 'El nombre de la persona no debe exceder 255 caracteres.',
            'empresa_receptora_puesto.string' => 'El puesto debe ser texto.',
            'empresa_receptora_puesto.max' => 'El puesto no debe exceder 255 caracteres.',
            'empresa_receptora_empresa.required_without' => 'La empresa es obligatoria cuando no se envía empresa_receptora_id.',
            'empresa_receptora_empresa.string' => 'La empresa debe ser texto.',
            'empresa_receptora_empresa.max' => 'La empresa no debe exceder 255 caracteres.',
            'empresa_receptora_telefono.string' => 'El teléfono debe ser texto.',
            'empresa_receptora_telefono.max' => 'El teléfono no debe exceder 30 caracteres.',
            'empresa_receptora_correo.email' => 'El correo debe ser válido.',
            'empresa_receptora_correo.max' => 'El correo no debe exceder 255 caracteres.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_emision.date' => 'La fecha de emisión debe tener un formato válido.',
            'concepto_general.required' => 'El concepto general es obligatorio.',
            'concepto_general.string' => 'El concepto general debe ser texto.',
            'con_iva.boolean' => 'El indicador con IVA debe ser verdadero o falso.',
            'iva_porcentaje.numeric' => 'El porcentaje de IVA debe ser numérico.',
            'iva_porcentaje.min' => 'El porcentaje de IVA no puede ser menor a 0.',
            'iva_porcentaje.max' => 'El porcentaje de IVA no puede ser mayor a 100.',
            'term_cond_dias_vigencia.integer' => 'Los días de vigencia deben ser un número entero.',
            'term_cond_moneda.string' => 'La moneda debe ser texto.',
            'term_cond_iva.numeric' => 'El IVA debe ser numérico.',
            'term_cond_anticipo_porcentaje.numeric' => 'El porcentaje de anticipo debe ser numérico.',
            'term_cond_tiempo_entrega_dias.integer' => 'Los días de tiempo de entrega deben ser un número entero.',
            'obs_garantia_dias.integer' => 'Los días de garantía deben ser un número entero.',
            'obs_traslados.boolean' => 'Traslados debe ser verdadero o falso.',
            'obs_viaticos.boolean' => 'Viáticos debe ser verdadero o falso.',
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
