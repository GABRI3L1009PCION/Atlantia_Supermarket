<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\RegisterForm;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function testClienteCanRegisterFromLivewireForm(): void
    {
        Notification::fake();

        Livewire::test(RegisterForm::class)
            ->set('name', 'Heriberto Hernandez')
            ->set('email', 'heriberto'.uniqid().'@gmail.com')
            ->set('phone', '55355469')
            ->set('password', 'Soyheriberto10%')
            ->set('password_confirmation', 'Soyheriberto10%')
            ->set('acepta_terminos', true)
            ->set('acepta_privacidad', true)
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('cliente.carrito.index'));

        $user = User::query()->where('phone', '55355469')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('cliente'));
    }

    public function testClienteCanRegisterFromStandardPostFallback(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Heriberto Hernandez',
            'email' => 'cliente'.uniqid().'@gmail.com',
            'phone' => '55355469',
            'password' => 'Soyheriberto10%',
            'password_confirmation' => 'Soyheriberto10%',
            'role' => 'cliente',
            'acepta_terminos' => '1',
            'acepta_privacidad' => '1',
        ]);

        $response->assertRedirect(route('cliente.carrito.index'));
        $this->assertAuthenticated();

        $user = User::query()->where('phone', '55355469')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('cliente'));
    }
}
