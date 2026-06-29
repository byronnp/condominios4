<?php

return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:9000'),
    'expires_hours' => (int) env('INVITATION_EXPIRES_HOURS', 24),
];
