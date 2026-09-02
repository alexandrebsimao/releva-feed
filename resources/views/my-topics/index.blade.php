<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Personalização</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Meus assuntos</h1>
                <p class="mt-3 max-w-2xl text-slate-500">Escolha os assuntos que orientam seu radar e os que devem ficar de fora.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($suggestions->isNotEmpty())
            <section class="mt-10 rounded-3xl border border-amber-200 bg-amber-50/70 p-6 sm:p-8">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-amber-700">Descoberta automática</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Assuntos encontrados nas suas fontes</h2>
                        <p class="mt-1 text-sm text-slate-600">São recorrentes nas fontes relacionadas aos seus interesses, mas não serão acompanhados automaticamente.</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-amber-700">{{ $suggestions->count() }} sugestão(ões)</span>
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($suggestions as $suggestion)
                        <article class="rounded-2xl border border-amber-100 bg-white p-5">
                            <h3 class="font-semibold text-slate-950">{{ $suggestion->topic->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $suggestion->article_count }} {{ $suggestion->article_count === 1 ? 'notícia' : 'notícias' }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('my-topics.follow', $suggestion->topic) }}">
                                    @csrf
                                    <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Acompanhar</button>
                                </form>
                                <form method="POST" action="{{ route('my-topics.suggestions.ignore', $suggestion) }}">
                                    @csrf
                                    <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Ignorar</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-10 grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-emerald-200 bg-emerald-50/60 p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-emerald-700">Interesses</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Quero acompanhar</h2>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-emerald-700">{{ $followedPreferences->count() }}</span>
                </div>
                <div class="mt-6 space-y-3">
                    @forelse ($followedPreferences as $preference)
                        <article class="rounded-2xl border border-emerald-100 bg-white p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-semibold text-slate-950">{{ $preference->topic->name }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">Prioridade atual: {{ $priorityLabels[$preference->priority] }}</p>
                                </div>
                                <form method="POST" action="{{ route('my-topics.update', $preference->topic) }}" class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <label for="priority-{{ $preference->topic_id }}" class="sr-only">Prioridade de {{ $preference->topic->name }}</label>
                                    <select id="priority-{{ $preference->topic_id }}" name="priority" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-700">
                                        @foreach ($priorityLabels as $priority => $label)
                                            <option value="{{ $priority }}" @selected($preference->priority === $priority)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Salvar</button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('my-topics.destroy', $preference->topic) }}" class="mt-3">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-slate-500 hover:text-rose-700">Remover preferência</button>
                            </form>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-emerald-200 bg-white/60 px-4 py-6 text-sm text-slate-500">Você ainda não acompanha nenhum assunto.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-rose-200 bg-rose-50/60 p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-rose-700">Filtros</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Não quero receber</h2>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-rose-700">{{ $excludedPreferences->count() }}</span>
                </div>
                <div class="mt-6 space-y-3">
                    @forelse ($excludedPreferences as $preference)
                        <article class="rounded-2xl border border-rose-100 bg-white p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-semibold text-slate-950">{{ $preference->topic->name }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">Prioridade atual: {{ $priorityLabels[$preference->priority] }}</p>
                                </div>
                                <form method="POST" action="{{ route('my-topics.update', $preference->topic) }}" class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <label for="excluded-priority-{{ $preference->topic_id }}" class="sr-only">Prioridade de {{ $preference->topic->name }}</label>
                                    <select id="excluded-priority-{{ $preference->topic_id }}" name="priority" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-700">
                                        @foreach ($priorityLabels as $priority => $label)
                                            <option value="{{ $priority }}" @selected($preference->priority === $priority)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Salvar</button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('my-topics.destroy', $preference->topic) }}" class="mt-3">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-slate-500 hover:text-rose-700">Remover preferência</button>
                            </form>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-rose-200 bg-white/60 px-4 py-6 text-sm text-slate-500">Você não bloqueou nenhum assunto.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="mt-10">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Descoberta</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-950">Assuntos disponíveis</h2>
                    <p class="mt-1 text-sm text-slate-500">Pesquise e escolha como cada assunto deve aparecer para você.</p>
                </div>
                <form method="GET" action="{{ route('my-topics.index') }}" class="flex w-full gap-2 sm:w-auto">
                    <label for="topic-search" class="sr-only">Pesquisar assuntos</label>
                    <input id="topic-search" name="q" value="{{ $search }}" placeholder="Pesquisar assuntos" class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 sm:w-72">
                    <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Pesquisar</button>
                </form>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($availableTopics as $topic)
                    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-slate-950">{{ $topic->name }}</h3>
                                <p class="mt-1 text-xs uppercase tracking-wider text-slate-400">{{ $topic->type }}</p>
                            </div>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" title="Ativo"></span>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('my-topics.follow', $topic) }}">
                                @csrf
                                <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Acompanhar</button>
                            </form>
                            <form method="POST" action="{{ route('my-topics.exclude', $topic) }}">
                                @csrf
                                <button class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Excluir</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-sm text-slate-500 sm:col-span-2 lg:col-span-3">Nenhum assunto disponível para esta pesquisa.</div>
                @endforelse
            </div>
        </section>
    </section>
</x-layouts.authenticated>
