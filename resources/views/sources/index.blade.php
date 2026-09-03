<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Minha curadoria</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Minhas fontes</h1>
                <p class="mt-3 max-w-2xl text-slate-500">Escolha as fontes que você quer acompanhar. A lista abaixo é exclusiva para o seu usuário.</p>
            </div>
            <a href="{{ route('sources.create') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Cadastrar fonte</a>
        </div>

        @if (session('status'))
            <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="mt-10">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Selecionadas</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-950">Fontes de seu interesse</h2>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ $selectedSources->count() }} {{ $selectedSources->count() === 1 ? 'fonte' : 'fontes' }}</span>
            </div>

            <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                @if ($selectedSources->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <h3 class="text-lg font-semibold text-slate-900">Você ainda não selecionou nenhuma fonte</h3>
                        <p class="mt-2 text-sm text-slate-500">Use o catálogo abaixo para escolher as fontes que deseja acompanhar.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Fonte</th>
                                    <th class="px-6 py-4 font-semibold">Feeds</th>
                                    <th class="px-6 py-4 font-semibold">RSS cadastrados</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold">Último sucesso</th>
                                    <th class="px-6 py-4 font-semibold">Saúde</th>
                                    <th class="px-6 py-4"><span class="sr-only">Ações</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($selectedSources as $source)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-6 py-5"><a href="{{ route('sources.edit', $source) }}" class="font-semibold text-slate-900 hover:text-amber-700">{{ $source->name }}</a><p class="mt-1 text-xs text-slate-400">{{ $source->domain }}</p></td>
                                        <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $source->feeds_count }}</td>
                                        <td class="min-w-72 px-6 py-5">
                                            @if ($source->feeds->isEmpty())
                                                <span class="text-xs text-slate-400">Nenhum RSS cadastrado</span>
                                            @else
                                                <details>
                                                    <summary class="cursor-pointer text-xs font-semibold text-amber-700">{{ $source->feeds_count }} {{ $source->feeds_count === 1 ? 'feed disponível' : 'feeds disponíveis' }}</summary>
                                                    <div class="mt-3 space-y-2">
                                                        @foreach ($source->feeds as $feed)
                                                            <div>
                                                                <p class="text-xs font-semibold text-slate-700">{{ $feed->name }}</p>
                                                                <p class="mt-0.5 break-all text-[11px] text-slate-400">{{ $feed->url }}</p>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-5">
                                            @if ($source->active)
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativa</span>
                                            @else
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Inativa</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $source->feeds_max_last_success_at ? \Illuminate\Support\Carbon::parse($source->feeds_max_last_success_at)->format('d/m/Y H:i') : 'Nunca' }}</td>
                                        <td class="whitespace-nowrap px-6 py-5">
                                            @if ($source->health_status === 'healthy')
                                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Saudável</span>
                                            @elseif ($source->health_status === 'unhealthy')
                                                <span class="inline-flex items-center gap-2 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Com falhas</span>
                                            @else
                                                <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Aguardando</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-5 text-right">
                                            <form method="POST" action="{{ route('sources.deselect', $source) }}" onsubmit="return confirm('Remover esta fonte da sua lista?')">
                                                @csrf @method('DELETE')
                                                <button class="font-medium text-rose-600 hover:text-rose-800">Remover da minha lista</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section class="mt-12">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Catálogo</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-950">Fontes disponíveis</h2>
                    <p class="mt-1 text-sm text-slate-500">Estas fontes já estão cadastradas e podem ser adicionadas à sua lista.</p>
                </div>
                <form method="GET" action="{{ route('sources.index') }}" class="flex w-full gap-2 sm:w-auto">
                    <label for="source-search" class="sr-only">Pesquisar fontes</label>
                    <input id="source-search" name="q" value="{{ $search }}" placeholder="Pesquisar fonte ou domínio" class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 sm:w-72">
                    <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Pesquisar</button>
                </form>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($availableSources as $source)
                    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold text-slate-950">{{ $source->name }}</h3>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $source->domain }}</p>
                            </div>
                            @if ($source->active)
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500" title="Ativa"></span>
                            @else
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-slate-400" title="Inativa"></span>
                            @endif
                        </div>
                        <p class="mt-4 text-xs text-slate-500">{{ $source->feeds_count }} {{ $source->feeds_count === 1 ? 'feed cadastrado' : 'feeds cadastrados' }}</p>
                        @if ($source->feeds->isEmpty())
                            <p class="mt-3 text-xs text-slate-400">Nenhum RSS cadastrado.</p>
                        @else
                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs font-semibold text-amber-700">Ver RSS da fonte</summary>
                                <div class="mt-3 space-y-2 rounded-xl bg-slate-50 p-3">
                                    @foreach ($source->feeds as $feed)
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">{{ $feed->name }}</p>
                                            <p class="mt-0.5 break-all text-[11px] text-slate-400">{{ $feed->url }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                        @if ($source->active)
                            <form method="POST" action="{{ route('sources.select', $source) }}" class="mt-5">
                                @csrf
                                <button class="w-full rounded-xl bg-amber-300 px-3 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-200">Adicionar à minha lista</button>
                            </form>
                        @else
                            <p class="mt-5 rounded-xl bg-slate-100 px-3 py-2.5 text-center text-xs font-semibold text-slate-500">Fonte inativa</p>
                        @endif
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-sm text-slate-500 sm:col-span-2 lg:col-span-3">
                        Nenhuma fonte encontrada. <a href="{{ route('sources.create') }}" class="font-semibold text-amber-700 hover:text-amber-900">Cadastre uma nova fonte</a>.
                    </div>
                @endforelse
            </div>
        </section>
    </section>
</x-layouts.authenticated>
