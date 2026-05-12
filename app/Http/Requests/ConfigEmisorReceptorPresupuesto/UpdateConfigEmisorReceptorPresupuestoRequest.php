<?php

namespace App\Http\Requests\ConfigEmisorReceptorPresupuesto;

use App\Models\ConfigEmisorReceptorPresupuesto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigEmisorReceptorPresupuestoRequest extends FormRequest
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
            // 'proveedor_id' => 'sometime|integer|exists:proveedores,id',
            'tipo' => 'sometimes|string|in:receptor,emisor', // 1: emisor, 2: receptor
            'nombre' => 'sometimes|string|max:60',
            'apellido' => 'sometimes|string|max:60',
            'puesto' => 'sometimes|string|max:40',
            'file_firma' => 'sometimes|file',
            'estado' => 'sometimes|string|in:activo,inactivo,default', // 1: activo, 2: inactivo, 3: default
        ];
    }


    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();
        if(isset($data['tipo'])) {
            $data['tipo'] = $data['tipo'] === ConfigEmisorReceptorPresupuesto::TIPO_EMISOR ? ConfigEmisorReceptorPresupuesto::TIPO_EMISOR : ConfigEmisorReceptorPresupuesto::TIPO_RECEPTOR;
        }
        
        if(isset($data['estado'])) {
            $data['estado'] = $data['estado'] === ConfigEmisorReceptorPresupuesto::ESTADO_ACTIVO ? ConfigEmisorReceptorPresupuesto::ESTADO_ACTIVO : ($data['estado'] === ConfigEmisorReceptorPresupuesto::ESTADO_INACTIVO ? ConfigEmisorReceptorPresupuesto::ESTADO_INACTIVO : ConfigEmisorReceptorPresupuesto::ESTADO_DEFAULT);
        }
        
        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // 'proveedor_id.exists' => 'El proveedor indicado no existe.',
            // 'tipo.required' => 'El tipo es obligatorio.',
            'tipo.in' => 'El tipo debe ser emisor o receptor.',
            // 'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe exceder 120 caracteres.',
            'apellido.max' => 'El apellido no debe exceder 120 caracteres.',
            'puesto.max' => 'El puesto no debe exceder 150 caracteres.',
            'file_firma.max' => 'La firma no debe exceder 500 caracteres.',
            // 'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser activo, inactivo o default.',
        ];
    }
}
