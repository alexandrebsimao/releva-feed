<x-layouts.authenticated>
    <section class="mx-auto max-w-3xl px-6 py-10 lg:py-14">
        <a href="{{ route('topics.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-950">← Voltar para assuntos</a>
        <div class="mt-6 flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Assunto</p><h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{{ $topic->name }}</h1><p class="mt-3 text-slate-500">Edite os dados, status e aliases deste assunto.</p></div><form method="POST" action="{{ route('topics.destroy', $topic) }}" onsubmit="return confirm('Remover este assunto e suas associações?')">@csrf @method('DELETE')<button class="rounded-xl px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Remover assunto</button></form></div>
        @if (session('status'))<div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('topics.update', $topic) }}" class="mt-10 space-y-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">@include('topics._form')</form>
        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">{{ $topic->articles()->count() }} artigo(s) associado(s) a este assunto.</div>
    </section>
</x-layouts.authenticated>
