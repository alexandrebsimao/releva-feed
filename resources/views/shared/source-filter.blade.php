@php($selectedSourceIds = collect($selectedSourceIds ?? [])->map(fn ($id) => (int) $id)->all())

<form method="GET" action="{{ $filterAction }}" class="rounded-3xl border border-slate-200 bg-white shadow-sm" data-source-filter>
    <details class="group">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 [&::-webkit-details-marker]:hidden">
            <span>
                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-amber-600">Personalize a leitura</span>
                <span class="mt-2 block text-xl font-semibold text-slate-950">Filtrar por fonte</span>
                <span class="mt-1 block text-sm text-slate-500">{{ $selectedSourceIds === [] ? 'Nenhum filtro aplicado' : count($selectedSourceIds).' '.(count($selectedSourceIds) === 1 ? 'fonte selecionada' : 'fontes selecionadas') }}</span>
            </span>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-600 transition group-open:rotate-180">⌄</span>
        </summary>

        <div class="border-t border-slate-100 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Escolha uma ou várias fontes para ver somente as notícias desejadas.</p>
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Aplicar filtros</button>
                    @if ($selectedSourceIds !== [])
                        <a href="{{ $filterAction }}" class="text-sm font-medium text-slate-500 hover:text-slate-950">Limpar</a>
                    @endif
                </div>
            </div>

            @if ($sources->isEmpty())
                <p class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Nenhuma fonte ativa cadastrada.</p>
            @else
                <div class="mt-5">
                    <label for="source-filter-search" class="sr-only">Buscar fonte</label>
                    <input id="source-filter-search" type="search" placeholder="Buscar fonte por nome ou domínio" data-source-filter-search class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
                </div>
                <div class="mt-4 grid max-h-64 gap-2 overflow-y-auto pr-1 sm:grid-cols-2 lg:grid-cols-3" data-source-filter-options>
                    @foreach ($sources as $source)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-3 py-3 transition hover:border-amber-300 hover:bg-amber-50" data-source-filter-option data-source-filter-text="{{ $source->name }} {{ $source->domain }}">
                            <input type="checkbox" name="sources[]" value="{{ $source->id }}" @checked(in_array($source->id, $selectedSourceIds, true)) class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-300">
                            <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-800">{{ $source->name }}</span><span class="mt-0.5 block truncate text-xs text-slate-400">{{ $source->domain }}</span></span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-3 hidden text-sm text-slate-500" data-source-filter-empty>Nenhuma fonte encontrada.</p>
            @endif
        </div>
    </details>
</form>
