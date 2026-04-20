<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory para usuarios reales de Atlantia.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Modelo asociado a la factory.
     *
     * @var class-string<User>
     */
    protected $model = User::class;

    /**
     * Password conocido para desarrollo local.
     */
    protected static ?string $password = null;

    /**
     * Define el estado base del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->randomElement([
            'Gabriel',
            'Ioni',
            'Henry',
            'Mariela',
            'Carlos',
            'Andrea',
            'Luis',
            'Sofia',
            'Jorge',
            'Daniela',
        ]);
        $lastName = fake()->randomElement([
            'Picon',
            'Rodas',
            'Diaz',
            'Mendez',
            'Castillo',
            'Morales',
            'Ramirez',
            'Barrios',
            'Estrada',
            'Cabrera',
        ]);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Atlantia2026!'),
            'phone' => '+502 ' . fake()->numerify('####-####'),
            'status' => 'active',
            'is_system_user' => false,
            'last_login_at' => fake()->optional(0.65)->dateTimeBetween('-30 days', 'now'),
            'last_login_ip' => fake()->optional(0.65)->ipv4(),
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Estado para cliente final.
     *
     * @return static
     */
    public function cliente(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'is_system_user' => false,
        ]);
    }

    /**
     * Estado para vendedor local.
     *
     * @return static
     */
    public function vendedor(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'is_system_user' => false,
        ]);
    }

    /**
     * Estado para administrador con 2FA.
     *
     * @return static
     */
    public function admin(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'is_system_user' => false,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Estado para repartidor.
     *
     * @return static
     */
    public function repartidor(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
        ]);
    }

    /**
     * Estado para empleado interno.
     *
     * @return static
     */
    public function empleado(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
        ]);
    }

    /**
     * Estado para cuenta suspendida.
     *
     * @return static
     */
    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => 'suspended',
        ]);
    }
}
