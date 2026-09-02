<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-12 lg:py-16">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Seu espaço</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Bom dia, {{ auth()->user()->name }}.</h1>
            <p class="mt-3 max-w-2xl text-slate-500">Seu radar reúne notícias ligadas aos assuntos acompanhados e respeita todas as exclusões.</p>
        </div>

        <section class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-100 px-6 py-6 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500">Atualizado pela sua curadoria</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-950">Seu radar</h2>
                </div>
                <a href="{{ route('my-topics.index') }}" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Ajustar assuntos</a>
            </div>
            @if ($articles->isEmpty())
                <div class="px-6 py-14 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">Seu radar ainda está vazio</h3>
                    <p class="mt-2 text-sm text-slate-500">Acompanhe um assunto para começar a receber notícias relevantes.</p>
                    <a href="{{ route('my-topics.index') }}" class="mt-5 inline-flex rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-200">Escolher assuntos</a>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($articles as $article)
                        <article class="px-6 py-6 transition hover:bg-slate-50">
                            <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="text-lg font-semibold leading-7 text-slate-900 hover:text-amber-700">{{ $article->original_title }}</a>
                            <p class="mt-2 text-sm text-slate-500">{{ $article->feed->source->name }} · {{ $article->feed->name }}</p>
                            @foreach ($article->clusters as $cluster)<a href="{{ route('news.show', $cluster) }}" class="mt-2 inline-flex text-sm font-semibold text-amber-700 hover:text-amber-600">Ver acontecimento consolidado →</a>@endforeach
                            <p class="mt-2 text-xs font-medium text-slate-400">Relevância para você: {{ $article->user_relevance_score }}/100</p>
                            @if ($article->description)<p class="mt-3 max-w-4xl text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($article->description, 240) }}</p>@endif
                            <div class="mt-4 flex flex-wrap gap-2">@foreach ($article->topics as $topic)<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $topic->name }}</span>@endforeach</div>
                        </article>
                    @endforeach
                </div>
                @if ($articles->hasPages())<div class="border-t border-slate-200 px-6 py-4">{{ $articles->links() }}</div>@endif
            @endif
        </section>
    </section>
</x-layouts.authenticated>
