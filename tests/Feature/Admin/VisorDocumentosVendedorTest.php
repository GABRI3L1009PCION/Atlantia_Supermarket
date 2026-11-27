<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisorDocumentosVendedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_can_be_viewed_inline_and_framed_only_by_the_site(): void
    {
        config(['filesystems.private_disk' => 'local']);
        Storage::fake('local');
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        $ruta = UploadedFile::fake()->image('dpi.jpg')->store('vendor-applications/prueba', 'local');
        $vendor = Vendor::factory()->create(['documents' => ['document_front' => $ruta]]);

        $ver = $this->actingAs($admin)->get(route('admin.vendedores.documents.show', [$vendor, 'document_front', 'ver' => 1]));
        $ver->assertOk();
        $this->assertStringStartsWith('inline', (string) $ver->headers->get('Content-Disposition'));
        $this->assertSame('SAMEORIGIN', $ver->headers->get('X-Frame-Options'));
        $this->assertStringContainsString("frame-ancestors 'self'", (string) $ver->headers->get('Content-Security-Policy'));

        $descarga = $this->actingAs($admin)->get(route('admin.vendedores.documents.show', [$vendor, 'document_front']));
        $descarga->assertOk();
        $this->assertStringStartsWith('attachment', (string) $descarga->headers->get('Content-Disposition'));
        $this->assertSame('DENY', $descarga->headers->get('X-Frame-Options'));

        $this->actingAs($admin)->get(route('admin.vendedores.index'))
            ->assertOk()
            ->assertSee('data-visor', false)
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
