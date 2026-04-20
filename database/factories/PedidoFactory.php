<?php

namespace Database\Factories;

use App\Models\Cliente\Direccion;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory para pedidos de Atlantia.
 *
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<Pedido>
     */
    protected $model = Pedido::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 65, 620);
        $envio = fake()->randomElement([15.00, 20.00, 25.00, 35.00]);
        $impuestos = round($subtotal * 0.12, 2);
        $descuento = fake()->optional(0.2, 0.00)->randomFloat(2, 5, 30);
        $total = $subtotal + $envio + $impuestos - $descuento;

        return [
            'uuid' => (string) Str::uuid(),
            'numero_pedido' => 'ATL-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('####'),
            'pedido_padre_id' => null,
            'cliente_id' => fn (): int => $this->clienteId(),
            'vendor_id' => fn (): ?int => Vendor::query()->inRandomOrder()->value('id'),
            'direccion_id' => fn (array $attributes): int => $this->direccionId((int) $attributes['cliente_id']),
            'dte_id' => null,
            'subtotal' => $subtotal,
            'envio' => $envio,
            'impuestos' => $impuestos,
            'descuento' => $descuento,
            'total' => $total,
            'estado' => fake()->randomElement(['pendiente', 'confirmado', 'preparando', 'entregado']),
            'metodo_pago' => fake()->randomElement(['efectivo', 'transferencia', 'tarjeta']),
            'estado_pago' => fake()->randomElement(['pendiente', 'validando', 'pagado']),
            'notas' => fake()->optional(0.35)->randomElement([
                'Entregar en recepcion si no contestan.',
                'Llamar al llegar al porton principal.',
                'Preferencia de entrega por la tarde.',
            ]),
            'confirmado_at' => fake()->optional(0.65)->dateTimeBetween('-15 days', 'now'),
            'cancelado_at' => null,
        ];
    }

    /**
     * Estado para pedido entregado.
     *
     * @return static
     */
    public function entregado(): static
    {
        return $this->state(fn (): array => [
            'estado' => 'entregado',
            'estado_pago' => 'pagado',
            'confirmado_at' => now()->subDays(2),
        ]);
    }

    /**
     * Estado para pedido pendiente.
     *
     * @return static
     */
    public function pendiente(): static
    {
        return $this->state(fn (): array => [
            'estado' => 'pendiente',
            'estado_pago' => 'pendiente',
            'confirmado_at' => null,
        ]);
    }

    /**
     * Obtiene o crea un cliente base.
     *
     * @return int
     */
    private function clienteId(): int
    {
        return (int) (User::query()->inRandomOrder()->value('id') ?? User::factory()->cliente()->create()->id);
    }

    /**
     * Obtiene o crea una direccion para el cliente.
     *
     * @param int $clienteId
     * @return int
     */
    private function direccionId(int $clienteId): int
    {
        return (int) (
            Direccion::query()->where('user_id', $clienteId)->inRandomOrder()->value('id')
            ?? Direccion::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $clienteId,
                'alias' => 'Casa',
                'nombre_contacto' => 'Cliente Atlantia',
                'telefono_contacto' => '+502 7948-1200',
                'municipio' => 'Puerto Barrios',
                'zona_o_barrio' => 'Barrio El Centro',
                'direccion_linea_1' => 'Avenida principal, Puerto Barrios',
                'direccion_linea_2' => null,
                'referencia' => 'Cerca del parque central.',
                'latitude' => 15.73090000,
                'longitude' => -88.59440000,
                'mapbox_place_id' => null,
                'es_principal' => true,
                'activa' => true,
            ])->id
        );
    }
}
