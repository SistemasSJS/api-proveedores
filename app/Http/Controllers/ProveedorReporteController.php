<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Services\ReporteService;
use Illuminate\Http\Request;

class ProveedorReporteController extends Controller
{
    protected $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    public function ventas(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'periodo' => 'nullable|in:semana,mes,trimestre,año',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $reporte = $this->reporteService->reporteVentasProveedor(
            $proveedor->id,
            $request->input('periodo', 'mes'),
            $request->input('fecha_inicio'),
            $request->input('fecha_fin')
        );

        return response()->json(['success' => true, 'data' => $reporte]);
    }

    public function productosPopulares(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'dias' => 'nullable|integer|min:1|max:365',
            'limite' => 'nullable|integer|min:5|max:50',
        ]);

        $productos = $this->reporteService->getProductosPopulares(
            $proveedor->id,
            $request->input('dias', 30),
            $request->input('limite', 20)
        );

        return response()->json([
            'success' => true,
            'data' => [
                'periodo_dias' => $request->input('dias', 30),
                'productos' => $productos,
                'total_productos' => count($productos),
            ],
        ]);
    }

    public function requisicionesMensuales(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'año' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'meses_anteriores' => 'nullable|integer|min:1|max:24',
        ]);

        $reporte = $this->reporteService->reporteRequisicionesMensuales(
            $proveedor->id,
            $request->input('año', date('Y')),
            $request->input('meses_anteriores', 12)
        );

        return response()->json(['success' => true, 'data' => $reporte]);
    }

    public function inventarioSucursales(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'con_stock_bajo' => 'nullable|boolean',
            'stock_minimo' => 'nullable|integer|min:0',
        ]);

        $reporte = $this->reporteService->reporteInventarioSucursales(
            $proveedor->id,
            $request->only(['sucursal_id', 'con_stock_bajo', 'stock_minimo'])
        );

        return response()->json(['success' => true, 'data' => $reporte]);
    }

    public function rendimientoCategorias(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'periodo' => 'nullable|in:semana,mes,trimestre,año',
            'incluir_subcategorias' => 'nullable|boolean',
        ]);

        $reporte = $this->reporteService->reporteRendimientoCategorias(
            $proveedor->id,
            $request->input('periodo', 'mes'),
            $request->boolean('incluir_subcategorias', true)
        );

        return response()->json(['success' => true, 'data' => $reporte]);
    }

    public function clientesActivos(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'dias' => 'nullable|integer|min:1|max:365',
            'limite' => 'nullable|integer|min:5|max:100',
        ]);

        $reporte = $this->reporteService->reporteClientesActivos(
            $proveedor->id,
            $request->input('dias', 90),
            $request->input('limite', 25)
        );

        return response()->json(['success' => true, 'data' => $reporte]);
    }

    public function exportar(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'tipo_reporte' => 'required|in:ventas,productos_populares,requisiciones_mensuales,inventario',
            'formato' => 'required|in:pdf,excel,csv',
            'periodo' => 'nullable|in:semana,mes,trimestre,año',
        ]);

        try {
            $archivo = $this->reporteService->exportarReporte(
                $proveedor->id,
                $request->tipo_reporte,
                $request->formato,
                $request->all()
            );

            return response()->download($archivo)->deleteFileAfterSend();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function comparativo(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'periodo_actual' => 'required|in:semana,mes,trimestre,año',
            'periodo_anterior' => 'required|in:semana,mes,trimestre,año',
        ]);

        $actual = $this->reporteService->reporteVentasProveedor(
            $proveedor->id,
            $request->periodo_actual
        );

        $anterior = $this->reporteService->reporteVentasProveedor(
            $proveedor->id,
            $request->periodo_anterior
        );

        $comparativo = [
            'periodo_actual' => $actual,
            'periodo_anterior' => $anterior,
            'diferencias' => [
                'requisiciones' => $actual['total_requisiciones'] - $anterior['total_requisiciones'],
                'monto' => $actual['total_monto'] - $anterior['total_monto'],
                'promedio' => $actual['promedio_por_requisicion'] - $anterior['promedio_por_requisicion'],
            ],
            'porcentajes' => [
                'requisiciones' => $anterior['total_requisiciones'] > 0
                    ? (($actual['total_requisiciones'] - $anterior['total_requisiciones']) / $anterior['total_requisiciones']) * 100
                    : 0,
                'monto' => $anterior['total_monto'] > 0
                    ? (($actual['total_monto'] - $anterior['total_monto']) / $anterior['total_monto']) * 100
                    : 0,
            ],
        ];

        return response()->json(['success' => true, 'data' => $comparativo]);
    }
}
