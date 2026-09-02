<?php

namespace Tests\Feature;

use App\Models\Source;
use Database\Seeders\SourceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_catalog_csv_is_imported_idempotently(): void
    {
        $this->seed(SourceCatalogSeeder::class);
        $this->seed(SourceCatalogSeeder::class);

        $this->assertDatabaseCount('sources', 31);
        $this->assertDatabaseCount('feeds', 39);
        $this->assertDatabaseHas('sources', [
            'domain' => 'folha.uol.com.br',
            'priority' => 9,
            'active' => true,
        ]);
        $this->assertDatabaseHas('feeds', [
            'url' => 'https://rss.dw.com/rdf/rss-br-top',
            'name' => 'Brasil',
            'active' => true,
        ]);
        $this->assertSame(31, Source::query()->count());
    }
}
