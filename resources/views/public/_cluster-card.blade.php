@php
    $sources = $cluster->articles
        ->map(fn ($article) => $article->feed?->source?->name)
        ->filter()
        ->unique()
        ->values();
    $topics = $cluster->articles
        ->flatMap(fn ($article) => $article->topics)
        ->unique('id')
        ->sortBy('name')
        ->values();
@endphp

<article class="flex h-full flex-col rounded-3xl border border-white/10 bg-white/[0.05] p-6 transition hover:-translate-y-0.5 hover:border-amber-300/50 hover:bg-white/[0.08]">
    <div class="flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.16em] text-amber-300">
        <span>{{ $cluster->category ?: 'Acontecimento' }}</span>
        @if ($cluster->general_score !== null)<span class="text-slate-500">{{ $cluster->general_score }}/100</span>@endif
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ($topics->take(3) as $topic)
            <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium normal-case tracking-normal text-slate-300">{{ $topic->name }}</span>
        @endforeach
    </div>
    <h3 class="mt-5 text-xl font-semibold leading-8 text-white">
        <a href="{{ route('news.show', $cluster) }}" class="hover:text-amber-200">{{ $cluster->title }}</a>
    </h3>
    @if ($cluster->summary)<p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-400">{{ $cluster->summary }}</p>@endif
    <div class="mt-auto pt-6 text-xs text-slate-500">
        <p>{{ $cluster->last_article_at?->locale('pt_BR')->translatedFormat('j \d\e F, H:i') }}</p>
        <p class="mt-1">{{ $sources->take(3)->implode(' · ') }}@if ($sources->count() > 3) · +{{ $sources->count() - 3 }}@endif</p>
    </div>
</article>
