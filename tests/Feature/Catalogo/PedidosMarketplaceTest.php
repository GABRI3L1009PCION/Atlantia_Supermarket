<?php

namespace Tests\Feature\Catalogo;

use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PedidosMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_restricted_orders_page_without_social_buttons(): void
    {
        $response = $this->get(route('cliente.pedidos.index'));

        $response->assertOk();
        $response->assertSee('Historial de pedidos', false);
        $response->assertSee('Inicia sesión para ver tu historial de compras', false);
        $response->assertSee('Iniciar sesión', false);
        $response->assertSee('Crear cuenta', false);
        $response->assertDontSee('Google', false);
        $response->assertDontSee('Facebook', false);
        $response->assertDontSee('Apple', false);
    }

    public function test_authenticated_client_can_see_own_orders_history(): void
    {
        Role::findOrCreate('cliente', 'web');

        $cliente = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $cliente->assignRole('cliente');

        $pedido = Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'numero_pedido' => 'ATL-TEST-1001',
            'estado' => 'entregado',
        ]);

        $response = $this
            ->actingAs($cliente)
            ->get(route('cliente.pedidos.index'));

        $response->assertOk();
        $response->assertSee('ATL-TEST-1001', false);
        $response->assertSee('Ver detalle', false);
        $response->assertDontSee('Inicia sesion para ver tu historial de compras', false);
    }

    public function test_authenticated_client_without_orders_sees_real_empty_state(): void
    {
        Role::findOrCreate('cliente', 'web');

        $cliente = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $cliente->assignRole('cliente');

        $response = $this->actingAs($cliente)->get(route('cliente.pedidos.index'));

        $response->assertOk();
        $response->assertSee('Aun no has realizado ningun pedido', false);
        $response->assertSee('images/pedidos-sin-pedidos.png', false);
        $response->assertSee('Explorar comercios', false);
    }

    public function test_active_orders_are_rendered_from_customer_data(): void
    {
        Role::findOrCreate('cliente', 'web');

        $cliente = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $cliente->assignRole('cliente');
        Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'numero_pedido' => 'ATL-LIVE-2001',
            'estado' => 'preparando',
            'total' => 245.80,
        ]);

        $response = $this->actingAs($cliente)->get(route('cliente.pedidos.index'));

        $response->assertOk();
        $response->assertSee('ATL-LIVE-2001', false);
        $response->assertSee('Preparando', false);
        $response->assertSee('wire:poll.8s', false);
        $response->assertSee('Seguir pedido', false);
    }

    public function test_history_exposes_owned_certified_invoice_download(): void
    {
        Role::findOrCreate('cliente', 'web');
        Storage::fake('local');

        $cliente = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $cliente->assignRole('cliente');
        $vendor = Vendor::factory()->approved()->create();
        $pedido = Pedido::factory()->entregado()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendor->id,
            'numero_pedido' => 'ATL-INVOICE-3001',
        ]);
        $dte = DteFactura::factory()->certificado()->create([
            'pedido_id' => $pedido->id,
            'vendor_id' => $vendor->id,
            'numero_dte' => 'DTE-CLIENTE-3001',
            'pdf_path' => 'dte/pdf/cliente-3001.pdf',
        ]);
        Storage::disk('local')->put($dte->pdf_path, '%PDF-1.4 customer invoice');

        $page = $this->actingAs($cliente)->get(route('cliente.pedidos.index'));
        $download = $this->actingAs($cliente)->get(route('dte.pdf', [
            'dte' => $dte,
            'download' => 1,
        ]));

        $page->assertOk();
        $page->assertSee('DTE-CLIENTE-3001', false);
        $page->assertSee('Descargar', false);
        $download->assertOk();
        $this->assertStringContainsString('attachment;', (string) $download->headers->get('Content-Disposition'));
    }
}
