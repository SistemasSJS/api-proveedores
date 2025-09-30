<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],
  'allowed methods' => ['*'],
  'allowed_origins' => ['*'],
  'allowed_origins_patterns' => [],
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true
];
