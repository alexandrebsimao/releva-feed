@csrf
@if ($feed->exists)
    @method('PUT')
@endif

<div class="space-y-5">
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
