<?php

namespace App\Exceptions\Api\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait TracksRequestData
{
    protected function requestContext(): array
    {
        return [
            'ip' => Request::ip(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'user_id' => optional(Auth::user())->id,
            'user_agent' => Request::userAgent(),
        ];
    }
}
