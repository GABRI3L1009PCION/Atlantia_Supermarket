<?php

namespace Database\Factories;

use App\Models\Devolucion;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory para devoluciones.
 *
 * @extends Factory<Devolucion>
 */
class DevolucionFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<Devolucion>
     */
    protected $model = Devolucion::class;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'pedido_id' => Pedido::factory()->entregado(),
            'user_id' => function (array $attributes): int {
                return (int) (Pedido::query()->findOrFail($attributes['pedido_id'])->cliente_id);
            },
            'motivo' => fake()->randomElement(['producto_defectuoso', 'no_llego', 'incorrecto', 'otro']),
            'estado' => 'solicitada',
            'monto_reembolso' => 0,
            'descripcion' => 'El producto recibido no coincide con lo solicitado y se requiere revision.',
            'notas_admin' => null,
            'foto_evidencia' => null,
            'resuelta_por' => null,
            'resuelta_at' => null,
        ];
    }

    /**
     * Estado aprobado.
     */
    public function aprobada(?User $admin = null): static
    {
        return $this->state(fn (): array => [
            'estado' => 'aprobada',
            'monto_reembolso' => fake()->randomFloat(2, 5, 200),
            'resuelta_por' => $admin?->id,
            'resuelta_at' => now(),
        ]);
    }
}
