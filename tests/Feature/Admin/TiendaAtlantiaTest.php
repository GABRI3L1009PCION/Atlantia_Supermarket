<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\AccesoTiendaAtlantiaNotification;
use App\Support\TiendaAtlantia;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TiendaAtlantiaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Vendor $atlantia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Gabriela Admin']);
        $this->admin->assignRole('admin');

        /* Como hoy en producción: la tienda propia está a nombre de un administrador. */
        $this->atlantia = Vendor::factory()->approved()->create([
            'user_id' => $this->admin->id,
            'slug' => TiendaAtlantia::slug(),
            'business_name' => 'Atlantia Supermarket',
        ]);
    }

    public function test_la_pagina_crea_la_cuenta_de_la_tienda_y_se_la_pasa(): void
    {
        $this->actingAs($this->admin)->get(route('admin.tienda-atlantia.index'))
            ->assertOk()->assertSee('Entrar al panel de la tienda')->assertSee('Nadie ha entrado todavía');

        $operador = $this->atlantia->fresh()->user;
        $this->assertNotSame($this->admin->id, $operador->id);
        $this->assertTrue($operador->hasRole('vendedor'));
        $this->assertSame(TiendaAtlantia::CORREO_OPERADOR, $operador->email);
        $this->assertNull($this->admin->fresh()->vendor);

        /* Llamarlo de nuevo no crea otra cuenta. */
        $this->assertSame($operador->id, TiendaAtlantia::operador($this->atlantia->fresh())->id);
    }

    public function test_admin_entra_al_panel_de_la_tienda_y_vuelve(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tienda-atlantia.entrar'))
            ->assertRedirect(route('vendedor.dashboard'));

        $this->assertSame($this->atlantia->fresh()->user_id, auth()->id());
        $this->assertSame(1, AuditLog::query()->where('event', 'tienda_atlantia.entrada')->where('user_id', $this->admin->id)->count());

        $panel = $this->get(route('vendedor.dashboard'))->assertOk();
        $panel->assertSee('Estás manejando Atlantia Store')->assertSee('Volver al administrador');
        /* La tienda propia no paga plan ni comisión. */
        $panel->assertDontSee(route('vendedor.plan.index'), false)->assertDontSee(route('vendedor.comisiones.index'), false);

        $this->get(route('vendedor.operacion.edit'))->assertOk();
        $this->get(route('vendedor.resenas.index'))->assertOk();

        $this->delete(route('admin.impersonation.stop'))->assertRedirect(route('admin.tienda-atlantia.index'));
        $this->assertSame($this->admin->id, auth()->id());
        $this->get(route('admin.tienda-atlantia.index'))->assertOk()->assertSee('Gabriela Admin');
    }

    public function test_el_horario_que_pone_la_tienda_es_el_de_atlantia(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tienda-atlantia.entrar'));

        $this->assertSame($this->atlantia->id, auth()->user()->vendor->id);
        $this->assertSame($this->atlantia->id, TiendaAtlantia::vendor()->id);
        $this->assertSame(auth()->user()->vendor->id, Vendor::query()->where('slug', TiendaAtlantia::slug())->value('id'));
    }

    public function test_dar_acceso_a_un_empleado_envia_el_enlace(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post(route('admin.tienda-atlantia.acceso'), ['email' => 'Bodega@Correo.com'])
            ->assertSessionHas('success');

        $operador = $this->atlantia->fresh()->user;
        $this->assertSame('bodega@correo.com', $operador->email);
        $this->assertFalse((bool) $operador->is_system_user);
        Notification::assertSentTo($operador, AccesoTiendaAtlantiaNotification::class);

        $this->actingAs($this->admin)->get(route('admin.tienda-atlantia.index'))->assertSee('Hoy entra directamente');
    }

    public function test_no_acepta_un_correo_que_ya_tiene_cuenta(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ocupado@correo.com']);

        $this->actingAs($this->admin)->post(route('admin.tienda-atlantia.acceso'), ['email' => 'ocupado@correo.com'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_un_vendedor_cualquiera_no_puede_entrar(): void
    {
        $otro = Vendor::factory()->approved()->create();
        $otro->user->assignRole('vendedor');

        $this->actingAs($otro->user)->get(route('admin.tienda-atlantia.index'))->assertForbidden();
        $this->actingAs($otro->user)->post(route('admin.tienda-atlantia.entrar'))->assertForbidden();
    }
}
