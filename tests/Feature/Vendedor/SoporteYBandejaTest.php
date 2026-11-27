<?php

namespace Tests\Feature\Vendedor;

use App\Models\ContactMessage;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MensajeRespondidoNotification;
use App\Notifications\SoporteVendedorNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SoporteYBandejaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function vendedor(): User
    {
        $vendor = Vendor::factory()->approved()->create(['business_name' => 'Tienda Luna']);
        $vendor->user->assignRole('vendedor');

        return $vendor->user->fresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_vendedor_pide_ayuda_y_el_admin_lo_ve_en_su_bandeja(): void
    {
        $vendedor = $this->vendedor();
        $admin = $this->admin();

        $this->actingAs($vendedor)->get(route('vendedor.soporte.index'))->assertOk()->assertSee('Aún no nos has escrito');

        $this->actingAs($vendedor)->post(route('vendedor.soporte.store'), [
            'categoria' => 'pagos',
            'asunto' => 'No me llegó el pago',
            'mensaje' => 'El pago del lunes no aparece en mi cuenta del banco.',
            'urgente' => '1',
        ])->assertRedirect(route('vendedor.soporte.index'));

        $mensaje = ContactMessage::query()->firstOrFail();
        $this->assertSame('soporte', $mensaje->tipo);
        $this->assertSame('pagos', $mensaje->categoria);
        $this->assertSame('alta', $mensaje->prioridad);
        $this->assertSame(1, $admin->notifications()->where('type', SoporteVendedorNotification::class)->count());

        $this->actingAs($vendedor)->get(route('vendedor.soporte.index'))->assertSee('No me llegó el pago')->assertSee('Esperando respuesta');

        $this->actingAs($admin)->get(route('admin.mensajes.index'))
            ->assertOk()->assertSee('No me llegó el pago')->assertSee('Tienda Luna')->assertSee('Urgente');
        $this->actingAs($admin)->get(route('admin.mensajes.index', ['tipo' => 'cotizacion']))->assertOk()->assertDontSee('data-mensaje="'.$mensaje->uuid.'"', false);
    }

    public function test_admin_atiende_con_respuesta_y_el_vendedor_la_ve(): void
    {
        $vendedor = $this->vendedor();
        $admin = $this->admin();
        $this->actingAs($vendedor)->post(route('vendedor.soporte.store'), [
            'categoria' => 'repartidores',
            'asunto' => 'Repartidor no llegó',
            'mensaje' => 'El repartidor nunca pasó por el pedido 1234.',
        ]);
        $mensaje = ContactMessage::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.mensajes.atender', $mensaje), ['respuesta' => 'Ya asignamos otro repartidor.'])
            ->assertSessionHas('success');

        $mensaje->refresh();
        $this->assertTrue($mensaje->atendido);
        $this->assertSame($admin->id, $mensaje->atendido_por);
        $this->assertSame('Ya asignamos otro repartidor.', $mensaje->respuesta);
        $this->assertSame(1, $vendedor->notifications()->where('type', MensajeRespondidoNotification::class)->count());

        $this->actingAs($vendedor)->get(route('vendedor.soporte.index'))->assertSee('Ya asignamos otro repartidor.')->assertSee('Respondido');
        $this->actingAs($admin)->get(route('admin.mensajes.index'))->assertDontSee('data-mensaje="'.$mensaje->uuid.'"', false);
        $this->actingAs($admin)->get(route('admin.mensajes.index', ['estado' => 'atendidos']))->assertSee('data-mensaje="'.$mensaje->uuid.'"', false)->assertSee('Ya asignamos otro repartidor.');
    }

    public function test_marcar_atendido_sin_respuesta_no_notifica(): void
    {
        $vendedor = $this->vendedor();
        $admin = $this->admin();
        $mensaje = ContactMessage::query()->create([
            'uuid' => (string) Str::uuid(), 'user_id' => $vendedor->id, 'tipo' => 'cotizacion',
            'nombre' => 'Tienda Luna', 'email' => $vendedor->email, 'asunto' => 'Cotización plan Personalizado · Tienda Luna',
            'mensaje' => 'Productos aproximados: 900', 'prioridad' => 'alta', 'atendido' => false,
        ]);

        $this->actingAs($admin)->get(route('admin.mensajes.index', ['tipo' => 'cotizacion']))->assertSee('Cotización plan Personalizado');
        $this->actingAs($admin)->post(route('admin.mensajes.atender', $mensaje))->assertSessionHas('success');

        $this->assertTrue($mensaje->fresh()->atendido);
        $this->assertSame(0, $vendedor->notifications()->count());
    }

    public function test_validaciones_y_limite_de_mensajes_abiertos(): void
    {
        $vendedor = $this->vendedor();

        $this->actingAs($vendedor)->post(route('vendedor.soporte.store'), ['categoria' => 'nada', 'asunto' => '', 'mensaje' => 'corto'])
            ->assertSessionHasErrors(['categoria', 'asunto', 'mensaje']);

        for ($i = 0; $i < 5; $i++) {
            ContactMessage::query()->create([
                'uuid' => (string) Str::uuid(), 'user_id' => $vendedor->id, 'tipo' => 'soporte', 'categoria' => 'otro',
                'nombre' => 'Tienda Luna', 'email' => $vendedor->email, 'asunto' => 'Asunto '.$i, 'mensaje' => 'Mensaje de prueba', 'atendido' => false,
            ]);
        }

        $this->actingAs($vendedor)->post(route('vendedor.soporte.store'), [
            'categoria' => 'otro', 'asunto' => 'Otro más', 'mensaje' => 'Un sexto mensaje sin respuesta.',
        ])->assertSessionHas('error');
        $this->assertSame(5, ContactMessage::query()->count());
    }

    public function test_vendedor_no_entra_a_la_bandeja_del_admin(): void
    {
        $this->actingAs($this->vendedor())->get(route('admin.mensajes.index'))->assertForbidden();
    }
}
