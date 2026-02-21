<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactoRequest;
use App\Mail\ContactoMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactoController extends Controller
{
    /**
     * Enviar correo de contacto
     *
     * @param ContactoRequest $request
     * @return JsonResponse
     */
    public function enviarContacto(ContactoRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Obtener el correo de destino desde las variables de entorno
            $destinatario = 'contacto@sjsconstrucciones.com';

            // Enviar el correo
            Mail::to($destinatario)->send(new ContactoMail(
                $validated['nombre'],
                $validated['email'],
                $validated['telefono'] ?? null,
                $validated['empresa'] ?? null,
                $validated['mensaje']
            ));

            // Log del envío exitoso
            Log::info('Correo de contacto enviado', [
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                'destinatario' => $destinatario
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado correctamente. Nos pondremos en contacto contigo pronto.'
            ], 200);
        } catch (\Exception $e) {
            // Log del error
            Log::error('Error al enviar correo de contacto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al enviar el mensaje. Por favor, intenta nuevamente más tarde.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
