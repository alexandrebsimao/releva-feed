<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Administração</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Fontes de notícias</h1>
                <p class="mt-3 text-slate-500">Gerencie as fontes e os feeds que alimentam sua curadoria.</p>
            </div>
            <a href="{{ route('sources.create') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Nova fonte</a>
        </div>

        @if (session('status'))
            <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            @if ($sources->isEmpty())
                <div class="px-6 py-16 text-center">
                    <h2 class="text-lg font-semibold text-slate-900">Nenhuma fonte cadastrada</h2>
                    <p class="mt-2 text-sm text-slate-500">Crie a primeira fonte para começar a organizar seus feeds.</p>
                    <a href="{{ route('sources.create') }}" class="mt-6 inline-flex rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-semibold text-slate-950">Cadastrar fonte</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Nome</th>
                                <th class="px-6 py-4 font-semibold">Domínio</th>
                                <th class="px-6 py-4 font-semibold">Prioridade</th>
                                <th class="px-6 py-4 font-semibold">Feeds</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Última tentativa</th>
                                <th class="px-6 py-4 font-semibold">Último sucesso</th>
                                <th class="px-6 py-4 font-semibold">Falhas</th>
                                <th class="px-6 py-4 font-semibold">Saúde</th>
                                <th class="px-6 py-4 font-semibold">Erro mais recente</th>
                                <th class="px-6 py-4"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($sources as $source)
                                @php($latestErrorFeed = $source->latest_error_feed)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-6 py-5"><a href="{{ route('sources.edit', $source) }}" class="font-semibold text-slate-900 hover:text-amber-700">{{ $source->name }}</a><p class="mt-1 text-xs text-slate-400">{{ $source->slug }}</p></td>
                                    <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $source->domain }}</td>
                                    <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $source->priority }}</td>
                                    <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $source->feeds_count }}</td>
                                    <td class="whitespace-nowrap px-6 py-5">
                                        @if ($source->active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativa</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Inativa</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $source->feeds_max_last_fetched_at ? \Illuminate\Support\Carbon::parse($source->feeds_max_last_fetched_at)->format('d/m/Y H:i') : 'Nunca' }}</td>
                                    <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $source->feeds_max_last_success_at ? \Illuminate\Support\Carbon::parse($source->feeds_max_last_success_at)->format('d/m/Y H:i') : 'Nunca' }}</td>
                                    <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $source->feeds_sum_failure_count ?? 0 }}</td>
                                    <td class="whitespace-nowrap px-6 py-5">
                                        @if ($source->health_status === 'healthy')
                                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Saudável</span>
                                        @elseif ($source->health_status === 'unhealthy')
                                            <span class="inline-flex items-center gap-2 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Com falhas</span>
                                        @else
                                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Aguardando</span>
                                        @endif
                                    </td>
                                    <td class="max-w-xs px-6 py-5 text-slate-600">{{ $latestErrorFeed ? \Illuminate\Support\Str::limit($latestErrorFeed->last_error, 80) : '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-5 text-right"><a href="{{ route('sources.edit', $source) }}" class="font-medium text-slate-600 hover:text-slate-950">Editar</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($sources->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">{{ $sources->links() }}</div>
                @endif
            @endif
        </div>
    </section>
</x-layouts.authenticated>
