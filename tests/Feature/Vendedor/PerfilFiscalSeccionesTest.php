<?php

namespace Tests\Feature\Vendedor;

use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PerfilFiscalSeccionesTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): Vendor
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');
        VendorFiscalProfile::query()->where('vendor_id', $vendor->id)->delete();

        return $vendor->refresh();
    }

    public function test_seller_without_nit_can_save_only_bank_account_and_then_request_payment_screen_knows_it(): void
    {
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->get(route('vendedor.perfil-fiscal.edit'))
            ->assertOk()
            ->assertSee('Cuenta para tus pagos')
            ->assertSee('data-seccion-form="banco"', false)
            ->assertDontSee('Atlantia Supermarket');

        $this->actingAs($vendor->user)->put(route('vendedor.perfil-fiscal.update'), [
            'seccion' => 'banco',
            'banco_nombre' => 'Banrural',
            'cuenta_bancaria_tipo' => 'ahorro',
            'cuenta_bancaria' => '3045-12345-6',
            'cuenta_bancaria_titular' => 'Ana López',
        ])->assertSessionHasNoErrors();

        $perfil = VendorFiscalProfile::query()->where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame('Banrural', $perfil->banco_nombre);
        $this->assertSame('3045123456', $perfil->cuenta_bancaria);
        $this->assertNull($perfil->nit);

        $this->actingAs($vendor->user->fresh())->get(route('vendedor.pagos.index'))
            ->assertOk()
            ->assertDontSee('Agrega tu cuenta bancaria para poder cobrar');
    }

    public function test_each_section_only_requires_its_own_fields(): void
    {
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->put(route('vendedor.perfil-fiscal.update'), ['seccion' => 'banco'])
            ->assertSessionHasErrors(['banco_nombre', 'cuenta_bancaria', 'cuenta_bancaria_tipo', 'cuenta_bancaria_titular'])
            ->assertSessionDoesntHaveErrors(['nit', 'direccion_fiscal']);

        $this->actingAs($vendor->user)->put(route('vendedor.perfil-fiscal.update'), ['seccion' => 'fiscal'])
            ->assertSessionHasErrors(['nit', 'razon_social', 'direccion_fiscal', 'codigo_establecimiento'])
            ->assertSessionDoesntHaveErrors(['banco_nombre']);
    }

    public function test_fiscal_section_saves_without_touching_bank_account(): void
    {
        $vendor = $this->vendedor();
        VendorFiscalProfile::query()->create(['vendor_id' => $vendor->id, 'certificador_fel' => 'infile', 'banco_nombre' => 'BAM', 'cuenta_bancaria' => '123456', 'cuenta_bancaria_tipo' => 'monetaria', 'cuenta_bancaria_titular' => 'Ana']);

        $this->actingAs($vendor->user)->put(route('vendedor.perfil-fiscal.update'), [
            'seccion' => 'fiscal',
            'nit' => '1234567-8',
            'razon_social' => 'Ana López',
            'direccion_fiscal' => '5a avenida 6-45, Puerto Barrios',
            'regimen_sat' => 'pequeno_contribuyente',
            'codigo_establecimiento' => '1',
            'afiliacion_iva' => 'peq',
        ])->assertSessionHasNoErrors();

        $perfil = VendorFiscalProfile::query()->where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame('1234567-8', $perfil->nit);
        $this->assertSame('PEQ', $perfil->afiliacion_iva);
        $this->assertSame('123456', $perfil->cuenta_bancaria);
    }
}
