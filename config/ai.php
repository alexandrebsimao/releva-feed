<?php

return [
    'provider' => env('AI_PROVIDER', 'groq'),
    'classifier_model' => env('AI_CLASSIFIER_MODEL'),
    'summarizer_model' => env('AI_SUMMARIZER_MODEL'),
    'request_interval_ms' => (int) env('AI_REQUEST_INTERVAL_MS', 3000),

    'default' => env('AI_PROVIDER', 'groq'),

    'providers' => [
        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
        ],
    ],
];
