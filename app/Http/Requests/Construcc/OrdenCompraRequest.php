<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdenCompraRequest extends FormRequest
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
        $rules = [
            // Datos básicos de la orden de compra
            'numero_oc' => 'required|string|max:255|unique:ordenes_compra,numero_oc',
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'empresa_id' => 'required|integer|exists:empresas,id',
            'departamento' => 'required|string|max:255',
            'fecha_oc' => 'required|date',
            'fecha_entrega_solicitada' => 'nullable|date|after_or_equal:fecha_oc',
            'moneda' => 'required|string|in:CLP,USD,EUR',
            'valor_neto' => 'required|numeric|min:0',
            'valor_iva' => 'nullable|numeric|min:0',
            'valor_total' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
            'estado' => 'nullable|string|in:borrador,enviada,confirmada,entregada,cancelada',
            
            // Detalles de la orden de compra
            'detalles' => 'required|array|min:1',
            'detalles.*.descripcion' => 'required|string|max:500',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.unidad_medida' => 'required|string|max:50',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.valor_total_detalle' => 'required|numeric|min:0',
        ];

        // Modificar reglas para actualización
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $ordenId = $this->route('orden');
            $rules['numero_oc'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('ordenes_compra', 'numero_oc')->ignore($ordenId)
            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'numero_oc' => 'número de OC',
            'proveedor_id' => 'proveedor',
            'empresa_id' => 'empresa',
            'departamento' => 'departamento',
            'fecha_oc' => 'fecha de la OC',
            'fecha_entrega_solicitada' => 'fecha de entrega solicitada',
            'moneda' => 'moneda',
            'valor_neto' => 'valor neto',
            'valor_iva' => 'valor IVA',
            'valor_total' => 'valor total',
            'observaciones' => 'observaciones',
            'estado' => 'estado',
            'detalles' => 'detalles',
            'detalles.*.descripcion' => 'descripción del detalle',
            'detalles.*.cantidad' => 'cantidad',
            'detalles.*.unidad_medida' => 'unidad de medida',
            'detalles.*.precio_unitario' => 'precio unitario',
            'detalles.*.valor_total_detalle' => 'valor total del detalle',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_oc.required' => 'El número de OC es obligatorio.',
            'numero_oc.unique' => 'Ya existe una orden de compra con este número.',
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'empresa_id.required' => 'La empresa es obligatoria.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
            'departamento.required' => 'El departamento es obligatorio.',
            'fecha_oc.required' => 'La fecha de la OC es obligatoria.',
            'fecha_oc.date' => 'La fecha de la OC debe ser una fecha válida.',
            'fecha_entrega_solicitada.date' => 'La fecha de entrega debe ser una fecha válida.',
            'fecha_entrega_solicitada.after_or_equal' => 'La fecha de entrega debe ser posterior o igual a la fecha de la OC.',
            'moneda.required' => 'La moneda es obligatoria.',
            'moneda.in' => 'La moneda debe ser CLP, USD o EUR.',
            'valor_neto.required' => 'El valor neto es obligatorio.',
            'valor_neto.numeric' => 'El valor neto debe ser un número.',
            'valor_neto.min' => 'El valor neto debe ser mayor o igual a 0.',
            'valor_iva.numeric' => 'El valor IVA debe ser un número.',
            'valor_iva.min' => 'El valor IVA debe ser mayor o igual a 0.',
            'valor_total.required' => 'El valor total es obligatorio.',
            'valor_total.numeric' => 'El valor total debe ser un número.',
            'valor_total.min' => 'El valor total debe ser mayor o igual a 0.',
            'estado.in' => 'El estado debe ser: borrador, enviada, confirmada, entregada o cancelada.',
            
            // Mensajes para detalles
            'detalles.required' => 'Los detalles de la orden son obligatorios.',
            'detalles.min' => 'Debe incluir al menos un detalle.',
            'detalles.*.descripcion.required' => 'La descripción del detalle es obligatoria.',
            'detalles.*.descripcion.max' => 'La descripción del detalle no puede exceder 500 caracteres.',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria.',
            'detalles.*.cantidad.numeric' => 'La cantidad debe ser un número.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'detalles.*.unidad_medida.required' => 'La unidad de medida es obligatoria.',
            'detalles.*.unidad_medida.max' => 'La unidad de medida no puede exceder 50 caracteres.',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio.',
            'detalles.*.precio_unitario.numeric' => 'El precio unitario debe ser un número.',
            'detalles.*.precio_unitario.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'detalles.*.valor_total_detalle.required' => 'El valor total del detalle es obligatorio.',
            'detalles.*.valor_total_detalle.numeric' => 'El valor total del detalle debe ser un número.',
            'detalles.*.valor_total_detalle.min' => 'El valor total del detalle debe ser mayor o igual a 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Establecer valores por defecto
        $defaults = [];
        
        if (!$this->has('estado')) {
            $defaults['estado'] = 'borrador';
        }
        
        if (!$this->has('moneda')) {
            $defaults['moneda'] = 'CLP';
        }

        if (!empty($defaults)) {
            $this->merge($defaults);
        }

        // Limpiar y formatear valores numéricos
        if ($this->has('valor_neto')) {
            $this->merge([
                'valor_neto' => $this->formatNumericValue($this->valor_neto)
            ]);
        }

        if ($this->has('valor_iva')) {
            $this->merge([
                'valor_iva' => $this->formatNumericValue($this->valor_iva)
            ]);
        }

        if ($this->has('valor_total')) {
            $this->merge([
                'valor_total' => $this->formatNumericValue($this->valor_total)
            ]);
        }

        // Formatear detalles
        if ($this->has('detalles') && is_array($this->detalles)) {
            $detallesFormateados = [];
            foreach ($this->detalles as $detalle) {
                if (is_array($detalle)) {
                    $detalleFormateado = $detalle;
                    
                    if (isset($detalle['cantidad'])) {
                        $detalleFormateado['cantidad'] = $this->formatNumericValue($detalle['cantidad']);
                    }
                    
                    if (isset($detalle['precio_unitario'])) {
                        $detalleFormateado['precio_unitario'] = $this->formatNumericValue($detalle['precio_unitario']);
                    }
                    
                    if (isset($detalle['valor_total_detalle'])) {
                        $detalleFormateado['valor_total_detalle'] = $this->formatNumericValue($detalle['valor_total_detalle']);
                    }
                    
                    $detallesFormateados[] = $detalleFormateado;
                }
            }
            
            $this->merge(['detalles' => $detallesFormateados]);
        }
    }

    /**
     * Formatear valor numérico removiendo caracteres no numéricos excepto punto y coma
     *
     * @param mixed $value
     * @return float|null
     */
    private function formatNumericValue($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Remover espacios y caracteres especiales, mantener solo números, punto y coma
        $cleaned = preg_replace('/[^\d,.-]/', '', (string)$value);
        
        // Reemplazar coma por punto para decimal
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Convertir a float
        return is_numeric($cleaned) ? (float)$cleaned : null;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que el valor total coincida con la suma de detalles más IVA
            $this->validateTotalValues($validator);
            
            // Validar que cada detalle tenga coherencia en sus valores
            $this->validateDetallesConsistency($validator);
        });
    }

    /**
     * Validar que los valores totales sean coherentes
     */
    private function validateTotalValues($validator)
    {
        if (!$this->has(['valor_neto', 'valor_total', 'detalles'])) {
            return;
        }

        $detalles = $this->detalles;
        if (!is_array($detalles)) {
            return;
        }

        // Calcular suma de detalles
        $sumaDetalles = 0;
        foreach ($detalles as $detalle) {
            if (isset($detalle['valor_total_detalle']) && is_numeric($detalle['valor_total_detalle'])) {
                $sumaDetalles += (float)$detalle['valor_total_detalle'];
            }
        }

        $valorNeto = (float)$this->valor_neto;
        $valorIva = (float)($this->valor_iva ?? 0);
        $valorTotal = (float)$this->valor_total;

        // Validar que la suma de detalles coincida con el valor neto (tolerancia de 1 peso)
        if (abs($sumaDetalles - $valorNeto) > 1) {
            $validator->errors()->add('valor_neto', 'El valor neto debe coincidir con la suma de los detalles.');
        }

        // Validar que el valor total sea valor neto + IVA (tolerancia de 1 peso)
        $totalEsperado = $valorNeto + $valorIva;
        if (abs($valorTotal - $totalEsperado) > 1) {
            $validator->errors()->add('valor_total', 'El valor total debe ser igual al valor neto más el IVA.');
        }
    }

    /**
     * Validar consistencia en cada detalle
     */
    private function validateDetallesConsistency($validator)
    {
        if (!$this->has('detalles') || !is_array($this->detalles)) {
            return;
        }

        foreach ($this->detalles as $index => $detalle) {
            if (!is_array($detalle)) {
                continue;
            }

            if (isset($detalle['cantidad'], $detalle['precio_unitario'], $detalle['valor_total_detalle'])) {
                $cantidad = (float)$detalle['cantidad'];
                $precioUnitario = (float)$detalle['precio_unitario'];
                $valorTotalDetalle = (float)$detalle['valor_total_detalle'];
                
                $totalEsperado = $cantidad * $precioUnitario;
                
                // Validar con tolerancia de 1 centavo
                if (abs($valorTotalDetalle - $totalEsperado) > 0.01) {
                    $validator->errors()->add(
                        "detalles.{$index}.valor_total_detalle",
                        "El valor total del detalle debe ser cantidad × precio unitario."
                    );
                }
            }
        }
    }
}