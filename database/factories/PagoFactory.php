<?php

namespace Database\Factories;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Models\Payment;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory para pagos de pedidos.
 *
 * @extends Factory<Payment>
 */
class PagoFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<Payment>
     */
    protected $model = Payment::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'pedido_id' => Pedido::factory(),
            'metodo' => fake()->randomElement([
                MetodoPago::Efectivo->value,
                MetodoPago::Transferencia->value,
                MetodoPago::Tarjeta->value,
            ]),
            'monto' => fake()->randomFloat(2, 10, 400),
            'estado' => fake()->randomElement([
                EstadoPago::Pendiente->value,
                EstadoPago::Validando->value,
                EstadoPago::Aprobado->value,
            ]),
            'transaccion_id_pasarela' => 'txn_' . Str::lower(Str::random(18)),
            'hmac_validado' => true,
            'referencia_bancaria' => null,
            'validado_por' => null,
            'validado_at' => now(),
            'pasarela_payload' => ['gateway' => 'testing'],
        ];
    }

    /**
     * Estado de pago aprobado.
     */
    public function aprobado(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoPago::Aprobado->value,
        ]);
    }
}
