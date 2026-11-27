<?php

namespace Tests\Feature\Notificaciones;

use App\Jobs\EnviarWhatsApp;
use App\Models\Pedido;
use App\Notifications\PedidoEnCaminoNotification;
use App\Services\Notificaciones\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class WhatsAppAvisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_guatemalan_phone_numbers(): void
    {
        $this->assertSame('50255551234', WhatsAppService::normalizarTelefono('5555-1234'));
        $this->assertSame('50255551234', WhatsAppService::normalizarTelefono('+502 5555 1234'));
        $this->assertNull(WhatsAppService::normalizarTelefono('1234'));
        $this->assertNull(WhatsAppService::normalizarTelefono(null));
    }

    public function test_order_on_the_way_queues_a_whatsapp_message_to_the_delivery_phone(): void
    {
        Queue::fake();
        config(['services.whatsapp.driver' => 'log']);

        $pedido = Pedido::factory()->create(['estado' => 'en_ruta']);
        $pedido->direccion->update(['telefono_contacto' => '55551234', 'nombre_contacto' => 'Ana López']);

        $pedido->cliente->notify(new PedidoEnCaminoNotification($pedido->fresh('direccion')));

        Queue::assertPushed(EnviarWhatsApp::class, function (EnviarWhatsApp $job) use ($pedido): bool {
            return $job->telefono === '50255551234'
                && $job->plantilla === 'pedido_en_camino'
                && str_contains($job->texto, 'Ana, tu pedido '.$pedido->numero_pedido.' va en camino');
        });
    }

    public function test_no_whatsapp_when_channel_is_disabled(): void
    {
        Queue::fake();
        config(['services.whatsapp.driver' => 'null']);

        $pedido = Pedido::factory()->create(['estado' => 'en_ruta']);
        $pedido->cliente->notify(new PedidoEnCaminoNotification($pedido));

        Queue::assertNotPushed(EnviarWhatsApp::class);
    }

    public function test_meta_driver_sends_template_message(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        config([
            'services.whatsapp.driver' => 'meta',
            'services.whatsapp.token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => '123456',
        ]);

        $enviado = app(WhatsAppService::class)->enviar('55551234', 'pedido_en_camino', ['Ana', 'ATL-1', 'https://x'], 'texto');

        $this->assertTrue($enviado);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/123456/messages')
            && $request['to'] === '50255551234'
            && $request['template']['name'] === 'pedido_en_camino'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'Ana');
    }

    public function test_guest_signed_link_opens_tracking_without_account(): void
    {
        $pedido = Pedido::factory()->create(['estado' => 'en_ruta']);

        $this->get(route('cliente.pedidos.guest-seguimiento', $pedido))->assertForbidden();

        $this->get(URL::temporarySignedRoute('cliente.pedidos.guest-seguimiento', now()->addDay(), $pedido))
            ->assertOk()
            ->assertSee('Tu pedido va en camino');

        $this->getJson(route('cliente.pedidos.guest-seguimiento.live', $pedido))->assertOk();
    }

    public function test_support_button_uses_configured_number(): void
    {
        config(['atlantia.support.whatsapp' => '5555-0101']);

        $this->get(route('legal.terminos'))
            ->assertOk()
            ->assertSee('https://wa.me/50255550101', false);
    }
}
