<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class ConstruccCuentaBancariaStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'alias' => 'required|string|max:255',
            'banco_clave' => 'required|string|max:10',
            'banco_nombre' => 'required|string|max:255',
            'tipo_cuenta' => 'required|string|max:255',
            'campo_dependiente' => 'required|string|max:255',
            'titular_cuenta' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
            'sucursal' => 'nullable|string|max:255',
            'swift' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'alias.required' => 'El alias de la cuenta bancaria es obligatorio',
            'alias.max' => 'El alias no debe exceder los 255 caracteres',
            
            'banco_clave.required' => 'La clave del banco es obligatoria',
            'banco_clave.max' => 'La clave del banco no debe exceder los 10 caracteres',
            
            'banco_nombre.required' => 'El nombre del banco es obligatorio',
            'banco_nombre.max' => 'El nombre del banco no debe exceder los 255 caracteres',
            
            'tipo_cuenta.required' => 'El tipo de cuenta es obligatorio',
            'tipo_cuenta.max' => 'El tipo de cuenta no debe exceder los 255 caracteres',
            
            'campo_dependiente.required' => 'El campo dependiente (CLABE/número de cuenta) es obligatorio',
            'campo_dependiente.max' => 'El campo dependiente no debe exceder los 255 caracteres',
            
            'titular_cuenta.required' => 'El titular de la cuenta es obligatorio',
            'titular_cuenta.max' => 'El titular de la cuenta no debe exceder los 255 caracteres',
            
            'referencia.max' => 'La referencia no debe exceder los 255 caracteres',
            'sucursal.max' => 'La sucursal no debe exceder los 255 caracteres',
            'swift.max' => 'El código SWIFT no debe exceder los 255 caracteres',
        ];
    }
}
