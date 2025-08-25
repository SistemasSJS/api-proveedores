<?php

namespace App\Services;

use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteService
{
  /**
   * Reporte de ventas del proveedor con período personalizable
   */
  public function reporteVentasProveedor(int $proveedorId, string $periodo = 'mes', ?string $fechaInicio = null, ?string $fechaFin = null): array
  {
    if ($fechaInicio && $fechaFin) {
      $inicio = Carbon::parse($fechaInicio);
      $fin = Carbon::parse($fechaFin);
    } else {
      $inicio = match ($periodo) {
        'semana' => now()->startOfWeek(),
        'mes' => now()->startOfMonth(),
        'trimestre' => now()->startOfQuarter(),
        'año' => now()->startOfYear(),
        default => now()->startOfMonth(),
      };
      $fin = now();
    }

    $requisiciones = Requisicion::where('proveedor_id', $proveedorId)
      ->whereBetween('created_at', [$inicio, $fin])
      ->whereIn('estatus', ['cotizada', 'entregada'])
      ->with(['detalles.producto'])
      ->get();

    $totalRequisiciones = $requisiciones->count();
    $totalMonto = $requisiciones->sum('total_estimado');

    $productosMasSolicitados = $requisiciones->flatMap(function ($req) {
      return $req->detalles;
    })->groupBy('producto_id')->map(function ($grupo) {
      $producto = $grupo->first()->producto;
      return [
        'producto' => $producto->nombre,
        'sku' => $producto->sku,
        'cantidad_total' => $grupo->sum('cantidad'),
        'monto_total' => $grupo->sum('subtotal_estimado'),
      ];
    })->sortByDesc('cantidad_total')->take(10)->values();

    // Análisis por categorías
    $ventasPorCategoria = $requisiciones->flatMap->detalles
      ->groupBy('producto.categoria.nombre')
      ->map(function ($grupo, $categoria) {
        return [
          'categoria' => $categoria ?? 'Sin categoría',
          'cantidad_productos' => $grupo->sum('cantidad'),
          'monto_total' => $grupo->sum('subtotal_estimado'),
          'productos_diferentes' => $grupo->pluck('producto_id')->unique()->count(),
        ];
      })->sortByDesc('monto_total');

    return [
      'periodo' => $periodo,
      'fecha_inicio' => $inicio->toDateString(),
      'fecha_fin' => $fin->toDateString(),
      'total_requisiciones' => $totalRequisiciones,
      'total_monto' => $totalMonto,
      'promedio_por_requisicion' => $totalRequisiciones > 0 ? $totalMonto / $totalRequisiciones : 0,
      'productos_mas_solicitados' => $productosMasSolicitados,
      'ventas_por_categoria' => $ventasPorCategoria,
      'tendencia_diaria' => $this->calcularTendenciaDiaria($requisiciones, $inicio, $fin),
    ];
  }

  /**
   * Obtener productos más populares
   */
  public function getProductosPopulares(int $proveedorId, int $diasAtras = 30, int $limite = 20): array
  {
    $fechaInicio = now()->subDays($diasAtras);

    $productosPopulares = Producto::where('proveedor_id', $proveedorId)
      ->withCount(['requisicionDetalles as veces_solicitado' => function ($query) use ($fechaInicio) {
        $query->whereHas('requisicion', function ($q) use ($fechaInicio) {
          $q->where('created_at', '>=', $fechaInicio);
        });
      }])
      ->with(['marca', 'categoria'])
      ->having('veces_solicitado', '>', 0)
      ->orderBy('veces_solicitado', 'desc')
      ->limit($limite)
      ->get();

    return $productosPopulares->map(function ($producto) {
      return [
        'id' => $producto->id,
        'sku' => $producto->sku,
        'nombre' => $producto->nombre,
        'descripcion' => $producto->descripcion,
        'marca' => $producto->marca?->nombre,
        'categoria' => $producto->categoria?->nombre,
        'precio_base' => (float) $producto->precio_base,
        'stock_actual' => (int) $producto->stock,
        'veces_solicitado' => (int) $producto->veces_solicitado,
        'imagen_principal' => $producto->imagen_principal,
      ];
    })->toArray();
  }

  /**
   * Reporte de requisiciones mensuales
   */
  public function reporteRequisicionesMensuales(int $proveedorId, int $año, int $mesesAnteriores = 12): array
  {
    $meses = collect();

    for ($i = $mesesAnteriores - 1; $i >= 0; $i--) {
      $fecha = Carbon::create($año)->subMonths($i);

      $requisiciones = Requisicion::where('proveedor_id', $proveedorId)
        ->whereYear('created_at', $fecha->year)
        ->whereMonth('created_at', $fecha->month)
        ->get();

      $meses->push([
        'mes' => $fecha->format('M Y'),
        'año' => $fecha->year,
        'mes_numero' => $fecha->month,
        'total_requisiciones' => $requisiciones->count(),
        'monto_total' => $requisiciones->sum('total_estimado'),
        'por_estatus' => $requisiciones->groupBy('estatus')->map->count(),
        'promedio_por_requisicion' => $requisiciones->count() > 0
          ? $requisiciones->sum('total_estimado') / $requisiciones->count()
          : 0,
      ]);
    }

    return [
      'año' => $año,
      'meses_datos' => $meses,
      'resumen' => [
        'total_requisiciones' => $meses->sum('total_requisiciones'),
        'monto_total' => $meses->sum('monto_total'),
        'promedio_mensual' => $meses->avg('total_requisiciones'),
        'mejor_mes' => $meses->sortByDesc('total_requisiciones')->first(),
      ],
    ];
  }

  /**
   * Reporte de inventario por sucursales
   */
  public function reporteInventarioSucursales(int $proveedorId, array $filtros = []): array
  {
    $query = Sucursal::where('proveedor_id', $proveedorId)
      ->with(['productos' => function ($q) use ($filtros) {
        if (isset($filtros['con_stock_bajo']) && $filtros['con_stock_bajo']) {
          $stockMinimo = $filtros['stock_minimo'] ?? 10;
          $q->wherePivot('stock_local', '<=', $stockMinimo);
        }
      }]);

    if (isset($filtros['sucursal_id'])) {
      $query->where('id', $filtros['sucursal_id']);
    }

    $sucursales = $query->get();

    return $sucursales->map(function ($sucursal) {
      $productos = $sucursal->productos;

      return [
        'sucursal_id' => $sucursal->id,
        'sucursal_nombre' => $sucursal->nombre,
        'productos_total' => $productos->count(),
        'productos_activos' => $productos->where('pivot.activo', true)->count(),
        'stock_total' => $productos->sum('pivot.stock_local'),
        'valor_inventario' => $productos->sum(function ($producto) {
          return $producto->pivot->stock_local * ($producto->pivot->precio_local ?? $producto->precio_base);
        }),
        'productos_sin_stock' => $productos->where('pivot.stock_local', 0)->count(),
        'productos_stock_bajo' => $productos->where('pivot.stock_local', '<=', 10)->count(),
      ];
    })->toArray();
  }

  /**
   * Reporte de clientes más activos
   */
  public function reporteClientesActivos(int $proveedorId, int $dias, int $limite): array
  {
    $fechaInicio = now()->subDays($dias);

    $clientes = DB::table('requisiciones')
      ->join('users', 'requisiciones.usuario_id', '=', 'users.id')
      ->where('requisiciones.proveedor_id', $proveedorId)
      ->where('requisiciones.created_at', '>=', $fechaInicio)
      ->select(
        'users.id',
        'users.name',
        'users.email',
        DB::raw('COUNT(requisiciones.id) as total_requisiciones'),
        DB::raw('SUM(requisiciones.total_estimado) as monto_total'),
        DB::raw('AVG(requisiciones.total_estimado) as promedio_por_requisicion'),
        DB::raw('MAX(requisiciones.created_at) as ultima_requisicion')
      )
      ->groupBy('users.id', 'users.name', 'users.email')
      ->orderBy('total_requisiciones', 'desc')
      ->limit($limite)
      ->get();

    return $clientes->map(function ($cliente) {
      return [
        'id' => $cliente->id,
        'nombre' => $cliente->name,
        'email' => $cliente->email,
        'total_requisiciones' => (int) $cliente->total_requisiciones,
        'monto_total' => (float) $cliente->monto_total,
        'promedio_por_requisicion' => (float) $cliente->promedio_por_requisicion,
        'ultima_requisicion' => $cliente->ultima_requisicion,
        'dias_desde_ultima' => Carbon::parse($cliente->ultima_requisicion)->diffInDays(now()),
      ];
    })->toArray();
  }

  /**
   * Reporte de rendimiento por categorías
   */
  public function reporteRendimientoCategorias(int $proveedorId, string $periodo, bool $incluirSubcategorias = true): array
  {
    $fechaInicio = match ($periodo) {
      'semana' => now()->startOfWeek(),
      'mes' => now()->startOfMonth(),
      'trimestre' => now()->startOfQuarter(),
      'año' => now()->startOfYear(),
      default => now()->startOfMonth(),
    };

    $query = DB::table('requisicion_detalles')
      ->join('requisiciones', 'requisicion_detalles.requisicion_id', '=', 'requisiciones.id')
      ->join('productos', 'requisicion_detalles.producto_id', '=', 'productos.id')
      ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
      ->where('productos.proveedor_id', $proveedorId)
      ->where('requisiciones.created_at', '>=', $fechaInicio)
      ->whereIn('requisiciones.estatus', ['cotizada', 'entregada']);

    if ($incluirSubcategorias) {
      $categorias = $query->select(
        'categorias.id',
        'categorias.nombre as categoria',
        'categorias.parent_id',
        DB::raw('COUNT(DISTINCT requisicion_detalles.requisicion_id) as requisiciones'),
        DB::raw('SUM(requisicion_detalles.cantidad) as cantidad_total'),
        DB::raw('SUM(requisicion_detalles.subtotal_estimado) as monto_total'),
        DB::raw('COUNT(DISTINCT productos.id) as productos_diferentes')
      )->groupBy('categorias.id', 'categorias.nombre', 'categorias.parent_id');
    } else {
      $categorias = $query->whereNull('categorias.parent_id')
        ->select(
          'categorias.id',
          'categorias.nombre as categoria',
          DB::raw('COUNT(DISTINCT requisicion_detalles.requisicion_id) as requisiciones'),
          DB::raw('SUM(requisicion_detalles.cantidad) as cantidad_total'),
          DB::raw('SUM(requisicion_detalles.subtotal_estimado) as monto_total'),
          DB::raw('COUNT(DISTINCT productos.id) as productos_diferentes')
        )->groupBy('categorias.id', 'categorias.nombre');
    }

    return $categorias->orderBy('monto_total', 'desc')->get()->map(function ($categoria) {
      return [
        'categoria_id' => $categoria->id,
        'categoria' => $categoria->categoria,
        'es_subcategoria' => isset($categoria->parent_id) && $categoria->parent_id !== null,
        'requisiciones' => $categoria->requisiciones,
        'cantidad_total' => $categoria->cantidad_total,
        'monto_total' => round($categoria->monto_total, 2),
        'productos_diferentes' => $categoria->productos_diferentes,
        'promedio_por_requisicion' => $categoria->requisiciones > 0
          ? round($categoria->monto_total / $categoria->requisiciones, 2)
          : 0,
      ];
    })->toArray();
  }

  /**
   * Exportar reporte en diferentes formatos
   */
  public function exportarReporte(int $proveedorId, string $tipoReporte, string $formato, array $parametros = []): string
  {
    // Obtener datos según el tipo de reporte
    $datos = match ($tipoReporte) {
      'ventas' => $this->reporteVentasProveedor($proveedorId, $parametros['periodo'] ?? 'mes'),
      'productos_populares' => $this->getProductosPopulares($proveedorId, $parametros['dias'] ?? 30),
      'requisiciones_mensuales' => $this->reporteRequisicionesMensuales($proveedorId, $parametros['año'] ?? date('Y')),
      'inventario' => $this->reporteInventarioSucursales($proveedorId, $parametros),
      default => throw new \InvalidArgumentException('Tipo de reporte no válido'),
    };

    // Generar archivo según el formato
    $nombreArchivo = "reporte_{$tipoReporte}_" . date('Y-m-d_H-i-s');

    return match ($formato) {
      'pdf' => $this->generarReportePDF($datos, $tipoReporte, $nombreArchivo),
      'excel' => $this->generarReporteExcel($datos, $tipoReporte, $nombreArchivo),
      'csv' => $this->generarReporteCSV($datos, $tipoReporte, $nombreArchivo),
      default => throw new \InvalidArgumentException('Formato no válido'),
    };
  }

  /**
   * Estadísticas generales del proveedor
   */
  public function reporteEstadisticasGenerales(int $proveedorId): array
  {
    $proveedor = Proveedor::findOrFail($proveedorId);

    $totalProductos = $proveedor->productos()->count();
    $productosActivos = $proveedor->productos()->where('activo', true)->count();
    $totalSucursales = $proveedor->sucursales()->count();
    $sucursalesActivas = $proveedor->sucursales()->where('activa', true)->count();

    $requisicionesUltimoMes = $proveedor->requisiciones()
      ->where('created_at', '>=', now()->subMonth())
      ->count();

    $requisicionesPendientes = $proveedor->requisiciones()
      ->where('estatus', 'pendiente')
      ->count();

    return [
      'productos' => [
        'total' => $totalProductos,
        'activos' => $productosActivos,
        'inactivos' => $totalProductos - $productosActivos,
      ],
      'sucursales' => [
        'total' => $totalSucursales,
        'activas' => $sucursalesActivas,
        'inactivas' => $totalSucursales - $sucursalesActivas,
      ],
      'requisiciones' => [
        'ultimo_mes' => $requisicionesUltimoMes,
        'pendientes' => $requisicionesPendientes,
      ],
    ];
  }

  /**
   * Calcular tendencia diaria de ventas
   */
  private function calcularTendenciaDiaria($requisiciones, Carbon $inicio, Carbon $fin): array
  {
    $dias = collect();
    $fechaActual = $inicio->copy();

    while ($fechaActual->lte($fin)) {
      $requisicionesDia = $requisiciones->filter(function ($req) use ($fechaActual) {
        return $req->created_at->isSameDay($fechaActual);
      });

      $dias->push([
        'fecha' => $fechaActual->toDateString(),
        'dia_semana' => $fechaActual->format('l'),
        'requisiciones' => $requisicionesDia->count(),
        'monto' => $requisicionesDia->sum('total_estimado'),
      ]);

      $fechaActual->addDay();
    }

    return $dias->toArray();
  }

  /**
   * Generar reporte PDF (retorna datos JSON)
   */
  private function generarReportePDF(array $datos, string $tipo, string $nombre): string
  {
    $rutaArchivo = storage_path("app/temp/{$nombre}.json");
    file_put_contents($rutaArchivo, json_encode($datos, JSON_PRETTY_PRINT));

    return $rutaArchivo;
  }

  /**
   * Generar reporte Excel (retorna datos JSON)
   */
  private function generarReporteExcel(array $datos, string $tipo, string $nombre): string
  {
    $rutaArchivo = storage_path("app/temp/{$nombre}.json");
    file_put_contents($rutaArchivo, json_encode($datos, JSON_PRETTY_PRINT));

    return $rutaArchivo;
  }

  /**
   * Generar reporte CSV
   */
  private function generarReporteCSV(array $datos, string $tipo, string $nombre): string
  {
    $rutaArchivo = storage_path("app/temp/{$nombre}.csv");

    $archivo = fopen($rutaArchivo, 'w');

    // Escribir encabezados y datos según el tipo de reporte
    match ($tipo) {
      'ventas' => $this->escribirCSVVentas($archivo, $datos),
      'productos_populares' => $this->escribirCSVProductos($archivo, $datos),
      'requisiciones_mensuales' => $this->escribirCSVRequisiciones($archivo, $datos),
      'inventario' => $this->escribirCSVInventario($archivo, $datos),
    };

    fclose($archivo);
    return $rutaArchivo;
  }

  /**
   * Escribir CSV para reporte de ventas
   */
  private function escribirCSVVentas($archivo, array $datos): void
  {
    // Encabezados
    fputcsv($archivo, [
      'Periodo',
      'Total Requisiciones',
      'Monto Total',
      'Promedio por Requisicion'
    ]);

    // Datos principales
    fputcsv($archivo, [
      $datos['periodo'],
      $datos['total_requisiciones'],
      $datos['total_monto'],
      $datos['promedio_por_requisicion']
    ]);

    // Separador
    fputcsv($archivo, []);
    fputcsv($archivo, ['Productos Más Solicitados']);
    fputcsv($archivo, ['Producto', 'SKU', 'Cantidad Total', 'Monto Total']);

    // Productos populares
    foreach ($datos['productos_mas_solicitados'] as $producto) {
      fputcsv($archivo, [
        $producto['producto'],
        $producto['sku'],
        $producto['cantidad_total'],
        $producto['monto_total']
      ]);
    }
  }

  /**
   * Escribir CSV para productos populares
   */
  private function escribirCSVProductos($archivo, array $datos): void
  {
    fputcsv($archivo, [
      'SKU',
      'Nombre',
      'Marca',
      'Línea',
      'Categoría',
      'Precio Base',
      'Stock Actual',
      'Veces Solicitado'
    ]);

    foreach ($datos as $producto) {
      fputcsv($archivo, [
        $producto['sku'],
        $producto['nombre'],
        $producto['marca'],
        $producto['categoria'],
        $producto['precio_base'],
        $producto['stock_actual'],
        $producto['veces_solicitado']
      ]);
    }
  }

  /**
   * Escribir CSV para requisiciones mensuales
   */
  private function escribirCSVRequisiciones($archivo, array $datos): void
  {
    fputcsv($archivo, [
      'Mes',
      'Total Requisiciones',
      'Monto Total',
      'Promedio por Requisición'
    ]);

    foreach ($datos['meses_datos'] as $mes) {
      fputcsv($archivo, [
        $mes['mes'],
        $mes['total_requisiciones'],
        $mes['monto_total'],
        $mes['promedio_por_requisicion']
      ]);
    }
  }

  /**
   * Escribir CSV para inventario
   */
  private function escribirCSVInventario($archivo, array $datos): void
  {
    fputcsv($archivo, [
      'Sucursal',
      'Productos Total',
      'Productos Activos',
      'Stock Total',
      'Valor Inventario',
      'Sin Stock',
      'Stock Bajo'
    ]);

    foreach ($datos as $sucursal) {
      fputcsv($archivo, [
        $sucursal['sucursal_nombre'],
        $sucursal['productos_total'],
        $sucursal['productos_activos'],
        $sucursal['stock_total'],
        $sucursal['valor_inventario'],
        $sucursal['productos_sin_stock'],
        $sucursal['productos_stock_bajo']
      ]);
    }
  }
}
