<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InterApiService
{
  protected $apiProveedoresUrl;
  protected $apiProveedoresApiKey;
  protected $timeout;
  public function __construct()
  {
    $this->apiProveedoresUrl = config('services.api_proveedores.url');
    $this->apiProveedoresApiKey = config('services.api_proveedores.apikey');
    $this->timeout = config('services.api_proveedores.timeout', 10);
  }
  /**
   * Notificar a API Proveedores sobre nueva orden de compra
   */
  public function notifyNewOrdenCompra($ordenCompra)
  {
    try {
      // Enviar a API Proveedores con ApiKey header
      $response = Http::withHeaders([
        'X-API-KEY' => $this->apiProveedoresApiKey,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
      ])
        ->timeout($this->timeout)
        ->retry(3, 100) // 3 reintentos con 100ms de delay
        ->post("{$this->apiProveedoresUrl}/api/notificaciones/nueva-orden", [
          'orden_compra_id' => $ordenCompra->id,
          'proveedor_id' => $ordenCompra->proveedor_id,
          'total' => $ordenCompra->total,
          'productos' => $ordenCompra->detalles->map(function ($detalle) {
            return [
              'nombre' => $detalle->producto_nombre,
              'cantidad' => $detalle->cantidad,
              'precio' => $detalle->precio_unitario,
              'subtotal' => $detalle->subtotal
            ];
          })->toArray(),
          'fecha' => $ordenCompra->created_at->toIso8601String(),
          'estado' => $ordenCompra->estado,
          'observaciones' => $ordenCompra->observaciones ?? null
        ]);
      if ($response->successful()) {
        Log::channel('inter_api')->info('Orden de compra notificada a proveedores', [
          'orden_compra_id' => $ordenCompra->id,
          'proveedor_id' => $ordenCompra->proveedor_id,
          'response' => $response->json()
        ]);
        return [
          'success' => true,
          'data' => $response->json()
        ];
      }
      Log::channel('inter_api')->error('Error al notificar orden de compra', [
        'orden_compra_id' => $ordenCompra->id,
        'status' => $response->status(),
        'body' => $response->body()
      ]);
      return [
        'success' => false,
        'error' => $response->body()
      ];
    } catch (\Exception $e) {
      Log::channel('inter_api')->error('Excepción al notificar orden de compra', [
        'orden_compra_id' => $ordenCompra->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      // No lanzamos la excepción para que no afecte el guardado
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }
}
