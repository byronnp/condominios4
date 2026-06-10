<?php

return [
    'secret' => env('JWT_SECRET', env('APP_KEY')),
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),
    'access_ttl_minutes' => (int) env('JWT_ACCESS_TTL_MINUTES', 60),
    'refresh_ttl_days' => (int) env('JWT_REFRESH_TTL_DAYS', 30),
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),
];
