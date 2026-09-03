<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Administração</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Catálogo de fontes</h1>
                <p class="mt-3 max-w-2xl text-slate-500">Cadastre fontes e controle quais fontes e feeds podem participar da coleta.</p>
            </div>
            <a href="{{ route('sources.create') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Cadastrar fonte</a>
        </div>

        @if (session('status'))
            <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-2xl font-semibold text-slate-950">Todas as fontes</h2>
                <p class="mt-1 text-sm text-slate-500">Desativar uma fonte interrompe a coleta dos seus feeds ativos. Os dados já coletados são preservados.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($sources as $source)
                    <article class="flex flex-col gap-5 px-6 py-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('sources.edit', $source) }}" class="text-lg font-semibold text-slate-950 hover:text-amber-700">{{ $source->name }}</a>
                                @if ($source->active)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativa</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Inativa</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $source->domain }} · Prioridade {{ $source->priority }} · {{ $source->feeds_count }} {{ $source->feeds_count === 1 ? 'feed' : 'feeds' }}</p>
                            @if ($source->feeds->isNotEmpty())
                                <details class="mt-4">
                                    <summary class="cursor-pointer text-sm font-semibold text-amber-700">Ver RSS cadastrados</summary>
                                    <div class="mt-3 space-y-2 rounded-2xl bg-slate-50 p-4">
                                        @foreach ($source->feeds as $feed)
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                                <span class="text-xs font-semibold text-slate-700">{{ $feed->name }}</span>
                                                <span class="break-all text-[11px] text-slate-400">{{ $feed->url }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-4 text-sm">
                            <a href="{{ route('sources.edit', $source) }}" class="font-medium text-slate-600 hover:text-slate-950">Editar</a>
                            <form method="POST" action="{{ route('admin.sources.toggle', $source) }}">
                                @csrf @method('PATCH')
                                <button class="rounded-xl {{ $source->active ? 'border border-rose-200 bg-white text-rose-700 hover:bg-rose-50' : 'bg-emerald-600 text-white hover:bg-emerald-700' }} px-4 py-2.5 text-sm font-semibold">
                                    {{ $source->active ? 'Desativar fonte' : 'Ativar fonte' }}
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="px-6 py-10 text-sm text-slate-500">Nenhuma fonte cadastrada.</p>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.authenticated>
