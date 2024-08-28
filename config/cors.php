<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Options
    |--------------------------------------------------------------------------
    |
    | The allowed_origins, allowed_headers and allowed_methods options
    | are set to accept all types of requests, which is fine for
    | development. Adjust these settings as per your production needs.
    |
    */

    'paths' => ['api/*', 'myfatoorah/callback', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'], // Allow all methods (GET, POST, PUT, DELETE, etc.)

    'allowed_origins' => ['*'], // Allow requests from any origin. Use a specific domain in production.

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Allow all headers

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
