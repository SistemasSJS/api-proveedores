<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificacionResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = Auth::user()->notificaciones()
            ->when($request->tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->when($request->leida !== null, function ($query) use ($request) {
                $query->where('leida', $request->boolean('leida'));
            })
            ->latest()
            ->paginate($request->per_page ?? 20);

        return NotificacionResource::collection($notificaciones);
    }

    public function marcarComoLeida(Notificacion $notificacion)
    {
        $this->authorize('update', $notificacion);

        $notificacion->update([
            'leida' => true,
            'fecha_lectura' => now(),
        ]);

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    public function marcarTodasComoLeidas(Request $request)
    {
        Auth::user()->notificaciones()
            ->where('leida', false)
            ->update([
                'leida' => true,
                'fecha_lectura' => now(),
            ]);

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas']);
    }

    public function destroy(Notificacion $notificacion)
    {
        $this->authorize('delete', $notificacion);
        $notificacion->delete();
        return response()->json(['message' => 'Notificación eliminada correctamente']);
    }
}
