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
      Log::channel('inter_api')->info('Iniciando notificación de nueva solicitud de compra', [
        'sp_id' => $sp->id ?? null
      ]);

      $payload = [
        'sp_id' => $sp->id,
        'sp_folio' => $sp->numero_folio_solicitud,
        'company' => '14',
        'obra' => '1',
        // 'message' => 'Ahora si lleva mensaje',
        'user_id' => 75
      ];

      Log::channel('inter_api')->info('Payload preparado para notificación SP', [
        'payload' => $payload
      ]);

      $url = "{$this->apiContruccUrl}/api/notify-sp";
      Log::channel('inter_api')->info('URL destino para notificación SP', [
        'url' => $url
      ]);

      // Enviar a API Proveedores con ApiKey header
      $response = Http::withoutVerifying()
        ->withHeaders([
          'X-API-KEY' => $this->apiContruccApiKey,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json'
        ])
        ->timeout($this->timeout)
        // ->retry(3, 100) // 3 reintentos con 100ms de delay
        ->post($url, $payload);

      Log::channel('inter_api')->info('Respuesta recibida desde API Proveedores', [
        'status' => $response->status(),
        'body' => $response->body()
      ]);

      if ($response->successful()) {
        Log::channel('inter_api')->info('Notificación SP enviada exitosamente', [
          'sp_id' => $sp->id
        ]);

        return [
          'success' => true,
          'data' => $response->json()
        ];
      }

      Log::channel('inter_api')->warning('Fallo en notificación SP', [
        'sp_id' => $sp->id,
        'status' => $response->status(),
        'error' => $response->body()
      ]);

      return [
        'success' => false,
        'error' => $response->body()
      ];
    } catch (\Exception $e) {
      Log::channel('inter_api')->error('Excepción al notificar SP', [
        'sp_id' => $sp->id ?? null,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'sp_id' => $sp->numero_folio_solicitud,
        'company' => '14',
        'obra' => '1',
        'message' => '',
        'user_id' => 75,
        'e' => $e
      ]);


      // No lanzamos la excepción para que no afecte el guardado
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }
}
