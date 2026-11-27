<?php

namespace Tests\Feature\Admin;

use App\Models\Cupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CuponesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->admin()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    public function test_coupons_page_uses_inline_form(): void
    {
        $this->actingAs($this->admin())->get(route('admin.cupones.index'))
            ->assertOk()
            ->assertSee('Navegacion super admin', false)
            ->assertSee('Cupones y descuentos')
            ->assertSee('data-cupon-form', false)
            ->assertSee('Crear cupón de bienvenida')
            ->assertDontSee('data-open-create-modal', false);
    }

    public function test_end_date_lasts_the_whole_day(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.cupones.store'), [
            'codigo' => 'PRUEBA10',
            'tipo' => 'porcentaje',
            'valor' => 10,
            'fecha_fin' => '2030-05-10',
            'activo' => 1,
            'solo_primera_compra' => 0,
        ])->assertRedirect();

        $cupon = Cupon::query()->where('codigo', 'PRUEBA10')->firstOrFail();
        $this->assertSame('2030-05-10 23:59:59', $cupon->fecha_fin->format('Y-m-d H:i:s'));

        $this->actingAs($admin)->get(route('admin.cupones.index'))
            ->assertOk()
            ->assertSee('PRUEBA10')
            ->assertSee('10 %')
            ->assertSee('Vigente');
    }
}
