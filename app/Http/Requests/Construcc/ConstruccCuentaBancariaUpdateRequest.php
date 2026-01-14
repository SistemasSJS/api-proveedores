<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class ConstruccCuentaBancariaUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'alias' => 'sometimes|string|max:255',
            'banco_clave' => 'sometimes|string|max:10',
            'banco_nombre' => 'sometimes|string|max:255',
            'tipo_cuenta' => 'sometimes|string|max:255',
            'campo_dependiente' => 'sometimes|string|max:255',
            'titular_cuenta' => 'sometimes|string|max:255',
            'referencia' => 'sometimes|nullable|string|max:255',
            'sucursal' => 'sometimes|nullable|string|max:255',
            'swift' => 'sometimes|nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'alias.string' => 'El alias debe ser texto válido',
            'alias.max' => 'El alias no debe exceder los 255 caracteres',
            
            'banco_clave.string' => 'La clave del banco debe ser texto válido',
            'banco_clave.max' => 'La clave del banco no debe exceder los 10 caracteres',
            
            'banco_nombre.string' => 'El nombre del banco debe ser texto válido',
            'banco_nombre.max' => 'El nombre del banco no debe exceder los 255 caracteres',
            
            'tipo_cuenta.string' => 'El tipo de cuenta debe ser texto válido',
            'tipo_cuenta.max' => 'El tipo de cuenta no debe exceder los 255 caracteres',
            
            'campo_dependiente.string' => 'El campo dependiente debe ser texto válido',
            'campo_dependiente.max' => 'El campo dependiente no debe exceder los 255 caracteres',
            
            'titular_cuenta.string' => 'El titular de la cuenta debe ser texto válido',
            'titular_cuenta.max' => 'El titular de la cuenta no debe exceder los 255 caracteres',
            
            'referencia.string' => 'La referencia debe ser texto válido',
            'referencia.max' => 'La referencia no debe exceder los 255 caracteres',
            
            'sucursal.string' => 'La sucursal debe ser texto válido',
            'sucursal.max' => 'La sucursal no debe exceder los 255 caracteres',
            
            'swift.string' => 'El código SWIFT debe ser texto válido',
            'swift.max' => 'El código SWIFT no debe exceder los 255 caracteres',
        ];
    }
}
