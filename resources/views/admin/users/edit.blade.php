<x-layouts.authenticated>
    <section class="mx-auto max-w-3xl px-6 py-10 lg:py-14">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-950">← Voltar para usuários</a>
        <h1 class="mt-6 text-4xl font-semibold tracking-tight text-slate-950">Editar usuário</h1>
        <p class="mt-3 text-slate-500">Atualize os dados de <strong>{{ $user->name }}</strong>.</p>
        
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-10 space-y-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @include('admin.users._form')
        </form>
    </section>
</x-layouts.authenticated>
