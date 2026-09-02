@csrf
@if ($feed->exists)
    @method('PUT')
@endif

<div class="space-y-5">
    @php($selectedSource = $sources->firstWhere('id', (int) old('source_id', $feed->source_id ?: $source->id)))
    <div data-source-picker class="relative">
        <label for="source-input" class="text-sm font-medium text-slate-700">Fonte</label>
        <input type="hidden" name="source_id" value="{{ old('source_id', $feed->source_id ?: $source->id) }}" data-source-id>
        <input id="source-input" type="search" value="{{ old('new_source_name') ? '' : old('source_name', $selectedSource?->name ?: $feed->source?->name ?: $source->name) }}" autocomplete="off" placeholder="Busque uma fonte cadastrada" data-source-input class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        <div class="absolute inset-x-0 top-full z-10 mt-2 hidden max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-lg" data-source-options>
            @foreach ($sources as $sourceOption)
                <button type="button" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left hover:bg-amber-50" data-source-option data-source-option-id="{{ $sourceOption->id }}" data-source-option-name="{{ $sourceOption->name }}" data-source-option-text="{{ $sourceOption->name }} {{ $sourceOption->domain }}">
                    <span class="truncate text-sm font-semibold text-slate-800">{{ $sourceOption->name }}</span><span class="shrink-0 text-xs text-slate-400">{{ $sourceOption->domain }}</span>
                </button>
            @endforeach
            <p class="hidden px-3 py-2 text-sm text-slate-500" data-source-empty>Nenhuma fonte encontrada.</p>
        </div>
        <p class="mt-2 text-xs text-slate-500">Selecione uma fonte já cadastrada ou adicione uma nova abaixo.</p>
        @error('source_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        @error('new_source_name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        @error('new_source_domain')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        <button type="button" data-new-source-toggle class="mt-3 text-sm font-semibold text-amber-700 hover:text-amber-900">+ Cadastrar nova fonte</button>
        <div class="mt-4 {{ old('new_source_name') || $errors->has('new_source_name') || $errors->has('new_source_domain') ? '' : 'hidden' }} rounded-2xl border border-amber-200 bg-amber-50 p-4" data-new-source-panel>
            <p class="text-sm font-semibold text-amber-950">Nova fonte</p>
            <p class="mt-1 text-xs text-amber-900/70">Ela será criada na base principal ao salvar este feed.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="new-source-name" class="text-sm font-medium text-slate-700">Nome da fonte</label>
                    <input id="new-source-name" name="new_source_name" value="{{ old('new_source_name') }}" data-new-source-name placeholder="Ex.: Jornal Exemplo" class="mt-2 w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
                </div>
                <div>
                    <label for="new-source-domain" class="text-sm font-medium text-slate-700">Domínio</label>
                    <input id="new-source-domain" name="new_source_domain" value="{{ old('new_source_domain') }}" placeholder="exemplo.com.br" class="mt-2 w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
                </div>
            </div>
        </div>
    </div>

    <div>
        <label for="name" class="text-sm font-medium text-slate-700">Nome do feed</label>
        <input id="name" name="name" value="{{ old('name', $feed->name) }}" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="url" class="text-sm font-medium text-slate-700">URL do feed</label>
        <input id="url" name="url" type="url" value="{{ old('url', $feed->url) }}" required placeholder="https://exemplo.com/feed.xml" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        <p class="mt-2 text-xs text-slate-500">Use uma URL válida começando com http:// ou https://.</p>
        @error('url')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="category" class="text-sm font-medium text-slate-700">Categoria <span class="font-normal text-slate-400">(opcional)</span></label>
        <input id="category" name="category" value="{{ old('category', $feed->category) }}" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('category')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <label class="flex items-center gap-3 text-sm font-medium text-slate-700"><input type="checkbox" name="active" value="1" @checked(old('active', $feed->exists ? $feed->active : true)) class="rounded border-slate-300 text-amber-500 focus:ring-amber-300"> Feed ativo</label>
</div>

<div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
    <a href="{{ route('sources.edit', $source) }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-950">Cancelar</a>
    <button class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Salvar feed</button>
</div>
