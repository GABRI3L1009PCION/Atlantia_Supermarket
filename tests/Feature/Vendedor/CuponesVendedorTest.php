<?php

namespace Tests\Feature\Vendedor;

use App\Models\Cupon;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuponesVendedorTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): Vendor
    {
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Comercial El Puerto']);
        $vendor->user->assignRole('vendedor');

        return $vendor;
    }

    public function test_seller_creates_pauses_edits_and_deletes_own_coupons(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->get(route('vendedor.cupones.index'))
            ->assertOk()->assertSee('Aún no tienes cupones');

        $this->actingAs($vendor->user)->post(route('vendedor.cupones.store'), [
            'codigo' => 'puerto 10', 'tipo' => 'porcentaje', 'valor' => 10, 'maximo_descuento' => 25,
            'un_uso_por_cliente' => '1', 'solo_primera_compra' => '0',
        ])->assertSessionHasNoErrors();

        $cupon = Cupon::query()->firstOrFail();
        $this->assertSame('PUERTO10', $cupon->codigo);
        $this->assertSame($vendor->id, (int) $cupon->vendor_id);
        $this->assertTrue($cupon->un_uso_por_cliente);

        $this->actingAs($vendor->user)->get(route('vendedor.cupones.index'))
            ->assertSee('PUERTO10')->assertSee('10 % de descuento')->assertSee('una vez por cliente');

        $this->actingAs($vendor->user)->patch(route('vendedor.cupones.toggle', $cupon))->assertSessionHasNoErrors();
        $this->assertFalse($cupon->refresh()->activo);

        $this->actingAs($vendor->user)->put(route('vendedor.cupones.update', $cupon), [
            'codigo' => 'PUERTO15', 'tipo' => 'porcentaje', 'valor' => 15,
        ])->assertSessionHasNoErrors();
        $this->assertSame('PUERTO15', $cupon->refresh()->codigo);

        $this->actingAs($vendor->user)->delete(route('vendedor.cupones.destroy', $cupon))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('cupones', ['id' => $cupon->id]);
    }

    public function test_seller_cannot_touch_another_stores_coupon_and_codes_are_validated(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = $this->vendedor();
        $otro = Vendor::factory()->approved()->create();
        $ajeno = Cupon::query()->create(['vendor_id' => $otro->id, 'codigo' => 'AJENO10', 'tipo' => 'porcentaje', 'valor' => 10, 'minimo_compra' => 0, 'activo' => true]);

        $this->actingAs($vendor->user)->patch(route('vendedor.cupones.toggle', $ajeno))->assertForbidden();
        $this->actingAs($vendor->user)->delete(route('vendedor.cupones.destroy', $ajeno))->assertForbidden();
        $this->actingAs($vendor->user)->get(route('vendedor.cupones.index'))->assertDontSee('AJENO10');

        $this->actingAs($vendor->user)->post(route('vendedor.cupones.store'), ['codigo' => 'AJENO10', 'tipo' => 'porcentaje', 'valor' => 10])
            ->assertSessionHasErrors(['codigo' => 'Ese código ya existe. Prueba con otro, por ejemplo con el nombre de tu tienda.']);
        $this->actingAs($vendor->user)->post(route('vendedor.cupones.store'), ['codigo' => 'MITAD', 'tipo' => 'porcentaje', 'valor' => 95])
            ->assertSessionHasErrors('valor');
    }

    public function test_admin_coupons_belong_to_atlantia_store(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $atlantia = Vendor::factory()->approved()->create(['slug' => config('atlantia.vendor_propio_slug', 'atlantia-supermarket'), 'business_name' => 'Atlantia Store']);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->post(route('admin.cupones.store'), [
            'codigo' => 'ATLANTIA5', 'tipo' => 'monto_fijo', 'valor' => 5, 'activo' => '1', 'solo_primera_compra' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertSame($atlantia->id, (int) Cupon::query()->where('codigo', 'ATLANTIA5')->value('vendor_id'));
        $this->actingAs($admin)->get(route('admin.cupones.index'))->assertOk()->assertSee('De vendedores');
    }
}
