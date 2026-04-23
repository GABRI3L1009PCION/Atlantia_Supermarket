<?php

namespace Tests\Feature\Catalogo;

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
        $response->assertSee('data-hero-banner="Banner vigente principal"', false);
        $response->assertSee('data-hero-banner-fallback="0"', false);
        $response->assertSee($active->getFirstMediaUrl('hero_desktop'), false);
    }

    public function testHomeFallsBackWhenNoActiveBannerExists(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-hero-banner="Fallback Atlantia"', false);
        $response->assertSee('data-hero-banner-fallback="1"', false);
    }
}
