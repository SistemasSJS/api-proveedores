<?php

namespace App\Http\Requests\Sucursal;

use Illuminate\Foundation\Http\FormRequest;

class SucursalUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|required|string|max:100',
            'direccion' => 'sometimes|required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'encargado' => 'nullable|string|max:100',
            'activa' => 'boolean',
            'coordenadas_lat' => 'nullable|numeric|between:-90,90',
            'coordenadas_lng' => 'nullable|numeric|between:-180,180',
        ];
    }
}
