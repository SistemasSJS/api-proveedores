<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Registro de un proveedor
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Esta empresa ya esta registrada en el sistema, favor de contactar a soporte tecnico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error en el registro',
                'errors' => $validator->errors()
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            // Iniciar transacción para asegurar que todo se registre correctamente
            DB::beginTransaction();

            // Crear el usuario (proveedor)
            $user = User::create([
                'name' => $request->nombre_comercial,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Crear el proveedor asociado al usuario
            $proveedor = Proveedor::create([
                'razon_social' => $request->razon_social,
                'nombre_comercial' => $request->nombre_comercial,
                'email' => $request->email,
                'user_id' => $user->id,
            ]);

            // Confirmar la transacción
            DB::commit();

            // Crear token de autenticación usando Sanctum
            $token = $user->createToken('ProveedorToken')->plainTextToken;

            return response()->json([
                'message' => 'Proveedor registrado correctamente',
                'user' => $user,
                'proveedor' => $proveedor,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            // Revertir la transacción si hay un error
            DB::rollBack();

            return response()->json([
                'message' => 'Error en el registro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login de un proveedor
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        // Obtener usuario autenticado
        $user = Auth::user();

        // Generar token con Sanctum
        $token = $user->createToken('ProveedorToken')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    /**
     * Obtener datos del proveedor autenticado
     */
    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()], 200);
    }

    /**
     * Cerrar sesión y revocar tokens
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }
}
