@csrf
@if ($topic->exists)
    @method('PUT')
@endif

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="text-sm font-medium text-slate-700">Nome</label>
        <input id="name" name="name" value="{{ old('name', $topic->name) }}" required placeholder="ex.: Inteligência Artificial" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="slug" class="text-sm font-medium text-slate-700">Slug</label>
        <input id="slug" name="slug" value="{{ old('slug', $topic->slug) }}" required placeholder="ex.: inteligencia-artificial" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        <p class="mt-2 text-xs text-slate-500">Use um slug estável; ele identifica o assunto no domínio.</p>
        @error('slug')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="type" class="text-sm font-medium text-slate-700">Tipo</label>
        <select id="type" name="type" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">@foreach ($types as $value => $label)<option value="{{ $value }}" @selected(old('type', $topic->type) === $value)>{{ $label }}</option>@endforeach</select>
        @error('type')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="status" class="text-sm font-medium text-slate-700">Status</label>
        <select id="status" name="status" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(old('status', $topic->status) === $value)>{{ $label }}</option>@endforeach</select>
        @error('status')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex items-center sm:pt-8"><label class="flex items-center gap-3 text-sm font-medium text-slate-700"><input type="checkbox" name="active" value="1" @checked(old('active', $topic->exists ? $topic->active : true)) class="rounded border-slate-300 text-amber-500 focus:ring-amber-300"> Assunto ativo</label></div>
</div>

<div>
    <label for="aliases" class="text-sm font-medium text-slate-700">Aliases <span class="font-normal text-slate-400">(opcional)</span></label>
    <textarea id="aliases" name="aliases" rows="4" placeholder="Um alias por linha — ex.: IA, AI, Artificial Intelligence" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">{{ old('aliases', $topic->exists ? $topic->aliases->pluck('name')->implode("\n") : '') }}</textarea>
    <p class="mt-2 text-xs text-slate-500">Aliases são normalizados sem acentos, com minúsculas e espaços consistentes para evitar duplicidades.</p>
    @error('aliases')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
</div>

<div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6"><a href="{{ route('topics.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-950">Cancelar</a><button class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Salvar assunto</button></div>
