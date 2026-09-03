<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Curadoria pessoal</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Acontecimentos</h1>
                <p class="mt-3 max-w-2xl text-slate-500">Notícias agrupadas quando tratam do mesmo acontecimento.</p>
            </div>
        </div>

        <div class="sticky top-0 z-20 -mx-4 mt-8 border-y border-slate-200 bg-slate-100/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:rounded-2xl lg:border">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button wire:click="selectTopic('')" class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition {{ $topic === '' ? 'bg-slate-950 text-white' : 'border border-slate-300 bg-white text-slate-600 hover:border-slate-400 hover:text-slate-950' }}">Todos</button>
                @foreach ($followedPreferences as $preference)
                    <button wire:key="radar-topic-{{ $preference->topic_id }}" wire:click="selectTopic('{{ $preference->topic->slug }}')" class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition {{ $topic === $preference->topic->slug ? 'bg-amber-300 text-slate-950' : 'border border-slate-300 bg-white text-slate-600 hover:border-slate-400 hover:text-slate-950' }}">{{ $preference->topic->name }}</button>
                @endforeach
                <a href="{{ route('my-topics.index') }}" class="shrink-0 rounded-full border border-dashed border-amber-500 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">+ Assuntos</a>
            </div>
            <div class="mt-4 border-t border-slate-200 pt-4">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Fontes</p><p class="mt-1 text-sm text-slate-500">Você pode selecionar mais de uma.</p></div>
                    @if ($sourceIds !== [])<button wire:click="$set('sourceIds', [])" class="text-sm font-semibold text-amber-700 hover:text-amber-600">Limpar fontes</button>@endif
                </div>
                <div class="mt-3 grid max-h-44 gap-2 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($sourceOptions as $sourceOption)
                        <label wire:key="radar-source-{{ $sourceOption->id }}" class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 transition hover:border-amber-300">
                            <input type="checkbox" value="{{ $sourceOption->id }}" wire:model.live="sourceIds" class="rounded border-slate-300 text-amber-500 focus:ring-amber-300">
                            <span class="truncate text-sm font-medium text-slate-700">{{ $sourceOption->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-8" wire:loading.class="opacity-60" wire:target="selectTopic, nextPage, previousPage, gotoPage">
            @if ($clusters->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-950">Seu radar está quieto</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Acompanhe mais assuntos ou aguarde novas matérias serem consolidadas.</p>
                    <a href="{{ route('my-topics.index') }}" class="mt-5 inline-flex rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Escolher assuntos</a>
                </div>
            @else
                <div class="space-y-10">
                    @foreach ($groupedClusters as $day => $dayClusters)
                        <section wire:key="radar-day-{{ $day }}">
                            <div class="mb-4 flex items-center gap-3">
                                <h2 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">{{ $dayLabels[$day] }}</h2>
                                <div class="h-px flex-1 bg-slate-200"></div>
                            </div>
                            <div class="space-y-4">
                                @foreach ($dayClusters as $cluster)
                                    @php
                                        $topics = $cluster->articles->flatMap->topics->unique('id')->take(3);
                                        $sources = $cluster->articles->map(fn ($article) => $article->feed?->source?->name)->filter()->unique()->values();
                                        $clusterUrl = route('news.show', $cluster);
                                        if ($sourceIds !== []) {
                                            $clusterUrl .= '?'.http_build_query(['sources' => $sourceIds]);
                                        }
                                    @endphp
                                    <article wire:key="radar-cluster-{{ $cluster->id }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow-md sm:p-6">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-bold uppercase tracking-[0.14em] text-slate-400">
                                                <time datetime="{{ $cluster->last_article_at->toIso8601String() }}" class="text-slate-700">{{ $cluster->last_article_at->format('H:i') }}</time>
                                            </div>
                                            <span class="shrink-0 text-xs font-medium text-slate-400">{{ $this->recencyLabel($cluster->last_article_at) }}</span>
                                        </div>
                                        <h3 class="mt-4 text-2xl font-semibold leading-tight tracking-tight text-slate-950 sm:text-3xl">{{ $cluster->title }}</h3>
                                        @if ($cluster->summary)<p class="mt-3 max-w-4xl text-sm leading-7 text-slate-600 sm:text-base">{{ $cluster->summary }}</p>@endif
                                        @if ($cluster->why_it_matters)<p class="mt-4 max-w-3xl border-l-2 border-amber-300 pl-3 text-sm leading-6 text-slate-600"><span class="font-semibold text-slate-800">Por que importa:</span> {{ $cluster->why_it_matters }}</p>@endif
                                        @if ($topics->isNotEmpty())
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                @foreach ($topics as $topicItem)
                                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $topicItem->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0"><p class="truncate">{{ $sources->take(3)->implode(' · ') }}</p><p class="mt-1 text-xs text-slate-400">{{ $sources->count() }} {{ $sources->count() === 1 ? 'fonte' : 'fontes' }}</p></div>
                                            <a href="{{ $clusterUrl }}" class="shrink-0 font-semibold text-amber-700 hover:text-amber-600">Ver detalhes →</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
                @if ($clusters->hasPages())<div class="mt-8 border-t border-slate-200 pt-5">{{ $clusters->links() }}</div>@endif
            @endif
        </div>
</section>
