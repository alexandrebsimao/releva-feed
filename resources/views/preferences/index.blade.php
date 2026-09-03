<x-layouts.authenticated>
    <section class="mx-auto max-w-5xl px-6 py-12 lg:py-16">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Configuração pessoal</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Preferências</h1>
            <p class="mt-3 max-w-2xl text-slate-500">Escolha os assuntos e as fontes que definem seus dois feeds pessoais.</p>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-2">
            <a href="{{ route('my-topics.index') }}" class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-700">#</div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $followedTopicsCount }} {{ $followedTopicsCount === 1 ? 'assunto' : 'assuntos' }}</span>
                </div>
                <h2 class="mt-6 text-2xl font-semibold text-slate-950 group-hover:text-amber-700">Assuntos</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Escolha os temas que devem aparecer no <strong class="font-semibold text-slate-700">Meu feed</strong>. Você também pode excluir temas e definir prioridades.</p>
                <span class="mt-6 inline-flex text-sm font-semibold text-amber-700">Configurar assuntos →</span>
            </a>

            <a href="{{ route('sources.index') }}" class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-xl text-amber-700">◎</div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ $selectedSourcesCount }} {{ $selectedSourcesCount === 1 ? 'fonte' : 'fontes' }}</span>
                </div>
                <h2 class="mt-6 text-2xl font-semibold text-slate-950 group-hover:text-amber-700">Fontes</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Escolha as fontes que devem aparecer no <strong class="font-semibold text-slate-700">Por fontes</strong>. Você também pode pesquisar no catálogo e cadastrar uma nova.</p>
                <span class="mt-6 inline-flex text-sm font-semibold text-amber-700">Configurar fontes →</span>
            </a>
        </div>

        <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50 p-6">
            <h2 class="font-semibold text-slate-900">Qual feed devo usar?</h2>
            <div class="mt-4 grid gap-4 text-sm text-slate-600 md:grid-cols-3">
                <div><p class="font-semibold text-slate-800">Meu feed</p><p class="mt-1">Artigos sobre os assuntos que você acompanha.</p></div>
                <div><p class="font-semibold text-slate-800">Por fontes</p><p class="mt-1">Tudo que foi publicado pelas suas fontes.</p></div>
                <div><p class="font-semibold text-slate-800">Acontecimentos</p><p class="mt-1">Várias notícias reunidas em um mesmo fato.</p></div>
            </div>
        </div>
    </section>
</x-layouts.authenticated>
