<x-layouts.authenticated>
    <section class="mx-auto max-w-5xl px-6 py-10 lg:py-14">
        <a href="{{ route('sources.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-950">← Voltar para fontes</a>
        <div class="mt-6 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div><p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Fonte</p><h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{{ $source->name }}</h1><p class="mt-3 text-slate-500">Atualize os dados da fonte ou administre seus feeds.</p></div>
            @if (auth()->user()?->is_admin === true)
                <form method="POST" action="{{ route('sources.destroy', $source) }}" onsubmit="return confirm('Remover esta fonte e seus feeds?')">@csrf @method('DELETE')<button class="rounded-xl px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Remover fonte</button></form>
            @else
                <form method="POST" action="{{ route('sources.deselect', $source) }}" onsubmit="return confirm('Remover esta fonte da sua lista?')">@csrf @method('DELETE')<button class="rounded-xl px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Remover da minha lista</button></form>
            @endif
        </div>

        @if (session('status'))<div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif

        <form method="POST" action="{{ route('sources.update', $source) }}" class="mt-10 space-y-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">@include('sources._form')</form>

        <div class="mt-10 flex items-end justify-between gap-4"><div><h2 class="text-2xl font-semibold text-slate-950">Feeds desta fonte</h2><p class="mt-2 text-sm text-slate-500">Cada fonte pode possuir vários endereços RSS.</p></div><a href="{{ route('sources.feeds.create', $source) }}" class="rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-200">Novo feed</a></div>
        <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            @if ($source->feeds->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-slate-500">Nenhum feed cadastrado para esta fonte.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($source->feeds as $feed)
                        <div class="flex flex-col justify-between gap-4 px-6 py-5 sm:flex-row sm:items-center">
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-slate-900">{{ $feed->name }}</h3>@if ($feed->health_status === 'healthy')<span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Saudável</span>@elseif ($feed->health_status === 'unhealthy')<span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>Com falhas</span>@else<span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Aguardando</span>@endif @if ($feed->active)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Ativo</span>@else<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-400">Inativo</span>@endif</div><p class="mt-1 truncate text-sm text-slate-500">{{ $feed->url }}</p><p class="mt-2 text-xs text-slate-400">{{ $feed->category ?: 'Sem categoria' }} · Tentativa: {{ $feed->last_fetched_at ? $feed->last_fetched_at->format('d/m/Y H:i') : 'Nunca' }} · Sucesso: {{ $feed->last_success_at ? $feed->last_success_at->format('d/m/Y H:i') : 'Nunca' }} · {{ $feed->failure_count }} falha(s)</p>@if ($feed->last_error)<p class="mt-1 max-w-2xl truncate text-xs text-rose-600" title="{{ $feed->last_error }}">Erro: {{ $feed->last_error }}</p>@endif</div>
                            <div class="flex shrink-0 items-center gap-4 text-sm"><a href="{{ route('sources.feeds.edit', [$source, $feed]) }}" class="font-medium text-slate-600 hover:text-slate-950">Editar</a><form method="POST" action="{{ route('sources.feeds.destroy', [$source, $feed]) }}" onsubmit="return confirm('Remover este feed?')">@csrf @method('DELETE')<button class="font-medium text-rose-600 hover:text-rose-800">Remover</button></form></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.authenticated>
