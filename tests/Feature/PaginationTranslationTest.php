<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_pagination_is_translated_to_portuguese(): void
    {
        $user = User::factory()->create();
        Article::factory()->count(21)->create();

        $this->actingAs($user)
            ->get(route('articles.index'))
            ->assertOk()
            ->assertSee('Mostrando')
            ->assertSee('resultados')
            ->assertSee('Próxima', false);
    }
}
