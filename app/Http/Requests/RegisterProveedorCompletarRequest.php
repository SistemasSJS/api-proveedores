<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterProveedorCompletarRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     *
     * @return bool
     */
    public function authorize()
    {
        // Retornamos true, ya que estamos permitiendo que cualquiera pueda completar el registro
        return true;
    }

    /**
     * Obtén las reglas de validación que se aplican a la solicitud.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'token' => 'required|string', // Token obligatorio, debe ser una cadena de texto
            'password' => 'required|min:8|confirmed', // Contraseña requerida, debe tener al menos 8 caracteres y confirmarse
        ];
    }

    /**
     * Mensajes personalizados para las reglas de validación.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'token.required' => 'El token de registro es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
