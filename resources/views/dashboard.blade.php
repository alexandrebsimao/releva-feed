<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-12 lg:py-16">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Sua leitura</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Meu feed</h1>
            <p class="mt-3 max-w-2xl text-slate-500">Escolha se quer ver todos os artigos, as suas fontes ou apenas os assuntos que acompanha.</p>
        </div>

        <nav class="sticky top-0 z-30 -mx-4 mt-10 flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-slate-100/95 p-2 shadow-sm backdrop-blur sm:-mx-6" aria-label="Filtro do feed">
            @foreach (['all' => 'Todos', 'sources' => 'Minhas fontes', 'interests' => 'Meus interesses'] as $feedMode => $label)
                <a href="{{ route('dashboard', ['view' => $feedMode]) }}" @class([
                    'rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                    'bg-slate-950 text-white' => $mode === $feedMode,
                    'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => $mode !== $feedMode,
                ])>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="mt-3">
            @include('shared.source-filter', [
                'filterAction' => route('dashboard', ['view' => $mode]),
                'sources' => $sources,
                'selectedSourceIds' => $selectedSourceIds,
            ])
        </div>

        <section @if (! $articles->isEmpty()) data-infinite-feed @endif class="mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-100 px-6 py-6 sm:flex-row sm:items-center">
                <div>
                    @if ($mode === 'all')
                        <p class="text-sm font-medium text-slate-500">Visão completa</p>
                        <h2 class="mt-1 text-2xl font-semibold text-slate-950">Todos os artigos</h2>
                    @elseif ($mode === 'sources')
                        <p class="text-sm font-medium text-slate-500">{{ $selectedSourceCount }} {{ $selectedSourceCount === 1 ? 'fonte selecionada' : 'fontes selecionadas' }}</p>
                        <h2 class="mt-1 text-2xl font-semibold text-slate-950">Artigos das minhas fontes</h2>
                    @else
                        <p class="text-sm font-medium text-slate-500">Atualizado pelos seus assuntos</p>
                        <h2 class="mt-1 text-2xl font-semibold text-slate-950">Artigos relevantes para você</h2>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($mode === 'interests')
                        <a href="{{ route('preferences.index') }}" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Ajustar preferências</a>
                    @elseif ($mode === 'sources')
                        <a href="{{ route('sources.index') }}" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Gerenciar fontes</a>
                    @endif
                </div>
            </div>
            @if ($articles->isEmpty())
                <div class="px-6 py-14 text-center">
                    @if ($mode === 'sources')
                        <h3 class="text-lg font-semibold text-slate-900">Nenhum artigo das suas fontes</h3>
                        <p class="mt-2 text-sm text-slate-500">Selecione uma fonte ou aguarde a próxima coleta de RSS.</p>
                        <a href="{{ route('sources.index') }}" class="mt-5 inline-flex rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-200">Escolher fontes</a>
                    @elseif ($mode === 'interests')
                        <h3 class="text-lg font-semibold text-slate-900">Seu feed ainda está vazio</h3>
                        <p class="mt-2 text-sm text-slate-500">Acompanhe um assunto para começar a receber artigos relevantes.</p>
                        <a href="{{ route('preferences.index') }}" class="mt-5 inline-flex rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-200">Escolher interesses</a>
                    @else
                        <h3 class="text-lg font-semibold text-slate-900">Nenhum artigo importado</h3>
                        <p class="mt-2 text-sm text-slate-500">Aguarde a coleta dos feeds para começar a visualizar artigos.</p>
                    @endif
                </div>
            @else
                <div data-feed-list class="divide-y divide-slate-100">
                    @php($renderedClusters = [])
                    @foreach ($articles as $article)
                        @foreach ($article->clusters as $cluster)
                            @if (! in_array($cluster->id, $renderedClusters, true))
                                @php($renderedClusters[] = $cluster->id)
                                @php($clusterTopics = $cluster->articles->flatMap->topics->unique('id')->take(3))
                                @php($clusterSources = $cluster->articles->map(fn ($clusterArticle) => $clusterArticle->feed?->source?->name)->filter()->unique()->values())
                                <article data-feed-item-key="cluster-{{ $cluster->id }}" class="px-6 py-7 transition hover:bg-slate-50">
                                    <div class="flex flex-wrap items-center gap-3 text-xs font-bold uppercase tracking-[0.14em] text-slate-400">
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-800">Acontecimento</span>
                                        @if ($cluster->last_article_at)<time datetime="{{ $cluster->last_article_at->toIso8601String() }}">{{ $cluster->last_article_at->format('d/m/Y H:i') }}</time>@endif
                                    </div>
                                    <h3 class="mt-4 text-2xl font-semibold leading-tight tracking-tight text-slate-950">{{ $cluster->title }}</h3>
                                    @if ($cluster->summary)<p class="mt-3 max-w-4xl text-sm leading-7 text-slate-600">{{ $cluster->summary }}</p>@endif
                                    @if ($cluster->why_it_matters)<p class="mt-4 max-w-3xl border-l-2 border-amber-300 pl-3 text-sm leading-6 text-slate-600"><span class="font-semibold text-slate-800">Por que importa:</span> {{ $cluster->why_it_matters }}</p>@endif
                                    @if ($clusterTopics->isNotEmpty())
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @foreach ($clusterTopics as $topic)
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $topic->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                                        <div><p>{{ $clusterSources->take(3)->implode(' · ') }}</p><p class="mt-1 text-xs text-slate-400">{{ $clusterSources->count() }} {{ $clusterSources->count() === 1 ? 'fonte' : 'fontes' }}</p></div>
                                        <a href="{{ route('news.show', $cluster) }}" class="shrink-0 font-semibold text-amber-700 hover:text-amber-600">Ver detalhes →</a>
                                    </div>
                                </article>
                            @endif
                        @endforeach
                        @if ($article->clusters->isEmpty())
                            <article data-feed-item-key="article-{{ $article->id }}" class="px-6 py-6 transition hover:bg-slate-50">
                                <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="text-lg font-semibold leading-7 text-slate-900 hover:text-amber-700">{{ $article->original_title }}</a>
                                <p class="mt-2 text-sm text-slate-500">{{ $article->feed->source->name }} · {{ $article->feed->name }}</p>
                                @if ($mode === 'interests')<p class="mt-2 text-xs font-medium text-slate-400">Relevância para você: {{ $article->user_relevance_score }}/100</p>@endif
                                @if ($article->description)<p class="mt-3 max-w-4xl text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($article->description, 240) }}</p>@endif
                                <div class="mt-4 flex flex-wrap gap-2">@foreach ($article->topics as $topic)<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $topic->name }}</span>@endforeach</div>
                            </article>
                        @endif
                    @endforeach
                </div>
                @if ($articles->hasPages())
                    <div data-feed-pagination class="border-t border-slate-200 px-6 py-4">{{ $articles->links() }}</div>
                    <div data-feed-sentinel class="px-6 py-5 text-center text-sm text-slate-400" aria-live="polite">Role para carregar mais.</div>
                @endif
            @endif
        </section>
    </section>
</x-layouts.authenticated>
