<?php

namespace Tests\Feature\Fel;

use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\Vendor;
use App\Services\Fel\DteGeneradorService;
use App\Services\Fel\InfileCertificadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Pruebas de emision FEL.
 */
class DteGenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reutiliza el DTE existente para evitar doble certificacion.
     */
    public function testReturnsExistingDteWhenPedidoAlreadyHasInvoice(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $pedido = Pedido::factory()->create(['vendor_id' => $vendor->id]);
        $dte = DteFactura::factory()->create([
            'pedido_id' => $pedido->id,
            'vendor_id' => $vendor->id,
            'estado' => 'certificado',
        ]);
        $pedido->update(['dte_id' => $dte->id]);

        $certificador = Mockery::mock(InfileCertificadorService::class);
        $certificador->shouldNotReceive('certificar');

        $result = (new DteGeneradorService($certificador))->emitirParaPedido($pedido);

        $this->assertTrue($dte->is($result));
    }
}
