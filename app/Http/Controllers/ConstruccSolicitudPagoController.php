<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConstruccSolicitudPago;

class ConstruccSolicitudPagoController extends Controller
{
    // Mapping of nivel_id to roles
    protected $nivelToRol = [
        0 => 'Administrador', // Directly authorized
        1 => 'Director General', // Directly authorized
        2 => 'Manager',
        3 => 'Supervisor',
    ];

    public function marcarComoVerificada(Request $request, $id)
    {
        $solicitudPago = ConstruccSolicitudPago::findOrFail($id);

        $nivelId = $request->user()->nivel_id;

        // Check if the user has a role that allows direct authorization
        if (in_array($nivelId, [0, 1])) {
            $solicitudPago->estado = 'autorizado';
            $solicitudPago->save();

            return response()->json([
                'message' => 'Solicitud de pago autorizada directamente para roles 0 (Administrador) y 1 (Director General).',
                'solicitudPago' => $solicitudPago,
            ]);
        }

        // Implement other authorization logic as needed...

        return response()->json([
            'message' => 'Usuario no autorizado para realizar esta acción directamente.',
        ], 403);
    }
}
