<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
        <a href="{{ url()->previous() }}" class="text-sm font-semibold text-amber-700 hover:text-amber-600">← Voltar</a>

        <div class="mt-6 max-w-4xl">
            <div class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                <span>{{ $newsCluster->category ?: 'Acontecimento' }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 tracking-normal text-slate-600">{{ $newsCluster->public_status }}</span>
            </div>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-950">{{ $newsCluster->title }}</h1>
            @if ($newsCluster->summary)<p class="mt-5 text-lg leading-8 text-slate-600">{{ $newsCluster->summary }}</p>@endif
            @if ($newsCluster->why_it_matters)<p class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-950"><span class="font-semibold">Por que importa:</span> {{ $newsCluster->why_it_matters }}</p>@endif
            @if ($newsCluster->importance_score !== null)<p class="mt-4 text-sm text-slate-500">Importância consolidada: <span class="font-semibold text-slate-800">{{ $newsCluster->importance_score }}/100</span></p>@endif
        </div>

        <section class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-6">
                <p class="text-sm font-medium text-slate-500">{{ $newsCluster->articles->count() }} fonte(s) associada(s)</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-950">Matérias que deram origem ao resumo</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($newsCluster->articles as $article)
                    <article class="px-6 py-6">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                            <div class="min-w-0">
                                <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="text-lg font-semibold leading-7 text-slate-900 hover:text-amber-700">{{ $article->original_title }}</a>
                                <p class="mt-2 text-sm text-slate-500">{{ $article->feed->source->name }} · {{ $article->feed->name }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400">{{ $article->published_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($article->description)<p class="mt-3 max-w-4xl text-sm leading-6 text-slate-600">{{ $article->description }}</p>@endif
                        <div class="mt-4 flex flex-wrap gap-2">@foreach ($article->topics as $topic)<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $topic->name }}</span>@endforeach</div>
                    </article>
                @endforeach
            </div>
        </section>
    </section>
</x-layouts.authenticated>
