<?php

namespace App\Services\Feed;

use Carbon\CarbonImmutable;
use RuntimeException;
use SimpleXMLElement;

class FeedParser
{
    private const ATOM_NAMESPACE = 'http://www.w3.org/2005/Atom';
    private const RSS_CONTENT_NAMESPACE = 'http://purl.org/rss/1.0/modules/content/';
    private const MEDIA_NAMESPACE = 'http://search.yahoo.com/mrss/';
    private const DC_NAMESPACE = 'http://purl.org/dc/elements/1.1/';

    /**
     * @return list<array<string, mixed>>
     */
    public function parse(string $xml, ?string $baseUrl = null): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_NOBLANKS);

            if ($document === false) {
                $errors = collect(libxml_get_errors())->map(fn ($error) => trim($error->message))->filter()->implode('; ');
                throw new RuntimeException('XML inválido: '.($errors ?: 'não foi possível interpretar o documento.'));
            }

            return match (strtolower($document->getName())) {
                'rss', 'rdf' => $this->parseRss($document, $baseUrl),
                'feed' => $this->parseAtom($document, $baseUrl),
                default => throw new RuntimeException('Formato de feed não suportado: '.$document->getName()),
            };
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** @return list<array<string, mixed>> */
    private function parseRss(SimpleXMLElement $document, ?string $baseUrl): array
    {
        $items = $document->channel?->item ?? $document->item;
        $articles = [];

        foreach ($items as $item) {
            $links = $this->rssLinks($item);
            $url = $this->resolveUrl($links['url'], $baseUrl);

            $articles[] = $this->normalizedItem(
                guid: $this->firstValue([(string) $item->guid, $this->namespacedValue($item, self::DC_NAMESPACE, 'identifier')]),
                url: $url,
                canonicalUrl: $this->resolveUrl($links['canonical_url'], $baseUrl),
                title: (string) $item->title,
                description: $this->firstValue([(string) $item->description, $this->namespacedValue($item, self::RSS_CONTENT_NAMESPACE, 'encoded')]),
                author: $this->firstValue([(string) $item->author, $this->namespacedValue($item, self::DC_NAMESPACE, 'creator')]),
                imageUrl: $this->rssImageUrl($item, $baseUrl),
                publishedAt: $this->firstValue([(string) $item->pubDate, $this->namespacedValue($item, self::DC_NAMESPACE, 'date')]),
            );
        }

        return $articles;
    }

    /** @return list<array<string, mixed>> */
    private function parseAtom(SimpleXMLElement $document, ?string $baseUrl): array
    {
        $articles = [];
        $atom = $document->children(self::ATOM_NAMESPACE);

        foreach ($atom->entry as $entry) {
            $links = $this->atomLinks($entry);
            $url = $this->resolveUrl($links['url'], $baseUrl);
            $atomEntry = $entry->children(self::ATOM_NAMESPACE);
            $author = (string) ($atomEntry->author->name ?? '');

            $articles[] = $this->normalizedItem(
                guid: (string) $atomEntry->id,
                url: $url,
                canonicalUrl: $this->resolveUrl($links['canonical_url'], $baseUrl),
                title: (string) $atomEntry->title,
                description: $this->firstValue([(string) $atomEntry->summary, (string) $atomEntry->content]),
                author: $author,
                imageUrl: $this->atomImageUrl($entry, $baseUrl),
                publishedAt: $this->firstValue([(string) $atomEntry->published, (string) $atomEntry->updated]),
            );
        }

        return $articles;
    }

    /** @return array{url: ?string, canonical_url: ?string} */
    private function rssLinks(SimpleXMLElement $item): array
    {
        $canonical = '';
        foreach ($item->children('http://www.w3.org/2005/Atom')->link ?? [] as $link) {
            if ($this->attribute($link, 'rel') === 'canonical') {
                $canonical = $this->attribute($link, 'href');
            }
        }

        return ['url' => (string) $item->link, 'canonical_url' => $canonical ?: (string) $item->canonical_url];
    }

    /** @return array{url: ?string, canonical_url: ?string} */
    private function atomLinks(SimpleXMLElement $entry): array
    {
        $url = $canonical = '';

        foreach ($entry->children(self::ATOM_NAMESPACE)->link as $link) {
            $rel = $this->attribute($link, 'rel');
            $href = $this->attribute($link, 'href');

            if ($rel === 'canonical') {
                $canonical = $href;
            } elseif ($rel === 'alternate' || $rel === '') {
                $url = $url ?: $href;
            }
        }

        return ['url' => $url, 'canonical_url' => $canonical];
    }

    private function rssImageUrl(SimpleXMLElement $item, ?string $baseUrl): ?string
    {
        $media = $item->children(self::MEDIA_NAMESPACE);
        $url = $this->attribute($media->content, 'url') ?: $this->attribute($media->thumbnail, 'url');

        if ($url === '') {
            $enclosure = $item->enclosure;
            $type = $this->attribute($enclosure, 'type');
            $url = str_starts_with($type, 'image/') ? $this->attribute($enclosure, 'url') : '';
        }

        return $this->resolveUrl($url, $baseUrl);
    }

    private function atomImageUrl(SimpleXMLElement $entry, ?string $baseUrl): ?string
    {
        $media = $entry->children(self::MEDIA_NAMESPACE);
        $url = $this->attribute($media->content, 'url') ?: $this->attribute($media->thumbnail, 'url');

        return $this->resolveUrl($url, $baseUrl);
    }

    private function attribute(SimpleXMLElement $element, string $name): string
    {
        return trim((string) ($element->attributes()[$name] ?? ''));
    }

    /** @param list<string> $values */
    private function firstValue(array $values): ?string
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function namespacedValue(SimpleXMLElement $element, string $namespace, string $name): ?string
    {
        $value = (string) ($element->children($namespace)->{$name} ?? '');

        return $value !== '' ? trim($value) : null;
    }

    /** @return array<string, mixed> */
    private function normalizedItem(?string $guid, ?string $url, ?string $canonicalUrl, string $title, ?string $description, ?string $author, ?string $imageUrl, ?string $publishedAt): array
    {
        if ($url === null && is_string($guid) && filter_var($guid, FILTER_VALIDATE_URL)) {
            $url = $guid;
        }

        $title = trim(strip_tags($title));
        $description = $description !== null ? trim($description) : null;

        return [
            'guid' => $this->firstValue([$guid ?? '']),
            'url' => $url,
            'canonical_url' => $canonicalUrl,
            'original_title' => $title !== '' ? $title : 'Sem título',
            'description' => $description !== '' ? $description : null,
            'author' => $this->firstValue([$author ?? '']),
            'image_url' => $imageUrl,
            'published_at' => $this->parseDate($publishedAt),
        ];
    }

    private function parseDate(?string $date): ?CarbonImmutable
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($date)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveUrl(?string $url, ?string $baseUrl): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (! preg_match('/^https?:\/\//i', $url)) {
            if ($baseUrl === null || ! preg_match('/^https?:\/\//i', $baseUrl)) {
                return null;
            }

            $base = parse_url($baseUrl);
            if ($base === false || empty($base['scheme']) || empty($base['host'])) {
                return null;
            }

            if (str_starts_with($url, '//')) {
                $url = $base['scheme'].':'.$url;
            } elseif (str_starts_with($url, '/')) {
                $port = isset($base['port']) ? ':'.$base['port'] : '';
                $url = $base['scheme'].'://'.$base['host'].$port.$url;
            } else {
                $basePath = isset($base['path']) ? dirname($base['path']).'/' : '/';
                $port = isset($base['port']) ? ':'.$base['port'] : '';
                $url = $base['scheme'].'://'.$base['host'].$port.$basePath.$url;
            }
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $normalized = strtolower($parts['scheme']).'://'.strtolower($parts['host']);
        if (isset($parts['port'])) {
            $normalized .= ':'.$parts['port'];
        }
        $normalized .= $parts['path'] ?? '';
        if (isset($parts['query'])) {
            $normalized .= '?'.$parts['query'];
        }

        return $normalized;
    }
}
