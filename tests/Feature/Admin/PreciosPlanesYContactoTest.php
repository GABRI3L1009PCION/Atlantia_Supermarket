<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\NuevoMensajeContactoNotification;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Support\AjustesMonetizacion;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PreciosPlanesYContactoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(AjustesMonetizacion::CACHE);
        AjustesMonetizacion::aplicar();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function datosPlan(array $cambios = []): array
    {
        $plan = PlanesVendedorService::datos('esencial');

        return array_replace([
            'precio' => $plan['precio'],
            'comision' => $plan['comision'],
            'max_productos' => $plan['max_productos'],
            'max_promociones' => $plan['max_promociones'],
            'carga_masiva' => '0',
            'destacados_incluidos' => 0,
            'banners_incluidos' => 0,
            'resumen' => $plan['resumen'],
            'beneficios' => implode("\n", $plan['beneficios']),
        ], $cambios);
    }

    public function test_admin_cambia_precio_y_limites_y_se_aplica_en_todo_el_sistema(): void
    {
        $this->actingAs($this->admin)->get(route('admin.precios-planes.index'))->assertOk()->assertSee('Esencial')->assertSee('Precios de publicidad');

        $this->actingAs($this->admin)->put(route('admin.precios-planes.plan', 'esencial'), $this->datosPlan([
            'precio' => '229.50', 'max_productos' => 90, 'max_promociones' => 4,
        ]))->assertRedirect();

        $plan = PlanesVendedorService::datos('esencial');
        $this->assertSame(229.5, (float) $plan['precio']);
        $this->assertSame(90, $plan['max_productos']);
        $this->assertContains('Hasta 90 productos', $plan['beneficios']);
        $this->assertContains('4 promociones activas', $plan['beneficios']);
        $this->assertSame(1, AuditLog::query()->where('event', 'precios.plan_actualizado')->count());

        /* La página pública y el límite real de productos usan el valor nuevo. */
        $this->get(route('vende'))->assertOk()->assertSee('229.50')->assertSee('Hasta 90 productos');
        $vendor = Vendor::factory()->approved()->create(['plan' => 'esencial', 'plan_hasta' => now()->addMonth()]);
        $this->assertSame(90, app(PlanesVendedorService::class)->limiteProductos($vendor));

        /* Sobrevive a un nuevo arranque (se lee de la base de datos). */
        Cache::forget(AjustesMonetizacion::CACHE);
        config(['monetizacion.planes.esencial.precio' => 199]);
        AjustesMonetizacion::aplicar();
        $this->assertSame(229.5, (float) PlanesVendedorService::datos('esencial')['precio']);
    }

    public function test_restaurar_vuelve_a_los_valores_originales(): void
    {
        $original = PlanesVendedorService::datos('esencial')['precio'];
        $this->actingAs($this->admin)->put(route('admin.precios-planes.plan', 'esencial'), $this->datosPlan(['precio' => 300]));
        $this->assertSame(300.0, (float) PlanesVendedorService::datos('esencial')['precio']);

        $this->actingAs($this->admin)->post(route('admin.precios-planes.restaurar', 'esencial'))->assertRedirect();
        $this->assertEquals($original, PlanesVendedorService::datos('esencial')['precio']);
    }

    public function test_el_plan_gratis_no_puede_cobrar_mensualidad(): void
    {
        $inicial = PlanesVendedorService::base();
        $this->actingAs($this->admin)->put(route('admin.precios-planes.plan', $inicial), $this->datosPlan(['precio' => 50, 'comision' => 8]))
            ->assertSessionHasErrors('precio', null, 'plan_'.$inicial);

        $this->actingAs($this->admin)->put(route('admin.precios-planes.plan', $inicial), array_diff_key($this->datosPlan(['comision' => 8, 'beneficios' => "Q 0 al mes + 7 % por venta\nHasta 15 productos"]), ['precio' => 1]))
            ->assertSessionHasNoErrors();
        $plan = PlanesVendedorService::datos($inicial);
        $this->assertEquals(0, $plan['precio']);
        $this->assertEquals(8, $plan['comision']);
        $this->assertContains('Q 0 al mes + 8 % por venta', $plan['beneficios']);
    }

    public function test_precios_de_publicidad(): void
    {
        $this->actingAs($this->admin)->put(route('admin.precios-planes.publicidad'), [
            'destacado_semana' => 60, 'patrocinado_semana' => 40,
            'banners' => [1 => ['semana' => 300, 'mes' => 900], 2 => ['semana' => 200, 'mes' => 600], 3 => ['semana' => 150, 'mes' => 450]],
        ])->assertRedirect();

        $this->assertEquals(60, config('monetizacion.destacado.semana'));
        $this->assertEquals(40, config('monetizacion.patrocinado.semana'));
        $this->assertEquals(900, config('monetizacion.banners.posiciones.1.mes'));
    }

    public function test_un_vendedor_no_puede_cambiar_precios(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $vendor->user->assignRole('vendedor');

        $this->actingAs($vendor->user)->get(route('admin.precios-planes.index'))->assertForbidden();
        $this->actingAs($vendor->user)->put(route('admin.precios-planes.plan', 'esencial'), $this->datosPlan(['precio' => 1]))->assertForbidden();
    }

    public function test_formulario_de_contacto_llega_a_la_bandeja(): void
    {
        $this->get(route('contacto'))->assertOk()->assertSee('Enviar mensaje');

        $this->post(route('contacto.store'), [
            'nombre' => 'María López', 'email' => 'Maria@Correo.com', 'tema' => 'pedido',
            'mensaje' => 'Mi pedido 1045 no ha llegado y ya pasó la hora.',
        ])->assertRedirect(route('contacto').'#escribenos')->assertSessionHas('success');

        $mensaje = ContactMessage::query()->firstOrFail();
        $this->assertSame('contacto', $mensaje->tipo);
        $this->assertSame('maria@correo.com', $mensaje->email);
        $this->assertSame('alta', $mensaje->prioridad);
        $this->assertSame(1, $this->admin->notifications()->where('type', NuevoMensajeContactoNotification::class)->count());

        $this->actingAs($this->admin)->get(route('admin.mensajes.index', ['tipo' => 'contacto']))
            ->assertOk()->assertSee('data-mensaje="'.$mensaje->uuid.'"', false)->assertSee('Un pedido')->assertSee('respóndele por correo');

        $this->actingAs($this->admin)->post(route('admin.mensajes.atender', $mensaje))->assertSessionHas('success');
        $this->assertTrue($mensaje->fresh()->atendido);
    }

    public function test_contacto_valida_y_descarta_robots(): void
    {
        $this->post(route('contacto.store'), ['nombre' => '', 'email' => 'no-es-correo', 'tema' => 'x', 'mensaje' => 'hola'])
            ->assertSessionHasErrors(['nombre', 'email', 'tema', 'mensaje']);

        $this->post(route('contacto.store'), [
            'nombre' => 'Robot', 'email' => 'bot@spam.com', 'tema' => 'otro', 'mensaje' => 'Compre seguidores baratos aquí.', 'sitio_web' => 'http://spam.test',
        ])->assertSessionHas('success');

        $this->assertSame(0, ContactMessage::query()->count());
    }
}
