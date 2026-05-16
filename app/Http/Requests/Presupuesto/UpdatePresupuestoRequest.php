<?php

namespace App\Http\Requests\Presupuesto;

use App\Models\PresupuestoConcepto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida la actualización de un presupuesto básico y sus conceptos.
 *
 * Receptor: con empresa_receptora_id (cartera o proveedor catálogo) no se exigen nombre/empresa en el cuerpo;
 * el controlador rellena desde cartera o proveedor. Sin id: captura manual / un solo uso → nombre y empresa obligatorios.
 */
class UpdatePresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $id = $this->input('empresa_receptora_id');
        if ($id === '' || $id === false) {
            $this->merge(['empresa_receptora_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero_presupuesto' => 'nullable|string|max:255',
            'proveedor_id' => 'required|exists:proveedores,id',
            'es_proveedor_receptor' => 'nullable|boolean',
            'empresa_receptora_id' => 'nullable|integer',
            'empresa_receptora_nombre' => 'nullable|string|max:255',
            'empresa_receptora_puesto' => 'nullable|string|max:255',
            'empresa_receptora_empresa' => 'nullable|string|max:255',
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
            'term_cond_tiempo_entrega_dias' => 'nullable|integer|min:0',
            'term_cond_inicio_trabajo' => 'nullable|integer|in:1,2',
            'term_cond_inicio_trabajo_porcentaje' => 'nullable|numeric|min:0|max:100',
            'term_cond_inicio_trabajo_cantidad' => 'nullable|numeric|min:0.01',
            'obs_garantia_dias' => 'nullable|integer|min:0',
            'term_cond_textos_libres' => 'nullable|array|max:4',
            'term_cond_textos_libres.*' => 'nullable|string|max:500',
            'term_cond_visibilidad' => 'nullable|array',
            'term_cond_visibilidad.pago_contra_conformidad' => 'nullable|boolean',
            'term_cond_visibilidad.garantia_calidad' => 'nullable|boolean',
            'term_cond_visibilidad.correccion_defectos' => 'nullable|boolean',
            'term_cond_visibilidad.incluye_materiales_insumos' => 'nullable|boolean',
            'term_cond_visibilidad.incluye_traslados' => 'nullable|boolean',
            'term_cond_visibilidad.incluye_viaticos' => 'nullable|boolean',
            'validacion_alcances' => 'nullable|array',
            'validacion_alcances.incluye_todos_los_costos' => 'nullable|boolean',
            'validacion_alcances.sin_costos_adicionales_no_autorizados' => 'nullable|boolean',
            'validacion_alcances.adicionales_requieren_autorizacion_escrita' => 'nullable|boolean',
            'configuracion_condiciones' => 'nullable|array',
            'estado' => 'nullable|string|in:borrador,enviado,aceptado,rechazado,rechazado_con_observacion,vencido',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.tipo' => 'nullable|string|in:concepto,parrafo',
            'conceptos.*.descripcion' => 'required|string|max:5000',
            'conceptos.*.cantidad' => 'required|numeric|min:0.0001',
            'conceptos.*.unidad' => 'required|string|max:50',
            'conceptos.*.precio_unitario' => 'required|numeric|min:0',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $v->getData();
            $id = $data['empresa_receptora_id'] ?? null;
            $esProveedorReceptor = filter_var($data['es_proveedor_receptor'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $inicioTrabajo = isset($data['term_cond_inicio_trabajo']) ? (int) $data['term_cond_inicio_trabajo'] : null;
            $inicioPct = $data['term_cond_inicio_trabajo_porcentaje'] ?? null;
            $inicioMonto = $data['term_cond_inicio_trabajo_cantidad'] ?? null;

            if ($esProveedorReceptor && ($id === null || $id === '')) {
                $v->errors()->add(
                    'empresa_receptora_id',
                    'Debe indicar el id del proveedor del catálogo cuando es_proveedor_receptor es verdadero.'
                );

                return;
            }

            if ($id !== null && $id !== '') {
                return;
            }

            $nombre = trim((string) ($data['empresa_receptora_nombre'] ?? ''));
            $empresa = trim((string) ($data['empresa_receptora_empresa'] ?? ''));
            if ($nombre === '') {
                $v->errors()->add(
                    'empresa_receptora_nombre',
                    'El nombre del contacto es obligatorio en captura manual (sin cliente de cartera ni proveedor del catálogo).'
                );
            }
            if ($empresa === '') {
                $v->errors()->add(
                    'empresa_receptora_empresa',
                    'La razón social o empresa es obligatoria en captura manual (sin cliente de cartera ni proveedor del catálogo).'
                );
            }

            if ($inicioTrabajo === 2) {
                $tienePct = $inicioPct !== null && $inicioPct !== '' && (float) $inicioPct > 0;
                $tieneMonto = $inicioMonto !== null && $inicioMonto !== '' && (float) $inicioMonto > 0;

                if (! $tienePct && ! $tieneMonto) {
                    $v->errors()->add(
                        'term_cond_inicio_trabajo',
                        'Cuando el inicio de trabajo es por anticipo, debe indicar porcentaje o cantidad.'
                    );
                }
            }

            if (
                $inicioPct !== null && $inicioPct !== ''
                && $inicioMonto !== null && $inicioMonto !== ''
                && (float) $inicioPct > 0 && (float) $inicioMonto > 0
            ) {
                $v->errors()->add(
                    'term_cond_inicio_trabajo_cantidad',
                    'Debe enviar solo una opción de anticipo: porcentaje o cantidad.'
                );
            }

            $conceptos = $data['conceptos'] ?? [];
            if (is_array($conceptos)) {
                $maxParrafo = PresupuestoConcepto::DESCRIPCION_PARRAFO_MAX;
                foreach ($conceptos as $index => $concepto) {
                    if (! is_array($concepto)) {
                        continue;
                    }
                    $tipo = $concepto['tipo'] ?? PresupuestoConcepto::TIPO_CONCEPTO;
                    if ($tipo !== PresupuestoConcepto::TIPO_PARRAFO) {
                        continue;
                    }
                    $desc = (string) ($concepto['descripcion'] ?? '');
                    if (mb_strlen($desc) > $maxParrafo) {
                        $v->errors()->add(
                            "conceptos.{$index}.descripcion",
                            "El párrafo no puede exceder {$maxParrafo} caracteres (aprox. tres renglones en el PDF)."
                        );
                    }
                }
            }
        });
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
            'empresa_receptora_nombre.required_without' => 'El nombre del contacto es obligatorio cuando no hay receptor seleccionado por id.',
            'empresa_receptora_nombre.string' => 'El nombre de la persona debe ser texto.',
            'empresa_receptora_nombre.max' => 'El nombre de la persona no debe exceder 255 caracteres.',
            'empresa_receptora_puesto.string' => 'El puesto debe ser texto.',
            'empresa_receptora_puesto.max' => 'El puesto no debe exceder 255 caracteres.',
            'empresa_receptora_empresa.required_without' => 'La empresa es obligatoria cuando no hay receptor seleccionado por id.',
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
            'term_cond_tiempo_entrega_dias.integer' => 'Los días de tiempo de entrega deben ser un número entero.',
            'term_cond_inicio_trabajo.in' => 'La opción de inicio de trabajo debe ser 1 (autorización) o 2 (anticipo).',
            'term_cond_inicio_trabajo_porcentaje.numeric' => 'El anticipo por porcentaje debe ser numérico.',
            'term_cond_inicio_trabajo_porcentaje.min' => 'El anticipo por porcentaje no puede ser menor a 0.',
            'term_cond_inicio_trabajo_porcentaje.max' => 'El anticipo por porcentaje no puede ser mayor a 100.',
            'term_cond_inicio_trabajo_cantidad.numeric' => 'El anticipo por cantidad debe ser numérico.',
            'term_cond_inicio_trabajo_cantidad.min' => 'El anticipo por cantidad debe ser mayor a 0.',
            'obs_garantia_dias.integer' => 'Los días de garantía deben ser un número entero.',
            'term_cond_textos_libres.array' => 'Los términos de texto libre deben enviarse como arreglo.',
            'term_cond_textos_libres.max' => 'Solo puede registrar hasta 4 textos libres.',
            'term_cond_textos_libres.*.string' => 'Cada texto libre debe ser texto.',
            'term_cond_textos_libres.*.max' => 'Cada texto libre no debe exceder 500 caracteres.',
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
