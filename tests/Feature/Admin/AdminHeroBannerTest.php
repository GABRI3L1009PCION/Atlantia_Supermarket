<?php

namespace Tests\Feature\Admin;

use App\Models\HeroBanner;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminHeroBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);
    }

    public function testAdminCanCreateHeroBannerWithDesktopAndMobileImages(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.hero-banners.store'), [
            'nombre' => 'Banner frescos inicio',
            'orden' => 1,
            'is_active' => true,
            'inicia_en' => now()->subHour()->format('Y-m-d H:i:s'),
            'termina_en' => now()->addDay()->format('Y-m-d H:i:s'),
            'desktop_image' => UploadedFile::fake()->image('hero-desktop.jpg', 1600, 600),
            'mobile_image' => UploadedFile::fake()->image('hero-mobile.jpg', 900, 1200),
        ]);

        $response->assertRedirect();

        $banner = HeroBanner::query()->firstOrFail();

        $this->assertSame('Banner frescos inicio', $banner->nombre);
        $this->assertTrue($banner->hasMedia('hero_desktop'));
        $this->assertTrue($banner->hasMedia('hero_mobile'));
    }
}
