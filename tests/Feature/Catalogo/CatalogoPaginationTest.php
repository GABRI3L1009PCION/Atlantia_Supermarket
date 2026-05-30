<?php

namespace Tests\Feature\Catalogo;

use App\Models\Producto;
use App\Services\Busqueda\MeilisearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogoPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function testCatalogoUsesFortyEightProductsPerPage(): void
    {
        Producto::factory()
            ->count(50)
            ->publicado()
            ->sequence(fn ($sequence): array => [
                'publicado_at' => now()->subMinutes($sequence->index),
            ])
            ->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Pagina 1 de 2', false);
        $this->assertSame(48, substr_count($response->getContent(), 'Agregar al carrito'));
    }

    public function testCatalogoSearchServiceHonorsRequestedPage(): void
    {
        Producto::factory()
            ->count(50)
            ->publicado()
            ->sequence(fn ($sequence): array => [
                'publicado_at' => now()->subMinutes($sequence->index),
            ])
            ->create();

        $results = app(MeilisearchService::class)->search([
            'per_page' => 48,
            'page' => 2,
        ]);

        $this->assertSame(2, $results['pagination']['current_page']);
        $this->assertSame(48, $results['pagination']['per_page']);
        $this->assertSame(2, $results['items']->count());
    }
}
