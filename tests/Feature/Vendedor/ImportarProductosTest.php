<?php

namespace Tests\Feature\Vendedor;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportarProductosTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): Vendor
    {
        Role::findOrCreate('vendedor', 'web');
        $vendor = Vendor::factory()->approved()->create(['plan' => 'profesional', 'plan_hasta' => now()->addMonth()]);
        $vendor->user->assignRole('vendedor');

        Categoria::query()->create([
            'nombre' => 'Abarrotes', 'slug' => 'abarrotes', 'descripcion' => 'Base', 'icon' => 'bag', 'orden' => 1, 'is_active' => true,
        ]);

        return $vendor;
    }

    private function csv(string $contenido): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('productos.csv', $contenido);
    }

    public function test_creates_and_updates_products_from_csv(): void
    {
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)
            ->post(route('vendedor.productos.importar.store'), ['archivo' => $this->csv(
                "sku,nombre,categoria,precio_sin_iva,oferta_sin_iva,existencias,unidad_medida,visible\n"
                ."ARROZ-1,Arroz 1 lb,Abarrotes,10.00,,50,libra,si\n"
                ."MALO-1,Sin categoria,Inexistente,5,,1,unidad,si\n"
            )])
            ->assertSessionHas('resultado_importacion', fn (array $r): bool => $r['creados'] === 1
                && count($r['errores']) === 1 && str_contains($r['errores'][0], 'Fila 3'));

        $producto = Producto::query()->where('sku', 'ARROZ-1')->firstOrFail();
        $this->assertEqualsWithDelta(11.20, (float) $producto->precio_base, 0.001);
        $this->assertSame(50, $producto->inventario->stock_actual);
        $this->assertNotNull($producto->publicado_at);

        /* Actualizacion solo de precio y existencias, con punto y coma como en Excel en espanol. */
        $this->actingAs($vendor->user)
            ->post(route('vendedor.productos.importar.store'), ['archivo' => $this->csv(
                "sku;precio_sin_iva;existencias\nARROZ-1;12,50;80\n"
            )])
            ->assertSessionHas('resultado_importacion', fn (array $r): bool => $r['actualizados'] === 1 && $r['errores'] === []);

        $producto->refresh();
        $this->assertEqualsWithDelta(14.00, (float) $producto->precio_base, 0.001);
        $this->assertSame(80, $producto->inventario->stock_actual);
        $this->assertSame('Arroz 1 lb', $producto->nombre);
    }

    public function test_final_price_columns_are_used_as_is(): void
    {
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)
            ->post(route('vendedor.productos.importar.store'), ['archivo' => $this->csv(
                "sku,nombre,categoria,precio,oferta,existencias
JUGO-1,Jugo de naranja,Abarrotes,24.65,21.85,30
"
            )])
            ->assertSessionHas('resultado_importacion', fn (array $r): bool => $r['creados'] === 1 && $r['errores'] === []);

        $producto = Producto::query()->where('sku', 'JUGO-1')->firstOrFail();
        $this->assertEqualsWithDelta(24.65, (float) $producto->precio_base, 0.001);
        $this->assertEqualsWithDelta(21.85, (float) $producto->precio_oferta, 0.001);
    }

    public function test_plan_without_bulk_upload_is_told_before_uploading(): void
    {
        $vendor = $this->vendedor();
        $vendor->update(['plan' => 'inicial', 'plan_hasta' => null]);

        $this->actingAs($vendor->user->fresh())->get(route('vendedor.productos.importar'))
            ->assertOk()
            ->assertSee('Tu plan actual no incluye la carga masiva.')
            ->assertSee('data-aviso-plan', false);
    }

    public function test_template_download_and_page(): void
    {
        $vendor = $this->vendedor();

        $this->actingAs($vendor->user)->get(route('vendedor.productos.importar'))->assertOk()->assertSee('Carga masiva');
        $this->actingAs($vendor->user)
            ->get(route('vendedor.productos.importar.plantilla'))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="plantilla-productos-atlantia.csv"');
    }
}
