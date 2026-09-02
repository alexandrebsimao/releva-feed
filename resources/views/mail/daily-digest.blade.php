<div style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6; max-width: 680px; margin: 0 auto;">
    <p style="font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: #b45309; font-weight: 700;">Releva Feed</p>
    <h1 style="font-size: 28px; margin: 8px 0 4px;">Seu resumo diário</h1>
    <p style="color: #64748b; margin-top: 0;">{{ $digest->date?->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y') }}</p>

    <p style="background: #f8fafc; border-radius: 10px; padding: 14px 16px; color: #475569;">
        Analisamos <strong>{{ $digest->analyzed_count }}</strong> publicações e encontramos
        <strong>{{ $digest->related_count }}</strong> relacionadas aos seus interesses.
    </p>

    <h2 style="font-size: 20px; margin-top: 28px;">Principais notícias</h2>
    @foreach ($digest->items as $item)
        @php($cluster = $item->newsCluster)
        <article style="border-top: 1px solid #e2e8f0; padding: 18px 0;">
            <p style="font-size: 12px; color: #b45309; font-weight: 700; margin: 0 0 5px;">#{{ $item->position }} · Relevância {{ $item->score }}/100</p>
            <h3 style="font-size: 18px; margin: 0 0 7px;">{{ $cluster->title }}</h3>
            @if ($cluster->summary)<p style="color: #475569; margin: 0 0 9px;">{{ $cluster->summary }}</p>@endif
            @if ($cluster->why_it_matters)<p style="color: #92400e; margin: 0 0 9px;"><strong>Por que importa:</strong> {{ $cluster->why_it_matters }}</p>@endif
            <p style="margin: 0 0 10px;">
                @foreach ($topicsByCluster[$cluster->getKey()] ?? [] as $topic)
                    <span style="display: inline-block; background: #ecfdf5; color: #047857; border-radius: 999px; padding: 3px 8px; font-size: 12px; margin-right: 4px;">{{ $topic->name }}</span>
                @endforeach
            </p>
            <a href="{{ route('news.show', $cluster) }}" style="color: #b45309; font-weight: 700;">Ler resumo consolidado →</a>
        </article>
    @endforeach

    <p style="border-top: 1px solid #e2e8f0; margin-top: 28px; padding-top: 18px; color: #64748b; font-size: 13px;">Você recebe este resumo porque o digest diário está habilitado nas suas preferências.</p>
</div>
