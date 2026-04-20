<?php

namespace Database\Factories;

use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory para facturas FEL DTE.
 *
 * @extends Factory<DteFactura>
 */
class DteFacturaFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<DteFactura>
     */
    protected $model = DteFactura::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $neto = fake()->randomFloat(2, 75, 700);
        $iva = round($neto * 0.12, 2);
        $total = $neto + $iva;
        $serie = fake()->randomElement(['ATL-A', 'ATL-B', 'IZB-A']);

        return [
            'uuid' => (string) Str::uuid(),
            'pedido_id' => fn (): ?int => Pedido::query()->inRandomOrder()->value('id'),
            'vendor_id' => fn (): int => (int) (Vendor::query()->inRandomOrder()->value('id') ?? Vendor::factory()->approved()->create()->id),
            'numero_dte' => 'DTE-' . now()->format('Y') . '-' . fake()->unique()->numerify('######'),
            'uuid_sat' => (string) Str::uuid(),
            'serie' => $serie,
            'numero' => fake()->unique()->numberBetween(1000, 999999),
            'tipo_dte' => 'FACT',
            'monto_neto' => $neto,
            'monto_iva' => $iva,
            'monto_total' => $total,
            'moneda' => 'GTQ',
            'xml_dte' => '<dte><emisor>Atlantia vendedor local</emisor><moneda>GTQ</moneda></dte>',
            'pdf_path' => 'dte/facturas/' . now()->format('Y/m') . '/' . Str::uuid() . '.pdf',
            'estado' => 'certificado',
            'fecha_certificacion' => now()->subDays(fake()->numberBetween(0, 30)),
            'certificador_respuesta' => [
                'certificador' => 'INFILE',
                'ambiente' => 'sandbox',
                'resultado' => 'certificado',
                'codigo' => 'SAT-OK',
            ],
        ];
    }

    /**
     * Estado para DTE certificado.
     *
     * @return static
     */
    public function certificado(): static
    {
        return $this->state(fn (): array => [
            'estado' => 'certificado',
            'fecha_certificacion' => now(),
        ]);
    }

    /**
     * Estado para DTE rechazado.
     *
     * @return static
     */
    public function rechazado(): static
    {
        return $this->state(fn (): array => [
            'uuid_sat' => null,
            'estado' => 'rechazado',
            'fecha_certificacion' => null,
            'certificador_respuesta' => [
                'certificador' => 'INFILE',
                'ambiente' => 'sandbox',
                'resultado' => 'rechazado',
                'codigo' => 'SAT-ERR',
            ],
        ]);
    }
}
