<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $proveedorId = $this->route('proveedor');

        return [
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',
                Rule::unique('proveedores')->ignore($proveedorId),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('proveedores')->ignore($proveedorId),
            ],
            'telefono' => ['required', 'string', 'max:15'],
            'estado' => ['required', 'string', 'max:255'],
            'municipio' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'max:10'],
            'direccion_fiscal' => ['nullable', 'string', 'max:255'],
            'contacto_nombre' => ['required', 'string', 'max:255'],
            'contacto_telefono' => ['required', 'string', 'max:15'],
            'contacto_correo' => ['required', 'email', 'max:255'],
            'notas' => ['nullable', 'string'],
            'estatus' => ['nullable', 'in:activo,baja'],
        ];
    }
}
