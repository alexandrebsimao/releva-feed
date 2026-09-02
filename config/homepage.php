<?php

return [
    'cache_ttl_minutes' => (int) env('HOMEPAGE_CACHE_TTL_MINUTES', 5),
    'automatic_min_score' => (int) env('HOMEPAGE_AUTOMATIC_MIN_SCORE', 50),
    'now_hours' => (int) env('HOMEPAGE_NOW_HOURS', 24),

    'limits' => [
        'highlights' => 5,
        'brasil' => 4,
        'tecnologia' => 4,
        'automotivo' => 4,
        'agora' => 8,
    ],

    'categories' => [
        'brasil' => [
            'terms' => ['brasil', 'brasileiro', 'brasileira'],
            'topic_terms' => ['brasil', 'brasileiro', 'brasileira'],
        ],
        'tecnologia' => [
            'terms' => ['tecnologia', 'tech', 'inovação', 'inteligência artificial'],
            'topic_terms' => ['tecnologia', 'tech', 'inovação', 'inteligência artificial', 'ia'],
        ],
        'automotivo' => [
            'terms' => ['automotivo', 'automóveis', 'automóvel', 'carros', 'carro', 'mobilidade'],
            'topic_terms' => ['automotivo', 'automóvel', 'carro', 'toyota', 'veículo', 'mobilidade'],
        ],
    ],
];
