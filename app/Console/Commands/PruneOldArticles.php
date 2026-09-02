<?php

namespace App\Console\Commands;

use App\Services\Article\ArticleRetentionService;
use App\Services\News\HomepageNewsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneOldArticles extends Command
{
    protected $signature = 'articles:prune';

    protected $description = 'Remove artigos mais antigos que o período de retenção';

    public function handle(ArticleRetentionService $retention, HomepageNewsService $homepage): int
    {
        $report = $retention->prune();

        if ($report['articles_deleted'] > 0 || $report['clusters_deleted'] > 0) {
            $homepage->invalidate();
        }

        Log::info('Limpeza de artigos antigos concluída.', [
            'cutoff' => $report['cutoff']->toIso8601String(),
            'articles_deleted' => $report['articles_deleted'],
            'clusters_deleted' => $report['clusters_deleted'],
        ]);

        $this->info(sprintf(
            '%d artigo(s) e %d cluster(s) removido(s). Corte: %s.',
            $report['articles_deleted'],
            $report['clusters_deleted'],
            $report['cutoff']->toDateTimeString(),
        ));

        return self::SUCCESS;
    }
}
