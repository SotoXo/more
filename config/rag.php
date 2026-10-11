<?php

return [
    'corpus_path' => base_path('services/mati-ai/resources/rag/corpus-v1.json'),
    'profile_path' => base_path('services/mati-ai/resources/rag/model-profile.json'),
    'max_query_chars' => 1000,
    'max_limit' => 10,
    'rrf_constant' => 60,
    // Only the explicit CLI evaluation harness may enable this in memory.
    'evaluation_mode' => false,
];
