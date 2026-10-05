<?php

namespace App\Console\Commands;

use App\Jobs\DispatchFeedFetchesJob;
use App\Jobs\ProcessArticleJob;
use App\Models\Article;
use App\Models\Feed;
use App\Services\Feed\ArticleImporter;
use App\Services\News\HomepageNewsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CollectFeeds extends Command
{
    protected $signature = 'feeds:collect
                            {--sync : Executa a coleta sincronamente no terminal sem depender do worker de filas}
                            {--process : Se usado com --sync, também executa o processamento com IA dos artigos importados}
                            {--feed= : ID de um feed específico para coletar}';

    protected $description = 'Despacha a coleta dos feeds ativos (assíncrono por padrão ou síncrono com --sync)';

    public function handle(ArticleImporter $importer, HomepageNewsService $homepage): int
    {
        $feedId = $this->option('feed');
        $sync = (bool) $this->option('sync');
        $process = (bool) $this->option('process');

        $query = Feed::query()
            ->where('active', true)
            ->whereHas('source', fn ($q) => $q->where('active', true))
            ->with('source');

        if ($feedId !== null) {
            $query->whereKey($feedId);
        }

        $feeds = $query->orderBy('id')->get();

        if ($feeds->isEmpty()) {
            $this->warn('Nenhum feed ativo encontrado para coleta.');
            $totalFeeds = Feed::query()->count();

            if ($totalFeeds === 0) {
                $this->error('A tabela de feeds está completamente vazia no banco de dados!');
                $this->line('Execute o seeder para carregar o catálogo de fontes e feeds:');
                $this->comment('  php artisan db:seed --class=DatabaseSeeder --force');
            }

            return self::FAILURE;
        }

        if (! $sync) {
            DispatchFeedFetchesJob::dispatch();
            $this->info(sprintf('Coleta assíncrona despachada para a fila "feeds" (%d feeds ativos).', $feeds->count()));

            try {
                $pendingJobs = DB::table('jobs')->where('queue', 'feeds')->count();
                if ($pendingJobs > 0) {
                    $this->line(sprintf('Existem %d job(s) aguardando na fila "feeds".', $pendingJobs));
                    $this->comment('Atenção: Garanta que o container worker do Dokploy esteja rodando:');
                    $this->comment('  php artisan queue:work --queue=feeds,articles,digests,topics,default');
                }
            } catch (Throwable) {
                // Tabela jobs pode não estar acessível
            }

            $this->line('Dica: Para testar a coleta imediatamente no terminal sem esperar o worker, execute:');
            $this->comment('  php artisan feeds:collect --sync');

            return self::SUCCESS;
        }

        // Modo Síncrono
        $this->info(sprintf('Iniciando coleta síncrona de %d feed(s)...', $feeds->count()));
        $totalImported = 0;
        $totalSkipped = 0;
        $failedFeeds = 0;

        foreach ($feeds as $feed) {
            $sourceName = $feed->source->name ?? 'Fonte';
            $this->output->write(sprintf('Coletando [%s] (%s)... ', $sourceName, $feed->name));

            $report = $importer->import($feed);

            if ($report->failed) {
                $failedFeeds++;
                $this->output->writeln(sprintf('<fg=red>[FALHA]</> %s', $report->error));
            } else {
                $totalImported += $report->imported;
                $totalSkipped += $report->skipped;
                $this->output->writeln(sprintf('<fg=green>[OK]</> %d novo(s), %d ignorado(s)', $report->imported, $report->skipped));
            }
        }

        $this->newLine();
        $this->info('=== Resumo da Coleta ===');
        $this->line(sprintf('Feeds consultados: %d', $feeds->count()));
        $this->line(sprintf('Feeds com falha de conexão/parse: %d', $failedFeeds));
        $this->line(sprintf('Novos artigos importados: %d', $totalImported));
        $this->line(sprintf('Artigos ignorados (duplicados): %d', $totalSkipped));

        if ($process) {
            $pendingArticles = Article::query()
                ->where('processing_status', Article::STATUS_PENDING)
                ->orderBy('id')
                ->get();

            if ($pendingArticles->isNotEmpty()) {
                $this->newLine();
                $this->info(sprintf('Processando %d artigo(s) pendente(s) com IA...', $pendingArticles->count()));

                $processedCount = 0;
                $failedCount = 0;

                foreach ($pendingArticles as $article) {
                    $this->output->write(sprintf('Artigo #%d "%s"... ', $article->id, Str::limit($article->original_title, 35)));
                    try {
                        ProcessArticleJob::dispatchSync($article->id);
                        $processedCount++;
                        $this->output->writeln('<fg=green>[PROCESSADO]</>');
                    } catch (Throwable $e) {
                        $failedCount++;
                        $this->output->writeln(sprintf('<fg=red>[ERRO]</> %s', Str::limit($e->getMessage(), 60)));
                    }
                }

                $homepage->invalidate();
                $this->info(sprintf('Processamento de IA concluído: %d processados, %d falharam.', $processedCount, $failedCount));
            } else {
                $this->line('Nenhum artigo com status PENDING para processar por IA.');
            }
        } elseif ($totalImported > 0) {
            $this->comment(sprintf('Os %d novos artigos foram enfileirados para o worker de IA.', $totalImported));
            $this->comment('Para processá-los agora pelo terminal, execute:');
            $this->comment('  php artisan queue:work --queue=articles,default --stop-when-empty');
        }

        $homepage->invalidate();

        return $failedFeeds === 0 ? self::SUCCESS : self::FAILURE;
    }
}
