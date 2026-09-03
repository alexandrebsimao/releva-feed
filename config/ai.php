<?php

return [
    'provider' => env('AI_PROVIDER', 'groq'),
    'classifier_model' => env('AI_CLASSIFIER_MODEL'),
    'summarizer_model' => env('AI_SUMMARIZER_MODEL'),
    'request_interval_ms' => (int) env('AI_REQUEST_INTERVAL_MS', 5000),
    'transient_retries' => (int) env('AI_TRANSIENT_RETRIES', 3),
    'article_description_max_chars' => (int) env('AI_ARTICLE_DESCRIPTION_MAX_CHARS', 6000),
    'cluster_article_limit' => (int) env('AI_CLUSTER_ARTICLE_LIMIT', 6),
    'cluster_article_max_chars' => (int) env('AI_CLUSTER_ARTICLE_MAX_CHARS', 3000),
    'max_topics' => (int) env('AI_MAX_TOPICS', 3),
    'min_topic_confidence' => (float) env('AI_MIN_TOPIC_CONFIDENCE', 0.65),
    'max_existing_topics' => (int) env('AI_MAX_EXISTING_TOPICS', 100),

    'tasks' => [
        'article_classifier' => [
            'max_tokens' => (int) env('AI_CLASSIFIER_MAX_TOKENS', 500),
            'temperature' => (float) env('AI_CLASSIFIER_TEMPERATURE', 0.1),
        ],
        'news_summarizer' => [
            'max_tokens' => (int) env('AI_SUMMARIZER_MAX_TOKENS', 600),
            'temperature' => (float) env('AI_SUMMARIZER_TEMPERATURE', 0.1),
        ],
    ],

    'default' => env('AI_PROVIDER', 'groq'),

    'providers' => [
        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
        ],
    ],
];
