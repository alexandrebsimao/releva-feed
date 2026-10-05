<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Feed;
use App\Models\NewsCluster;
use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FeedsStatus extends Command
{
    protected $signature = 'feeds:status';

    protected $description = 'Exibe o status e diagnóstico completo das fontes, feeds, filas e IA no Dokploy';

    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('   RELEVA FEED - DIAGNÓSTICO DO SISTEMA (DOKPLOY)   ');
        $this->info('====================================================');
        $this->newLine();

        $alerts = [];

        // 1. Fontes e Feeds
        $this->checkSourcesAndFeeds($alerts);

        // 2. Fila de Jobs (PostgreSQL)
        $this->checkQueues($alerts);

        // 3. Status dos Artigos
        $this->checkArticles($alerts);

        // 4. Notícias e Clusters
        $this->checkClusters($alerts);

        // 5. Configurações de IA
        $this->checkAi($alerts);

        // 6. Alertas e Ações Recomendadas
        $this->renderAlertsAndRecommendations($alerts);

        return self::SUCCESS;
    }

    /** @param list<string> $alerts */
    private function checkSourcesAndFeeds(array &$alerts): void
    {
        $this->line('<fg=cyan;options=bold>1. Fontes e Feeds de Notícias</>');

        try {
            $totalSources = Source::query()->count();
            $activeSources = Source::query()->where('active', true)->count();
            $totalFeeds = Feed::query()->count();
            $activeFeeds = Feed::query()->where('active', true)->count();
            $lastFetch = Feed::query()->max('last_fetched_at');
            $failedFeeds = Feed::query()->where('failure_count', '>', 0)->get();

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total de Fontes', sprintf('%d (%d ativas)', $totalSources, $activeSources)],
                    ['Total de Feeds', sprintf('%d (%d ativos)', $totalFeeds, $activeFeeds)],
                    ['Última Coleta Realizada', $lastFetch ? Carbon::parse($lastFetch)->diffForHumans().' ('.$lastFetch.')' : '<fg=yellow>Nunca executada</>'],
                    ['Feeds com Falha Recente', $failedFeeds->count() > 0 ? sprintf('<fg=red>%d feed(s)</>', $failedFeeds->count()) : '<fg=green>Nenhum (0)</>'],
                ]
            );

            if ($totalFeeds === 0) {
                $alerts[] = '🔴 BANCO VAZIO: Nenhum feed encontrado no banco de dados. Execute: php artisan db:seed --class=DatabaseSeeder --force';
            } elseif ($activeFeeds === 0) {
                $alerts[] = '🟡 FEEDS INATIVOS: Existem feeds no banco, mas nenhum está com active = true.';
            }

            if ($failedFeeds->isNotEmpty()) {
                $this->line('<fg=yellow>Feeds com falha:</>');
                $rows = $failedFeeds->take(5)->map(fn ($f) => [
                    $f->id,
                    \Illuminate\Support\Str::limit($f->name, 25),
                    $f->failure_count,
                    \Illuminate\Support\Str::limit($f->last_error ?? 'Desconhecido', 45),
                ])->all();

                $this->table(['ID', 'Feed', 'Falhas', 'Último Erro'], $rows);
            }
        } catch (Throwable $e) {
            $this->error('Erro ao consultar fontes/feeds: '.$e->getMessage());
            $alerts[] = '🔴 ERRO DE BANCO: Não foi possível consultar a tabela de feeds/fontes. Verifique se as migrações foram executadas.';
        }

        $this->newLine();
    }

    /** @param list<string> $alerts */
    private function checkQueues(array &$alerts): void
    {
        $this->line('<fg=cyan;options=bold>2. Filas de Processamento (Queue Database)</>');

        try {
            if (! Schema::hasTable('jobs')) {
                $this->error('Tabela "jobs" não encontrada no banco.');
                $alerts[] = '🔴 FILA: Tabela jobs ausente. Execute: php artisan migrate --force';

                return;
            }

            $totalJobs = DB::table('jobs')->count();
            $jobsByQueue = DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->pluck('count', 'queue');

            $failedJobsCount = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

            $queueBreakdown = $jobsByQueue->map(fn ($cnt, $q) => "{$q}: {$cnt}")->implode(', ');

            $this->table(
                ['Fila / Tabela', 'Total de Jobs', 'Detalhe'],
                [
                    ['Tabela `jobs` (Pendentes)', $totalJobs > 0 ? "<fg=yellow>{$totalJobs}</>" : '<fg=green>0</>', $queueBreakdown ?: 'Nenhum job pendente'],
                    ['Tabela `failed_jobs` (Falhas)', $failedJobsCount > 0 ? "<fg=red>{$failedJobsCount}</>" : '<fg=green>0</>', $failedJobsCount > 0 ? 'Use `php artisan queue:failed`' : 'Nenhuma falha'],
                ]
            );

            if ($totalJobs > 0) {
                $alerts[] = "🔴 WORKER PARADO: Existem {$totalJobs} jobs pendentes na fila! O container do Queue Worker não está rodando no Dokploy ou não está escutando as filas corretas. Comando necessário: php artisan queue:work --queue=feeds,articles,digests,topics,default";
            }

            if ($failedJobsCount > 0) {
                $alerts[] = "🟡 JOBS COM FALHA: Existem {$failedJobsCount} jobs que falharam. Inspecione com: php artisan queue:failed";
            }
        } catch (Throwable $e) {
            $this->error('Erro ao consultar tabelas de fila: '.$e->getMessage());
        }

        $this->newLine();
    }

    /** @param list<string> $alerts */
    private function checkArticles(array &$alerts): void
    {
        $this->line('<fg=cyan;options=bold>3. Artigos Coletados</>');

        try {
            $totalArticles = Article::query()->count();
            $last24hArticles = Article::query()->where('created_at', '>=', now()->subDay())->count();

            $byStatus = Article::query()
                ->select('processing_status', DB::raw('count(*) as count'))
                ->groupBy('processing_status')
                ->pluck('count', 'processing_status');

            $pending = $byStatus->get(Article::STATUS_PENDING, 0);
            $processing = $byStatus->get(Article::STATUS_PROCESSING, 0);
            $processed = $byStatus->get(Article::STATUS_PROCESSED, 0);
            $failed = $byStatus->get(Article::STATUS_FAILED, 0);
            $ignored = $byStatus->get(Article::STATUS_IGNORED, 0);

            $this->table(
                ['Status', 'Quantidade'],
                [
                    ['Total Geral', $totalArticles],
                    ['Importados nas últimas 24h', $last24hArticles],
                    ['Processados com sucesso (PROCESSED)', "<fg=green>{$processed}</>"],
                    ['Aguardando processamento (PENDING)', $pending > 0 ? "<fg=yellow>{$pending}</>" : '0'],
                    ['Em processamento (PROCESSING)', $processing > 0 ? "<fg=yellow>{$processing}</>" : '0'],
                    ['Com falha de IA (FAILED)', $failed > 0 ? "<fg=red>{$failed}</>" : '<fg=green>0</>'],
                    ['Ignorados / Não-notícia (IGNORED)', $ignored],
                ]
            );

            if ($failed > 0) {
                $latestError = Article::query()
                    ->where('processing_status', Article::STATUS_FAILED)
                    ->whereNotNull('ai_last_error')
                    ->latest('updated_at')
                    ->value('ai_last_error');

                if ($latestError) {
                    $this->line('<fg=red>Último erro de IA registrado nos artigos:</>');
                    $this->line('  '.\Illuminate\Support\Str::limit($latestError, 120));
                }

                $alerts[] = "🟡 ARTIGOS COM FALHA: {$failed} artigo(s) falharam no processamento de IA. Verifique as credenciais do GROQ_API_KEY.";
            }

            if ($totalArticles === 0) {
                $alerts[] = 'ℹ️ NENHUM ARTIGO: Nenhum artigo foi importado ainda. Execute: php artisan feeds:collect --sync';
            }
        } catch (Throwable $e) {
            $this->error('Erro ao consultar artigos: '.$e->getMessage());
        }

        $this->newLine();
    }

    /** @param list<string> $alerts */
    private function checkClusters(array &$alerts): void
    {
        $this->line('<fg=cyan;options=bold>4. Notícias e Agrupamentos (Clusters da Home)</>');

        try {
            $totalClusters = NewsCluster::query()->count();
            $todayClusters = NewsCluster::query()->where('created_at', '>=', today())->count();
            $minScore = (int) config('homepage.automatic_min_score', 50);
            $visibleClusters = NewsCluster::query()
                ->where(fn ($q) => $q->where('public_status', NewsCluster::STATUS_FEATURED)
                    ->orWhere(fn ($a) => $a->where('public_status', NewsCluster::STATUS_AUTOMATIC)->where('general_score', '>=', $minScore)))
                ->count();

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total de Clusters Criados', $totalClusters],
                    ['Clusters Criados Hoje', $todayClusters],
                    ['Clusters Elegíveis para a Home (Score >= '.$minScore.')', $visibleClusters > 0 ? "<fg=green>{$visibleClusters}</>" : '<fg=yellow>0</>'],
                ]
            );

            if ($visibleClusters === 0 && $totalClusters > 0) {
                $alerts[] = 'ℹ️ HOME VAZIA: Existem clusters, mas nenhum atingiu a pontuação mínima de corte (score >= '.$minScore.').';
            }
        } catch (Throwable $e) {
            $this->error('Erro ao consultar clusters: '.$e->getMessage());
        }

        $this->newLine();
    }

    /** @param list<string> $alerts */
    private function checkAi(array &$alerts): void
    {
        $this->line('<fg=cyan;options=bold>5. Configuração do Provedor de IA (Groq)</>');

        $provider = config('ai.provider', 'groq');
        $rawKey = env('GROQ_API_KEY');
        $keyStatus = ! empty($rawKey) ? '<fg=green>Configurada ('.substr($rawKey, 0, 8).'...)</>' : '<fg=red>NÃO CONFIGURADA</>';
        $classifierModel = config('ai.classifier_model') ?: '<fg=yellow>Não definido</>';
        $summarizerModel = config('ai.summarizer_model') ?: '<fg=yellow>Não definido</>';

        $this->table(
            ['Parâmetro', 'Valor Configurado'],
            [
                ['Provedor (AI_PROVIDER)', $provider],
                ['Chave de API (GROQ_API_KEY)', $keyStatus],
                ['Modelo Classificador (AI_CLASSIFIER_MODEL)', $classifierModel],
                ['Modelo Sumarizador (AI_SUMMARIZER_MODEL)', $summarizerModel],
            ]
        );

        if (empty($rawKey)) {
            $alerts[] = '🔴 CHAVE DE IA AUSENTE: A variável GROQ_API_KEY está vazia. Os artigos importados não poderão ser classificados nem exibidos na página inicial.';
        }

        $this->newLine();
    }

    /** @param list<string> $alerts */
    private function renderAlertsAndRecommendations(array $alerts): void
    {
        $this->line('<fg=cyan;options=bold>6. Diagnóstico e Ações Recomendadas</>');

        if (empty($alerts)) {
            $this->info('✅ Tudo parece estar configurado corretamente!');
            $this->line('Para disparar uma coleta manual agora e ver as notícias atualizando:');
            $this->comment('  php artisan feeds:collect --sync');

            return;
        }

        foreach ($alerts as $alert) {
            $this->line('  '.$alert);
        }

        $this->newLine();
        $this->info('🛠️ Comandos úteis no Dokploy:');
        $this->line('  - Testar coleta imediatamente no terminal:');
        $this->comment('    php artisan feeds:collect --sync');
        $this->line('  - Processar os artigos pendentes na fila:');
        $this->comment('    php artisan queue:work --queue=feeds,articles,digests,topics,default --stop-when-empty');
        $this->line('  - Popular fontes se estiver vazio:');
        $this->comment('    php artisan db:seed --class=DatabaseSeeder --force');
    }
}
