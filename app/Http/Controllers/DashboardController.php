<?php

namespace App\Http\Controllers;

use App\Http\Resources\RequisicionResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $user = Auth::user();

        $stats = [
            'requisiciones_pendientes' => $user->requisiciones()->where('estatus', 'pendiente')->count(),
            'requisiciones_en_proceso' => $user->requisiciones()->where('estatus', 'en_proceso')->count(),
            'requisiciones_completadas' => $user->requisiciones()->where('estatus', 'entregada')->count(),
            'notificaciones_no_leidas' => $user->notificaciones()->where('leida', false)->count(),
        ];

        $requisiciones_recientes = $user->requisiciones()
            ->with(['proveedor', 'detalles'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'requisiciones_recientes' => RequisicionResource::collection($requisiciones_recientes),
        ]);
    }
}
