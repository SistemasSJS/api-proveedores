<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CompletarRegistroProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sobrescribir el método para retornar JSON en caso de error
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'ERROR',
                'code' => 422,
                'message' => 'Error de validación',
                'data' => null,
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function rules(): array
    {
        $proveedorId = $this->input('proveedor_id');

        return [
            // Datos requeridos
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'token_temporal' => 'required|string',
            'password' => 'required|string|min:8|confirmed',

            // Datos opcionales que se pueden actualizar
            'razon_social' => 'sometimes|string|max:255',
            'nombre_comercial' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('proveedores', 'email')->ignore($proveedorId),
            ],
            'telefono' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('proveedores', 'telefono')->ignore($proveedorId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'El ID del proveedor es obligatorio',
            'proveedor_id.exists' => 'El proveedor no existe',
            'token_temporal.required' => 'El token temporal es obligatorio',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'razon_social.max' => 'La razón social no debe exceder los 255 caracteres',
            'nombre_comercial.max' => 'El nombre comercial no debe exceder los 255 caracteres',
            'email.email' => 'El email debe ser una dirección válida',
            'email.unique' => 'El email ya está registrado en otro proveedor',
            'email.max' => 'El email no debe exceder los 255 caracteres',
            'telefono.unique' => 'El teléfono ya está registrado en otro proveedor',
            'telefono.max' => 'El teléfono no debe exceder los 20 caracteres',
        ];
    }
}
