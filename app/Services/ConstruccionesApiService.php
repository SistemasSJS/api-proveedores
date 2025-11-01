<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConstruccionesApiService
{
    protected $apiUrl;
    protected $apiKey;
    protected $timeout;

    public function __construct()
    {
        $this->apiUrl = config('services.api_construcciones.url');
        $this->apiKey = config('services.api_construcciones.apikey');
        $this->timeout = config('services.api_construcciones.timeout', 15);
    }

    /**
     * Obtener lista de órdenes de compra de un proveedor
     */
    public function getOrdenesCompraByProveedor(int $proveedorId, array $params = [])
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Accept' => 'application/json'
            ])
                ->timeout($this->timeout)
                ->get("{$this->apiUrl}/api/ordenes-compra/proveedor/{$proveedorId}", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::channel('inter_api')->error('Error al obtener órdenes de compra', [
                'proveedor_id' => $proveedorId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status()
            ];
        } catch (\Exception $e) {
            Log::channel('inter_api')->error('Excepción al obtener órdenes de compra', [
                'proveedor_id' => $proveedorId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener detalle de una orden de compra específica
     */
    public function getOrdenCompraById(string $ordenCompraId)
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Accept' => 'application/json'
            ])
                ->timeout($this->timeout)
                ->get("{$this->apiUrl}/api/ordenes-compra/{$ordenCompraId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::channel('inter_api')->error('Error al obtener detalle de orden de compra', [
                'orden_compra_id' => $ordenCompraId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status()
            ];
        } catch (\Exception $e) {
            Log::channel('inter_api')->error('Excepción al obtener detalle de orden de compra', [
                'orden_compra_id' => $ordenCompraId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
