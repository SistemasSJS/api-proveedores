<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InterApiService
{
  protected $apiContruccUrl;
  protected $apiContruccApiKey;
  protected $timeout;
  public function __construct()
  {
    $this->apiContruccUrl = config('services.api_construcciones.url');
    $this->apiContruccApiKey = config('services.api_construcciones.apikey');
    $this->timeout = config('services.api_construcciones.timeout', 10);
  }
  /**
   * Notificar a API Proveedores sobre nueva orden de compra
   */
  public function notifyNewSolicitudCompra($sp)
  {
    try {
      $payload = [
        'sp_id' => $sp->id,
        'company' => 14,
        'obra' => 1,
        'message' => 'Nueva Solicitud de compra',
        'user_id' => 75,
      ];

      // Enviar a API Proveedores con ApiKey header
      $response = Http::withoutVerifying()
        ->withHeaders([
          'X-API-KEY' => $this->apiContruccApiKey,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json'
        ])
        ->timeout($this->timeout)
        ->retry(3, 100) // 3 reintentos con 100ms de delay
        ->post("{$this->apiContruccUrl}/notify-sp", $payload);

      if ($response->successful()) {
        return [
          'success' => true,
          'data' => $response->json()
        ];
      }
      return [
        'success' => false,
        'error' => $response->body()
      ];
    } catch (\Exception $e) {
      Log::channel('inter_api')->error('Excepción al notificar sp', [
        'sp_id' => $sp->id,
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
