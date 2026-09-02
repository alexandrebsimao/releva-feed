<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SourceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $rows = $this->rows();
        $sourcePriorities = $this->sourcePriorities($rows);

        DB::transaction(function () use ($rows, $sourcePriorities): void {
            foreach ($rows as $row) {
                $source = Source::query()->updateOrCreate(
                    ['domain' => $row['domain']],
                    [
                        'name' => $row['source_name'],
                        'slug' => $this->sourceSlug($row['source_name'], $row['domain']),
                        'priority' => $sourcePriorities[$row['domain']],
                        'active' => $row['active'],
                    ],
                );

                $source->feeds()->updateOrCreate(
                    ['url' => $row['url']],
                    [
                        'name' => $row['feed_name'],
                        'category' => $row['category'],
                        'active' => $row['active'],
                    ],
                );
            }
        });
    }

    private function sourceSlug(string $name, string $domain): string
    {
        $base = Str::slug($name) ?: Str::slug($domain);
        $slug = $base;
        $suffix = 2;

        while (Source::query()->where('slug', $slug)->where('domain', '!=', $domain)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /** @return list<array{source_name: string, domain: string, feed_name: string, category: string, url: string, priority: int, active: bool}> */
    private function rows(): array
    {
        $path = database_path('data/source-catalog.csv');
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Não foi possível abrir o catálogo de fontes: {$path}");
        }

        try {
            $header = fgetcsv($handle);
            $header = is_array($header) ? array_map(static fn ($value): string => trim((string) $value), $header) : [];
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '') ?? '';
            $expectedHeader = ['source_name', 'domain', 'feed_name', 'category', 'url', 'priority', 'active'];

            if ($header !== $expectedHeader) {
                throw new RuntimeException('Cabeçalho inválido no catálogo de fontes.');
            }

            $rows = [];
            $line = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $line++;

                if ($values === [null] || count(array_filter($values, static fn ($value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                if (count($values) !== count($expectedHeader)) {
                    throw new RuntimeException("Linha {$line} inválida no catálogo de fontes.");
                }

                $row = array_combine($expectedHeader, array_map(static fn ($value): string => trim((string) $value), $values));

                if ($row === false) {
                    throw new RuntimeException("Dados inválidos na linha {$line} do catálogo de fontes.");
                }

                $priority = filter_var($row['priority'], FILTER_VALIDATE_INT);
                $active = filter_var($row['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($priority === false || $priority === null || $priority < 0 || $active === null || collect(array_slice($row, 0, 5))->contains(static fn ($value): bool => $value === '')) {
                    throw new RuntimeException("Dados inválidos na linha {$line} do catálogo de fontes.");
                }

                $rows[] = [
                    'source_name' => $row['source_name'],
                    'domain' => $row['domain'],
                    'feed_name' => $row['feed_name'],
                    'category' => $row['category'],
                    'url' => $row['url'],
                    'priority' => $priority,
                    'active' => $active,
                ];
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /** @param list<array{source_name: string, domain: string, feed_name: string, category: string, url: string, priority: int, active: bool}> $rows */
    private function sourcePriorities(array $rows): array
    {
        $priorities = [];

        foreach ($rows as $row) {
            $priorities[$row['domain']] = max($priorities[$row['domain']] ?? 0, $row['priority']);
        }

        return $priorities;
    }
}
