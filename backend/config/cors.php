<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Dev : ouvert. Restreindre aux origines du front en recette/production.
    'allowed_origins' => array_filter(
        explode(',', env('FRONTEND_ORIGINS', '*'))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['RateLimit-Limit', 'RateLimit-Remaining', 'RateLimit-Reset', 'Retry-After'],

    'max_age' => 0,

    'supports_credentials' => false,

];
