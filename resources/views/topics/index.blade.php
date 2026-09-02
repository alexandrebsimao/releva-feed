<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Curadoria</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Assuntos</h1>
                <p class="mt-3 text-slate-500">Organize os assuntos que ajudam a dar sentido ao seu feed.</p>
            </div>
            <a href="{{ route('topics.create') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Novo assunto</a>
        </div>

        @if (session('status'))<div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif

        <div class="mt-10 space-y-10">
            @foreach ($statusLabels as $status => $label)
                @php($topics = $topicsByStatus->get($status, collect()))
                <section>
                    <div class="flex items-center justify-between gap-4"><div><h2 class="text-2xl font-semibold text-slate-950">{{ $label }}</h2><p class="mt-1 text-sm text-slate-500">{{ $topics->count() }} assunto(s)</p></div></div>
                    @if ($topics->isEmpty())
                        <div class="mt-4 rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-sm text-slate-500">Nenhum assunto nesta categoria.</div>
                    @else
                        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($topics as $topic)
                                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <div class="flex items-start justify-between gap-3"><div><h3 class="font-semibold text-slate-950">{{ $topic->name }}</h3><p class="mt-1 text-xs uppercase tracking-wider text-slate-400">{{ $typeLabels[$topic->type] ?? $topic->type }}</p></div><span class="h-3 w-3 shrink-0 rounded-full {{ $topic->active ? 'bg-emerald-500' : 'bg-slate-300' }}" title="{{ $topic->active ? 'Ativo' : 'Inativo' }}"></span></div>
                                    <div class="mt-5 flex items-center gap-2 text-sm text-slate-500"><span class="font-semibold text-slate-900">{{ $topic->articles_count }}</span> artigo(s) associado(s)</div>
                                    @if ($topic->aliases->isNotEmpty())<div class="mt-5 flex flex-wrap gap-2">@foreach ($topic->aliases as $alias)<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $alias->name }}</span>@endforeach</div>@else<p class="mt-5 text-xs text-slate-400">Sem aliases cadastrados.</p>@endif
                                    <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5"><a href="{{ route('topics.edit', $topic) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">Editar</a>@if ($status !== \App\Models\Topic::STATUS_APPROVED)<form method="POST" action="{{ route('topics.approve', $topic) }}">@csrf @method('PATCH')<button class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">Aprovar</button></form>@endif @if ($status !== \App\Models\Topic::STATUS_IGNORED)<form method="POST" action="{{ route('topics.ignore', $topic) }}">@csrf @method('PATCH')<button class="text-sm font-semibold text-rose-600 hover:text-rose-800">Ignorar</button></form>@endif</div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </section>
</x-layouts.authenticated>
