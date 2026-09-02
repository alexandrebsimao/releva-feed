<x-layouts.authenticated>
    <section class="mx-auto max-w-7xl px-6 py-10 lg:py-14">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Operação</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Administração e observabilidade</h1>
            <p class="mt-3 max-w-2xl text-slate-500">Saúde dos feeds, processamento de artigos, clusters e chamadas de IA.</p>
        </div>

        @if (session('status'))
            <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([['pending', 'Pendentes'], ['processing', 'Processando'], ['processed', 'Processados'], ['failed', 'Com falha']] as [$status, $label])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-semibold text-slate-950">{{ $processing[$status] ?? 0 }}</p></div>
            @endforeach
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><p class="text-sm text-amber-800">Clusters criados hoje</p><p class="mt-2 text-3xl font-semibold text-slate-950">{{ $todayClusters->count() }}</p><p class="mt-1 text-xs text-amber-800">{{ number_format($averageSources, 1, ',', '.') }} fontes em média</p></div>
        </section>

        <section class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-2xl font-semibold text-slate-950">Feeds</h2><p class="mt-1 text-sm text-slate-500">Última execução, sucesso, falhas e artigos encontrados.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-3">Feed</th><th class="px-4 py-3">Ativo</th><th class="px-4 py-3">Execução</th><th class="px-4 py-3">Sucesso</th><th class="px-4 py-3">Falhas</th><th class="px-4 py-3">Artigos</th><th class="px-6 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">
                @foreach ($feeds as $feed)
                    <tr><td class="px-6 py-4"><p class="font-semibold text-slate-900">{{ $feed->name }}</p><p class="text-xs text-slate-500">{{ $feed->source?->name }}</p></td><td class="px-4 py-4">{{ $feed->active ? 'Sim' : 'Não' }}</td><td class="px-4 py-4 text-slate-500">{{ $feed->last_fetched_at?->format('d/m/Y H:i') ?: 'Nunca' }}</td><td class="px-4 py-4 text-slate-500">{{ $feed->last_success_at?->format('d/m/Y H:i') ?: 'Nunca' }}</td><td class="px-4 py-4 font-semibold {{ $feed->failure_count > 0 ? 'text-rose-700' : 'text-slate-700' }}">{{ $feed->failure_count }}</td><td class="px-4 py-4">{{ $feed->articles_count }}</td><td class="px-6 py-4 text-right">@if ($feed->failure_count > 0 || $feed->last_error)<form method="POST" action="{{ route('admin.feeds.retry', $feed) }}">@csrf<button class="font-semibold text-amber-700 hover:text-amber-600">Executar novamente</button></form>@endif</td></tr>
                @endforeach
            </tbody></table></div>
        </section>

        <section class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-2xl font-semibold text-slate-950">Artigos com falha</h2><p class="mt-1 text-sm text-slate-500">O reprocessamento é assíncrono e passa novamente pelos filtros e pela IA.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($failedArticles as $article)
                    <div class="flex items-center justify-between gap-4 px-6 py-4"><div><p class="font-semibold text-slate-900">{{ $article->original_title }}</p><p class="mt-1 text-xs text-slate-500">{{ $article->id }} · {{ $article->feed?->name }}</p></div><form method="POST" action="{{ route('admin.articles.reprocess', $article) }}">@csrf<button class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Reprocessar</button></form></div>
                @empty
                    <p class="px-6 py-8 text-sm text-slate-500">Nenhum artigo com falha.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-2xl font-semibold text-slate-950">NewsClusters de hoje</h2><p class="mt-1 text-sm text-slate-500">Score geral recalculável sem alterar o resumo consolidado.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($todayClusters as $cluster)
                    <div class="flex items-center justify-between gap-4 px-6 py-4"><div><p class="font-semibold text-slate-900">{{ $cluster->title }}</p><p class="mt-1 text-xs text-slate-500">Score {{ $cluster->general_score ?? '—' }} · {{ $cluster->articles->count() }} artigo(s)</p></div><form method="POST" action="{{ route('admin.news-clusters.recalculate', $cluster) }}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Recalcular</button></form></div>
                @empty
                    <p class="px-6 py-8 text-sm text-slate-500">Nenhum cluster criado hoje.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-2xl font-semibold text-slate-950">Métricas de IA</h2><p class="mt-1 text-sm text-slate-500">Somente metadados operacionais; prompts, respostas e chaves não são armazenados.</p></div>
            <div class="flex flex-wrap gap-3 px-6 py-5 text-sm"><span class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-700">Total: {{ $aiMetrics->count() }} exibidas</span>@foreach ($aiTotals as $status => $total)<span class="rounded-full {{ $status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} px-3 py-1.5">{{ $status }}: {{ $total }}</span>@endforeach</div>
            <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-3">Data</th><th class="px-4 py-3">Provider</th><th class="px-4 py-3">Modelo</th><th class="px-4 py-3">Operação</th><th class="px-4 py-3">Tokens</th><th class="px-4 py-3">Duração</th><th class="px-6 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($aiMetrics as $metric)<tr><td class="px-6 py-4 text-slate-500">{{ $metric->recorded_at->format('d/m/Y H:i:s') }}</td><td class="px-4 py-4">{{ $metric->provider }}</td><td class="px-4 py-4">{{ $metric->model }}</td><td class="px-4 py-4">{{ $metric->operation }}</td><td class="px-4 py-4 text-slate-500">{{ $metric->input_tokens ?? '—' }} / {{ $metric->output_tokens ?? '—' }}</td><td class="px-4 py-4 text-slate-500">{{ $metric->duration_ms ?? '—' }} ms</td><td class="px-6 py-4"><span class="font-semibold {{ $metric->status === 'success' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $metric->status }}</span></td></tr>@empty<tr><td colspan="7" class="px-6 py-8 text-sm text-slate-500">Nenhuma chamada de IA registrada.</td></tr>@endforelse</tbody></table></div>
        </section>
    </section>
</x-layouts.authenticated>
