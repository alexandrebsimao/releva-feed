<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Administração</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Artigos importados</h1>
            <p class="mt-3 text-slate-500">Visualização administrativa dos artigos recebidos pelos feeds.</p>
        </div>

        <div class="mt-8">
            @include('shared.source-filter', ['filterAction' => route('articles.index')])
        </div>

        <div class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            @if ($articles->isEmpty())
                <div class="px-6 py-16 text-center"><h2 class="text-lg font-semibold text-slate-900">Nenhum artigo importado</h2><p class="mt-2 text-sm text-slate-500">Execute a coleta de feeds para começar a visualizar artigos.</p></div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($articles as $article)
                        <article class="px-6 py-6 transition hover:bg-slate-50">
                            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                <div class="min-w-0"><a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="text-lg font-semibold leading-7 text-slate-900 hover:text-amber-700">{{ $article->original_title }}</a><p class="mt-2 text-sm text-slate-500">{{ $article->feed->source->name }} · {{ $article->feed->name }}</p></div>
                                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $article->processing_status }}</span>
                            </div>
                            @if ($article->description)<p class="mt-4 max-w-4xl text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($article->description, 280) }}</p>@endif
                            <div class="mt-4 flex flex-wrap items-center gap-2" aria-label="Tags do artigo">
                                @forelse ($article->topics as $topic)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $topic->name }}</span>
                                @empty
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">Aguardando classificação</span>
                                @endforelse
                            </div>
                            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-400"><span>Publicado {{ $article->published_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span><span>Importado {{ $article->imported_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>@if ($article->author)<span>Por {{ $article->author }}</span>@endif</div>
                        </article>
                    @endforeach
                </div>
                @if ($articles->hasPages())<div class="border-t border-slate-200 px-6 py-4">{{ $articles->links() }}</div>@endif
            @endif
        </div>
    </section>
</x-layouts.authenticated>
