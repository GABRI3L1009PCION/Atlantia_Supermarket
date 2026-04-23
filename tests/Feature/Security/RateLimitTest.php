<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Pruebas de rate limiting en endpoints sensibles.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura roles base.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        RateLimiter::clear('cliente@atlantia.test|127.0.0.1');
    }

    /**
     * Bloquea login despues de 5 intentos fallidos.
     */
    public function testLoginBloqueaDespuesDeCincoIntentosFallidos(): void
    {
        $user = User::factory()->cliente()->create([
            'email' => 'cliente@atlantia.test',
        ]);
        $user->assignRole('cliente');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'PasswordIncorrecto!123',
            ])->assertRedirect();
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'PasswordIncorrecto!123',
        ])->assertStatus(429);
    }

    /**
     * Bloquea registro despues de 5 intentos por IP y hora.
     */
    public function testRegistroBloqueaDespuesDeCincoIntentosPorHora(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('register.store'), [
                'name' => '',
                'email' => '',
                'phone' => '',
                'password' => '',
            ])->assertRedirect();
        }

        $this->post(route('register.store'), [
            'name' => '',
            'email' => '',
            'phone' => '',
            'password' => '',
        ])->assertStatus(429);
    }
}
