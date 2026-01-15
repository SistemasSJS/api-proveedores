<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class ConstruccProveedorUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $proveedorId = $this->route('proveedor')->id ?? null;

        return [
            // Datos del proveedor con validación sometimes
            'razon_social' => 'sometimes|string|max:255|unique:proveedores,razon_social,' . $proveedorId,
            'rfc' => 'sometimes|string|min:12|max:13|unique:proveedores,rfc,' . $proveedorId,
            'nombre_comercial' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:proveedores,email,' . $proveedorId,
            'telefono' => 'sometimes|string|max:20|unique:proveedores,telefono,' . $proveedorId,
            'celular' => 'sometimes|string|max:20|unique:proveedores,celular,' . $proveedorId,
            // Datos de autorización (requeridos para validar permisos)
            'usuario_id' => 'required|integer',
            'nivel_id' => 'required|integer|min:0|max:6', // 0: Admin, 1: DG, 2: DT, 3: DA, 4: SI, 5: PC, 6: RO
        ];
    }

    public function messages()
    {
        return [
            // Mensajes para datos del proveedor
            'razon_social.string' => 'La razón social debe ser texto válido',
            'razon_social.max' => 'La razón social no debe exceder los 255 caracteres',
            'razon_social.unique' => 'La razón social ya está registrada en el sistema',

            'rfc.string' => 'El RFC debe ser texto válido',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres',
            'rfc.max' => 'El RFC no debe exceder los 13 caracteres',
            'rfc.unique' => 'El RFC ya está registrado en el sistema',

            'nombre_comercial.string' => 'El nombre comercial debe ser texto válido',
            'nombre_comercial.max' => 'El nombre comercial no debe exceder los 255 caracteres',

            'email.email' => 'El email debe ser una dirección válida',
            'email.max' => 'El email no debe exceder los 255 caracteres',
            'email.unique' => 'El email ya está registrado en el sistema',

            'telefono.string' => 'El teléfono debe ser texto válido',
            'telefono.max' => 'El teléfono no debe exceder los 20 caracteres',
            'telefono.unique' => 'El teléfono ya está registrado en el sistema',

            // Mensajes para autorización
            'usuario_id.required' => 'El ID del usuario es obligatorio para validar permisos',
            'usuario_id.integer' => 'El ID del usuario debe ser un número entero',

            'nivel_id.required' => 'El nivel del usuario es obligatorio para validar permisos',
            'nivel_id.integer' => 'El nivel del usuario debe ser un número entero',
            'nivel_id.min' => 'El nivel del usuario debe ser mayor o igual a 0',
            'nivel_id.max' => 'El nivel del usuario no debe exceder 6',
        ];
    }
}
