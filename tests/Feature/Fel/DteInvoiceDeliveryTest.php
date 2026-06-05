<?php

namespace Tests\Feature\Fel;

use App\Jobs\EnviarCorreoFactura;
use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Fel\DteComprobantePdf;
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
    public function testFacturaPdfSeEnviaAlCorreoDeFacturacionDelInvitado(): void
    {
        Mail::fake();
        Storage::fake('public');

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
        Storage::disk('public')->assertExists($dte->pdf_path);
        $this->assertDatabaseHas('sent_emails', [
            'to' => 'cliente.real@example.com',
            'subject' => 'Factura Atlantia DTE-TEST-0001',
            'status' => 'sent',
        ]);
    }

    /**
     * El PDF contiene el formato principal de factura FEL emulada.
     */
    public function testPdfIncluyeFormatoFacturaFelEmulada(): void
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
}
