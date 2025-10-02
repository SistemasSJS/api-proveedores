<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    // API_REGISTRATION_KEY=/%-!?=T35sT._¿¿<1|:
    // esta linea es rara porque no lea la clave del .env
    $validKey = env('#%*-!?=53$6/8-._22<1|:');
    $providedKey = $request->header('X-API-KEY');

    if ($providedKey !== $validKey) {
      return response()->json(['error' => 'Invalid API key'], 401);
    }

    return $next($request);
  }
}
