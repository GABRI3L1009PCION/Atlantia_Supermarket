<?php

namespace Tests\Feature\Fel;

use App\Jobs\EnviarCorreoFactura;
use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Fel\DteComprobantePdf;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pruebas del envio de factura FEL emulada al correo fiscal real.
 */
class DteInvoiceDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Envia el PDF al correo de facturacion aunque el cliente sea invitado interno.
     */
    public function test_factura_pdf_se_envia_al_correo_de_facturacion_del_invitado(): void
    {
        Mail::fake();
        Storage::fake('local');

        $guest = User::factory()->cliente()->create([
            'email' => 'guest-test@invitados.atlantia.local',
        ]);
        $vendor = Vendor::factory()->approved()->create();
        $pedido = Pedido::factory()->create([
            'cliente_id' => $guest->id,
            'vendor_id' => $vendor->id,
            'facturacion_tipo' => 'cf',
            'facturacion_nombre' => 'Consumidor final',
            'facturacion_nit' => 'CF',
            'facturacion_email' => 'cliente.real@example.com',
            'metodo_pago' => 'efectivo',
            'estado_pago' => 'pagado',
            'subtotal' => 145,
            'envio' => 0,
            'impuestos' => 17.40,
            'descuento' => 0,
            'total' => 162.40,
        ]);
        $dte = DteFactura::factory()->create([
            'pedido_id' => $pedido->id,
            'vendor_id' => $vendor->id,
            'numero_dte' => 'DTE-TEST-0001',
            'monto_neto' => 145,
            'monto_iva' => 17.40,
            'monto_total' => 162.40,
            'pdf_path' => null,
            'certificador_respuesta' => [
                'mock' => true,
                'resultado' => 'certificado',
            ],
        ]);
        $dte->items()->create([
            'descripcion' => 'Ferrero Rocher',
            'cantidad' => 1,
            'precio_unitario' => 145,
            'descuento' => 0,
            'monto_iva' => 17.40,
            'monto_total' => 145,
        ]);

        (new EnviarCorreoFactura($dte->id))->handle(app(DteComprobantePdf::class));

        $dte->refresh();

        $this->assertNotNull($dte->pdf_path);
        Storage::disk('local')->assertExists($dte->pdf_path);
        $this->assertDatabaseHas('sent_emails', [
            'to' => 'cliente.real@example.com',
            'subject' => 'Factura Atlantia DTE-TEST-0001',
            'status' => 'sent',
        ]);
    }

    /**
     * El PDF contiene el formato principal de factura FEL emulada.
     */
    public function test_pdf_incluye_formato_factura_fel_emulada(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $pedido = Pedido::factory()->create([
            'vendor_id' => $vendor->id,
            'facturacion_nombre' => 'Consumidor final',
            'facturacion_nit' => 'CF',
            'facturacion_email' => 'cliente.real@example.com',
        ]);
        $dte = DteFactura::factory()->create([
            'pedido_id' => $pedido->id,
            'vendor_id' => $vendor->id,
            'numero_dte' => 'DTE-TEST-0002',
            'pdf_path' => null,
            'certificador_respuesta' => ['mock' => true],
        ]);
        $dte->items()->create([
            'descripcion' => 'Producto de prueba',
            'cantidad' => 1,
            'precio_unitario' => 25,
            'descuento' => 0,
            'monto_iva' => 3,
            'monto_total' => 25,
        ]);

        $pdf = app(DteComprobantePdf::class)->output($dte);

        $this->assertStringContainsString('FACTURA ELECTRONICA FEL', $pdf);
        $this->assertStringContainsString('RECEPTOR / CLIENTE', $pdf);
        $this->assertStringContainsString('Consumidor final', $pdf);
        $this->assertStringContainsString('DOCUMENTO EMULADO PARA PRUEBAS', $pdf);
    }

    /**
     * La ruta de PDF fiscal privado solo responde al cliente propietario.
     */
    public function test_pdf_privado_requiere_cliente_propietario(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $cliente = User::factory()->cliente()->create();
        $cliente->assignRole('cliente');
        $otroCliente = User::factory()->cliente()->create();
        $otroCliente->assignRole('cliente');
        $vendor = Vendor::factory()->approved()->create();
        $pedido = Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendor->id,
        ]);
        $dte = DteFactura::factory()->certificado()->create([
            'pedido_id' => $pedido->id,
            'vendor_id' => $vendor->id,
            'pdf_path' => 'dte/pdf/test-private.pdf',
        ]);

        Storage::disk('local')->put($dte->pdf_path, '%PDF-1.4 private invoice');

        $this->get(route('dte.pdf', $dte))->assertForbidden();
        $this->actingAs($otroCliente)->get(route('dte.pdf', $dte))->assertForbidden();
        $this->actingAs($cliente)->get(route('dte.pdf', $dte))->assertOk();
    }
}
