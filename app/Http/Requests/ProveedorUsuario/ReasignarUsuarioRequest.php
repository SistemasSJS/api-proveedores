<?php

namespace App\Http\Requests\ProveedorUsuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReasignarUsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo administradores pueden reasignar usuarios
        return $this->user() && $this->user()->isUserAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proveedor_origen_id' => [
                'required',
                'integer',
                'exists:proveedores,id',
            ],
            'proveedor_destino_id' => [
                'required',
                'integer',
                'exists:proveedores,id',
                'different:proveedor_origen_id',
            ],
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
            ],
            'tipo_relacion' => [
                'required',
                'string',
                Rule::in(['PRINCIPAL', 'SECUNDARIO']),
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proveedor_origen_id.required' => 'El proveedor de origen es requerido.',
            'proveedor_origen_id.exists' => 'El proveedor de origen no existe.',
            'proveedor_destino_id.required' => 'El proveedor de destino es requerido.',
            'proveedor_destino_id.exists' => 'El proveedor de destino no existe.',
            'proveedor_destino_id.different' => 'El proveedor de destino debe ser diferente al proveedor de origen.',
            'role_id.required' => 'El rol es requerido.',
            'role_id.exists' => 'El rol seleccionado no existe.',
            'tipo_relacion.required' => 'El tipo de relación es requerido.',
            'tipo_relacion.in' => 'El tipo de relación debe ser PRINCIPAL o SECUNDARIO.',
            'observaciones.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
        ];
    }
}
