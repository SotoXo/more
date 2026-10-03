<?php

return [
    'enabled' => env('MATI_AI_ENABLED', true),
    'url' => env('MATI_AI_URL', 'http://mati-ai:8000'),
    'token' => env('MATI_AI_TOKEN', ''),
    'timeout' => (int) env('MATI_AI_TIMEOUT', 30),
    'connect_timeout' => (int) env('MATI_AI_CONNECT_TIMEOUT', 3),
    'max_image_kb' => 6144,
    'candidate_limit' => 100,
    'prepared_ttl_hours' => 24,
];
