<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogIncomingRequests
{
    public function handle(Request $request, Closure $next)
    {
        // Datos a registrar
        $log = [
            'ip'       => $request->ip(),
            'method'   => $request->method(),
            'url'      => $request->fullUrl(),
            'headers'  => $request->headers->all(),
            'body'     => $request->except(['password', 'password_confirmation']),
            'user_id'  => optional($request->user())->id,
        ];

        Log::channel('requests')->info('Incoming Request', $log);

        return $next($request);
    }
}
