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
   * Notificar a API Construcciones sobre nueva solicitud de pago
   */
  public function notifyNewSolicitudCompra($sp)
  {
    try {
      Log::channel('inter_api')->info('Iniciando notificación de nueva solicitud de pago', [
        'sp_id' => $sp->id ?? null
      ]);

      $payload = [
        'sp_id' => $sp->id,
        'sp_folio' => $sp->numero_folio_solicitud,
        'company' => $sp->empresa_construcc_id,
        'obra' => '1',
        // 'message' => 'Ahora si lleva mensaje',
        'user_id' => $sp->residente
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

      Log::channel('inter_api')->info('Respuesta recibida desde API Construcciones', [
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
        'sp_folio' => $sp->numero_folio_solicitud ?? null,
        'company' => $sp->empresa_construcc_id ?? null,
        'user_id' => $sp->residente ?? null,
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

  /**
   * Notificar a API Construcciones sobre validación de SP
   * 
   * @param int $spId ID de la solicitud de pago
   * @param string $spFolio Folio de la solicitud de pago
   * @param string $company ID de la compañía
   * @param int $validatorUserId ID del usuario validador
   * @return array
   */
  public function spNotifyByValidator($spId, $spFolio, $company, $validatorUserId)
  {
    try {
      Log::channel('inter_api')->info('Iniciando notificación de validación por validador', [
        'sp_id' => $spId,
        'sp_folio' => $spFolio,
        'company' => $company,
        'validator_user_id' => $validatorUserId
      ]);

      $payload = [
        'sp_id' => $spId,
        'sp_folio' => $spFolio,
        'company' => $company,
        'validator_user_id' => $validatorUserId
      ];

      Log::channel('inter_api')->info('Payload preparado para notificación de validador', [
        'payload' => $payload
      ]);

      $url = "{$this->apiContruccUrl}/api/sp-notify-by-validator";
      Log::channel('inter_api')->info('URL destino para notificación de validador', [
        'url' => $url
      ]);

      // Enviar a API Construcciones con ApiKey header
      $response = Http::withoutVerifying()
        ->withHeaders([
          'X-API-KEY' => $this->apiContruccApiKey,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json'
        ])
        ->timeout($this->timeout)
        ->post($url, $payload);

      Log::channel('inter_api')->info('Respuesta recibida desde API Construcciones', [
        'status' => $response->status(),
        'body' => $response->body()
      ]);

      if ($response->successful()) {
        Log::channel('inter_api')->info('Notificación de validador enviada exitosamente', [
          'sp_id' => $spId,
          'sp_folio' => $spFolio
        ]);

        return [
          'success' => true,
          'data' => $response->json()
        ];
      }

      Log::channel('inter_api')->warning('Fallo en notificación de validador', [
        'sp_id' => $spId,
        'status' => $response->status(),
        'error' => $response->body()
      ]);

      return [
        'success' => false,
        'error' => $response->body()
      ];
    } catch (\Exception $e) {
      Log::channel('inter_api')->error('Excepción al notificar validador', [
        'sp_id' => $spId ?? null,
        'sp_folio' => $spFolio ?? null,
        'company' => $company ?? null,
        'validator_user_id' => $validatorUserId ?? null,
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
