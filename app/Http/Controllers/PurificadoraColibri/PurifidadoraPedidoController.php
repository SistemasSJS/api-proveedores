<?php

namespace App\Http\Controllers\PurificadoraColibri;

use App\Http\Controllers\Controller;
use App\Models\PurificadoraPedido;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurifidadoraPedidoController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(
                [
                    'nombre' => ['required', 'string', 'max:120'],
                    'celular' => ['required', 'regex:/^[0-9]+$/', 'size:10'],
                    'correo' => ['nullable', 'email', 'max:180'],
                    'calle' => ['required', 'string', 'max:120'],
                    'numero' => ['required', 'string', 'max:20'],
                    'colonia' => ['required', 'string', 'max:120'],
                    'codigoPostal' => ['nullable', 'regex:/^[0-9]{5}$/'],
                    'municipio' => ['nullable', 'string', 'max:120'],
                ],
                [
                    'nombre.required' => 'El nombre es obligatorio.',
                    'nombre.max' => 'El nombre no debe exceder 120 caracteres.',
                    'celular.required' => 'El celular es obligatorio.',
                    'celular.regex' => 'El celular solo debe contener números.',
                    'celular.size' => 'El celular debe tener 10 dígitos.',
                    'correo.email' => 'El correo no es válido.',
                    'correo.max' => 'El correo no debe exceder 180 caracteres.',
                    'calle.required' => 'La calle es obligatoria.',
                    'calle.max' => 'La calle no debe exceder 120 caracteres.',
                    'numero.required' => 'El número es obligatorio.',
                    'numero.max' => 'El número no debe exceder 20 caracteres.',
                    'colonia.required' => 'La colonia es obligatoria.',
                    'colonia.max' => 'La colonia no debe exceder 120 caracteres.',
                    'codigoPostal.regex' => 'El código postal debe tener 5 dígitos.',
                ]
            );
        } catch (ValidationException $e) {
            return $this->error('Error de validación.', $e->errors(), 422);
        }

        $pedido = PurificadoraPedido::create([
            'nombre' => $validated['nombre'],
            'celular' => $validated['celular'],
            'correo' => $validated['correo'] ?? null,
            'calle' => $validated['calle'],
            'numero' => $validated['numero'],
            'colonia' => $validated['colonia'],
            'codigo_postal' => $validated['codigoPostal'] ?? null,
            'municipio' => $validated['municipio'] ?? 'Ahome',
        ]);

        return $this->success($pedido, 'Pedido registrado correctamente.', 201);
    }
}
