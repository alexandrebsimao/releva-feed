<x-layouts.public>
    <section class="mx-auto max-w-6xl px-6 pb-14 pt-14 lg:pb-20 lg:pt-20">
        <div class="max-w-3xl">
            <p class="mb-5 text-sm font-semibold uppercase tracking-[0.24em] text-amber-300">Releva Feed · contexto em primeiro lugar</p>
            <h1 class="text-5xl font-semibold tracking-tight text-white sm:text-7xl">Notícias que merecem seu tempo. Reunidas em acontecimentos.</h1>
            <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-400">Leituras consolidadas a partir de fontes diferentes, com contexto para você entender o que aconteceu e por que importa.</p>
            <div class="mt-8 flex flex-wrap items-center gap-4">
                <a href="{{ route('login') }}" class="rounded-full bg-amber-300 px-6 py-3 font-semibold text-slate-950 transition hover:bg-amber-200">Acessar meu radar</a>
                <span class="text-sm text-slate-500">Personalize os assuntos que você acompanha</span>
            </div>
        </div>

        <div class="mt-12">
            @include('shared.source-filter', ['filterAction' => route('home')])
        </div>

        <section class="mt-16">
            <div class="flex items-end justify-between gap-4 border-b border-white/10 pb-4"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-300">Em evidência</p><h2 class="mt-2 text-3xl font-semibold text-white">Destaques</h2></div><span class="hidden text-sm text-slate-500 sm:inline">Seleção por relevância geral</span></div>
            @if ($highlights->isEmpty())
                <p class="py-10 text-sm text-slate-500">Os primeiros destaques aparecerão assim que houver acontecimentos processados.</p>
            @else
                <div class="mt-6 grid gap-5 lg:grid-cols-3">@foreach ($highlights as $cluster) @include('public._cluster-card', ['cluster' => $cluster]) @endforeach</div>
            @endif
        </section>

        @foreach ([['brasil', 'Brasil'], ['tecnologia', 'Tecnologia'], ['automotivo', 'Automotivo']] as [$key, $label])
            <section class="mt-16">
                <div class="flex items-end justify-between gap-4 border-b border-white/10 pb-4"><h2 class="text-3xl font-semibold text-white">{{ $label }}</h2><span class="text-sm text-slate-500">Mais relevantes</span></div>
                @if (${$key}->isEmpty())
                    <p class="py-8 text-sm text-slate-500">Nenhum acontecimento disponível nesta seção.</p>
                @else
                    <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-4">@foreach (${$key} as $cluster) @include('public._cluster-card', ['cluster' => $cluster]) @endforeach</div>
                @endif
            </section>
        @endforeach

        <section class="mt-16">
            <div class="flex items-end justify-between gap-4 border-b border-white/10 pb-4"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-300">Atualizações recentes</p><h2 class="mt-2 text-3xl font-semibold text-white">Agora</h2></div><span class="text-sm text-slate-500">Ordem cronológica</span></div>
            @if ($agora->isEmpty())
                <p class="py-8 text-sm text-slate-500">Nenhum acontecimento recente.</p>
            @else
                <div class="mt-4 divide-y divide-white/10">
                    @foreach ($agora as $cluster)
                        @php
                            $clusterUrl = route('news.show', $cluster);
                            if (! empty($selectedSourceIds)) {
                                $clusterUrl .= '?'.http_build_query(['sources' => $selectedSourceIds]);
                            }
                        @endphp
                        <a href="{{ $clusterUrl }}" class="flex flex-col gap-2 py-5 transition hover:bg-white/[0.03] sm:flex-row sm:items-baseline sm:gap-6"><time class="shrink-0 text-sm font-semibold text-amber-300">{{ $cluster->last_article_at?->format('H:i') }}</time><span class="text-lg font-medium text-white">{{ $cluster->title }}</span><span class="text-sm text-slate-500 sm:ml-auto">{{ $cluster->category ?: 'Acontecimento' }}</span></a>
                    @endforeach
                </div>
            @endif
        </section>
    </section>
</x-layouts.public>
