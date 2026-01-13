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
        // 'sp_folio' => $sp->numero_folio_solicitud,
        'sp_folio' => $sp->folio_sp_consecutivo,
        'company' => $sp->empresa_construcc_id,
        'user_id' => $sp->usuario_id
        // 'obra' => '1', // la obra se indica despues de validar la SP... a este punto este valor es desconocido
        // 'message' => 'Ahora si lleva mensaje',
      ];

      Log::channel('inter_api')->info('Payload preparado para notificación SP', [
        'payload' => $payload
      ]);


      /**
       * unicamente para el caso del usuario: Julio Salazar se realiara la notificacion especial
       * se geenerara una nueva notioficacion que consiste en notificar a todos los usuarios directores
       * DT, PC, DA...
       */
      $USUARIO_ID_JULIO_SALAZAR = 41;
      // $USUARIO_ID_JULIO_SALAZAR = 75; // only test

      $url = "{$this->apiContruccUrl}/api/notify-sp-validada";
      if ($sp->usuario_id == $USUARIO_ID_JULIO_SALAZAR) {
        // nuevo end point para notificar a todos los directivos.
        $url = "{$this->apiContruccUrl}/api/notify-sp-directores";
      }

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
        'user_id' => $sp->usuario_id ?? null,
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

      $url = "{$this->apiContruccUrl}/api/notify-sp-validar";
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


  /**
   * Notificacion de pago para el usuario validador
   *         
   *    'sp_id'        => 'required|integer',
   *    'sp_folio'     => 'required|string',
   *    'company_id'   => 'required|integer',     // OJO: mejor numérico, no string
   *    'obra'         => 'required|string',
   *    'proveedor'    => 'required|string',      // <= lo que necesitas
   *    'monto'        => 'nullable|numeric',
   *    'fecha_pago'   => 'nullable|date',
   *    'user_id'      => 'nullable|integer',     // si lo sabes, lo mandas
   *    'folio_factura'     => 'required|string',
   */
  public function spPagoNotify($data)
  {
    try {
      Log::channel('inter_api')->info('Iniciando notificación de SP pagada', [
        'sp_id' => $data['sp_id'] ?? null,
        'sp_folio' => $data['sp_folio'] ?? null,
        'company_id' => $data['company_id'] ?? null,
      ]);

      $payload = [
        'sp_id' => $data['sp_id'],
        'sp_folio' => $data['sp_folio'],
        'company_id' => $data['company_id'],
        'folio_factura' => $data['folio_factura'],
        'proveedor' => $data['proveedor'],
        'monto' => $data['monto'] ?? null,
        'fecha_pago' => $data['fecha_pago'] ?? null,
        'user_id' => $data['user_id'] ?? null,
      ];

      Log::channel('inter_api')->info('Payload preparado para notificación de pago', [
        'payload' => $payload
      ]);

      $url = "{$this->apiContruccUrl}/api/notify-sp-pagada";

      Log::channel('inter_api')->info('URL destino para notificación de SP pagada', [
        'url' => $url
      ]);

      $response = Http::withoutVerifying()
        ->withHeaders([
          'X-API-KEY' => $this->apiContruccApiKey,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json'
        ])
        ->timeout($this->timeout)
        // ->retry(3, 200) // opcional
        ->post($url, $payload);

      Log::channel('inter_api')->info('Respuesta recibida desde API Construcciones (SP pagada)', [
        'status' => $response->status(),
        'body' => $response->body()
      ]);

      if ($response->successful()) {
        Log::channel('inter_api')->info('Notificación de SP pagada enviada exitosamente', [
          'sp_id' => $data['sp_id'],
          'sp_folio' => $data['sp_folio']
        ]);

        return [
          'success' => true,
          'data' => $response->json()
        ];
      }

      Log::channel('inter_api')->warning('Fallo en notificación de SP pagada', [
        'sp_id' => $data['sp_id'],
        'status' => $response->status(),
        'error' => $response->body()
      ]);

      return [
        'success' => false,
        'error' => $response->body()
      ];
    } catch (\Exception $e) {
      Log::channel('inter_api')->error('Excepción al notificar SP pagada', [
        'sp_id' => $data['sp_id'] ?? null,
        'sp_folio' => $data['sp_folio'] ?? null,
        'company_id' => $data['company_id'] ?? null,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      // No lanzamos la excepción para no romper el flujo principal
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }
}
