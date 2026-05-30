<?php

namespace Tests\Feature\Catalogo;

use App\Models\Categoria;
use App\Models\HeroBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroBannerRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    public function testHomeUsesActiveCurrentHeroBannerWhenAvailable(): void
    {
        $inactive = HeroBanner::factory()->create([
            'nombre' => 'Banner inactivo',
            'is_active' => false,
            'orden' => 0,
        ]);
        $inactive->addMedia(UploadedFile::fake()->image('inactive.jpg', 1600, 600))->toMediaCollection('hero_desktop');

        $active = HeroBanner::factory()->create([
            'nombre' => 'Banner vigente principal',
            'is_active' => true,
            'orden' => 1,
            'inicia_en' => now()->subHour(),
            'termina_en' => now()->addDay(),
        ]);
        $active->addMedia(UploadedFile::fake()->image('active.jpg', 1600, 600))->toMediaCollection('hero_desktop');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($active->getFirstMediaUrl('hero_desktop'), false);
        $response->assertSee('class="hidden h-full w-full object-cover object-center md:block"', false);
        $response->assertSee('alt="Banner promocional Banner vigente principal"', false);
    }

    public function testHomeFallsBackWhenNoActiveBannerExists(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('https://images.unsplash.com/photo-1604719312566-8912e9227c6a', false);
        $response->assertSee('alt="Banner promocional Fallback Atlantia"', false);
    }

    public function testHomeRendersCategoryImagesInCarousel(): void
    {
        Storage::disk('public')->put('categorias/frutas.png', 'category-image');

        Categoria::query()->create([
            'nombre' => 'Frutas y Verduras',
            'slug' => 'frutas-y-verduras',
            'imagen' => 'categorias/frutas.png',
            'orden' => 0,
            'is_active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('/storage/categorias/frutas.png', false);
        $response->assertSee('alt="Frutas y Verduras"', false);
    }
}
