<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-12 lg:py-16">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Curadoria pessoal</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Feed das fontes</h1>
                <p class="mt-3 max-w-2xl text-slate-500">Artigos publicados pelas fontes que você escolheu acompanhar.</p>
            </div>
            <a href="{{ route('sources.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Gerenciar fontes</a>
        </div>

        <section class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-100 px-6 py-6 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500">Sem filtro por assunto</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-950">Artigos das minhas fontes</h2>
                </div>
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Ir para Meu feed</a>
            </div>

            @if ($articles->isEmpty())
                <div class="px-6 py-14 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">Nenhum artigo das suas fontes</h3>
                    <p class="mt-2 text-sm text-slate-500">Selecione uma fonte ou aguarde a próxima coleta de RSS.</p>
                    <a href="{{ route('sources.index') }}" class="mt-5 inline-flex rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-200">Escolher fontes</a>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($articles as $article)
                        <article class="px-6 py-6 transition hover:bg-slate-50">
                            <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="text-lg font-semibold leading-7 text-slate-900 hover:text-amber-700">{{ $article->original_title }}</a>
                            <p class="mt-2 text-sm text-slate-500">{{ $article->feed->source->name }} · {{ $article->feed->name }}</p>
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
        </section>
    </section>
</x-layouts.authenticated>
