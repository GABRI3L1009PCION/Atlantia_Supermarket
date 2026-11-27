<?php

namespace Tests\Feature\Vendedor;

use App\Exceptions\DteCertificadorException;
use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use App\Notifications\FacturaDisponibleNotification;
use App\Services\Fel\DteGeneradorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacturacionManualTest extends TestCase
{
    use RefreshDatabase;

    private const AUTORIZACION = '1A2B3C4D-5E6F-4A1B-9C2D-3E4F5A6B7C8D';

    private User $cliente;

    private Vendor $tienda;

    private Pedido $padre;

    private Pedido $parte;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $this->cliente = User::factory()->create(['name' => 'Ana Pérez']);
        $this->cliente->assignRole('cliente');
        $this->tienda = Vendor::factory()->approved()->create(['business_name' => 'Panadería La Espiga']);
        $this->tienda->user->assignRole('vendedor');
        VendorFiscalProfile::query()->updateOrCreate(['vendor_id' => $this->tienda->id], ['nit' => '1234567K', 'razon_social' => 'La Espiga S.A.', 'fel_activo' => false]);

        $producto = Producto::factory()->create(['vendor_id' => $this->tienda->id, 'nombre' => 'Pan francés']);
        $this->padre = Pedido::factory()->create([
            'cliente_id' => $this->cliente->id, 'vendor_id' => null, 'estado' => 'entregado', 'total' => 75, 'envio' => 25,
            'facturacion_nombre' => 'Ana Pérez', 'facturacion_nit' => '9876543', 'metodo_pago' => 'efectivo',
        ]);
        $this->parte = Pedido::factory()->create([
            'cliente_id' => $this->cliente->id, 'vendor_id' => $this->tienda->id, 'pedido_padre_id' => $this->padre->id, 'estado' => 'entregado',
            'subtotal' => 50, 'descuento' => 0, 'envio' => 25, 'total' => 75, 'direccion_id' => $this->padre->direccion_id, 'metodo_pago' => 'efectivo',
        ]);
        PedidoItem::query()->create([
            'pedido_id' => $this->parte->id, 'producto_id' => $producto->id, 'cantidad' => 10,
            'precio_unitario_snapshot' => 5, 'subtotal' => 50, 'producto_nombre_snapshot' => 'Pan francés',
        ]);
    }

    private function datosFactura(array $cambios = []): array
    {
        return array_replace([
            'serie' => '8F2A1C3D', 'numero' => '1234567890', 'autorizacion' => self::AUTORIZACION,
            'fecha' => now()->toDateString(), 'monto_total' => '50.00',
            'pdf' => UploadedFile::fake()->create('factura.pdf', 120, 'application/pdf'),
        ], $cambios);
    }

    public function test_el_cliente_descarga_su_comprobante_de_compra_con_el_logo_de_la_tienda(): void
    {
        /* Logo de la tienda: una imagen PNG real. */
        $imagen = imagecreatetruecolor(40, 20);
        ob_start();
        imagepng($imagen);
        Storage::disk('public')->put('tiendas/logo.png', (string) ob_get_clean());
        $this->tienda->update(['logo_path' => 'tiendas/logo.png']);

        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $this->padre))
            ->assertOk()->assertSee('Comprobante de compra')->assertDontSee('emitira FEL al momento de despachar');

        $respuesta = $this->actingAs($this->cliente)->get(route('pedidos.comprobante', $this->parte))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $pdf = $respuesta->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('COMPROBANTE DE COMPRA', $pdf);
        $this->assertStringContainsString('no sustituye una factura', $pdf);
        $this->assertStringContainsString('Pan franc', $pdf);
        $this->assertStringContainsString('/Subtype /Image', $pdf, 'Lleva el logo de la tienda.');

        $otro = User::factory()->create();
        $otro->assignRole('cliente');
        $this->actingAs($otro)->get(route('pedidos.comprobante', $this->parte))->assertForbidden();
    }

    public function test_la_tienda_elige_facturar_en_la_sat_y_no_se_emite_sola(): void
    {
        $this->actingAs($this->tienda->user)->put(route('vendedor.perfil-fiscal.update'), ['seccion' => 'modo', 'modo_facturacion' => 'manual_sat'])
            ->assertSessionHas('success');
        $this->assertSame('manual_sat', $this->tienda->fiscalProfile()->value('modo_facturacion'));

        $this->expectException(DteCertificadorException::class);
        app(DteGeneradorService::class)->emitirParaPedido($this->parte->fresh());
    }

    public function test_la_tienda_copia_los_datos_y_registra_la_factura_y_el_cliente_la_ve(): void
    {
        Notification::fake();
        $this->tienda->fiscalProfile()->update(['modo_facturacion' => 'manual_sat']);

        $this->actingAs($this->tienda->user)->get(route('vendedor.pedidos.show', $this->parte))
            ->assertOk()->assertSee('Registrar factura emitida')->assertSee('Copiar todo')
            ->assertSee('data-copiar="9876543"', false)->assertSee('50.00');

        $this->actingAs($this->tienda->user)->post(route('vendedor.pedidos.factura.store', $this->parte), $this->datosFactura())
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $factura = DteFactura::query()->firstOrFail();
        $this->assertSame('manual', $factura->origen);
        $this->assertSame('certificado', $factura->estado);
        $this->assertSame(self::AUTORIZACION, $factura->uuid_sat);
        $this->assertSame($factura->id, $this->parte->fresh()->dte_id);
        $this->assertEqualsWithDelta(5.36, (float) $factura->monto_iva, 0.01);
        Storage::disk('local')->assertExists($factura->pdf_path);
        Notification::assertSentTo($this->cliente, FacturaDisponibleNotification::class);

        $this->actingAs($this->cliente)->get(route('cliente.pedidos.show', $this->padre))
            ->assertSee('Ver factura')->assertSee(self::AUTORIZACION);
        $this->actingAs($this->cliente)->get(route('dte.pdf', $factura))->assertOk();

        /* Corregir: la quita y se puede registrar de nuevo. */
        $this->actingAs($this->tienda->user)->delete(route('vendedor.pedidos.factura.destroy', $this->parte))->assertSessionHas('success');
        $this->assertSame(0, DteFactura::withTrashed()->count());
        $this->assertNull($this->parte->fresh()->dte_id);
    }

    public function test_valida_los_datos_de_la_factura(): void
    {
        $this->actingAs($this->tienda->user)->post(route('vendedor.pedidos.factura.store', $this->parte), $this->datosFactura([
            'autorizacion' => '123', 'serie' => '', 'fecha' => now()->addDay()->toDateString(), 'pdf' => UploadedFile::fake()->create('foto.jpg', 50, 'image/jpeg'),
        ]))->assertSessionHasErrors(['autorizacion', 'serie', 'fecha', 'pdf'], null, 'factura');

        $this->assertSame(0, DteFactura::query()->count());
    }

    public function test_otra_tienda_no_puede_registrar_factura_en_un_pedido_ajeno(): void
    {
        $otra = Vendor::factory()->approved()->create();
        $otra->user->assignRole('vendedor');

        $this->actingAs($otra->user)->post(route('vendedor.pedidos.factura.store', $this->parte), $this->datosFactura())->assertForbidden();
    }

    public function test_historial_del_mes_con_descarga_y_ventas_sin_factura(): void
    {
        $this->actingAs($this->tienda->user)->get(route('vendedor.dte.index'))
            ->assertOk()->assertSee('Resumen del mes')->assertSee('Ventas entregadas sin factura')->assertSee('#'.$this->parte->numero_pedido);

        $this->actingAs($this->tienda->user)->post(route('vendedor.pedidos.factura.store', $this->parte), $this->datosFactura());

        $this->actingAs($this->tienda->user)->get(route('vendedor.dte.index'))
            ->assertSee('Q50.00')->assertDontSee('Ventas entregadas sin factura')->assertSee('registrada por ti');

        $csv = $this->actingAs($this->tienda->user)->get(route('vendedor.dte.exportar', ['mes' => now()->format('Y-m')]))->assertOk()->streamedContent();
        $this->assertStringContainsString(self::AUTORIZACION, $csv);
        $this->assertStringContainsString('9876543', $csv);
        $this->assertStringContainsString('Registrada por la tienda', $csv);
    }
}
