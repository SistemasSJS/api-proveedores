<?php

namespace App\Http\Controllers;

use App\Http\Resources\TiendaProductoResource;
use App\Http\Resources\TiendaProveedorResource;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\AccesoRapido;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TiendaController extends Controller
{
    public function accesosRapidos()
    {
        $accesos = AccesoRapido::where('activo', true)
            ->orderBy('orden')
            ->select('id', 'titulo', 'descripcion', 'icono', 'url', 'color')
            ->get();

        return $this->success($accesos);
    }

    public function proveedoresPrincipales(Request $request)
    {
        $query = Proveedor::where('estatus', 'activo')
            ->where('principal', true);

        // Filtros de búsqueda
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('categoria')) {
            $query->where('categoria', $request->get('categoria'));
        }

        if ($request->has('ciudad')) {
            $query->where('ciudad', $request->get('ciudad'));
        }

        if ($request->has('calificacion_min')) {
            $query->where('calificacion', '>=', $request->get('calificacion_min'));
        }

        // $proveedores = $query->with(['productos' => function ($q) {
        //     $q->where('activo', true)->limit(3);
        // }])
        $proveedores = $query->with(Proveedor::eagerLodable())
            // ->select('id', 'nombre', 'descripcion', 'logo', 'calificacion', 'categoria', 'ciudad')
            ->orderBy('calificacion', 'desc')
            ->paginate($request->get('per_page', 15));

        $data = TiendaProveedorResource::collection($proveedores)->resolve();
        return $this->paginated($proveedores->setCollection(collect($data)));
    }

    public function productosDestacados(Request $request)
    {
        $limit = $request->get('limit', 6);

        $query = Producto::where('activo', true)
            ->where('destacado', true);

        // Filtros de búsqueda
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('codigo', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('categoria')) {
            $query->where('categoria', $request->get('categoria'));
        }

        if ($request->has('proveedor_id')) {
            $query->where('proveedor_id', $request->get('proveedor_id'));
        }

        if ($request->has('precio_min')) {
            $query->where('precio', '>=', $request->get('precio_min'));
        }

        if ($request->has('precio_max')) {
            $query->where('precio', '<=', $request->get('precio_max'));
        }

        if ($request->has('disponible') && $request->get('disponible') == true) {
            $query->where('stock', '>', 0);
        }

        $productos = $query->with(Producto::eagerLodable())
            // ->select('id', 'nombre', 'descripcion', 'precio', 'imagen', 'stock', 'categoria', 'proveedor_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success(TiendaProductoResource::collection($productos));
    }

    public function productosMasPedidos(Request $request)
    {
        $limit = $request->get('limit', 8);

        $query = Producto::where('activo', true)
            ->whereHas('pedidoProductos');

        // Filtros de búsqueda
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('codigo', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('categoria')) {
            $query->where('categoria', $request->get('categoria'));
        }

        if ($request->has('proveedor_id')) {
            $query->where('proveedor_id', $request->get('proveedor_id'));
        }

        if ($request->has('precio_min')) {
            $query->where('precio', '>=', $request->get('precio_min'));
        }

        if ($request->has('precio_max')) {
            $query->where('precio', '<=', $request->get('precio_max'));
        }

        if ($request->has('disponible') && $request->get('disponible') == true) {
            $query->where('stock', '>', 0);
        }

        if ($request->has('periodo')) {
            $periodo = $request->get('periodo');
            $fechaInicio = match ($periodo) {
                'semana' => Carbon::now()->subWeek(),
                'mes' => Carbon::now()->subMonth(),
                'trimestre' => Carbon::now()->subMonths(3),
                default => Carbon::now()->subMonth()
            };

            $query->whereHas('pedidoProductos.pedido', function ($q) use ($fechaInicio) {
                $q->where('created_at', '>=', $fechaInicio);
            });
        }

        $productos = $query->withCount(['pedidoProductos as total_pedidos' => function ($q) use ($request) {
            if ($request->has('periodo')) {
                $periodo = $request->get('periodo');
                $fechaInicio = match ($periodo) {
                    'semana' => Carbon::now()->subWeek(),
                    'mes' => Carbon::now()->subMonth(),
                    'trimestre' => Carbon::now()->subMonths(3),
                    default => Carbon::now()->subMonth()
                };

                $q->whereHas('pedido', function ($query) use ($fechaInicio) {
                    $query->where('created_at', '>=', $fechaInicio);
                });
            }
        }])
            ->with(['proveedor:id,nombre,logo'])
            ->select('id', 'nombre', 'descripcion', 'precio', 'imagen', 'stock', 'categoria', 'proveedor_id')
            ->orderBy('total_pedidos', 'desc')
            ->limit($limit)
            ->get();

        return $this->success(TiendaProductoResource::collection($productos));
    }

    public function productosRecientes(Request $request)
    {
        $limit = $request->get('limit', 8);

        $query = Producto::where('activo', true);

        // Filtros de búsqueda
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('codigo', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('categoria')) {
            $query->where('categoria', $request->get('categoria'));
        }

        if ($request->has('proveedor_id')) {
            $query->where('proveedor_id', $request->get('proveedor_id'));
        }

        if ($request->has('precio_min')) {
            $query->where('precio', '>=', $request->get('precio_min'));
        }

        if ($request->has('precio_max')) {
            $query->where('precio', '<=', $request->get('precio_max'));
        }

        if ($request->has('disponible') && $request->get('disponible') == true) {
            $query->where('stock', '>', 0);
        }

        if ($request->has('dias')) {
            $dias = $request->get('dias', 30);
            $query->where('created_at', '>=', Carbon::now()->subDays($dias));
        }

        $productos = $query->with(Producto::eagerLodable())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        return $this->success(TiendaProductoResource::collection($productos));
    }
}
