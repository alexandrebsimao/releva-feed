<x-layouts.public>
    <section class="mx-auto max-w-5xl px-6 py-12 lg:py-16">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="text-sm font-semibold text-amber-300 hover:text-amber-200">← Voltar</a>

        <article class="mt-8">
            <div class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-300">
                <span>{{ $newsCluster->category ?: 'Acontecimento' }}</span>
                <span class="text-slate-500">{{ $newsCluster->published_at?->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y, H:i') }}</span>
            </div>
            <h1 class="mt-5 max-w-4xl text-4xl font-semibold leading-tight tracking-tight text-white sm:text-6xl">{{ $newsCluster->title }}</h1>

            <div class="mt-7 rounded-3xl border border-amber-300/30 bg-amber-300/10 p-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-300">Resumo consolidado</p>
                @if ($newsCluster->summary)<p class="mt-4 text-lg leading-8 text-slate-200">{{ $newsCluster->summary }}</p>@endif
                @if ($newsCluster->why_it_matters)<p class="mt-5 border-t border-amber-300/20 pt-5 text-sm leading-7 text-amber-100"><span class="font-semibold text-amber-300">Por que importa:</span> {{ $newsCluster->why_it_matters }}</p>@endif
                <p class="mt-4 text-xs leading-5 text-slate-400">Este texto reúne os pontos em comum das fontes abaixo. Consulte as publicações originais para ler cada cobertura completa.</p>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-2">
                @foreach ($topics as $topic)
                    <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-slate-300">{{ $topic->name }}</span>
                @endforeach
                <span class="ml-1 text-sm text-slate-500">{{ $sourceCount }} {{ $sourceCount === 1 ? 'fonte' : 'fontes' }}</span>
            </div>
        </article>

        <section class="mt-14">
            <div class="flex flex-col justify-between gap-2 border-b border-white/10 pb-5 sm:flex-row sm:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-300">Apuração de origem</p>
                    <h2 class="mt-2 text-3xl font-semibold text-white">Fontes</h2>
                </div>
                <p class="text-sm text-slate-500">{{ $newsCluster->articles->count() }} {{ $newsCluster->articles->count() === 1 ? 'matéria associada' : 'matérias associadas' }}</p>
            </div>

            <div class="mt-5 divide-y divide-white/10 rounded-3xl border border-white/10 bg-white/[0.04]">
                @foreach ($newsCluster->articles as $article)
                    @php
                        $externalUrl = filter_var($article->url, FILTER_VALIDATE_URL);
                        $externalScheme = is_string($externalUrl) ? parse_url($externalUrl, PHP_URL_SCHEME) : null;
                    @endphp
                    <article class="flex flex-col gap-4 p-6 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $article->feed?->source?->name ?: 'Fonte não identificada' }}</p>
                            <h3 class="mt-2 text-lg font-semibold leading-7 text-white">{{ $article->original_title }}</h3>
                            <p class="mt-2 text-sm text-slate-500">Publicado em {{ $article->published_at?->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y, H:i') }}</p>
                        </div>
                        @if (in_array($externalScheme, ['http', 'https'], true))
                            <a href="{{ $externalUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-white/15 px-4 py-2.5 text-sm font-semibold text-amber-300 transition hover:border-amber-300 hover:bg-amber-300/10">Ler fonte original ↗</a>
                        @else
                            <span class="text-sm text-slate-500">Link original indisponível</span>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    </section>
</x-layouts.public>
