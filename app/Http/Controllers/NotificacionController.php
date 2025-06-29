<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Http\Resources\NotificacionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tipo' => 'nullable|in:requisicion_nueva,requisicion_actualizada,cotizacion_recibida,sistema',
            'leida' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

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

    public function show(Notificacion $notificacion)
    {
        $this->authorize('view', $notificacion);

        // Marcar como leída automáticamente al ver
        if (!$notificacion->leida) {
            $notificacion->update([
                'leida' => true,
                'fecha_lectura' => now(),
            ]);
        }

        return new NotificacionResource($notificacion);
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
        $count = Auth::user()->notificaciones()
            ->where('leida', false)
            ->when($request->tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->update([
                'leida' => true,
                'fecha_lectura' => now(),
            ]);

        return response()->json([
            'message' => 'Notificaciones marcadas como leídas',
            'count' => $count
        ]);
    }

    public function destroy(Notificacion $notificacion)
    {
        $this->authorize('delete', $notificacion);
        $notificacion->delete();
        return response()->json(['message' => 'Notificación eliminada correctamente']);
    }

    public function resumen()
    {
        $user = Auth::user();

        $resumen = [
            'total' => $user->notificaciones()->count(),
            'no_leidas' => $user->notificaciones()->where('leida', false)->count(),
            'por_tipo' => $user->notificaciones()
                ->selectRaw('tipo, count(*) as total, sum(case when leida = 0 then 1 else 0 end) as no_leidas')
                ->groupBy('tipo')
                ->get()
                ->keyBy('tipo'),
            'ultima_semana' => $user->notificaciones()
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
        ];

        return response()->json(['data' => $resumen]);
    }

    public function eliminarLeidas(Request $request)
    {
        $diasAntiguedad = $request->input('dias', 30);

        $count = Auth::user()->notificaciones()
            ->where('leida', true)
            ->where('created_at', '<', now()->subDays($diasAntiguedad))
            ->delete();

        return response()->json([
            'message' => 'Notificaciones leídas eliminadas',
            'count' => $count
        ]);
    }
}
