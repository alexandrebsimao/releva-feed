@csrf
@if ($source->exists)
    @method('PUT')
@endif

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="text-sm font-medium text-slate-700">Nome</label>
        <input id="name" name="name" value="{{ old('name', $source->name) }}" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="slug" class="text-sm font-medium text-slate-700">Slug</label>
        <input id="slug" name="slug" value="{{ old('slug', $source->slug) }}" required placeholder="ex.: the-news" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('slug')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="domain" class="text-sm font-medium text-slate-700">Domínio</label>
        <input id="domain" name="domain" value="{{ old('domain', $source->domain) }}" required placeholder="ex.: thenews.com" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('domain')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="priority" class="text-sm font-medium text-slate-700">Prioridade</label>
        <input id="priority" name="priority" type="number" min="0" max="1000" value="{{ old('priority', $source->priority ?? 0) }}" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('priority')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex items-center sm:pt-8">
        @if (auth()->user()?->is_admin === true)
            <label class="flex items-center gap-3 text-sm font-medium text-slate-700"><input type="checkbox" name="active" value="1" @checked(old('active', $source->exists ? $source->active : true)) class="rounded border-slate-300 text-amber-500 focus:ring-amber-300"> Fonte ativa</label>
        @else
            <p class="text-sm text-slate-500">O status da fonte é gerenciado pela administração.</p>
        @endif
    </div>
</div>

<div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
    <a href="{{ route('sources.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-950">Cancelar</a>
    <button class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Salvar fonte</button>
</div>
