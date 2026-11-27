<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\NuevaSolicitudVendedorNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificacionSolicitudVendedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_vendor_application_notifies_admins_and_shows_in_panel(): void
    {
        config(['filesystems.private_disk' => 'local']);
        Storage::fake('local');
        Storage::fake('public');
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('vendedor', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');
        $cliente = User::factory()->create();

        $this->post(route('vendedor.solicitar.store'), [
            'name' => 'Vendedora Atlantia',
            'email' => 'vendedora@example.test',
            'phone' => '+502 1234 5678',
            'birthdate' => '1990-01-01',
            'address_street' => 'Avenida Principal',
            'address_number' => '12-34',
            'address_municipio' => 'Puerto Barrios', 'address_colonia' => 'Barrio El Centro',
            'address_departamento' => 'Izabal',
            'document_type' => 'dpi',
            'document_number' => '12345678-1234',
            'document_front' => UploadedFile::fake()->image('dpi-frente.jpg'),
            'document_back' => UploadedFile::fake()->image('dpi-reverso.jpg'),
            'business_name' => 'Comercial El Puerto',
            'business_description' => 'Abarrotes y productos de consumo diario.',
            'business_category' => 'alimentos_frescos',
            'business_logo' => UploadedFile::fake()->image('logo.jpg'),
            'has_nit' => false,
            'seller_plan' => 'inicial',
            'bank' => 'Banrural',
            'account_type' => 'ahorros',
            'account_number' => '123456789',
            'account_holder' => 'Vendedora Atlantia',
            'bank_proof' => UploadedFile::fake()->image('banco.jpg'),
            'payment_frequency' => 'quincenal',
            'preferred_payment_method' => 'transferencia',
            'terms' => '1',
            'truth' => '1',
            'data_consent' => '1',
        ])->assertRedirect();

        $this->assertSame(1, $admin->notifications()->where('type', NuevaSolicitudVendedorNotification::class)->count());
        $this->assertSame(0, $cliente->notifications()->count());

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-campana', false)
            ->assertSee('Nueva solicitud para vender')
            ->assertSee('Comercial El Puerto quiere vender en Atlantia', false);

        $this->actingAs($admin)->getJson(route('notificaciones.recientes'))
            ->assertOk()
            ->assertJsonPath('no_leidas', 1)
            ->assertJsonPath('items.0.titulo', 'Nueva solicitud para vender')
            ->assertJsonPath('items.0.leida', false);
    }
}
