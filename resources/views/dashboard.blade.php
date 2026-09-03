<x-layouts.authenticated>
    @php
        $firstName = explode(' ', trim(auth()->user()->name))[0];
        $userInitial = strtoupper(substr(trim(auth()->user()->name), 0, 1));
        $sourceStoryColors = ['violet', 'blue', 'orange', 'green', 'pink', 'gold'];
    @endphp

    <section class="feed-page">
        <header class="feed-page-header">
            <div>
                <p class="eyebrow">Seu radar · {{ now()->locale('pt_BR')->translatedFormat('l, j \d\e F') }}</p>
                <h1>Bom dia, {{ $firstName }} <span aria-hidden="true">✦</span></h1>
                <p class="feed-intro">Uma seleção com contexto para você começar bem o dia.</p>
            </div>
            <div class="feed-header-stat"><span class="stat-dot"></span><span><strong>{{ $articles->total() }}</strong> leituras no radar</span></div>
        </header>

        <section class="source-stories" aria-labelledby="fontes-em-foco">
            <div class="section-label-row"><h2 id="fontes-em-foco">Fontes em foco</h2><a href="{{ route('my-sources.index') }}">Ver todas</a></div>
            <div class="story-list">
                @forelse ($sources->take(6) as $index => $source)
                    @php($storyInitial = strtoupper(substr(trim($source->name), 0, 1)))
                    <a href="{{ route('dashboard', ['view' => 'sources', 'sources[]' => $source->id]) }}" class="story-item"><span class="story-ring story-ring-{{ $sourceStoryColors[$index % count($sourceStoryColors)] }}"><span>{{ $storyInitial }}</span></span><span>{{ \Illuminate\Support\Str::limit($source->name, 14) }}</span></a>
                @empty
                    <p class="empty-inline">Suas fontes aparecerão aqui quando estiverem selecionadas.</p>
                @endforelse
            </div>
        </section>

        <div class="feed-layout">
            <div class="feed-column">
                <div class="feed-toolbar">
                    <nav class="feed-tabs" aria-label="Modo de leitura">
                        @foreach (['interests' => 'Para você', 'all' => 'Todos', 'sources' => 'Minhas fontes'] as $feedMode => $label)
                            <a href="{{ route('dashboard', ['view' => $feedMode]) }}" @class(['feed-tab', 'is-active' => $mode === $feedMode])>{{ $label }}</a>
                        @endforeach
                    </nav>
                    <button type="button" class="toolbar-icon" aria-label="Atualizar feed" onclick="window.location.reload()"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M19.3 8.3A8 8 0 1 0 20 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M20 4.5v4.8h-4.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                </div>

                <div class="feed-filter-wrap">
                    @include('shared.source-filter', ['filterAction' => route('dashboard', ['view' => $mode]), 'sources' => $sources, 'selectedSourceIds' => $selectedSourceIds, 'categories' => $categories, 'selectedCategory' => $selectedCategory])
                </div>

                <section @if (! $articles->isEmpty()) data-infinite-feed @endif class="feed-stream" aria-label="Artigos do seu feed">
                    @if ($articles->isEmpty())
                        <div class="feed-empty"><span class="feed-empty-icon">✦</span><h2>Seu radar está quieto</h2><p>Escolha alguns assuntos ou fontes para receber uma leitura mais sua.</p><a href="{{ route('preferences.index') }}" class="primary-action">Personalizar feed</a></div>
                    @else
                        <div data-feed-list class="feed-list">
                            @php($renderedClusters = [])
                            @foreach ($articles as $article)
                                @foreach ($article->clusters as $cluster)
                                    @if (! in_array($cluster->id, $renderedClusters, true))
                                        @php($renderedClusters[] = $cluster->id)
                                        @php($clusterTopics = $cluster->articles->flatMap->topics->unique('id')->take(3))
                                        @php($clusterSources = $cluster->articles->map(fn ($clusterArticle) => $clusterArticle->feed?->source?->name)->filter()->unique()->values())
                                        @php($clusterSource = $clusterSources->first() ?: 'Releva Feed')
                                        @php($clusterInitial = strtoupper(substr(trim($clusterSource), 0, 1)))
                                        @php($clusterDate = $cluster->last_article_at ?? $cluster->published_at)
                                        <article data-feed-item-key="cluster-{{ $cluster->id }}" class="feed-card">
                                            <div class="feed-card-header"><span class="source-avatar source-avatar-{{ $cluster->id % 5 }}">{{ $clusterInitial }}</span><div class="feed-card-author"><strong>{{ $clusterSource }}</strong><span>{{ $cluster->category ?: 'Acontecimento' }} · {{ $clusterDate?->locale('pt_BR')->translatedFormat('j \d\e F') }}</span></div><span class="feed-more" aria-hidden="true">•••</span></div>
                                            <a href="{{ route('news.show', $cluster) }}" class="feed-visual feed-visual-{{ $cluster->id % 4 }}"><span class="feed-visual-kicker">Releva / em contexto</span><span class="feed-visual-title">{{ $cluster->title }}</span><span class="feed-visual-mark">R</span></a>
                                            <div class="feed-card-body">
                                                <div class="feed-action-row"><div class="feed-actions"><button type="button" data-save-item aria-label="Salvar acontecimento"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M6.5 4.5A2 2 0 0 1 8.5 2.5h7a2 2 0 0 1 2 2v17l-5.5-3.7-5.5 3.7v-17Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></button><a href="{{ route('news.show', $cluster) }}" aria-label="Abrir acontecimento"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 12h15M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div>@if ($cluster->general_score !== null)<span class="relevance-score">{{ $cluster->general_score }}% relevância</span>@endif</div>
                                                <h2><a href="{{ route('news.show', $cluster) }}">{{ $cluster->title }}</a></h2>
                                                @if ($cluster->summary)<p class="feed-summary">{{ $cluster->summary }}</p>@endif
                                                @if ($cluster->why_it_matters)<p class="why-it-matters"><strong>Por que importa</strong> {{ $cluster->why_it_matters }}</p>@endif
                                                @if ($clusterTopics->isNotEmpty())<div class="topic-pills">@foreach ($clusterTopics as $topic)<span>{{ $topic->name }}</span>@endforeach</div>@endif
                                                <div class="feed-card-footer"><span>{{ $clusterSources->count() }} {{ $clusterSources->count() === 1 ? 'fonte' : 'fontes' }} · {{ $cluster->articles->count() }} {{ $cluster->articles->count() === 1 ? 'matéria' : 'matérias' }}</span><a href="{{ route('news.show', $cluster) }}">Ler contexto <span aria-hidden="true">→</span></a></div>
                                            </div>
                                        </article>
                                    @endif
                                @endforeach
                                @if ($article->clusters->isEmpty())
                                    @php($articleSource = $article->feed?->source?->name ?: 'Fonte não identificada')
                                    @php($articleInitial = strtoupper(substr(trim($articleSource), 0, 1)))
                                    <article data-feed-item-key="article-{{ $article->id }}" class="feed-card">
                                        <div class="feed-card-header"><span class="source-avatar source-avatar-{{ $article->id % 5 }}">{{ $articleInitial }}</span><div class="feed-card-author"><strong>{{ $articleSource }}</strong><span>Matéria recém-chegada</span></div><span class="feed-more" aria-hidden="true">•••</span></div>
                                        <div class="feed-card-body feed-card-body-standalone"><div class="feed-action-row"><div class="feed-actions"><button type="button" data-save-item aria-label="Salvar matéria"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M6.5 4.5A2 2 0 0 1 8.5 2.5h7a2 2 0 0 1 2 2v17l-5.5-3.7-5.5 3.7v-17Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></button></div></div>
                                            <h2><a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer">{{ $article->original_title }}</a></h2>
                                            @if ($article->description)<p class="feed-summary">{{ \Illuminate\Support\Str::limit($article->description, 280) }}</p>@endif
                                            <div class="feed-card-footer"><span>{{ $article->published_at?->locale('pt_BR')->translatedFormat('j \d\e F, H:i') ?: 'Importado recentemente' }}</span><a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer">Abrir fonte <span aria-hidden="true">↗</span></a></div>
                                        </div>
                                    </article>
                                @endif
                            @endforeach
                        </div>
                        @if ($articles->hasPages())<div data-feed-pagination class="feed-pagination">{{ $articles->links() }}</div><div data-feed-sentinel class="feed-sentinel" aria-live="polite">Role para carregar mais</div>@endif
                    @endif
                </section>
            </div>

            <aside class="feed-aside">
                <div class="profile-card"><div class="profile-card-top"><span class="user-avatar user-avatar-lg">{{ $userInitial }}</span><div><strong>{{ auth()->user()->name }}</strong><span>Leitor em construção</span></div></div><div class="profile-stats"><span><strong>{{ $sources->count() }}</strong> fontes</span><span><strong>{{ $articles->total() }}</strong> leituras</span></div><a href="{{ route('preferences.index') }}" class="profile-link">Editar preferências</a></div>
                <div class="aside-section"><div class="aside-title"><span>Sugestões para você</span><a href="{{ route('preferences.index') }}">Ver tudo</a></div><p class="aside-note">Acompanhe assuntos que combinam com o seu radar.</p><a href="{{ route('my-topics.index') }}" class="suggestion-row"><span class="suggestion-icon">#</span><span><strong>Descobrir assuntos</strong><small>Personalize sua curadoria</small></span><span class="suggestion-arrow">→</span></a></div>
                <p class="aside-footer">RELEVA FEED · LEITURA COM CONTEXTO</p>
            </aside>
        </div>
    </section>
</x-layouts.authenticated>
