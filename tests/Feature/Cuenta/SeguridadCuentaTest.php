<?php

namespace Tests\Feature\Cuenta;

use App\Models\User;
use App\Models\Vendor;
use App\Services\Auth\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeguridadCuentaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['atlantia.auth.verificar_contrasenas_filtradas' => false]);
        $this->seed(RolePermissionSeeder::class);
    }

    private function vendedor(): User
    {
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        return $vendor->user->fresh();
    }

    public function test_vendedor_cambia_su_contrasena_desde_el_panel(): void
    {
        $user = $this->vendedor();

        $this->actingAs($user)->get(route('cuenta.seguridad'))->assertOk()->assertSee('Seguridad de la cuenta');

        $this->actingAs($user)->from(route('cuenta.seguridad'))->put(route('cuenta.seguridad.contrasena'), [
            'actual' => 'Atlantia2026!',
            'nueva' => 'NuevaClave#2027',
            'nueva_confirmation' => 'NuevaClave#2027',
        ])->assertRedirect(route('cuenta.seguridad'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('NuevaClave#2027', $user->fresh()->password));
    }

    public function test_contrasena_actual_incorrecta_o_debil_no_se_acepta(): void
    {
        $user = $this->vendedor();

        $this->actingAs($user)->put(route('cuenta.seguridad.contrasena'), [
            'actual' => 'otra-cosa',
            'nueva' => 'NuevaClave#2027',
            'nueva_confirmation' => 'NuevaClave#2027',
        ])->assertSessionHasErrors('actual', null, 'contrasena');

        $this->actingAs($user)->put(route('cuenta.seguridad.contrasena'), [
            'actual' => 'Atlantia2026!',
            'nueva' => 'debil',
            'nueva_confirmation' => 'debil',
        ])->assertSessionHasErrors('nueva', null, 'contrasena');

        $this->assertTrue(Hash::check('Atlantia2026!', $user->fresh()->password));
    }

    public function test_regenera_codigos_de_respaldo_y_los_viejos_dejan_de_servir(): void
    {
        $user = $this->vendedor();
        $servicio = app(TwoFactorService::class);
        $viejos = $servicio->enable($user)['recovery_codes'];
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->actingAs($user)->post(route('cuenta.seguridad.codigos'), ['contrasena' => 'mala'])
            ->assertSessionHasErrors('contrasena', null, 'codigos');

        $respuesta = $this->actingAs($user)->from(route('cuenta.seguridad'))
            ->post(route('cuenta.seguridad.codigos'), ['contrasena' => 'Atlantia2026!'])
            ->assertSessionHas('codigos_respaldo_nuevos');

        $nuevos = $respuesta->getSession()->get('codigos_respaldo_nuevos');
        $this->assertCount(8, $nuevos);
        $this->assertEmpty(array_intersect($viejos, $nuevos));
        $this->assertSame(8, $servicio->codigosRestantes($user));

        $this->actingAs($user)->withSession(['codigos_respaldo_nuevos' => $nuevos])
            ->get(route('cuenta.seguridad'))->assertSee(substr($nuevos[0], 0, 5).'-'.substr($nuevos[0], 5));
    }

    public function test_sin_dos_pasos_no_se_pueden_regenerar_codigos(): void
    {
        $user = $this->vendedor();

        $this->actingAs($user)->post(route('cuenta.seguridad.codigos'), ['contrasena' => 'Atlantia2026!'])->assertStatus(422);
    }

    public function test_cerrar_sesion_en_otros_dispositivos_pide_la_contrasena(): void
    {
        $user = $this->vendedor();

        $this->actingAs($user)->post(route('cuenta.seguridad.cerrar-otras'), ['contrasena' => 'mala'])
            ->assertSessionHasErrors('contrasena', null, 'sesiones');

        $this->actingAs($user)->from(route('cuenta.seguridad'))->post(route('cuenta.seguridad.cerrar-otras'), ['contrasena' => 'Atlantia2026!'])
            ->assertRedirect(route('cuenta.seguridad'))->assertSessionHas('success');
    }

    public function test_cliente_tambien_tiene_su_pagina_de_seguridad(): void
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        $this->actingAs($cliente)->get(route('cuenta.seguridad'))->assertOk()->assertSee('Cambiar contraseña');
    }
}
