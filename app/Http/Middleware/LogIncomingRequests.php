<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogIncomingRequests
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Datos a registrar
        $log = [
            'method'   => $request->method(),
            'path'       => $request->path(),
            'host'       => $request->host(),
            'url'      => $request->fullUrl(),
            'ip'       => $request->ip(),
            'body'     => $request->except(['password', 'password_confirmation']),
            'headers'  => $request->headers->all(),
        ];

        Log::channel('requests')->info($request->path(), $log);

        return $next($request);
    }
}
