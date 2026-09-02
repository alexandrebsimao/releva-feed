<div class="grid gap-5 md:grid-cols-3">
    <article class="rounded-3xl bg-slate-950 p-6 text-white md:col-span-2">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-400">Próximo passo</p>
                <h2 class="mt-3 text-2xl font-semibold">Defina seus assuntos</h2>
            </div>
            <span class="rounded-full bg-amber-300 px-3 py-1 text-xs font-bold text-slate-950">Em breve</span>
        </div>
        <p class="mt-4 max-w-xl leading-7 text-slate-400">Você poderá indicar o que quer acompanhar e também o que prefere não receber. Essas escolhas vão orientar a curadoria do seu feed.</p>
        <button wire:click="addTopic" class="mt-8 rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15">Adicionar assunto de teste</button>
        @if ($selectedTopics > 0)
            <p class="mt-4 text-sm text-amber-300">{{ $selectedTopics }} assunto(s) selecionado(s) nesta demonstração.</p>
        @endif
    </article>
    <article class="rounded-3xl border border-slate-200 bg-white p-6">
        <p class="text-sm font-medium text-slate-500">Estado do sistema</p>
        <div class="mt-8 flex items-center gap-3"><span class="h-3 w-3 rounded-full bg-emerald-500"></span><span class="font-semibold text-slate-900">Fundação ativa</span></div>
        <p class="mt-3 text-sm leading-6 text-slate-500">RSS e IA serão conectados nas próximas etapas.</p>
    </article>
</div>
