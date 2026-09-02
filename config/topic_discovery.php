<?php

return [
    'lookback_days' => (int) env('TOPIC_DISCOVERY_LOOKBACK_DAYS', 30),
    'minimum_articles' => (int) env('TOPIC_DISCOVERY_MINIMUM_ARTICLES', 3),
    'max_suggestions' => (int) env('TOPIC_DISCOVERY_MAX_SUGGESTIONS', 10),
    'generic_terms' => [
        'a', 'as', 'assunto', 'assuntos', 'atualidade', 'atualidades', 'coisa', 'coisas',
        'dado', 'dados', 'dia', 'dias', 'evento', 'eventos', 'geral', 'informacao',
        'informacoes', 'mundo', 'noticia', 'noticias', 'novidade', 'novidades',
        'pessoa', 'pessoas', 'tema', 'temas', 'tempo', 'vida',
    ],
];
