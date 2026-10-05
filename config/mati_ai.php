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
    'vectors' => [
        'enabled' => env('MATI_AI_VECTOR_ENABLED', true),
        'default_limit' => 12,
        'max_limit' => 30,
        'max_dimensions' => 16000,
        // Heuristic candidate flag; similarity is never an identity probability.
        'duplicate_similarity_threshold' => (float) env('MATI_AI_DUPLICATE_SIMILARITY', 0.98),
    ],
];
