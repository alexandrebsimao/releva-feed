<?php

return [
    'general' => [
        'weights' => [
            'importance' => 0.30,
            'recency' => 0.25,
            'source_count' => 0.15,
            'source_priority' => 0.15,
            'novelty' => 0.15,
        ],
        'recency_half_life_hours' => 48,
        'max_sources' => 5,
        'max_source_priority' => 1000,
    ],
    'user' => [
        'weights' => [
            'followed_topics' => 0.25,
            'preference_priority' => 0.20,
            'importance' => 0.15,
            'recency' => 0.15,
            'source_count' => 0.10,
            'source_quality' => 0.15,
        ],
        'recency_half_life_hours' => 48,
        'max_sources' => 5,
        'max_source_priority' => 1000,
    ],
];
