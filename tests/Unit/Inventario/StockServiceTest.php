<?php

namespace Tests\Unit\Inventario;

use App\Exceptions\StockInsuficienteException;
use App\Models\Inventario;
use App\Models\Producto;
use App\Services\Inventario\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de reglas atomicas de inventario.
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reserva stock sin modificar el stock fisico.
     */
    public function testReserveIncreasesReservedStockOnly(): void
    {
        $producto = Producto::factory()->publicado()->create();
        Inventario::query()->create([
            'producto_id' => $producto->id,
            'stock_actual' => 20,
            'stock_reservado' => 3,
            'stock_minimo' => 5,
            'stock_maximo' => 40,
            'ultima_actualizacion' => now(),
        ]);

        $inventario = app(StockService::class)->reserve($producto, 4);

        $this->assertSame(20, $inventario->stock_actual);
        $this->assertSame(7, $inventario->stock_reservado);
    }

    /**
     * Impide reservar mas unidades que el disponible real.
     */
    public function testReserveFailsWhenAvailableStockIsNotEnough(): void
    {
        $producto = Producto::factory()->publicado()->create();
        Inventario::query()->create([
            'producto_id' => $producto->id,
            'stock_actual' => 5,
            'stock_reservado' => 4,
            'stock_minimo' => 2,
            'stock_maximo' => 20,
            'ultima_actualizacion' => now(),
        ]);

        $this->expectException(StockInsuficienteException::class);

        app(StockService::class)->reserve($producto, 2);
    }

    /**
     * Calcula disponibilidad para catalogo sin exponer stock negativo.
     */
    public function testAvailabilityNeverReturnsNegativeStock(): void
    {
        $producto = Producto::factory()->publicado()->create();
        Inventario::query()->create([
            'producto_id' => $producto->id,
            'stock_actual' => 2,
            'stock_reservado' => 8,
            'stock_minimo' => 3,
            'stock_maximo' => 20,
            'ultima_actualizacion' => now(),
        ]);

        $availability = app(StockService::class)->availability($producto, ['cantidad' => 1]);

        $this->assertSame(0, $availability['stock_disponible']);
        $this->assertFalse($availability['disponible']);
        $this->assertTrue($availability['bajo_minimo']);
    }
}
